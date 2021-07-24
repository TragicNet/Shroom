<?php

function awpcp_listing_is_about_to_expire_notification() {
    return new AWPCP_ListingIsAboutToExpireNotification(
        awpcp_listing_renderer(),
        awpcp()->settings
    );
}

class AWPCP_ListingIsAboutToExpireNotification {

    private $listing_renderer;
    private $settings;

    public function __construct( $listing_renderer, $settings ) {
        $this->listing_renderer = $listing_renderer;
        $this->settings = $settings;
    }

    public function render_subject( $listing ) {
        $subject_template = get_awpcp_option( 'renew-ad-email-subject' );
        $subject_template = str_replace( '%d', '%s', $subject_template );

        return sprintf( $subject_template, $this->days_before_listing_expires( $listing ) );
    }

    private function days_before_listing_expires( $listing ) {
        $end_date = strtotime( $this->listing_renderer->get_end_date( $listing ) );
        $extended_end_date = awpcp_extend_date_to_end_of_the_day( $end_date );

        if ( $this->listing_renderer->has_expired( $listing ) ) {
            $time_left = 0;
        } else {
            $time_left = $extended_end_date - current_time( 'timestamp' );
        }

        $days_left = $time_left / (24 * 60 * 60);

        if ( $days_left == 0 || $days_left >= 1 ) {
            return floor( $days_left );
        } else {
            return __( 'less than 1', 'another-wordpress-classifieds-plugin' );
        }
    }

    public function render_body( $listing ) {
        $introduction = $this->settings->get_option( 'renew-ad-email-body' );
        if ( strpos( $introduction, '%d' ) !== false ) {
            $days_before_listing_expires = $this->days_before_listing_expires( $listing );
            $introduction = sprintf( str_replace( '%d', '%s', $introduction ), $days_before_listing_expires );
        }

        $listing_title = $this->listing_renderer->get_listing_title( $listing );
        $start_date = $this->listing_renderer->get_start_date( $listing );
        $end_date = $this->listing_renderer->get_end_date( $listing );

        $renew_url = urldecode( awpcp_get_renew_ad_url( $listing->ID ) );

        ob_start();
        include( AWPCP_DIR . '/templates/email/listing-is-about-to-expire-notification.plain.tpl.php' );
        $content = ob_get_contents();
        ob_end_clean();

        return $content;
    }
}
