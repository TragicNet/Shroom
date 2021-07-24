<?php echo get_awpcp_option('listingaddedbody') ?> 

<?php esc_html_e( 'Listing Title', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo $listing_title; ?>

<?php esc_html_e( 'Listing URL', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo urldecode( url_showad( $ad->ID ) ); ?>

<?php esc_html_e( 'Listing ID', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo $ad->ID; ?>

<?php esc_html_e( 'Listing Edit Email', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo $contact_email; ?>

<?php if ( $include_listing_access_key ): ?>
<?php esc_html_e( 'Listing Edit Key', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo $access_key; ?>

<?php endif; ?>
<?php if ($transaction): ?>
<?php printf( __( '%s Transaction', 'another-wordpress-classifieds-plugin' ), esc_html( $blog_name ) ); ?>: <?php echo $transaction->id ?>

<?php   if ($transaction->get('txn-id')): ?>
<?php esc_html_e( 'Payment Transaction', 'another-wordpress-classifieds-plugin' ); ?>: <?php echo $transaction->get('txn-id') ?>

<?php   endif ?>
<?php   if ( $show_total_amount ): ?>
<?php echo esc_html( __( 'Order Total', 'another-wordpress-classifieds-plugin' ) ); ?> (<?php echo esc_html( $currency_code ); ?>): <?php echo esc_html( awpcp_format_money( $total_amount ) ); ?> 

<?php   endif; ?>
<?php   if ( $show_total_credits ): ?>
<?php echo esc_html( __( 'Order Total (credits)', 'another-wordpress-classifieds-plugin' ) ); ?>: <?php echo esc_html( $total_credits ); ?> 

<?php   endif; ?>

<?php endif ?>
<?php if ( $include_edit_listing_url ): ?>

<?php _e( 'The next link will take you to a page where you can edit the listing:', 'another-wordpress-classifieds-plugin' ); ?> 

<?php echo awpcp_get_edit_listing_url( $ad, 'email' ); ?> 

<?php endif; ?>
<?php if (!empty($message)): ?>
<?php _e('Additional Details', 'another-wordpress-classifieds-plugin') ?> 

<?php echo $message ?> 

<?php endif ?>
<?php printf( __( 'If you have questions about your listing contact %s. Thank you for your business.', 'another-wordpress-classifieds-plugin' ), $admin_email ); ?>

<?php echo $blog_name; ?> 
<?php echo home_url(); ?> 
