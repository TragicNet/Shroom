<?php
/**
 * @package AWPCP\Settings
 */

/**
 * @since 4.0.0
 */
class AWPCP_SettingsRenderer {

    /**
     * @var array
     */
    private $settings_renderers;

    /**
     * @var SettingsManager
     */
    private $settings_manager;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings_renderers, $settings_manager ) {
        $this->settings_renderers = $settings_renderers;
        $this->settings_manager   = $settings_manager;
    }

    /**
     * @since 4.0.0
     */
    public function render_settings_section( $params ) {
        $section = $this->settings_manager->get_settings_section( $params['id'] );

        if ( isset( $section['description'] ) ) {
            echo $section['description']; // XSS Ok.
        }

        if ( isset( $section['callback'] ) && is_callable( $section['callback'] ) ) {
            echo call_user_func( $section['callback'], $section ); // XSS Ok.
        }
    }

    /**
     * @since 4.0.0
     */
    public function render_setting( $params ) {
        $setting = $this->settings_manager->get_setting( $params['setting_id'] );
        $config  = [];

        if ( ! empty( $setting['behavior'] ) || ! empty( $setting['validation'] ) ) {
            $config = $this->settings_manager->get_setting_configuration( $setting );
        }

        try {
            return $this->get_settings_renderer( $setting['type'] )->render_setting( $setting, $config );
        } catch ( AWPCP_Exception $e ) {
            echo $e->getMessage(); // XSS Ok.
        }
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  If there is no setting renderer for this setting type.
     */
    private function get_settings_renderer( $setting_type ) {
        if ( ! isset( $this->settings_renderers[ $setting_type ] ) ) {
            throw new AWPCP_Exception( "Setting renderer not found for setting type: {$setting_type}." );
        }

        return $this->settings_renderers[ $setting_type ];
    }

    /**
     * @since 4.0.0
     */
    public function is_renderer_available( $setting ) {
        try {
            $this->get_settings_renderer( $setting['type'] );
        } catch ( AWPCP_Exception $e ) {
            return false;
        }

        return true;
    }
}
