<?php
/**
 * @package AWPCP\Media
 */

/**
 * Constructor function for Image File Processor.
 */
function awpcp_image_file_processor() {
    return new AWPCP_ImageFileProcessor( awpcp()->settings );
}

/**
 * Process uploaded image files before they are associated with listings.
 */
class AWPCP_ImageFileProcessor {

    /**
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     */
    public function __construct( $settings ) {
        $this->settings = $settings;
    }

    /**
     * Entry point.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process_file( $listing, $file ) {
        $this->try_to_fix_image_rotation( $file );
    }

    /**
     * Attemps to fix image rotation.
     */
    private function try_to_fix_image_rotation( $file ) {
        if ( ! function_exists( 'exif_read_data' ) ) {
            return;
        }

        $exif_data = exif_read_data( $file->get_path() );

        $orientation = isset( $exif_data['Orientation'] ) ? $exif_data['Orientation'] : 0;
        $mime_type   = isset( $exif_data['MimeType'] ) ? $exif_data['MimeType'] : '';

        $rotation_angle = 0;
        if ( 6 === $orientation ) {
            $rotation_angle = 90;
        } elseif ( 3 === $orientation ) {
            $rotation_angle = 180;
        } elseif ( 8 === $orientation ) {
            $rotation_angle = 270;
        }

        if ( $rotation_angle > 0 ) {
            awpcp_rotate_image( $file->get_path(), $mime_type, $rotation_angle );
        }
    }
}
