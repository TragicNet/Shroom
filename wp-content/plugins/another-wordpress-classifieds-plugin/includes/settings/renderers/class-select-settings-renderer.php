<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * @since 4.0.0
 */
class AWPCP_SelectSettingsRenderer {

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
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function render_setting( $setting ) {
        $current = stripslashes( $this->settings->get_option( $setting['id'] ) );

        $html = '<select id="' . esc_attr( $setting['id'] ) . '" name="awpcp-options[' . esc_attr( $setting['id'] ) . ']">';

        foreach ( $setting['options'] as $value => $label ) {
            if ( 0 === strcmp( $value, $current ) ) {
                $html .= '<option value="' . esc_attr( $value ) . '" selected="selected">' . esc_html( $label ) . '</option>';
            } else {
                $html .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $label ) . '</option>';
            }
        }

        $html .= '</select><br/>';
        $html .= '<span class="description">' . $setting['description'] . '</span>';

        echo $html; // XSS Ok.
    }
}
