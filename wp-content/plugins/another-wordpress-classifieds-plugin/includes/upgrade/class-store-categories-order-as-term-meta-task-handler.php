<?php
/**
 * @package AWPCP\Upgrade
 */

/**
 * Imports the values from the category_order column in the awpcp_categories table
 * and stores them as term meta associaded with new categpry terms.
 */
class AWPCP_StoreCategoriesOrderAsTermMetaTaskHandler implements AWPCP_Upgrade_Task_Runner {

    /**
     * @var CategoriesCollection
     */
    private $categories;

    /**
     * @var CategoriesRegistry
     */
    private $categories_registry;

    /**
     * @var WordPress
     */
    private $wordpress;

    /**
     * @var wpdb
     */
    private $db;

    /**
     * @since 4.0.0
     */
    public function __construct( $categories, $categories_registry, $wordpress, $db ) {
        $this->categories          = $categories;
        $this->categories_registry = $categories_registry;
        $this->wordpress           = $wordpress;
        $this->db                  = $db;
    }

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function count_pending_items( $last_item_id ) {
        return $this->categories->count_categories( $this->get_categories_query_vars() );
    }

    /**
     * @since 4.0.0
     */
    private function get_categories_query_vars() {
        // @phpcs:disable WordPress.DB.SlowDBQuery.slow_db_query_meta_query
        return [
            'orderby'    => 'term_id',
            'order'      => 'ASC',
            'meta_query' => [
                [
                    'key'     => '_awpcp_order',
                    'compare' => 'NOT EXISTS',
                ],
            ],
        ];
        // @phpcs:enable
    }

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function get_pending_items( $last_item_id ) {
        $query_vars = array_merge(
            $this->get_categories_query_vars(),
            [
                'number' => 50,
            ]
        );

        return $this->categories->find_categories( $query_vars );
    }

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process_item( $item, $last_item_id ) {
        $categories_registry = $this->categories_registry->get_categories_registry();
        $legacy_category_id  = array_search( $item->term_id, $categories_registry, true );
        $category_order      = 0;

        if ( $legacy_category_id ) {
            $category_order = $this->get_legacy_category_order( $legacy_category_id );
        }

        $this->wordpress->update_term_meta( $item->term_id, '_awpcp_order', $category_order );

        return $item->category_id;
    }

    /**
     * @since 4.0.0
     */
    private function get_legacy_category_order( $category_id ) {
        $sql = 'SELECT category_order FROM ' . AWPCP_TABLE_CATEGORIES . ' WHERE category_id = %d';

        return intval( $this->db->get_var( $this->db->prepare( $sql, $category_id ) ) );
    }
}
