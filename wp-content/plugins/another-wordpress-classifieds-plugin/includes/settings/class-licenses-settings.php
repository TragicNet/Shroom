<?php
/**
 * @package AWPCP\Settings
 */

/**
 * @since 4.0.0
 */
class AWPCP_LicensesSettings {

    /**
     * @var SettingsManager
     */
    private $settings_manager;

    /**
     * @since 4.0.0
     */
    public function __construct( $settings_manager ) {
        $this->settings_manager = $settings_manager;
    }

    /**
     * @since 4.0.0
     */
    public function add_license_setting( $module_name, $module_slug ) {
        $group = $this->settings_manager->get_settings_group( 'licenses-settings' );

        if ( is_null( $group ) ) {
            $this->register_licenses_settings_group();
        }

        $setting_label = __( '<module-name> License Key', 'another-wordpress-classifieds-plugin' );
        $setting_label = str_replace( '<module-name>', $module_name, $setting_label );

        $this->settings_manager->add_setting( [
            'id'      => "$module_slug-license",
            'name'    => $setting_label,
            'type'    => 'license',
            'default' => '',
            'params'  => compact( 'module_name', 'module_slug' ),
            'section' => 'premium-modules-licenses',
        ] );
    }

    /**
     * Defines Licenses settings group, sub group and section.
     */
    private function register_licenses_settings_group() {
        $this->settings_manager->add_settings_group( [
            'id'       => 'licenses-settings',
            'name'     => __( 'Licenses', 'another-wordpress-classifieds-plugin' ),
            'priority' => 100000,
        ] );

        $this->settings_manager->add_settings_subgroup( [
            'id'     => 'licenses-settings',
            'name'   => __( 'Licenses', 'another-wordpress-classifieds-plugin' ),
            'parent' => 'licenses-settings',
        ] );

        $this->settings_manager->add_settings_section( [
            'id'          => 'premium-modules-licenses',
            'name'        => __( 'Premium Modules', 'another-wordpress-classifieds-plugin' ),
            'subgroup'    => 'licenses-settings',
            'callback'    => [ $this, 'render_description_for_licenses_settings_section' ],
            'description' => $this->render_section_description(),
        ] );
    }

    /**
     * @since 3.7.6
     */
    private function render_section_description() {
        $ip_address = awpcp_get_server_ip_address();

        if ( ! $ip_address ) {
            return '';
        }

        $description = _x( 'The IP address of your server is <ip-address>. Please make sure to include that information if you need to contact support about problems trying to activate your licenses.', 'settings', 'WPBDM' );
        $description = str_replace( '<ip-address>', '<strong>' . $ip_address . '</strong>', $description );

        return '<p class="description">' . $description . '</p>';
    }
}
