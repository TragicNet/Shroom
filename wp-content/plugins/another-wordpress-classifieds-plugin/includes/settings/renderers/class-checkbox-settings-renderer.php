<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * @since 4.0.0
 */
class AWPCP_CheckboxSettingsRenderer {

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
    public function render_setting( $setting, $config ) {
        $value = intval( $this->settings->get_option( $setting['id'] ) );

        $html = '<input type="hidden" value="0" name="awpcp-options[' . $setting['id'] . ']" ';

        if ( ! empty( $config ) ) {
            $html .= 'awpcp-setting="' . esc_attr( wp_json_encode( $config ) ) . '" />';
        } else {
            $html .= '/>';
        }

        $html .= '<input id="' . $setting['id'] . '" value="1" ';
        $html .= 'type="checkbox" name="awpcp-options[' . $setting['id'] . ']" ';

        if ( $value ) {
            $html .= 'checked="checked" ';
        }

        if ( ! empty( $config ) ) {
            $html .= 'awpcp-setting="' . esc_attr( wp_json_encode( $config ) ) . '" />';
        } else {
            $html .= '/>';
        }

        $html .= '<label for="' . $setting['id'] . '">';
        $html .= '&nbsp;<span class="description">' . $setting['description'] . '</span>';
        $html .= '</label>';

        echo $html; // XSS Ok.
    }
}
