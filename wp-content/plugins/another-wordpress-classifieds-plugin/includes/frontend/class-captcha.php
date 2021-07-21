<?php
/**
 * @package AWPCP\Frontend
 */

/**
 * Handles CAPTCHA validation for different forms.
 */
class AWPCP_CAPTCHA {

    /**
     * @var CATPCHAProviderInterface
     */
    private $provider;

    /**
     * @var RolesAndCapabilities
     */
    private $roles;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * @since 4.0.0
     */
    public function __construct( $provider, $roles, $settings ) {
        $this->provider = $provider;
        $this->roles    = $roles;
        $this->settings = $settings;
    }

    /**
     * @since 4.0.0
     */
    public function is_captcha_required() {
        if ( ! $this->settings->get_option( 'captcha-enabled-in-place-listing-form' ) ) {
            return false;
        }

        if ( $this->roles->current_user_is_administrator() ) {
            return false;
        }

        return true;
    }

    /**
     * @since 4.0.0
     */
    public function render() {
        return $this->provider->render();
    }

    /**
     * @since 4.0.0
     * @throws AWPCP_Exception  If the answer to the CAPTCHA challenge is not correct.
     */
    public function validate() {
        if ( ! $this->is_captcha_required() ) {
            return true;
        }

        // The provider should throw an exception if the answer to the challenge
        // is not correct. If the provider simply returns false, then we throw a
        // generic exception.
        if ( ! $this->provider->validate() ) {
            throw new AWPCP_Exception( __( 'An unknown error ocurred trying to validate the answer to the CAPTCHA challenge.', 'another-wordpress-classifieds-plugin' ) );
        }

        return true;
    }
}
