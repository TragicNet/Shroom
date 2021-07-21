<?php
/**
 * @package AWPCP\Admin\Categories
 */

/**
 * Constructor for AWPCP_Create_Category_Admin_Page.
 */
function awpcp_create_category_admin_page() {
    return new AWPCP_Create_Category_Admin_Page(
        awpcp_categories_logic(),
        awpcp_router(),
        awpcp_request()
    );
}

/**
 * Handles admin requests to create categories.
 */
class AWPCP_Create_Category_Admin_Page {

    private $categories_data_mapper;
    private $router;
    private $request;

    public function __construct( $categories_data_mapper, $router, $request ) {
        $this->categories_data_mapper = $categories_data_mapper;
        $this->router                 = $router;
        $this->request                = $request;
    }

    public function dispatch() {
        $category_order = absint( $this->request->param( 'category_order' ) );
        $category_data  = array(
            'name'        => wp_unslash( $this->request->param( 'category_name' ) ),
            'description' => wp_unslash( $this->request->param( 'category_description' ) ),
            'parent'      => intval( $this->request->param( 'category_parent_id' ) ),
        );

        try {
            $this->categories_data_mapper->create_category( $category_data, $category_order );
            awpcp_flash( __( 'The new category was successfully added.', 'another-wordpress-classifieds-plugin' ) );
        } catch ( AWPCP_Exception $e ) {
            awpcp_flash( $e->getMessage(), 'error' );
        }

        $route = [
            'parent' => 'awpcp.php',
            'page'   => 'awpcp-admin-categories',
        ];

        $this->router->serve_admin_page( $route );

        return false; // halt rendering process. Ugh!
    }
}
