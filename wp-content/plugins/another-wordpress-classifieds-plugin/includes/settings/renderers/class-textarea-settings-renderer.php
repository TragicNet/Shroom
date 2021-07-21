<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * @since 4.0.0
 */
class AWPCP_TextareaSettingsRenderer {

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
        $value = stripslashes( $this->settings->get_option( $setting['id'] ) );

        $html  = '<textarea id="' . $setting['id'] . '" class="all-options" ';
        $html .= 'name="awpcp-options[' . $setting['id'] . ']">';
        $html .= esc_html( $value );
        $html .= '</textarea><br/>';
        $html .= '<span class="description">' . $setting['description'] . '</span>';

        echo $html; // XSS Ok.
    }
}
