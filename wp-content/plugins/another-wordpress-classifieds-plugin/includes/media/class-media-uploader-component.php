<?php
/**
 * @package AWPCP\UI
 */

/**
 * Constructor function for Media Uploader Component
 */
function awpcp_media_uploader_component() {
    return new AWPCP_MediaUploaderComponent( awpcp()->js );
}

/**
 * Generic UI component used to upload media.
 */
class AWPCP_MediaUploaderComponent {

    /**
     * @var JavaScript
     */
    private $javascript;

    /**
     * @param JavaScript $javascript    An instance of JavaScript.
     */
    public function __construct( $javascript ) {
        $this->javascript = $javascript;
    }

    /**
     * @param array $configuration  An array of configuration options.
     */
    public function render( $configuration ) {
        $configuration = wp_parse_args( $configuration, array(
            'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
            'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
        ) );

        $this->javascript->set( 'media-uploader-data', $configuration );

        return $this->render_component( $configuration );
    }

    /**
     * @param array $configuration  An array of configuration options.
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function render_component( $configuration ) {
        ob_start();
        include AWPCP_DIR . '/templates/components/media-uploader.tpl.php';
        $output = ob_get_contents();
        ob_end_clean();

        return $output;
    }
}
