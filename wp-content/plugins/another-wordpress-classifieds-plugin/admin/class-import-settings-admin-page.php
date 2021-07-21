<?php
/**
 * @package AWPCP\Admin\Pages
 */

/**
 * Constructor function for AWPCP_Import_Settings_Admin_Page class
 */
function awpcp_import_settings_admin_page() {
    return new AWPCP_Import_Settings_Admin_Page(
        awpcp_settings_json_writer(),
        awpcp_roles_and_capabilities(),
        awpcp_template_renderer(),
        awpcp_request()
    );
}

/**
 * Admin page that allows users to import settings from a JSON file.
 */
class AWPCP_Import_Settings_Admin_Page {

    /**
     * @var string
     */
    private $nonce_action = 'awpcp-import-settings';

    /**
     * @var object
     */
    private $settings_writer;

    /**
     * @var object
     */
    private $capabilities;

    /**
     * @var object
     */
    private $template_renderer;

    /**
     * @var object
     */
    private $request;

    /**
     * Constructor.
     *
     * @param object $settings_writer   An instance of Settings Writer.
     * @param object $capabilities      An instance of RolesAndCapabilities.
     * @param object $template_renderer An instance of Template_Renderer.
     * @param object $request           An instance of Request.
     */
    public function __construct( $settings_writer, $capabilities, $template_renderer, $request ) {
        $this->settings_writer   = $settings_writer;
        $this->capabilities      = $capabilities;
        $this->template_renderer = $template_renderer;
        $this->request           = $request;
    }

    /**
     * Executed during admin_init when this page is visited.
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function on_admin_init() {
        if ( ! isset( $_FILES['settings_file'] ) ) { // WPCS: Input var okay.
            return;
        }

        $file = wp_unslash( $_FILES['settings_file'] ); // WPCS: Input var and sanitization okay.

        try {
            $this->try_to_import_settings( $file );
        } catch ( AWPCP_Exception $e ) {
            awpcp_flash( $e->getMessage(), array( 'notice', 'notice-error' ) );
        }

        $params = array(
            'page'         => 'awpcp-admin-settings',
            'awpcp-action' => 'import-settings',
        );

        $redirect_url = add_query_arg( $params, admin_url( 'admin.php' ) );

        wp_safe_redirect( $redirect_url );
        exit();
    }

    /**
     * Verifies that the request is valid, that a file was uploaded
     * and uses the Settings Writer to update the settings.
     *
     * @param array $file An entry from $_FILES.
     *
     * @throws AWPCP_Exception When input parameters are invalid or there
     *                          is an error trying to write from the JSON
     *                          file.
     */
    private function try_to_import_settings( $file ) {
        $nonce = $this->request->post( '_wpnonce' );

        if ( ! wp_verify_nonce( $nonce, $this->nonce_action ) ) {
            $message = _x( 'Are you sure you want to do this?', 'import settings', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( $message );
        }

        if ( isset( $file['error'] ) && UPLOAD_ERR_OK !== absint( $file['error'] ) ) {
            $error = awpcp_uploaded_file_error( $file );
            throw new AWPCP_Exception( $error[1] );
        }

        $settings_file = $file['tmp_name'];

        if ( ! is_uploaded_file( $settings_file ) ) {
            $message = _x( "There was a problem trying to read the settings file; it appears the file wasn't uploaded correctly. Please try again.", 'import settings', 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( $message );
        }

        $this->settings_writer->write( $settings_file );

        awpcp_flash( _x( 'Your settings have been successfully imported.', 'another-wordpress-classifieds-plugin' ), array( 'notice', 'notice-info' ) );
    }

    /**
     * Shows the import settings form.
     */
    public function dispatch() {
        return $this->render_import_settings_form();
    }

    /**
     * Renders the import settings form.
     */
    private function render_import_settings_form() {
        $template = AWPCP_DIR . '/templates/admin/tools/import-settings-admin-page.tpl.php';

        $params = array(
            'action_url'   => '',
            'nonce_action' => $this->nonce_action,
            'tools_url'    => remove_query_arg( 'awpcp-view' ),
        );

        return $this->template_renderer->render_template( $template, $params );
    }
}
