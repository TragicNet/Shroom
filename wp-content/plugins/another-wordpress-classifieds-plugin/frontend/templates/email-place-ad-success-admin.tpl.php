<?php $message = __( 'A new ad has been submitted. A copy of the details sent to the customer can be found below. You can follow this link %s to go to the Classified Ads section to approve/reject/spam and see the full version of the ad.', 'another-wordpress-classifieds-plugin') ?>
<?php echo sprintf( $message, esc_url_raw( $url ) ); ?>

<?php echo $content ?>
