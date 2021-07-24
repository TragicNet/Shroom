<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * @since 4.0.0
 */
class AWPCP_RadioSettingsRenderer {

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
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function render_setting( $setting ) {
        $current = esc_html( stripslashes( $this->settings->get_option( $setting['id'] ) ) );
        $options = array();

        if ( is_array( $setting['options'] ) ) {
            $options = $setting['options'];
        }

        if ( is_callable( $setting['options'] ) ) {
            $options = call_user_func( $setting['options'] );
        }

        $html = '';

        foreach ( $options as $key => $label ) {
            $value = $key;

            if ( is_array( $label ) ) {
                $value = $label['value'];
                $label = $label['label'];
            }

            $id    = "{$setting['id']}-$key";
            $label = ' <label for="' . esc_attr( $id ) . '">' . $label . '</label>';

            $html .= '<input id="' . esc_attr( $id ) . '"type="radio" value="' . esc_attr( $value ) . '" ';
            $html .= 'name="awpcp-options[' . $setting['id'] . ']" ';

            if ( 0 === strcmp( (string) $key, $current ) ) {
                $html .= 'checked="checked" />' . $label;
            } else {
                $html .= '>' . $label;
            }

            $html .= '<br/>';
        }

        $html .= '<span class="description">' . $setting['description'] . '</span>';

        echo $html; // XSS Ok.
    }
}
