<?php
/**
 * @package AWPCP\UI
 */

function awpcp_classifieds_bar() {
    return new AWPCP_Classifieds_Bar(
        awpcp_classifieds_search_bar_component(),
        new AWPCP_Classifieds_Menu_Component( awpcp()->settings ),
        awpcp()->settings
    );
}

class AWPCP_Classifieds_Bar {

    private $classifieds_search_bar;
    private $classifieds_menu;
    private $settings;

    public function __construct( $classifieds_search_bar, $classifieds_menu, $settings ) {
        $this->classifieds_search_bar = $classifieds_search_bar;
        $this->classifieds_menu       = $classifieds_menu;
        $this->settings               = $settings;
    }

    public function render( $components = array() ) {
        $components = wp_parse_args(
            $components,
            [
                'search_bar' => $this->settings->get_option( 'show-classifieds-search-bar' ),
                'menu'       => true,
            ]
        );

        $should_show_classifieds_bar = $this->settings->get_option( 'show-classifieds-bar' );
        $output                      = '';

        /**
         * Filters whether the Classifieds Bar should be rendered.
         *
         * Allows others parts of the plugin, modules and third party plugins to
         * disalbe the Classifieds Bar on specific locations.
         *
         * @since 4.0.0
         */
        if ( ! apply_filters( 'awpcp_should_show_classifieds_bar', $should_show_classifieds_bar ) ) {
            return $output;
        }

        if ( $components['search_bar'] ) {
            $output .= $this->classifieds_search_bar->render();
        }

        if ( $components['menu'] ) {
            $output .= $this->classifieds_menu->render();
        }

        return $output;
    }
}
