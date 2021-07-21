<?php
/**
 * @package AWPCP\Settings\Renderers
 */

/**
 * @since 4.0.0
 */
class AWPCP_LicenseSettingsRenderer {

    /**
     * @var LicensesManager
     */
    private $licenses_manager;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $licenses_manager, $settings ) {
        $this->licenses_manager = $licenses_manager;
        $this->settings         = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function render_setting( $setting ) {
        $module_name = $setting['params']['module_name'];
        $module_slug = $setting['params']['module_slug'];

        $license = $this->settings->get_option( $setting['id'] );

        echo '<input id="' . $setting['id'] . '" class="regular-text" type="text" name="awpcp-options[' . $setting['id'] . ']" value="' . esc_attr( $license ) . '">'; // XSS Ok.

        if ( ! empty( $license ) ) {
            if ( $this->licenses_manager->is_license_valid( $module_name, $module_slug ) ) {
                echo '<input class="button-secondary" type="submit" name="awpcp-deactivate-' . $module_slug . '-license" value="' . __( 'Deactivate', 'another-wordpress-classifieds-plugin' ) . '"/>'; // XSS Ok.
                echo '<br>' . str_replace( '<license-status>', '<span class="awpcp-license-status awpcp-license-valid">' . __( 'active', 'another-wordpress-classifieds-plugin' ) . '</span>.', __( 'Status: <license-status>', 'another-wordpress-classifieds-plugin' ) ); // XSS Ok.
            } elseif ( $this->licenses_manager->is_license_inactive( $module_name, $module_slug ) ) {
                echo '<input class="button-secondary" type="submit" name="awpcp-activate-' . $module_slug . '-license" value="' . __( 'Activate', 'another-wordpress-classifieds-plugin' ) . '"/>'; // XSS Ok.
                echo '<br>' . str_replace( '<license-status>', '<span class="awpcp-license-status awpcp-license-inactive">' . __( 'inactive', 'another-wordpress-classifieds-plugin' ) . '</span>.', __( 'Status: <license-status>', 'another-wordpress-classifieds-plugin' ) ); // XSS Ok.
            } else {
                echo '<input class="button-secondary" type="submit" name="awpcp-activate-' . $module_slug . '-license" value="' . __( 'Activate', 'another-wordpress-classifieds-plugin' ) . '"/>'; // XSS Ok.

                $contact_url     = 'https://awpcp.com/contact';
                $contact_message = __( 'Click the button above to check the status of your license. Please <contact-link>contact customer support</a> if you think the reported status is wrong.', 'another-wordpress-classifieds-plugin' );

                echo '<br>' . str_replace( '<contact-link>', '<a href="' . esc_url( $contact_url ) . '" target="_blank">', $contact_message ); // XSS Ok.

                if ( $this->licenses_manager->is_license_expired( $module_name, $module_slug ) ) {
                    echo '<br>' . str_replace( '<license-status>', '<span class="awpcp-license-status awpcp-license-expired">' . __( 'expired', 'another-wordpress-classifieds-plugin' ) . '</span>.', __( 'Status: <license-status>', 'another-wordpress-classifieds-plugin' ) ); // XSS Ok.
                } elseif ( $this->licenses_manager->is_license_disabled( $module_name, $module_slug ) ) {
                    echo '<br>' . str_replace( '<license-status>', '<span class="awpcp-license-status awpcp-license-invalid">' . __( 'disabled', 'another-wordpress-classifieds-plugin' ) . '</span>.', __( 'Status: <license-status>', 'another-wordpress-classifieds-plugin' ) ); // XSS Ok.
                } else {
                    echo '<br>' . str_replace( '<license-status>', '<span class="awpcp-license-status awpcp-license-invalid">' . __( 'unknown', 'another-wordpress-classifieds-plugin' ) . '</span>.', __( 'Status: <license-status>', 'another-wordpress-classifieds-plugin' ) ); // XSS Ok.
                }
            }
            wp_nonce_field( 'awpcp-update-license-status-nonce', 'awpcp-update-license-status-nonce' );
        }
    }
}
