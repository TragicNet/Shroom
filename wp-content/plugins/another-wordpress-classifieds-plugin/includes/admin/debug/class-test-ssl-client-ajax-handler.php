<?php
/**
 * @package AWPCP\Admin\Debug
 */

/**
 * Ajax handler for the Test SSL Client action.
 */
class AWPCP_TestSSLClientAjaxHandler {

    /**
     * @since 4.0.0
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
    public function ajax() {
        if ( ! awpcp_current_user_is_admin() ) {
            die();
        }

        // phpcs:disable WordPress.WP.AlternativeFunctions
        // phpcs:disable WordPress.XSS.EscapeOutput

        if ( ! function_exists( 'curl_init' ) ) {
            die( 'cURL not available.' );
        }

        $ch = curl_init( 'https://www.howsmyssl.com/a/check' );

        curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
        curl_setopt( $ch, CURLOPT_SSLVERSION, 6 );

        $data = curl_exec( $ch );

        if ( 0 !== curl_errno( $ch ) ) {
            die( 'cURL error: ' . curl_error( $ch ) );
        }

        curl_close( $ch );

        if ( ! $data ) {
            die( 'No response from remote server.' );
        }

        $json = json_decode( $data );

        echo "Cipher Suites:\n" . implode( ',', $json->given_cipher_suites ) . "\n\n"; // XSS Ok.
        echo "TLS Version:\n" . $json->tls_version . "\n\n"; // XSS Ok.
        echo "Rating:\n" . $json->rating; // XSS Ok.

        exit();
    }
}
