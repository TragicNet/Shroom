<?php
/**
 * @package AWPCP\Admin
 */

// phpcs:disable

function awpcp_categories_admin_page() {
    return new AWPCP_CategoriesAdminPage(
        awpcp()->container['listing_category_taxonomy'],
        awpcp_categories_collection(),
        awpcp_template_renderer(),
        awpcp_request()
    );
}

class AWPCP_CategoriesAdminPage {

    /**
     * @var string
     */
    private $listing_category_taxonomy;

    private $categories;
    private $template_renderer;
    private $request;

    public function __construct( $listing_category_taxonomy, $categories, $template_renderer, $request ) {
        $this->listing_category_taxonomy = $listing_category_taxonomy;
        $this->categories = $categories;
        $this->template_renderer = $template_renderer;
        $this->request = $request;
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function dispatch() {
        global $hascaticonsmodule; // Ugh!

        $icons = array(
            array(
                'label' => __( 'Edit Category', 'another-wordpress-classifieds-plugin' ),
                'class' => 'fa fa-pen fa-pencil',
                'image' => array(
                    'attributes' => array(
                        'alt' => __( 'Edit Category', 'another-wordpress-classifieds-plugin' ),
                        'src' => AWPCP_URL . "/resources/images/edit_ico.png",
                        'border' => 0,
                    ),
                ),
            ),
            array(
                'label' => __( 'Delete Category', 'another-wordpress-classifieds-plugin' ),
                'class' => 'fa fa-trash-alt fa-trash',
                'image' => array(
                    'attributes' => array(
                        'alt' => __( 'Delete Category', 'another-wordpress-classifieds-plugin' ),
                        'src' => AWPCP_URL . "/resources/images/delete_ico.png",
                        'border' => 0,
                    ),
                ),
            ),
        );

        if ( $hascaticonsmodule == 1 ) {
            $icons[] = array(
                'label' => __( 'Manage Category Icon', 'another-wordpress-classifieds-plugin' ),
                'class' => 'fa fa-wrench',
                'image' => array(
                    'attributes' => array(
                        'alt' => __( 'Manage Category Icon', 'another-wordpress-classifieds-plugin' ),
                        'src' => AWPCP_URL . "/resources/images/icon_manage_ico.png",
                        'border' => 0,
                    ),
                ),
            );
        }

        $children = $this->categories->get_hierarchy();
        $categories = $this->categories->get_all();

        $offset = (int) $this->request->param( 'offset' );
        $results = max( (int) $this->request->param( 'results', 10 ), 1 );
        $count = 0;

        $category_id = $this->request->param( 'cat_ID' );

        try {
            $category = $this->categories->get( $category_id );
        } catch ( AWPCP_Exception $e ) {
            $category = null;
        }

        $items = awpcp_admin_categories_render_category_items( $categories, $children, $offset, $results, $count );

        $template = AWPCP_DIR . '/templates/admin/manage-categories-admin-page.tpl.php';
        $params = array(
            'icons' => $icons,
            'pager1' => awpcp_pagination(
                [
                    'total'         => count( $categories ),
                    'offset'        => $offset,
                    'results'       => $results,
                    'show_dropdown' => false,
                ],
                ''
            ),
            'pager2' => awpcp_pagination(
                [
                    'total'          => count( $categories ),
                    'offset'         => $offset,
                    'results'        => $results,
                    'dropdown_label' => __( 'Categories per page:', 'another-wordpress-classifieds-plugin' ),
                ],
                ''
            ),
            'parent_category_dropdown_args' => [
                'hide_empty'        => false,
                'hide_if_empty'     => false,
                'taxonomy'          => $this->listing_category_taxonomy,
                'name'              => 'category_parent_id',
                'selected'          => $category ? $category->parent : false,
                'exclude_tree'      => $category ? $category->term_id : null,
                'hierarchical'      => true,
                'show_option_none'  => __( 'None (this is a top level category)', 'another-wordpress-classifieds-plugin' ),
                'option_none_value' => 0,
                'class'             => '',
            ],
            'target_category_dropdown_args' => [
                'hide_empty'        => false,
                'hide_if_empty'     => false,
                'taxonomy'          => $this->listing_category_taxonomy,
                'name'              => 'moveadstocategory',
                'hierarchical'      => true,
                'show_option_none'  => __( 'Select target category', 'another-wordpress-classifieds-plugin' ),
                'option_none_value' => 0,
                'class'             => '',
            ],
            'form_title' => $category ? __( 'Edit Category', 'another-wordpress-classifieds-plugin' ) : __( 'Add New Category', 'another-wordpress-classifieds-plugin' ),
            'form_values' => array(
                'category_id' => $category_id,
                'category_name' => $category ? $category->name : null,
                'category_description' => $category ? $category->description : null,
                'category_parent_id' => $category ? $category->parent : null,
                'category_order' => $category ? intval( get_term_meta( $category->term_id, '_awpcp_order', true ) ) : null,
                'action' => $category ? 'update-category' : 'create-category',
            ),
            'form_submit' => $category ? __( 'Update category', 'another-wordpress-classifieds-plugin' ) : __( 'Add new category', 'another-wordpress-classifieds-plugin' ),
            'items' => $items,
            'offset' => $offset,
            'results' => $results,
        );

        return $this->template_renderer->render_template( $template, $params );
    }
}
