<?php
/**
 * Export Settings admin page.
 *
 * @package AWPCP\Admin\Pages
 */

/**
 * Constructor function for AWPCP_Export_Settings_Admin_Page class.
 */
function awpcp_export_settings_admin_page() {
    return new AWPCP_Export_Settings_Admin_Page(
        awpcp_settings_json_reader(),
        awpcp()->container['TemplateRenderer'],
        awpcp()->container['Request']
    );
}

/**
 * Admin page that allows users to export settings into a JSON file.
 */
class AWPCP_Export_Settings_Admin_Page {

    /**
     * @var string
     */
    private $nonce_action = 'awpcp-export-settings';

    /**
     * @var string
     */
    private $template = '/admin/tools/export-settings-admin-page.tpl.php';

    /**
     * An instance of a Settings Reader.
     *
     * @var object
     */
    private $settings_reader;

    /**
     * @var TemplateRenderer
     */
    private $template_renderer;

    /**
     * Constructor.
     */
    public function __construct( $settings_reader, $template_renderer, $request ) {
        $this->settings_reader   = $settings_reader;
        $this->template_renderer = $template_renderer;
        $this->request           = $request;
    }

    /**
     * Code executed during admin_init when this page is visited.
     *
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function on_admin_init() {
        if ( ! wp_verify_nonce( $this->request->post( '_wpnonce' ), $this->nonce_action ) ) {
            return;
        }

        $filename = 'awpcp-settings-' . awpcp_datetime( 'Ymd-His' ) . '.json';

        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=' . $filename );
        header( 'Content-Type: application/json; charset=' . get_option( 'blog_charset' ), true );

        echo $this->settings_reader->read_all(); // WPCS: XSS OK.

        exit();
    }

    /**
     * @since 4.0.0
     */
    public function dispatch() {
        $params = array(
            'nonce_action' => $this->nonce_action,
            'tools_url'    => remove_query_arg( 'awpcp-view' ),
        );

        return $this->template_renderer->render_template( $this->template, $params );
    }
}
