<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * @since 4.0.0
 */
class AWPCP_ButtonSettingsRenderer {

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
        add_thickbox();
        $html = '<a href="#TB_inline?&width=500&height=130&modal=true&inlineId=pop-' . esc_attr( $setting['id'] ) . '" class="button-secondary thickbox"';

        if ( ! empty( $config ) ) {
            $html .= 'awpcp-setting="' . esc_attr( wp_json_encode( $config ) ) . '" ';
        }

        $nonce = wp_create_nonce( 'reset-default' );
        $html .= '>' . esc_html( $setting['default'] ) . '</a>';
        $html .= strlen( $setting['description'] ) > 20 ? '<br/>' : '&nbsp;';
        $html .= '<span class="description">' . $setting['description'] . '</span>';
        $html .= '<div id="pop-' . esc_attr( $setting['id'] ) . '" style="display:none">';
        $html .= '<h2 style="text-align: center">' . __( 'Resetting to the default layout will cause any custom HTML layout changes you\'ve made to be lost. Are you sure?', 'another-wordpress-classifieds-plugin' ) . '</h2>';
        $html .= '<p style="text-align: center">';
        $html .= '<button  class="button-secondary TB_closeWindowButton">' . __( 'Cancel', ' another-wordpress-classifieds-plugin' ) . '</button> ';
        $html .= '<button data-nonce="' . esc_attr( $nonce ) . '" id="' . esc_attr( $setting['id'] ) . '" class="button-primary">' . __( 'Reset', ' another-wordpress-classifieds-plugin' ) . '</button></p>';
        $html .= '</div>';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        echo $html;
    }
}
