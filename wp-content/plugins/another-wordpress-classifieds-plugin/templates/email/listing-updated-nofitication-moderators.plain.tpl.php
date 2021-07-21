<?php
    $message = __( 'The ad "<listing-title>" was modified. A copy of the details sent to the customer can be found below. You can follow this link <manage-listing-link> to go to the Manage Ad Listing section to approve/reject/spam and see the full version of the Ad.', 'another-wordpress-classifieds-plugin' );
    $message = str_replace( '<listing-title>', $listing_title, $message );
    $message = str_replace( '<manage-listing-link>', $manage_listing_url, $message );

    echo $message; ?>

<?php echo $content ?>
