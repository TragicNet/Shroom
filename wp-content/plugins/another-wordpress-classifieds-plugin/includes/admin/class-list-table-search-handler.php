<?php
/**
 * @package AWPCP\Admin
 */

/**
 * A class to handle different search mode on the classifieds table.
 */
class AWPCP_ListTableSearchHandler {

    /**
     * @var object
     */
    private $search_modes;

    /**
     * @var object
     */
    private $html_renderer;

    /**
     * @var object
     */
    private $request;

    /**
     * @param array  $search_modes      An array of available search modes.
     * @param object $html_renderer     An instance of HTML Renderer.
     * @param object $request           An instance of Request.
     * @since 4.0.0
     */
    public function __construct( $search_modes, $html_renderer, $request ) {
        $this->search_modes  = $search_modes;
        $this->html_renderer = $html_renderer;
        $this->request       = $request;
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.14
     */
    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-listings-table' );
    }

    /**
     * @param object $query     An instance of WP_Query.
     * @since 4.0.0
     */
    public function pre_get_posts( $query ) {
        $search_mode_id = $this->get_selected_search_mode_id();

        if ( isset( $this->search_modes[ $search_mode_id ] ) ) {
            $this->search_modes[ $search_mode_id ]->pre_get_posts( $query );
            return;
        }

        $this->search_modes['keyword']->pre_get_posts( $query );
    }

    /**
     * @since 4.0.0
     */
    public function get_selected_search_mode_id() {
        return $this->request->param( 'awpcp_search_by' );
    }

    /**
     * @since 4.0.0
     */
    public function get_search_query() {
        return $this->request->param( 's' );
    }

    /**
     * @param string $position  The postion of the nav controls being rendered.
     * @since 4.0.0
     */
    public function render_search_mode_dropdown( $position ) {
        if ( 'bottom' === $position ) {
            return;
        }

        $options = array();

        foreach ( $this->search_modes as $id => $search_mode ) {
            $options[ $id ] = $search_mode->get_name();
        }

        $container = [
            '#type'       => 'div',
            '#attributes' => [
                'class' => [
                    'awpcp-search-mode-dropdown-container',
                    'awpcp-hidden',
                ],
            ],
            '#content'    => [
                'dropdown-label' => [
                    '#type'       => 'label',
                    '#attributes' => [
                        'for' => 'awpcp-search-mode-dropdown',
                    ],
                    '#content'    => __( 'Search for listings matching', 'another-wordpress-classifieds-plugin' ),
                ],
                'dropdown'       => [
                    '#type'       => 'select',
                    '#attributes' => array(
                        'id'    => 'awpcp-search-mode-dropdown',
                        'name'  => 'awpcp_search_by',
                        'class' => array(
                            'awpcp-search-mode-dropdown',
                        ),
                    ),
                    '#options'    => $options,
                    '#value'      => $this->get_selected_search_mode_id(),
                ],
            ],
        ];

        echo $this->html_renderer->render( $container ); // XSS Ok.
    }
}
