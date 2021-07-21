<?php
/**
 * @package AWPCP\Listings
 */

// phpcs:disable Generic.Commenting
// phpcs:disable Generic.Formatting
// phpcs:disable Generic.Functions
// phpcs:disable PEAR.Functions
// phpcs:disable PSR2.ControlStructures
// phpcs:disable Squiz.Commenting
// phpcs:disable Squiz.Functions
// phpcs:disable Squiz.PHP
// phpcs:disable Squiz.Strings
// phpcs:disable WordPress.Arrays
// phpcs:disable WordPress.Classes
// phpcs:disable WordPress.NamingConventions
// phpcs:disable WordPress.PHP
// phpcs:disable WordPress.VIP
// phpcs:disable WordPress.WP
// phpcs:disable WordPress.WhiteSpace

/**
 * Return an array of Ad Fees.
 *
 * @since 2.0.7
 * @deprecated  since 3.0
 */
function awpcp_get_fees() {
	global $wpdb;

	$sql = 'SELECT * FROM ' . AWPCP_TABLE_ADFEES . ' ORDER BY adterm_name ASC';
	$results = $wpdb->get_results($sql);

	return is_array($results) ? $results : array();
}

/**
 * Generic function to calculate an date relative to a given start date.
 *
 * @since 2.0.7
 * @SuppressWarnings(PHPMD)
 */
function awpcp_calculate_end_date($increment, $period, $start_date) {
	$periods = array('D' => 'DAY', 'W' => 'WEEK', 'M' => 'MONTH', 'Y' => 'YEAR');
	if (in_array($period, array_keys($periods))) {
		$period = $periods[$period];
	}

	// 0 means no expiration date, we understand that as ten years
	if ($increment == 0 && $period == 'DAY') {
		$increment = 3650;
	} else if ($increment == 0 && $period == 'WEEK') {
		$increment = 5200;
	} else if ($increment == 0 && $period == 'MONTH') {
		$increment = 1200;
	} else if ($increment == 0 && $period == 'YEAR') {
		$increment = 10;
	}

	return date('Y-m-d H:i:s', strtotime("+ $increment $period", $start_date));
}

/**
 * It must be possible to have more than one transaction associated to a single
 * Ad, for example, when an Ad has been posted AND renewed one or more times.
 *
 * This can be moved into the Ad class. We actually don't need a transaction,
 * because the payment_status is stored in the Ad object. We need, however, to update
 * the payment_status when the Ad is placed AND renewed. ~2012-09-19
 *
 * @param $id	Ad ID.
 * @param $transaction	Payment Transaction associated to the Ad being posted
 * @deprecated 4.0.0    This is function is no longer used.
 * @SuppressWarnings(PHPMD)
 */
function awpcp_calculate_ad_disabled_state($id=null, $transaction=null, $payment_status=null) {
    if ( is_null( $payment_status ) && ! is_null( $transaction ) ) {
        $payment_status = $transaction->payment_status;
    }

    $payment_is_pending = $payment_status == AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;

    if ( awpcp_current_user_is_moderator() ) {
        $disabled = 0;
    } else if ( get_awpcp_option( 'adapprove' ) == 1 ) {
        $disabled = 1;
    } else if ( $payment_is_pending && get_awpcp_option( 'enable-ads-pending-payment' ) == 1 ) {
        $disabled = 0;
    } else if ( $payment_is_pending ) {
        $disabled = 1;
    } else {
        $disabled = 0;
    }

    return $disabled;
}

/**
 * @deprecated 4.0.0    This function is no longer used.
 * @SuppressWarnings(PHPMD)
 */
function awpcp_should_disable_new_listing_with_payment_status( $listing, $payment_status ) {
    $payment_is_pending = $payment_status == AWPCP_Payment_Transaction::PAYMENT_STATUS_PENDING;

    if ( awpcp_current_user_is_moderator() ) {
        $should_disable = false;
    } else if ( get_awpcp_option( 'adapprove' ) == 1 ) {
        $should_disable = true;
    } else if ( $payment_is_pending && get_awpcp_option( 'enable-ads-pending-payment' ) == 1 ) {
        $should_disable = false;
    } else if ( $payment_is_pending ) {
        $should_disable = true;
    } else {
        $should_disable = false;
    }

    return $should_disable;
}

/**
 * @deprecated 4.0.0    This function is no longer used.
 */
function awpcp_should_enable_new_listing_with_payment_status( $listing, $payment_status ) {
    return awpcp_should_disable_new_listing_with_payment_status( $listing, $payment_status ) ? false : true;
}

/**
 * @SuppressWarnings(PHPMD)
 */
function awpcp_should_disable_existing_listing( $listing ) {
    if ( awpcp_current_user_is_moderator() ) {
        $should_disable = false;
    } else if ( get_awpcp_option( 'disable-edited-listings-until-admin-approves' ) ) {
        $should_disable = true;
    } else {
        $should_disable = false;
    }

    return $should_disable;
}

