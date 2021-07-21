<?php
/**
 * @package AWPCP\Admin\Importer
 */

/**
 * @since 4.1.0
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AWPCP_ExportListingsAdminPage {

	private $request;

	public function __construct( $request ) {
		$this->request = $request;
	}

	public function enqueue_scripts() {
		wp_enqueue_style( 'awpcp-admin-export-style' );
		wp_enqueue_script( 'awpcp-admin-export' );
	}

    public function dispatch() {
    	$template = AWPCP_DIR . '/templates/admin/export-listings-admin-page.tpl.php';
	    return awpcp_render_template( $template, array() );
    }

	/**
	 * @SuppressWarnings(PHPMD.StaticAccess)
	 * @SuppressWarnings(PHPMD.ElseExpression)
	 * @SuppressWarnings(PHPMD.CyclomaticComplexity)
	 * @SuppressWarnings(PHPMD.NPathComplexity)
	 */
    public function ajax() {
	    $verify_nonce = check_ajax_referer( 'awpcp-export-csv' );
	    if ( ! current_user_can( 'administrator' ) || ! $verify_nonce ) {
		    wp_send_json_error();
	    }

	    $error = '';

	    try {
		    if ( ! isset( $_REQUEST['state'] ) ) {
			    $export = new AWPCP_CSVExporter( array_merge( $this->request->post( 'settings' ), array() ), awpcp_settings_api() );
		    } else {
			    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode
			    $state = json_decode( base64_decode( $this->request->post( 'state' ) ), true );
			    if ( ! $state || ! is_array( $state ) || empty( $state['workingdir'] ) ) {
				    $error = _x( 'Could not decode export state information.', 'admin csv-export', 'another-wordpress-classifieds-plugin' );
			    }

			    $export = AWPCP_CSVExporter::from_state( $state );

			    if ( isset( $_REQUEST['cleanup'] ) && $_REQUEST['cleanup'] === '1' ) {
				    $export->cleanup();
			    } else {
				    $export->advance();
			    }
		    }
	    } catch ( Exception $e ) {
		    $error = $e->getMessage();
	    }

	    $state = ! $error ? $export->get_state() : null;

	    $response          = array();
	    $response['error'] = $error;
	    // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
	    $response['state']    = $state ? base64_encode( wp_json_encode( $state ) ) : null;
	    $response['count']    = $state ? count( $state['listings'] ) : 0;
	    $response['exported'] = $state ? $state['exported'] : 0;
	    $response['filesize'] = $state ? size_format( $state['filesize'] ) : 0;
	    $response['isDone']   = $state ? $state['done'] : false;
	    $response['fileurl']  = $state ? ( $state['done'] ? $export->get_file_url() : '' ) : '';
	    $response['filename'] = $state ? ( $state['done'] ? basename( $export->get_file_url() ) : '' ) : '';

	    wp_send_json( $response );
    }
}
