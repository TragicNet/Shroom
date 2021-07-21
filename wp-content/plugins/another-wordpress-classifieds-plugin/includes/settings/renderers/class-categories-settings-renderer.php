<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * @since 4.0.0
 */
class AWPCP_CategoriesSettingsRenderer {

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function render_setting( $setting ) {
        $params = array(
            'field_name'           => 'awpcp-options[' . $setting['id'] . ']',
            'selected'             => $this->settings->get_option( $setting['id'] ),
            'first_level_ul_class' => 'awpcp-categories-list',
            'no-cache'             => time(),
        );

        $checklist = awpcp_categories_checkbox_list_renderer()->render( $params );

        echo sprintf( '<div class="cat-checklist category-checklist">%s</div>', $checklist ); // XSS Ok.
        echo '<span class="description">' . $setting['description'] . '</span>'; // XSS Ok.
    }
}