function awpcp_should_enable_existing_listing( $listing ) {
    return awpcp_should_disable_existing_listing( $listing ) ? false : true;
}

/**
 * @since 3.0.2
 * @deprecated 4.0.0    Use ListingRenewedEmailNotifications::send_user_notification().
 */
function awpcp_ad_renewed_user_email( $ad ) {
	$listing_renderer = awpcp_listing_renderer();

	$introduction = get_awpcp_option( 'ad-renewed-email-body' );
	$listing_title = $listing_renderer->get_listing_title( $ad );
	$contact_name = $listing_renderer->get_contact_name( $ad );
	$contact_email = $listing_renderer->get_contact_email( $ad );
	$access_key = $listing_renderer->get_access_key( $ad );
	$end_date = $listing_renderer->get_end_date( $ad );

	$mail = new AWPCP_Email;
	$mail->to[] = awpcp_format_recipient_address( $contact_email, $contact_name );
	$mail->subject = sprintf( get_awpcp_option( 'ad-renewed-email-subject' ), $listing_title );

	$template = AWPCP_DIR . '/frontend/templates/email-ad-renewed-success-user.tpl.php';
	$params = compact( 'ad', 'listing_title', 'contact_name', 'contact_email', 'access_key', 'end_date', 'introduction' );

	$mail->prepare( $template, $params );

	return $mail;
}


/**
 * @since 3.0.2
 * @deprecated 4.0.0    Use ListingRenewedEmailNotifications::send_admin_notification().
 */
function awpcp_ad_renewed_admin_email( $ad, $body ) {
	$subject = __( 'The ad "%s" has been successfully renewed.', 'another-wordpress-classifieds-plugin' );
	$subject = sprintf( $subject, awpcp_listing_renderer()->get_listing_title( $ad ) );

	$mail = new AWPCP_Email;
	$mail->to[] = awpcp_admin_email_to();
	$mail->subject = $subject;

	$template = AWPCP_DIR . '/frontend/templates/email-ad-renewed-success-admin.tpl.php';
	$mail->prepare( $template, compact( 'body' ) );

	return $mail;
}


/**
 * @since 2.1.2
 */
function awpcp_send_ad_renewed_email($ad) {
	// send notification to the user
	$user_email = awpcp_ad_renewed_user_email( $ad );
	$user_email->send();

    if ( awpcp()->settings->get_option( 'send-listing-renewed-notification-to-admin' ) ) {
        // send notification to the admin
        $admin_email = awpcp_ad_renewed_admin_email( $ad, $user_email->body );
        $admin_email->send();
    }
}

/**
 * @since 2.0.7
 * @deprecated 4.0.0 No longer used by internal code.
 * @SuppressWarnings(PHPMD)
 */
function awpcp_renew_ad_success_message($ad, $text=null, $send_email=true) {
	if (is_null($text)) {
		$text = __( 'The Ad has been successfully renewed. New expiration date is %s.', 'another-wordpress-classifieds-plugin' );
	}

	$return = '';
	if (is_admin()) {
		$return = sprintf('<a href="%1$s">%2$s</a>', awpcp_get_user_panel_url(), __( 'Return to Listings', 'another-wordpress-classifieds-plugin'));
	}

	if ($send_email) {
		awpcp_send_ad_renewed_email($ad);
	}

	return sprintf("%s %s", sprintf($text, $ad->get_end_date()), $return);
}

/**
 * @SuppressWarnings(PHPMD)
 */
function deletead($adid, $adkey, $editemail, $force=false, &$errors=array()) {
	$output = '';
	$awpcppage = get_currentpagename();
	$awpcppagename = sanitize_title($awpcppage, $post_ID='');

	$isadmin = checkifisadmin() || $force;

	if (get_awpcp_option('onlyadmincanplaceads') && ( $isadmin != 1 )) {
		$message = __("You do not have permission to perform the function you are trying to perform. Access to this page has been denied",'another-wordpress-classifieds-plugin');
		$errors[] = $message;

	} else {
		$savedemail=get_adposteremail($adid);

		if ( $isadmin == 1 || strcasecmp( $editemail, $savedemail ) == 0 ) {
			try {
				$ad = awpcp_listings_collection()->get( $adid );
			} catch ( AWPCP_Exception $e ) {
				$ad = null;
			}

			if ( $ad && awpcp_listings_api()->delete_listing( $ad ) ) {
				if (( $isadmin == 1 ) && is_admin()) {
					$message=__("The Ad has been deleted",'another-wordpress-classifieds-plugin');
					return $message;
				} else {
					$message=__("Your Ad details and any photos you have uploaded have been deleted from the system",'another-wordpress-classifieds-plugin');
					$errors[] = $message;
				}
			} else if ( $ad === null ) {
				$errors[] = __( "The specified Ad doesn't exists.", 'another-wordpress-classifieds-plugin' );
			} else {
				$errors[] = __( "There was an error trying to delete the Ad. The Ad was not deleted.", 'another-wordpress-classifieds-plugin' );
			}
		} else {
			$message=__("Problem encountered. Cannot complete  request",'another-wordpress-classifieds-plugin');
			$errors[] = $message;
		}
	}

	$output .= "<div id=\"classiwrapper\">";
	$output .= awpcp_menu_items();
	$output .= "<p>";
	$output .= $message;
	$output .= "</p>";
	$output .= "</div>";

	return $output;
}


