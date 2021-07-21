<?php
/**
 * @package AWPCP\Admin\Pages
 */

/**
 * Constructor function for AWPCP_SettingsAdminPage
 */
function awpcp_settings_admin_page() {
    return new AWPCP_SettingsAdminPage(
        awpcp()->container['SettingsManager'],
        awpcp()->container['Settings'],
        awpcp()->container['Request']
    );
}

/**
 * Admin page that allows administrators to configure the plugin.
 *
 * @SuppressWarnings(PHPMD)
 */
class AWPCP_SettingsAdminPage {

    /**
     * @var object
     */
	private $settings;

    /**
     * @var object
     */
	private $request;

    /**
     * Constructor.
     *
     * @param object $settings_manager  An instance of SettingsManager.
     * @param object $settings          An instance of SettingsAPI.
     * @param object $request           An instance of Request.
     */
	public function __construct( $settings_manager, $settings, $request ) {
        $this->settings_manager = $settings_manager;
        $this->settings         = $settings;
        $this->request          = $request;

		$this->instantiate_auxiliar_pages();
	}

    /**
     * Enqueue page scripts.
     */
	public function enqueue_scripts() {
		wp_enqueue_script( 'awpcp-admin-settings' );
	}

    /**
     * Renders the page.
     */
	public function dispatch() {
        $groups         = $this->settings_manager->get_settings_groups();
        $subgroups      = $this->settings_manager->get_settings_subgroups();
        $current_groups = $this->get_current_groups( $groups, $subgroups );

		unset( $groups['private-settings'] );

		$params = array(
            'groups'           => $groups,
            'subgroups'        => $subgroups,
            'current_group'    => $current_groups['group'],
            'current_subgroup' => $current_groups['subgroup'],
            'settings'         => $this->settings,
            'setting_name'     => $this->settings->setting_name,
            'current_url'      => remove_query_arg( [ 'sg', 'g' ], awpcp_current_url() ),
		);

		$template = AWPCP_DIR . '/templates/admin/settings-admin-page.tpl.php';

		return awpcp_render_template( $template, $params );
	}

    /**
     * @since 4.0.0
     */
    private function get_current_groups( $groups, $subgroups ) {
        $subgroup_id = $this->request->param( 'sg' );

        if ( isset( $subgroups[ $subgroup_id ] ) ) {
            $subgroup = $subgroups[ $subgroup_id ];
            $group    = $this->sort_group_subgroups( $groups[ $subgroup['parent'] ], $subgroups );

            return compact( 'group', 'subgroup' );
        }

        $group_id = $this->request->param( 'g' );

        if ( empty( $groups[ $group_id ]['subgroups'] ) ) {
            $group_id = 'general-settings';
        }

        $group       = $this->sort_group_subgroups( $groups[ $group_id ], $subgroups );
        $subgroup_id = reset( $group['subgroups'] );
        $subgroup    = $subgroups[ $subgroup_id ];

        return compact( 'group', 'subgroup' );
    }

    /**
     * @since 4.0.0
     */
    private function sort_group_subgroups( $group, $subgroups ) {
        $group['subgroups'] = array_intersect( array_keys( $subgroups ), $group['subgroups'] );

        return $group;
    }

    // phpcs:disable

	private function instantiate_auxiliar_pages() {
		$pages = awpcp_classfieds_pages_settings();
		$facebook = new AWPCP_Facebook_Page_Settings();
	}
}

function awpcp_classfieds_pages_settings() {
	return new AWPCP_Classified_Pages_Settings( awpcp_missing_pages_finder() );
}

/**
 * @SuppressWarnings(PHPMD)
 */
class AWPCP_Classified_Pages_Settings {

	private $missing_pages_finder;

	public function __construct( $missing_pages_finder ) {
		$this->missing_pages_finder = $missing_pages_finder;

		add_action('awpcp-admin-settings-page--pages-settings', array($this, 'dispatch'));
	}

	public function dispatch() {
		global $awpcp;

		if ( $this->should_restore_pages() ) {
			$restored_pages = awpcp_pages_creator()->restore_missing_pages();
		} else {
			$restored_pages = array();
		}

		$missing = awpcp_array_filter_recursive( $this->missing_pages_finder->find_broken_page_id_references() );

		ob_start();
			include(AWPCP_DIR . '/admin/templates/admin-panel-settings-pages-settings.tpl.php');
			$content = ob_get_contents();
		ob_end_clean();

		echo $content;
	}

