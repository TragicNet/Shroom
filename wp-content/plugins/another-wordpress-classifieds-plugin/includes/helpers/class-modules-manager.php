<?php
/**
 * @package AWPCP
 */

// phpcs:disable

/**
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class AWPCP_ModulesManager {

    private $plugin;
    private $upgrade_tasks;
    private $licenses_manager;
    private $modules_updater;
    private $licenses_settings;
    private $request;

    private $modules = array();
    private $notices = array();

    public function __construct( $plugin, $upgrade_tasks, $licenses_manager, $modules_updater, $licenses_settings, $request ) {
        $this->plugin = $plugin;
        $this->upgrade_tasks = $upgrade_tasks;
        $this->licenses_manager = $licenses_manager;
        $this->modules_updater = $modules_updater;
        $this->licenses_settings = $licenses_settings;
        $this->request = $request;
    }

    public function load_modules( $container ) {
        do_action( 'awpcp-load-modules', $this, $container );
    }

    public function load( $module ) {
        $this->modules[ $module->slug ] = $module;

        try {
            $this->load_module( $module );
        } catch ( AWPCP_Exception $e ) {
            // pass
        }
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
     */
    private function load_module( $module ) {
        $module->load_textdomain();

        $this->handle_module_updates( $module );
        $this->verify_version_compatibility( $module );

        $is_premium_module = $this->is_premium_module( $module );

        if ( $is_premium_module && is_admin() ) {
            $this->licenses_settings->add_license_setting( $module->name, $module->slug );
        }

        if ( $is_premium_module ) {
            $this->verify_license_status( $module );
        }

        $module->setup( $this->plugin );

        if ( ! $this->upgrade_tasks->has_pending_tasks( array( 'context' => $module->slug ) ) ) {
            add_action( 'awpcp-configure-routes', array( $module, 'configure_routes' ) );

            // run after load_dependencies() in new modules and init() in old modules
            add_action( 'init', array( $module, 'setup_module' ), 4 );
        } else {
            $this->notices['module-requires-manual-upgrade'][] = $module;
        }
    }

    private function verify_version_compatibility( $module ) {
        $modules_information = $this->plugin->get_premium_modules_information();

        if ( ! isset( $modules_information[ $module->slug ] ) ) {
            $this->notices['modules-not-registered'][] = $module;
            throw new AWPCP_Exception( 'Module is not registered.' );
        }

        $module_information = $modules_information[ $module->slug ];

        if ( isset( $module_information['removed'] ) && is_callable( $module_information['removed'] ) ) {
            $this->notices['modules-removed'][] = $module;
            throw new AWPCP_Exception( 'Module is no longer supported.' );
        }

        if ( version_compare( $this->plugin->version, $module->required_awpcp_version, '<' ) ) {
            $this->notices['modules-that-require-different-awpcp-version'][] = $module;
            throw new AWPCP_Exception( 'Required AWPCP version not installed.' );
        }

        if ( ! $this->plugin->is_compatible_with( $module->slug, $module->version ) ) {
            $this->notices['modules-not-compatible'][] = $module;
            throw new AWPCP_Exception( 'Module not compatible with installed AWPCP version.' );
        }
    }

    protected function is_premium_module( $module ) {
        $free_modules = array( 'xml-sitemap' );

        if ( in_array( $module->slug, $free_modules ) ) {
            return false;
        }

        $hidden_modules = array( 'videos', 'google-checkout' );

        if ( in_array( $module->slug, $hidden_modules ) ) {
            return false;
        }

        return true;
    }

    private function verify_license_status( $module ) {
        if ( $this->licenses_manager->is_license_inactive( $module->name, $module->slug ) ) {
            $module->notices[] = 'inactive-license-notice';
            $this->notices['modules-with-inactive-license'][] = $module;
            throw new AWPCP_Exception( "Module's license is inactive." );
        } else if ( ! $this->module_has_an_accepted_license( $module ) ) {
            $this->notices['modules-with-invalid-license'][] = $module;
            throw new AWPCP_Exception( 'Module has not valid license.' );
        }

        if ( $this->licenses_manager->is_license_expired( $module->name, $module->slug ) ) {
            $this->notices['modules-with-expired-license'][] = $module;
        }
    }

    private function module_has_an_accepted_license( $module ) {
        if ( $this->licenses_manager->is_license_valid( $module->name, $module->slug ) ) {
            return true;
        }

        if ( $this->licenses_manager->is_license_expired( $module->name, $module->slug ) ) {
            return true;
        }

        return false;
    }

    private function handle_module_updates( $module ) {
        // TODO: maybe we don't need to pass the license.
        // Maybe we can have the Modules Updater fetch it when necessary.
        $license = $this->licenses_manager->get_module_license( $module->slug );
        $this->modules_updater->watch( $module, $license );
    }

    public function show_admin_notices() {
        if ( ! awpcp_current_user_is_admin() ) {
            return;
        }

        foreach ( $this->notices as $notice => $modules ) {
            $this->show_admin_notice( $notice, $modules );
        }
    }

    private function show_admin_notice( $notice, $modules ) {
        switch ( $notice ) {
            case 'modules-not-registered':
                echo $this->show_modules_no_registered_notice( $modules );
                break;
            case 'modules-that-require-different-awpcp-version':
                echo $this->show_required_awpcp_version_notice( $modules );
                break;
            case 'modules-not-compatible':
                echo $this->show_modules_not_compatible_notice( $modules );
                break;
            case 'modules-removed':
                echo $this->show_modules_removed_notice( $modules );
                break;
            case 'modules-with-inactive-license':
                echo $this->show_inactive_licenses_notice( $modules );
                break;
            case 'modules-with-invalid-license':
                echo $this->show_invalid_licenses_notice( $modules );
                break;
            case 'modules-with-expired-license':
                echo $this->show_expired_licenses_notice( $modules );
                break;
            case 'module-requires-manual-upgrade':
                echo $this->show_module_requires_manual_upgrade_notice( $modules );
                break;
        }
    }

    private function show_modules_no_registered_notice( $modules ) {
        $message = _n( 'Yikes, there has been a mistake. It looks like you have an outdated version of AWPCP <module-name>, or you need a newer version of AWPCP to use that module. Please contact customer support and ask for an update. Please also include a reference to this error in your message.', 'Yikes, there has been a mistake. It looks like you have an outdated version of AWPCP <modules-names>, or you need a newer version of AWPCP to use those modules. Please contact customer support and ask for an update. Please also include a reference to this error in your message.', count( $modules ), 'another-wordpress-classifieds-plugin' );
        $message = $this->replace_modules_names_in_message( $message, $modules );

        return awpcp_print_error( $message );
    }

    private function replace_modules_names_in_message( $message, $modules ) {
        $message = str_replace( '<module-name>', '<name>', $message );
        $message = str_replace( '<modules-names>', '<names>', $message );

        return awpcp_replace_names_in_message(
            $message, $this->get_modules_names( $modules )
        );
    }

    private function get_modules_names( $modules ) {
        foreach ( $modules as $module ) {
            $modules_names[] = $module->name;
        }

        return $modules_names;
    }

    private function show_required_awpcp_version_notice( $modules ) {
        foreach ( $modules as $module ) {
            echo $module->required_awpcp_version_notice();
        }
    }

    private function show_modules_not_compatible_notice( $modules ) {
        $modules_information = $this->plugin->get_premium_modules_information();

        $message  = _n( 'The version of AWPCP {modules_names} is not compatible with version {awpcp_version}.', 'The versions of AWPCP {modules_names} are not compatible with version {awpcp_version}.', count( $modules ), 'another-wordpress-classifieds-plugin' );
        $message .= '<br><br>';
        $message .= __( 'Please get AWPCP {required_modules_versions} or newer!', 'another-wordpress-classifieds-plugin' );
        $strings  = [];

        foreach ( $modules as $module ) {
            $strings['modules'][]           = $module->name;
            $strings['required_versions'][] = "{$module->name} ({$modules_information[ $module->slug ]['required']})";
        }

        $message = str_replace( '{modules_names}', awpcp_string_with_names( $strings['modules'] ), $message );
        $message = str_replace( '{awpcp_version}', '<strong>' . $this->plugin->version . '</strong>', $message );
        $message = str_replace( '{required_modules_versions}', awpcp_string_with_names( $strings['required_versions'] ), $message );

        return awpcp_print_error( $message );
    }

    /**
     * @since 4.0.0
     */
    private function show_modules_removed_notice( $modules ) {
        $modules_information = $this->plugin->get_premium_modules_information();
        $notices             = '';

        foreach ( $modules as $module ) {
            $content  = call_user_func( $modules_information[ $module->slug ]['removed'] );
            $notices .= awpcp_activation_failed_notice( $content );
        }

        return $notices;
    }

    private function show_inactive_licenses_notice( $modules ) {
        $message = _n( 'The license for AWPCP <module-name> is inactive. All features will remain disabled until you activate the license. Please go to the <licenses-settings-link>License Settings</a> section to activate it.', 'The licenses for AWPCP <modules-names> are inactive. The features for those modules will remain disabled until you activate their licenses. Please go to the <licenses-settings-link>License Settings</a> section to activate them.', count( $modules ), 'another-wordpress-classifieds-plugin' );
        return $this->show_license_notice( $message, $modules );
    }

    private function show_license_notice( $message, $modules ) {
        $link = sprintf( '<a href="%s">', awpcp_get_admin_settings_url( 'licenses-settings' ) );

        $message = $this->replace_modules_names_in_message( $message, $modules );
        $message = str_replace( '<licenses-settings-link>', $link, $message );

        return awpcp_print_error( $message );
    }

    private function show_invalid_licenses_notice( $modules ) {
        $message = _n( 'The AWPCP <module-name> requires a license to be used. All features will remain disabled until a valid license is entered. Please go to the <licenses-settings-link>Licenses Settings</a> section to enter or update your license.', 'The AWPCP <modules-names> require a license to be used. The features on each of those modules will remain disabled until a valid license is entered. Please go to the <licenses-settings-link>Licenses Settings</a> section to enter or update your license.', count( $modules ), 'another-wordpress-classifieds-plugin' );
        return $this->show_license_notice( $message, $modules );
    }

    private function show_expired_licenses_notice( $modules ) {
        $message = _n( 'The license for AWPCP <module-name> expired. The module will continue to work but you will not receive automatic updates when a new version is available.', 'The license for AWPCP <modules-names> expired. Those modules will continue to work but you will not receive automatic updates when a new version is available.', count( $modules ), 'another-wordpress-classifieds-plugin' );
        return $this->show_license_notice( $message, $modules );
    }

    private function show_module_requires_manual_upgrade_notice( $modules ) {
        if ( $this->request->param('page') == 'awpcp-admin-upgrade' ) {
            return;
        }

        $upgrade_url = add_query_arg( 'context', 'premium-modules', awpcp_get_admin_upgrade_url() );

        $message = _n( 'The AWPCP <module-name> is currently disabled because it requires you to perform a manual upgrade before continuing. Please <upgrade-link>go to the Classifieds admin section to Upgrade</a>.', 'The AWPCP <modules-names> are currently disabled because they require you to perform a manual upgrade before continuing. Please <upgrade-link>go to the Classifieds admin section to Upgrade</a>.', count( $modules ), 'another-wordpress-classifieds-plugin' );
        $message = $this->replace_modules_names_in_message( $message, $modules );
        $message = str_replace( '<upgrade-link>', sprintf( '<a href="%s">', $upgrade_url ), $message );

        return awpcp_print_error( $message );
    }

    public function get_modules() {
        return $this->modules;
    }

    public function get_module( $module_slug ) {
        if ( ! isset( $this->modules[ $module_slug ] ) ) {
            throw new AWPCP_Exception( __( 'The specified module does not exists!.', 'another-wordpress-classifieds-plugin' ) );
        }

        return $this->modules[ $module_slug ];
    }
}
