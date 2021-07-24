<?php
/**
 * @package AWPCP\FormFields
 */

/**
 * @deprecated 4.0.0    Use an instance of Form Field Renderer instead.
 */
function awpcp_form_fields() {
    _deprecated_function( __FUNCTION__, '4.0.0', 'Use an instance of Form Field Renderer instead.' );
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_FormFields();
    }

    return $instance;
}

/**
 * @deprecated 4.0.0    Use an instance of Form Field Renderer instead.
 */
class AWPCP_FormFields {

    public function get_fields() {
        _deprecated_function( __FUNCTION__, '4.0.0', 'AWPCP_FormFieldsRenderer::get_fields()' );
        return [];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function get_field( $slug ) {
        _deprecated_function( __FUNCTION__, '4.0.0', 'AWPCP_FormFieldsRenderer::get_field()' );
        return (object) [];
    }

    public function get_fields_order() {
        _deprecated_function( __FUNCTION__, '4.0.0', '' );
        return [];
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function update_fields_order( $order ) {
        _deprecated_function( __FUNCTION__, '4.0.0', '' );
        return false;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render_fields( $form_values, $form_errors, $listing, $context ) {
        _deprecated_function( __FUNCTION__, '4.0.0', 'AWPCP_FormFieldsRenderer::render_fields()' );
        return '';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function render_field( $field, $form_value, $form_errors, $listing, $context ) {
        _deprecated_function( __FUNCTION__, '4.0.0', 'AWPCP_FormFieldsRenderer::render_field()' );
        return '';
    }
}
