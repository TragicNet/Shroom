<?php
/**
 * @package AWPCP\Admin
 */

/**
 * Constructor function for AWPCP_FormFieldsAdminPage.
 */
function awpcp_form_fields_admin_page() {
    return new AWPCP_FormFieldsAdminPage(
        'awpcp-form-fields',
        awpcp_admin_page_title( __( 'Form Fields', 'another-wordpress-classifieds-plugin' ) ),
        awpcp_listing_form_fields(),
        awpcp_form_fields_table_factory()
    );
}

/**
 * Admin page to manage order of listing's form fields.
 */
class AWPCP_FormFieldsAdminPage extends AWPCP_AdminPageWithTable {

    /**
     * @var ListingFormFields
     */
    private $form_fields;

    /**
     * @var FormFieldsTableFactory
     */
    private $table_factory;

    /**
     * Constructor.
     */
    public function __construct( $page, $title, $form_fields, $table_factory ) {
        parent::__construct( $page, $title, _x( 'Form Fields', 'sub menu title', 'another-wordpress-classifieds-plugin' ) );

        $this->form_fields   = $form_fields;
        $this->table_factory = $table_factory;
    }

    /**
     * Creates an instance of table used to render form fields rows.
     */
    public function get_table() {
        if ( ! isset( $this->table ) || is_null( $this->table ) ) {
            $this->table = $this->table_factory->create_table( $this );
        }

        return $this->table;
    }

    /**
     * Enqueue required scripts.
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-form-fields' );
    }

    /**
     * Renders the page.
     */
    public function dispatch() {
        $form_fields = $this->form_fields->get_listing_details_form_fields();

        $table = $this->get_table();
        $table->prepare( $form_fields, count( $form_fields ) );

        $params = array(
            'page'  => $this,
            'table' => $table,
        );

        $template = AWPCP_DIR . '/templates/admin/form-fields-admin-page.tpl.php';

        return awpcp_render_template( $template, $params );
    }
}