	private function should_restore_pages() {
		$nonce = awpcp_post_param( '_wpnonce' );
		$restore = awpcp_post_param( 'restore-pages', false );

		return $restore && wp_verify_nonce( $nonce, 'awpcp-restore-pages' );
	}
}

class AWPCP_Facebook_Page_Settings {

    /**
     * @var Settings
     */
    private $settings;

	public function __construct() {
        $this->settings = awpcp()->settings;

		add_action( 'current_screen', array( $this, 'maybe_redirect' ) );
		add_action( 'awpcp-admin-settings-page--facebook-settings', array($this, 'dispatch'));
	}

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
	public function maybe_redirect() {
		if ( !isset( $_GET['g'] ) || $_GET['g'] != 'facebook-settings' || $this->get_current_action() != 'obtain_user_token' )
			return;

		if ( isset( $_GET[ 'error_code' ] ) ) {
			return $this->redirect_with_error( $_GET[ 'error_code' ], urlencode( $_GET['error_message'] )  );
		}

		$code = isset( $_GET['code'] ) ? $_GET['code'] : '';

		$fb = AWPCP_Facebook::instance();
		$access_token = $fb->token_from_code( $code );

		if ( ! $access_token ) {
			return $this->redirect_with_error( 1, 'Unkown error trying to exchange code for access token.' );
		}

        $this->settings->update_option( 'facebook-user-access-token', $access_token );

		wp_redirect( admin_url( 'admin.php?page=awpcp-admin-settings&g=facebook-settings' ) );
		die();
	}

	public function get_current_action() {
		if ( isset( $_POST['diagnostics'] ) )
			return 'diagnostics';

		if ( isset( $_REQUEST['obtain_user_token'] ) && $_REQUEST['obtain_user_token'] == 1 )
			return 'obtain_user_token';

		return 'display_settings';
	}

    /**
     * @SuppressWarnings(PHPMD.ExitExpression)
     */
	private function redirect_with_error( $error_code, $error_message ) {
		$params = array( 'code_error' => $error_code, 'error_message' => $error_message );
		$settings_url = admin_url( 'admin.php?page=awpcp-admin-settings&g=facebook-settings' );
		wp_redirect( add_query_arg( urlencode_deep( $params ), $settings_url ) );
		die();
	}

	public function dispatch() {
		$action = $this->get_current_action();

		switch ( $action ) {
			case 'diagnostics':
			case 'display_settings':
			default:
				return $this->display_settings();
				break;
		}
	}

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.UnusedLocalVariable)
     */
	private function display_settings( $errors=array() ) {
        $fb = AWPCP_Facebook::instance();

        $redirect_uri = add_query_arg( 'obtain_user_token', 1, admin_url( '/admin.php?page=awpcp-admin-settings&g=facebook-settings' ) );

		if ( isset( $_GET['code_error'] ) && isset( $_GET['error_message'] )  ) {
            $error_message = __( 'We could not obtain a valid access token from Facebook. The API returned the following error: %s', 'another-wordpress-classifieds-plugin' );
            $error_message = sprintf( $error_message, wp_unslash( urldecode_deep( $_GET['error_message'] ) ) );

            $errors[] = esc_html( $error_message );
		} else if ( isset( $_GET['code_error'] ) ) {
			$errors[] = esc_html( __( 'We could not obtain a valid access token from Facebook. Please try again.', 'another-wordpress-classifieds-plugin' ) );
		}

		if ( $this->get_current_action() == 'diagnostics' ) {
			$diagnostics_errors = array();
			$fb->validate_config( $diagnostics_errors );

			$error_msg  = '';
			$error_msg .= '<strong>' . __( 'Facebook Config Diagnostics', 'another-wordpress-classifieds-plugin' ) . '</strong><br />';

			if ( $diagnostics_errors ) {
				foreach ( $diagnostics_errors as &$e ) {
					$error_msg .= '&#149; ' . $e . '<br />';
				}
			} else {
				$error_msg .= __( 'Everything looks OK.', 'another-wordpress-classifieds-plugin' );
			}

			$errors[] = $error_msg;
		}

		ob_start();
			include(AWPCP_DIR . '/admin/templates/admin-panel-settings-facebook-settings.tpl.php');
			$content = ob_get_contents();
		ob_end_clean();

		echo $content;
	}
}
