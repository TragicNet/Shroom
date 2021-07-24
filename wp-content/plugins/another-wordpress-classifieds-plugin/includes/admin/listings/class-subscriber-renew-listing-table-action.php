<?php
/**
 * Renew Listing Table Action for Subscribers.
 *
 * @package AWPCP\Admin\Listings
 */

/**
 * @since 4.0.0
 */
class AWPCP_SubscriberRenewListingTableAction implements
    AWPCP_ListTableActionInterface,
    AWPCP_ConditionalListTableActionInterface {

    /**
     * Implements some of the methods from the AWPCP_ListTableActionInterface.
     */
    use AWPCP_RenewListingTableAction;

    /**
     * @var object
     */
    private $listing_renderer;

    /**
     * @var object
     */
    private $roles;

    /**
     * @since 4.0.0
     *
     * @param object $listing_renderer      An instance of Listing Renderer.
     * @param object $roles                 An instance of Roles and Capabilities.
     */
    public function __construct( $listing_renderer, $roles ) {
        $this->listing_renderer = $listing_renderer;
        $this->roles            = $roles;
    }
    /**
     * @since 4.0.0
     */
    public function is_needed() {
        return ! $this->roles->current_user_is_moderator();
    }

    /**
     * @since 4.0.0
     */
    public function should_show_as_bulk_action() {
        return false;
    }

    /**
     * @since 4.0.0
     *
     * @param object $post          An instance of WP_Post.
     * @param string $current_url   The URL of the current page.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) $current_url
     */
    public function get_url( $post, $current_url ) {
        return awpcp_get_renew_ad_url( $post->ID );
    }

    /**
     * @since 4.0.0
     *
     * @param object $post  An instance of WP_Post.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function process_item( $post ) {
    }

    /**
     * @since 4.0.0
     *
     * @param array $result_codes   An array of result codes from this action.
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function get_messages( $result_codes ) {
        return [];
    }
}