/**
 * @since 3.0.2
 * @SuppressWarnings(PHPMD)
 */
function awpcp_ad_posted_user_email( $ad, $transaction = null, $message='' ) {
	$admin_email = awpcp_admin_recipient_email_address();

	$payments_api = awpcp_payments_api();
	$show_total_amount = $payments_api->payments_enabled();
	$show_total_credits = $payments_api->credit_system_enabled();
	$currency_code = awpcp_get_currency_code();
	$blog_name = awpcp_get_blog_name();

	if ( ! is_null( $transaction ) ) {
		$transaction_totals = $transaction->get_totals();
		$total_amount = $transaction_totals['money'];
		$total_credits = $transaction_totals['credits'];
	} else {
		$total_amount = 0;
		$total_credits = 0;
	}

	if ( get_awpcp_option( 'requireuserregistration' ) ) {
		$include_listing_access_key = false;
		$include_edit_listing_url = true;
	} else {
		$include_listing_access_key = get_awpcp_option( 'include-ad-access-key' );
		$include_edit_listing_url = false;
	}

	$listing_renderer = awpcp_listing_renderer();

	$listing_title = $listing_renderer->get_listing_title( $ad );
	$contact_name = $listing_renderer->get_contact_name( $ad );
	$contact_email = $listing_renderer->get_contact_email( $ad );
	$access_key = $listing_renderer->get_access_key( $ad );

	$params = compact(
		'ad',
		'listing_title',
		'contact_email',
		'access_key',
		'admin_email',
		'transaction',
		'currency_code',
		'show_total_amount',
		'show_total_credits',
		'include_listing_access_key',
		'include_edit_listing_url',
		'total_amount',
		'total_credits',
		'message',
		'blog_name'
	);

	$email = new AWPCP_Email;
	$email->to[] = awpcp_format_recipient_address( $contact_email, $contact_name );
	$email->subject = get_awpcp_option('listingaddedsubject');
	$email->prepare( AWPCP_DIR . '/frontend/templates/email-place-ad-success-user.tpl.php', $params );

	return $email;
}

/**
 * Renders each listing using the layout configured in the plugin
 * settings.
 *
 * @since 3.3.2
 *
 * @param Array $listings An array of AWPCP_Ad objects.
 * @param string $context The context where the listings will be shown: listings, ?.
 * @param Array $options An array of parameters related with $context.
 * @return Array An array of rendered items.
 */
function awpcp_render_listings_items( $listings, $context, $options = array() ) {
	$parity = array( 'displayaditemseven', 'displayaditemsodd' );
	$layout = get_awpcp_option('displayadlayoutcode');

	if ( empty( $layout ) ) {
		$layout = awpcp()->settings->get_option_default_value( 'displayadlayoutcode' );
	}

	$listing_renderer = awpcp_listing_renderer();

	$items    = array();
	$featured = array();
	foreach ( $listings as $i => $listing ) {
		$rendered_listing = awpcp_do_placeholders( $listing, $layout, $context );
		$rendered_listing = str_replace( "\$awpcpdisplayaditems", $parity[$i % 2], $rendered_listing );
		
		if ( 'latest-listings-shortcode' === $context && ! empty( $options['featured_on_top'] ) && $listing_renderer->is_featured( $listing ) ) {
			$featured[] = apply_filters( 'awpcp-render-listing-item', $rendered_listing, $listing, $i + 1 );
			continue;
		}

		$items[] = apply_filters( 'awpcp-render-listing-item', $rendered_listing, $listing, $i + 1 );
	}

	return array_merge( $featured, $items );
}

/**
 * Generates HTML to display login form when user is not registered.
 * @tested
 */
function awpcp_login_form($message=null, $redirect=null) {
	if ( is_null( $redirect ) ) {
		$redirect = awpcp_current_url();
	}

	$login_form = apply_filters( 'awpcp-login-form-implementation', null );

	if ( ! is_object( $login_form ) || ! method_exists( $login_form, 'render' ) ) {
		$login_form = awpcp_default_login_form_implementation();
	}

	return $login_form->render( $redirect, $message );
}

/**
 * @SuppressWarnings(PHPMD)
 */
function awpcp_user_payment_terms_sort($a, $b) {
	$result = strcasecmp($a->type, $b->type);
	if ($result == 0) {
		$result = strcasecmp($a->name, $b->name);
	}
	return $result;
}
