<?php
/**
 * @package AWPCP\Admin\Categories
 */

/**
 * Constructor for Update Category Admin Page.
 */
function awpcp_update_category_admin_page() {
    return new AWPCP_Update_Category_Admin_Page(
        awpcp_categories_logic(),
        awpcp_categories_collection(),
        awpcp_router(),
        awpcp_request()
    );
}

class AWPCP_Update_Category_Admin_Page {

    private $categories_logic;
    private $categories;
    private $router;
    private $request;

    public function __construct( $categories_logic, $categories, $router, $request ) {
        $this->categories_logic = $categories_logic;
        $this->categories       = $categories;
        $this->router           = $router;
        $this->request          = $request;
    }

    public function dispatch() {
        try {
            $this->try_to_update_category();
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

    private function try_to_update_category() {
        $category_id = $this->request->param( 'category_id' );

        try {
            $category = $this->categories->get( $category_id );
        } catch ( AWPCP_Exception $e ) {
            $message = __( "The category you're trying to update doesn't exist.", 'another-wordpress-classifieds-plugin' );
            throw new AWPCP_Exception( $message );
        }

        $category->name        = wp_unslash( $this->request->param( 'category_name' ) );
        $category->description = wp_unslash( $this->request->param( 'category_description' ) );
        $category->parent      = absint( $this->request->param( 'category_parent_id' ) );
        $category_order        = absint( $this->request->param( 'category_order' ) );

        $this->categories_logic->update_category( $category, $category_order );

        awpcp_flash( __( 'The category was successfully updated.', 'another-wordpress-classifieds-plugin' ) );
    }
}
