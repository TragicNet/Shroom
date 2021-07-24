<?php
/**
 * @package AWPCP\Frontend
 */

/**
 * Interface for CAPTCHA providers.
 */
interface AWPCP_CAPTCHAProviderInterface {

    /**
     * @since 4.0.0
     */
    public function render();

    /**
     * @since 4.0.0
     */
    public function validate();
}
