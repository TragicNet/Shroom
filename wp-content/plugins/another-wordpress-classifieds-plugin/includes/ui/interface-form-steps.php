<?php
/**
 * @package AWPCP\UI
 */

/**
 * Interface for Form Steps class that defines the list of steps displayed by the Form Steps Component.
 */
interface AWPCP_FormSteps {

    /**
     * @since 4.0.0
     */
    public function get_steps( $params = [] );
}
