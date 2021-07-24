<?php
/**
 * @package AWPCP\Admin
 */

/**
 * Constructor function.
 */
function awpcp_user_panel() {
    $container = awpcp()->container;

    return new AWPCP_User_Panel(
        $container['listing_post_type'],
        awpcp_upgrade_tasks_manager(),
        $container['Settings']
    );
}

/**
 * Register admin menu items for subscribers.
 */
class AWPCP_User_Panel {

    /**
     * @var string
     */
    private $listing_post_type;

    /**
     * @var UpgradeTasksManager
     */
    private $upgrade_tasks;

    /**
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     */
	public function __construct( $listing_post_type, $upgrade_tasks, $settings ) {
        $this->listing_post_type = $listing_post_type;
        $this->upgrade_tasks     = $upgrade_tasks;
        $this->settings          = $settings;

        $this->account = awpcp_account_balance_page();
	}

    /**
     * Handler for the awpcp-configure-routes action.
     */
    public function configure_routes( $router ) {
        $params = [
            'context'  => 'plugin',
            'blocking' => true,
        ];

        if ( $this->upgrade_tasks->has_pending_tasks( $params ) ) {
            return;
        }

        if ( awpcp_payments_api()->credit_system_enabled() && ! awpcp_current_user_is_admin() ) {
            $this->add_users_page( $router );
        }
    }

    /**
     * Registers the page used by subscribers to see their credit account balance.
     */
    private function add_users_page( $router ) {
        $router->add_admin_users_page(
            __( 'Account Balance', 'another-wordpress-classifieds-plugin' ),
            __( 'Account Balance', 'another-wordpress-classifieds-plugin' ),
            'awpcp-user-account',
            'awpcp_account_balance_page',
            awpcp_user_capability()
        );
    }
}
