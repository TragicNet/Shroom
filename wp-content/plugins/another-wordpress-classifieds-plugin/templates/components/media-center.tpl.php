<?php
/**
 * @package AWPCP\Templates
 */

?><div class="awpcp-media-center">
    <?php $media_uploader = awpcp_listings_media_uploader_component(); ?>
    <?php echo $media_uploader->render( $media_uploader_configuration ); // XSS Ok. ?>

    <?php $messages = awpcp_messages_component(); ?>
    <?php echo $messages->render( array( 'media-uploader', 'media-manager', 'thumbnails-generator' ) ); // XSS Ok. ?>

    <?php if ( $show_background_color_explanation ) : ?>
    <p><?php echo esc_html( __( 'The images or files with pale red background have been rejected by an administrator user. Likewise, files with a pale yellow background are awaiting approval. Files that are awaiting approval and rejected files, cannot be shown in the frontend.', 'another-wordpress-classifieds-plugin' ) ); ?></p>
    <?php endif; ?>

    <?php $media_manager = awpcp_media_manager_component(); ?>
    <?php echo $media_manager->render( $files, $media_manager_configuration ); // XSS Ok. ?>

    <div class="awpcp-thumbnails-generator" data-nonce="<?php echo esc_attr( wp_create_nonce( 'awpcp-upload-generated-thumbnail-for-listing-' . $listing->ID ) ); ?>">
        <video preload="none" muted="muted" width="0" height="0"></video>
        <canvas></canvas>
    </div>
</div>
