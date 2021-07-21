<?php
/**
 * @package AWPCP\Admin\Fess
 */

// @phpcs:disable

function awpcp_fee_details_admin_page() {
    return new AWPCP_Fee_Details_Admin_Page(
        awpcp_fee_details_form(),
        awpcp_fees_collection(),
        awpcp_html_renderer(),
        awpcp_router(),
        awpcp_request()
    );
}

class AWPCP_Fee_Details_Admin_Page {

    private $fee_details_form;
    private $fees;
    private $html_renderer;
    private $router;
    private $request;

    public function __construct( $fee_details_form, $fees, $html_renderer, $router, $request ) {
        $this->fee_details_form = $fee_details_form;
        $this->fees = $fees;
        $this->html_renderer = $html_renderer;
        $this->router = $router;
        $this->request = $request;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-fee-details' );
    }

    public function dispatch() {
        $action = $this->request->get( 'awpcp-action' );

        if ( 'add-fee' === $action ) {
            return $this->add_fee();
        }

        return $this->edit_fee();
    }

    private function add_fee() {
        $fee = new AWPCP_Fee();

        if ( $this->request->post('save') || $this->request->post('save_and_continue') ) {
            return $this->update_fee( $fee );
        }

        return $this->render_form( $fee );
    }

    private function update_fee( $fee ) {
        $fee->update( $this->get_posted_data() );

        $errors = array();

        if ( $fee->save( $errors ) === false ) {
            awpcp_flash( __( 'The form has errors', 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->render_form( $fee );
        }

        awpcp_flash( __( 'Fee successfully updated.', 'another-wordpress-classifieds-plugin' ) );

        $redirect_target = array( 'parent' => 'awpcp.php', 'page' => 'awpcp-admin-fees' );

        return $this->router->redirect( apply_filters( 'awpcp-fee-details-successful-redirect', $redirect_target, $fee ) );
    }

    private function get_posted_data() {
        $fee_data = array(
            'name' => $this->request->post( 'name' ),
            'description' => $this->request->post( 'description' ),
            'price' => awpcp_parse_money( $this->request->post( 'price_in_currency' ) ),
            'credits' => max( 0, intval( $this->request->post( 'price_in_credits' ) ) ),
            'duration_amount' => $this->request->post( 'duration_amount' ),
            'duration_interval' => $this->request->post( 'duration_interval' ),
            'images' => $this->request->post( 'images_allowed' ),
            'private' => $this->request->post( 'is_private', false ),
            'featured' => $this->request->post( 'use_for_featured_listings', false ),
        );

		$values = array(
			'title_characters' => 'characters_allowed_in_title',
			'characters'       => 'characters_allowed_in_description',
		);
		foreach ( $values as $name => $value ) {
			if ( ! $this->request->post( $value . '_enabled' ) ) {
				$fee_data[ $name ] = 0;
			} else {
				$fee_data[ $name ] = $this->request->post( $value );
			}
		}

        return apply_filters( 'awpcp-fee-details-posted-data', $fee_data );
    }

    private function render_form( $fee ) {
        $params = array(
            'form_title' => __( 'Create Fee Plan', 'another-wordpress-classifieds-plugin' ),
            'fee' => $fee,
            'action' => 'create-fee',
        );

        return $this->html_renderer->render( $this->fee_details_form->build( $params ) );
    }

    private function edit_fee() {
        $fee_id = $this->request->param( 'id' );

        if ( empty( $fee_id ) ) {
            awpcp_flash( __( 'No Fee Plan id was specified.', 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->router->redirect( array( 'parent' => 'awpcp.php', 'page' => 'awpcp-admin-fees' ) );
        }

        try {
            $fee = $this->fees->get( $fee_id );
        } catch ( AWPCP_Exception $e ) {
            awpcp_flash( __( "The specified Fee Plan doesn't exist or couldn't be loaded.", 'another-wordpress-classifieds-plugin' ) );
            return $this->router->redirect( array( 'parent' => 'awpcp.php', 'page' => 'awpcp-admin-fees' ) );
        }

        if ( $this->request->post('save') || $this->request->post('save_and_continue') ) {
            return $this->update_fee( $fee );
        }

        return $this->render_form( $fee );
    }
}
