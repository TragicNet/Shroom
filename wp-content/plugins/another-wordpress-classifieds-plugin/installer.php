<?php
/**
 * Installation and Upgrade functions
 *
 * @package AWPCP
 */

// phpcs:disable PEAR.Functions.FunctionCallSignature.SpaceBeforeCloseBracket
// phpcs:disable PEAR.Functions.FunctionCallSignature.SpaceAfterOpenBracket
// phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning
// phpcs:disable WordPress.WhiteSpace.OperatorSpacing.NoSpaceBefore
// phpcs:disable WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceAfterOpenParenthesis
// phpcs:disable WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeCloseParenthesis
// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.NoSpaceBeforeArrayCloser
// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.NoSpaceAfterArrayOpener
// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
// phpcs:disable WordPress.WhiteSpace.ControlStructureSpacing.ExtraSpaceBeforeCloseParenthesis
// phpcs:disable WordPress.Arrays.MultipleStatementAlignment.DoubleArrowNotAligned
// phpcs:disable PEAR.Files.IncludingFile.BracketsNotRequired
// phpcs:disable Generic.Formatting.MultipleStatementAlignment.IncorrectWarning
// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired
// phpcs:disable PSR2.ControlStructures.ElseIfDeclaration.NotAllowed
// phpcs:disable Squiz.Functions.FunctionDeclarationArgumentSpacing.SpacingAfterOpen
// phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.NoSpaceBeforeEquals
// phpcs:disable WordPress.WhiteSpace.OperatorSpacing.NoSpaceAfter
// phpcs:disable WordPress.PHP.StrictComparisons.LooseComparison
// phpcs:disable WordPress.Arrays.ArrayIndentation.CloseBraceNotAligned
// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.CloseBraceNewLine
// phpcs:disalbe Squiz.Commenting.InlineComment.SpacingAfter
// phpcs:disable Generic.Commenting.DocComment.Empty
// phpcs:disable WordPress.Arrays.CommaAfterArrayItem.NoComma
// phpcs:disable PEAR.Functions.FunctionCallSignature.CloseBracketLine
// phpcs:disable Generic.Functions.FunctionCallArgumentSpacing.NoSpaceAfterEquals
// phpcs:disable Squiz.Commenting.FunctionComment.WrongStyle
// phpcs:disable Squiz.Commenting.InlineComment.SpacingAfter
// phpcs:disable Squiz.Commenting.InlineComment.SpacingBefore
// phpcs:disable PEAR.Functions.FunctionCallSignature.ContentAfterOpenBracket
// phpcs:disable PEAR.Functions.FunctionCallSignature.MultipleArguments
// phpcs:disable PEAR.Functions.FunctionCallSignature.Indent

global $wpdb;

define('AWPCP_TABLE_ADFEES', $wpdb->prefix . "awpcp_adfees");
define('AWPCP_TABLE_ADS', $wpdb->prefix . "awpcp_ads");
define('AWPCP_TABLE_AD_REGIONS', $wpdb->prefix . "awpcp_ad_regions");
define('AWPCP_TABLE_AD_META', $wpdb->prefix . 'awpcp_admeta');
define('AWPCP_TABLE_MEDIA', $wpdb->prefix . "awpcp_media");
define('AWPCP_TABLE_CATEGORIES', $wpdb->prefix . "awpcp_categories");
define('AWPCP_TABLE_PAYMENTS', $wpdb->prefix . 'awpcp_payments');
define('AWPCP_TABLE_CREDIT_PLANS', $wpdb->prefix . 'awpcp_credit_plans');
define('AWPCP_TABLE_PAGES', $wpdb->prefix . "awpcp_pages");
define('AWPCP_TABLE_TASKS', $wpdb->prefix . "awpcp_tasks");

// TODO: Remove references to AWPCP_TABLE_ADPHOTOS constant when the routines
// to upgrade to 2.x are removed from the codebase.
define( 'AWPCP_TABLE_ADPHOTOS', $wpdb->prefix . 'awpcp_adphotos' );

// TODO: remove these constants after another major release (Added in 3.5.3)
define( 'AWPCP_TABLE_PAGENAME', $wpdb->prefix . 'awpcp_pagename' );


function awpcp_installer() {
    static $instance = null;

    if ( is_null( $instance ) ) {
        $instance = new AWPCP_Installer(
            awpcp_upgrade_tasks_manager(),
            awpcp_database_tables(),
            awpcp_database_helper(),
            awpcp_database_column_creator(),
            awpcp_settings_api(),
            $GLOBALS['wpdb']
        );
    }

    return $instance;
}

/**
 * @SuppressWarnings(PHPMD)
 */
class AWPCP_Installer {

    private static $instance = null;

    private $upgrade_tasks;
    private $plugin_tables;
    private $database_helper;
    private $columns;
    private $settings;
    private $db;

    public function __construct( $upgrade_tasks, $plugin_tables, $database_helper, $columns, $settings, $db ) {
        $this->upgrade_tasks = $upgrade_tasks;
        $this->plugin_tables = $plugin_tables;
        $this->database_helper = $database_helper;
        $this->columns = $columns;
        $this->settings = $settings;
        $this->db = $db;
    }

    public function activate() {
        $this->install_or_upgrade();

        update_option( 'awpcp-activated', true );
    }

    public function install_or_upgrade() {
        global $awpcp_db_version;

        $installed_version = get_option( 'awpcp_db_version' );

        if ( ! $this->is_version_number( $awpcp_db_version ) ) {
            // Something is wrong. The version extracted from the plugin's headers
            // is not a valid version number.

            // We create a log entry for debug purposes, but abort the operation.
            $this->log_upgrade( $installed_version, $awpcp_db_version );

            return;
        }

        if ( $installed_version !== false && ! $this->is_version_number( $installed_version ) ) {
            // Something is wrong. The installed version should always be false
            // or a valid version number.

            // We create a log entry for debug purposes, but abort the operation.
            $this->log_upgrade( $installed_version, $awpcp_db_version );

            return;
        }

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        // if table exists, this is an upgrade
        if ( $installed_version !== false && awpcp_table_exists( AWPCP_TABLE_PAYMENTS ) ) {
            $this->upgrade( $installed_version, $awpcp_db_version );
        } else {
            $this->install( $awpcp_db_version );
        }

        update_option( 'awpcp-installed-or-upgraded', true );
    }

    /**
     * @since 3.8.4
     */
    private function is_version_number( $version_string ) {
        return preg_match( '/^\d[\d.]*/', $version_string );
    }

    private function log_upgrade( $oldversion, $newversion ) {
        $upgrade_log = get_option( 'awpcp-upgrade-log', array() );

        $upgrade_log[] = array(
            'oldversion'    => $oldversion,
            'newversion'    => $newversion,
            'PHP_SELF'      => isset( $_SERVER['PHP_SELF'] ) ? wp_strip_all_tags( wp_unslash( $_SERVER['PHP_SELF'] ) ) : '',
            'DOCUMENT_ROOT' => isset( $_SERVER['DOCUMENT_ROOT'] ) ? wp_strip_all_tags( wp_unslash( $_SERVER['DOCUMENT_ROOT'] ) ) : '',
            'SERVER_NAME'   => isset( $_SERVER['SERVER_NAME'] ) ? wp_strip_all_tags( wp_unslash( $_SERVER['SERVER_NAME'] ) ) : '',
            'REQUEST_URI'   => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
            'QUERY_STRING'  => isset( $_SERVER['QUERY_STRING'] ) ? esc_url_raw( wp_unslash( $_SERVER['QUERY_STRING'] ) ) : '',
            'date'          => current_time( 'mysql' ),
        );

        // Keep latest 100 entries. This should prevent filling the database with
        // log entries if `awpcp_db_version` is set to something invalid permanently.
        $upgrade_log = array_slice( $upgrade_log, 0, 100 );

        update_option( 'awpcp-upgrade-log', $upgrade_log );
    }

    /**
     * Creates AWPCP tables.
     */
    public function install( $version ) {
        global $awpcp, $wpdb;

        dbDelta( $this->plugin_tables->get_listing_meta_table_definition() );
        dbDelta( $this->plugin_tables->get_listing_regions_table_definition() );
        dbDelta( $this->plugin_tables->get_fees_table_definition() );
        dbDelta( $this->plugin_tables->get_payments_table_definition() );
        dbDelta( $this->plugin_tables->get_credit_plans_table_definition() );
        dbDelta( $this->plugin_tables->get_tasks_table_definition() );

        // insert default Fee
        $fee = $wpdb->get_results( 'SELECT * FROM ' . AWPCP_TABLE_ADFEES . ' WHERE adterm_id = 1' );
        if ( empty( $fee ) ) {
            $data = array(
                'adterm_id' => 1,
                'adterm_name' => __( '30 Day Listing', 'another-wordpress-classifieds-plugin' ),
                'amount' => 9.99,
                'recurring' => 1,
                'rec_period' => 30,
                'rec_increment' => 'D',
                'buys' => 0,
                'imagesallowed' => 6
            );

            $wpdb->insert(AWPCP_TABLE_ADFEES, $data);
        }

        $result = update_option( 'awpcp_db_version', $version );

        $awpcp->settings->update_option('show-quick-start-guide-notice', true, true);
        $awpcp->settings->update_option( 'show-drip-autoresponder', true, true );

        update_option( 'awpcp-installed', true );
        do_action('awpcp_install');

        return $result;
    }

    // TODO: remove settings table after another major release
    // TODO: remove pages table after another major release (Added in 3.5.3)
    public function upgrade($oldversion, $newversion) {
        foreach ( $this->get_upgrade_routines() as $version => $routines ) {
            if ( version_compare( $oldversion, $version ) >= 0 ) {
                continue;
            }

            foreach ( (array) $routines as $routine ) {
                if ( method_exists( $this, $routine ) ) {
                    $this->{$routine}( $oldversion );
                }
            }
        }

        do_action( 'awpcp_upgrade', $oldversion, $newversion );

        $this->log_upgrade( $oldversion, $newversion );

        return update_option( "awpcp_db_version", $newversion );
    }

    /**
     * TODO: Update upgrade system to ensure that tasks are executed in the
     * order they are defined here.
     *
     * In 4.0.0beta10 routines were still executed in the order they are registered
     * in Manual Upgrade Tasks.
     */
    private function get_upgrade_routines() {
        // You have to use at least major.minor.patch version numbers.
        return array(
            '2.0.0' => 'upgrade_to_2_0_0',
            '2.0.1' => 'upgrade_to_2_0_1',
            '2.0.5' => 'upgrade_to_2_0_5',
            '2.0.6' => 'upgrade_to_2_0_6',
            '2.0.7' => 'upgrade_to_2_0_7',
            '2.1.3' => 'upgrade_to_2_1_3',
            '2.2.1' => 'upgrade_to_2_2_1',
            '3.0.0-beta23' => 'upgrade_to_3_0_0',
            '3.0.2' => 'upgrade_to_3_0_2',
            '3.2.2' => 'upgrade_to_3_2_2',
            '3.3.2' => 'upgrade_to_3_3_2',
            '3.3.3' => 'upgrade_to_3_3_3',
            '3.4.0' => 'upgrade_to_3_4',
            '3.5.3' => 'upgrade_to_3_5_3',
            '3.6.4' => array(
                'create_tasks_table',
                'create_metadata_column_in_media_table',
                'create_regions_column_in_fees_table',
                'create_description_column_in_fees_table',
                'try_to_convert_tables_to_utf8mb4',
                'allow_null_values_in_user_id_column_in_payments_table',
            ),
            '3.6.4.1' => array(
                'create_tasks_table',
                'create_metadata_column_in_media_table',
                'create_regions_column_in_fees_table',
                'create_description_column_in_fees_table',
                'try_to_convert_tables_to_utf8mb4',
                'allow_null_values_in_user_id_column_in_payments_table',
            ),
            '3.7.1' => array(
                'create_phone_number_digits_column',
                'enable_upgrade_task_to_store_phone_number_digits',
            ),
            '3.7.2' => array(
                'set_flag_to_store_browse_categories_page_information',
                'set_flag_to_maybe_fix_browse_categories_page_information',
            ),
            '3.7.4' => array(
                'set_flag_to_show_missing_paypal_merchant_id_setting_notice',
            ),
            '3.8.5' => array(
                'remove_fulltext_index_from_listings_table',
                'convert_tables_to_innodb',
                'create_listings_table_if_missing',
            ),
            '3.8.6' => array(
                'migrate_facebook_integration_settings',
            ),
            '4.0.0beta1' => array(
                'create_old_listing_id_column_in_listing_regions_table',
                'migrate_wordpress_page_settings',
                'migrate_reply_to_ad_email_template',
                'migrate_verify_email_message_email_template',
                'keep_legacy_url_structure',
                'remove_old_capabilities',
                'enable_upgrade_routine_to_migrate_listing_categories',
                'enable_upgrade_routine_to_migrate_listings',
            ),
            '4.0.0beta2' => array(
                'enable_routine_to_fix_id_collision_for_listing_categories',
            ),
            '4.0.0beta4' => [
                'enable_routine_to_store_categories_order_as_term_meta',
            ],
            '4.0.0beta5' => [
                'rename_translation_files_using_outdated_textdomain',
            ],
            '4.0.0beta6' => [
                'enable_routine_to_fix_id_collision_for_listings',
            ],
            '4.0.0beta8' => [
                'enable_routine_to_force_post_id',
            ],
            '4.0.0beta13' => [
                'fix_old_listing_id_metadata',
                'maybe_enable_routine_to_update_categories_term_count',
                'maybe_enable_upgrade_routines_to_migrate_media',
            ],
            '4.0.0' => [
                'enable_routine_to_add_missing_is_paid_meta',
                'enable_routine_to_add_missing_views_meta',
                'delete_settings_table',
            ],
            '4.0.2' => [
                'remove_invalid_admin_editor_metadata',
            ],
            '4.0.5' => [
                'enable_routine_to_update_most_recent_date',
            ],
            '4.0.6' => [
                'enable_routine_to_add_default_awpcp_order',
            ],
            '4.0.7' => [
                'enable_routine_to_add_awpcp_contact_phone_number_digits',
            ],
            '4.0.14' => [
                'increase_adfee_table_amount_field_max_value',
            ],
        );
    }

    private function upgrade_to_2_0_0($version) {
        global $awpcp;
        // Change Expired Ad subject line setting
        if (version_compare($version, '1.9.9.4 beta') <= 0) {
            $awpcp->settings->update_option('adexpiredsubjectline',
                'Your classifieds listing at %s has expired', $force=true);
        }
    }

    private function upgrade_to_2_0_1($version) {
        global $wpdb;

        // update CHARSET and COLLATE values for standard AWPCP tables and columns
        $tables = $wpdb->get_col("SHOW TABLES LIKE '%_awpcp_%'");
        awpcp_fix_table_charset_and_collate($tables);
    }

    private function upgrade_to_2_0_5($version) {
        global $wpdb, $awpcp;

        $translations = array(
            'userpagename' => 'main-page-name',
            'showadspagename' => 'show-ads-page-name',
            'placeadpagename' => 'place-ad-page-name',
            'editadpagename' => 'edit-ad-page-name',
            'page-name-renew-ad' => 'renew-ad-page-name',
            'replytoadpagename' => 'reply-to-ad-page-name',
            'browseadspagename' => 'browse-ads-page-name',
            'searchadspagename' => 'search-ads-page-name',
            'browsecatspagename' => 'browse-categories-page-name',
            'categoriesviewpagename' => 'view-categories-page-name',
            'paymentthankyoupagename' => 'payment-thankyou-page-name',
            'paymentcancelpagename' => 'payment-cancel-page-name');

        // Users who upgraded from 1.8.9.4 to 2.0.4 have an installation
        // with no AWPCP pages. The pages exist, but are not recognized
        // by the plugin.
        foreach ($translations as $old => $new) {
            $page_id = awpcp_get_page_id_by_ref( $new );

            if ( $page_id > 0 ) {
                continue;
            }

            // Let's try to find the pages using the old AND new names
            foreach (array($old, $new) as $option) {
                // The setting doesn't exist. Nothing to do.
                $name = $awpcp->settings->get_option($option, null);
                if ($name == null) {
                    continue;
                }

                $sanitized = sanitize_title($name);
                $sql = "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type = 'page'";

                $id = intval($wpdb->get_var($wpdb->prepare($sql, $sanitized)));
                $id = $id > 0 ? $id : -1;

                awpcp_update_plugin_page_id( $new, $id );

                if ($id > 0) {
                    $awpcp->settings->update_option($new, $name, true);
                    break;
                }
            }
        }

        // Since pages automatic creation is not enabled, we need to create the
        // Renew Ad page manually.
        $plugin_pages = awpcp_pages();

        awpcp_create_subpage(
            'renew-ad-page-name',
            $plugin_pages['renew-ad-page-name'][0],
            '[AWPCP-RENEW-AD]'
        );
    }

    private function upgrade_to_2_0_6($version) {
        global $awpcp;

        // force disable recurring payments
        $awpcp->settings->update_option('paypalpaymentsrecurring', 0, true);
        $awpcp->settings->update_option('twocheckoutpaymentsrecurring', 0, true);
    }

    private function upgrade_to_2_0_7($version) {
        global $wpdb;
        global $awpcp;

        // change Ad's title CSS class to avoid problems with Ad Blocker extensions
        $value = $awpcp->settings->get_option('awpcpshowtheadlayout');
        $value = preg_replace('/<div class="adtitle">/', '<div class="awpcp-title">', $value);
        $awpcp->settings->update_option('awpcpshowtheadlayout', $value);

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADPHOTOS, 'is_primary' ) ) {
            $wpdb->query("ALTER TABLE " . AWPCP_TABLE_ADPHOTOS . "  ADD `is_primary` TINYINT(1) NOT NULL DEFAULT 0");
        }

        // add character limit to Fee plans
        if ( ! awpcp_column_exists( AWPCP_TABLE_ADFEES, 'characters_allowed' ) ) {
            $wpdb->query("ALTER TABLE " . AWPCP_TABLE_ADFEES . "  ADD `characters_allowed` INT(1) NOT NULL DEFAULT 0");
        }

        $fees = awpcp_get_fees();
        $characters_allowed = get_awpcp_option('maxcharactersallowed', 0);
        foreach ($fees as $fee) {
            $sql = 'UPDATE ' . AWPCP_TABLE_ADFEES . ' SET characters_allowed = %d WHERE adterm_id = %d';
            $wpdb->query($wpdb->prepare($sql, $characters_allowed, $fee->adterm_id));
        }
    }

    private function upgrade_to_2_1_3($version) {
        global $wpdb;

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADS, 'renewed_date' ) ) {
            $wpdb->query("ALTER TABLE " . AWPCP_TABLE_ADS . "  ADD `renewed_date` DATETIME");
        }
    }

    private function upgrade_to_2_2_1($version) {
        global $wpdb;

        // Upgrade posterip for IPv6 address space
        if ( awpcp_column_exists( AWPCP_TABLE_ADS, 'posterip' ) ) {
            $sql = $this->database_helper->replace_charset_and_collate( "ALTER TABLE " . AWPCP_TABLE_ADS . "  MODIFY `posterip` VARCHAR(50) CHARACTER SET <charset> COLLATE <collate> NOT NULL DEFAULT ''" );
            $wpdb->query( $sql );
        }
    }

    private function upgrade_to_2_2_2($version) {
        global $wpdb;

        // Users who installed (not upgraded) version 2.2.1 got a posterip field
        // that does not support more than 15 caharacters. We need to
        // upgrade the field again
        // https://github.com/drodenbaugh/awpcp/issues/347#issuecomment-13159975
        if ( awpcp_column_exists( AWPCP_TABLE_ADS, 'posterip' ) ) {
            $sql = $this->database_helper->replace_charset_and_collate( "ALTER TABLE " . AWPCP_TABLE_ADS . "  MODIFY `posterip` VARCHAR(50) CHARACTER SET <charset> COLLATE <collate> NOT NULL DEFAULT ''" );
            $wpdb->query( $sql );
        }
    }

    private function upgrade_to_3_0_0($version) {
        global $wpdb, $awpcp;

        /* Create Credit Plans table */
        dbDelta( $this->plugin_tables->get_credit_plans_table_definition() );

        /* Create Payments table and tell AWPCP to migrate Payment Transactions information */
        dbDelta( $this->plugin_tables->get_payments_table_definition() );

        /* Add payment_term_type columns to Ads table */

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADS, 'payment_term_type' ) ) {
            $wpdb->query("ALTER TABLE " . AWPCP_TABLE_ADS . "  ADD `payment_term_type` VARCHAR(64) NOT NULL DEFAULT 'fee'");
        }

        /* Add credits, private, title_characters columns to Fees table */

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADFEES, 'credits' ) ) {
            $wpdb->query("ALTER TABLE " . AWPCP_TABLE_ADFEES . "  ADD `credits` INT(10) NOT NULL DEFAULT 0");
        }

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADFEES, 'private' )   ) {
            $wpdb->query( "ALTER TABLE " . AWPCP_TABLE_ADFEES . " ADD `private` TINYINT(1) UNSIGNED NOT NULL DEFAULT 0" );
        }

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADFEES, 'title_characters' )   ) {
            $wpdb->query( "ALTER TABLE " . AWPCP_TABLE_ADFEES . " ADD `title_characters` INT(1) NOT NULL DEFAULT 0" );
        }

        /* Remove widget options that can break the Latest Ads Widget */
        $widget = get_option( 'widget_awpcp-latest-ads' );
        unset( $widget[0] );
        update_option( 'widget_awpcp-latest-ads', $widget );

        /* Increase min image file size */
        $size = $awpcp->settings->get_option( 'maximagesize', 150000 );
        if ( $size == 150000 ) {
            $awpcp->settings->update_option( 'maximagesize', 1048576 );
        }

        if ( is_null( $awpcp->settings->get_option( 'show-widget-modification-notice', null ) ) ) {
            $awpcp->settings->update_option('show-widget-modification-notice', true, true);
        }

        $query = "SELECT option_name FROM $wpdb->options ";
        $query.= "WHERE option_name LIKE 'awpcp-payment-transaction-%' ";
        $query.= "LIMIT 0, 100";

        $transactions = $wpdb->get_results( $query );

        if ( count( $transactions ) > 0 ) {
            update_option('awpcp-import-payment-transactions', true);
            update_option('awpcp-pending-manual-upgrade', true);
        }
    }

    private function upgrade_to_3_0_2($oldversion) {
        global $wpdb;

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');

        $manual_upgrade_required = false;
        $settings = awpcp()->settings;

        // fix for all Ads being (visually) marked as featured (part of #527).
        $layout = $settings->get_option( 'displayadlayoutcode' );
        $layout = str_replace( 'awpcp_featured_ad_wrapper', '$isfeaturedclass', $layout );
        $settings->update_option( 'displayadlayoutcode', $layout );

        // create awpcp_ad_regions table
        dbDelta( $this->plugin_tables->get_listing_regions_table_definition() );

        // create awpcp_media table
        dbDelta( $this->plugin_tables->get_media_table_definition() );

        // Create ad metadata table.
        dbDelta( $this->plugin_tables->get_listing_meta_table_definition() );

        // migrate old regions
        if ( awpcp_column_exists( AWPCP_TABLE_ADS, 'ad_country' )   ) {
            update_option( 'awpcp-migrate-regions-information', true );

            // the following option was used as the cursor during the first
            // upgrade. However, we had to rollback some of the modifications
            // and the upgrade had to be run again. The new cursor is:
            // 'awpcp-migrate-regions-info-cursor'.
            delete_option( 'awpcp-migrate-regions-information-cursor' );

            $manual_upgrade_required = true;
        }

        // migrate media regions
        if ( awpcp_table_exists( AWPCP_TABLE_ADPHOTOS ) ) {
            update_option( 'awpcp-migrate-media-information', true );

            $manual_upgrade_required = true;
        }

        // add columns required for email verification feature
        $this->columns->create( AWPCP_TABLE_ADS, 'verified', "TINYINT(1) NOT NULL DEFAULT 1" );
        $this->columns->create( AWPCP_TABLE_ADS, 'verified_at', "DATETIME" );

        // add payer email column
        $column_definition = $this->database_helper->replace_charset_and_collate( "VARCHAR(255) CHARACTER SET <charset> COLLATE <collate> NOT NULL DEFAULT '' AFTER `payment_status`" );
        $this->columns->create( AWPCP_TABLE_ADS, 'payer_email', $column_definition );
        $this->columns->create( AWPCP_TABLE_PAYMENTS, 'payment_gateway', $column_definition );
        $this->columns->create( AWPCP_TABLE_PAYMENTS, 'payer_email', $column_definition );

        if ( awpcp_column_exists( AWPCP_TABLE_ADS, 'payer_email' )   ) {
            $wpdb->query( "UPDATE " . AWPCP_TABLE_ADS . " SET payer_email = ad_contact_email" );
        }

        if ( $manual_upgrade_required ) {
            update_option( 'awpcp-pending-manual-upgrade', true );
        }
    }

    private function upgrade_to_3_2_2( $oldversion ) {
        global $wpdb;

        if ( ! awpcp_column_exists( AWPCP_TABLE_MEDIA, 'status' ) ) {
            $sql = 'ALTER TABLE ' . AWPCP_TABLE_MEDIA . ' ADD `status` VARCHAR(20) CHARACTER SET <charset> COLLATE <collate> NOT NULL DEFAULT %s AFTER `enabled`';
            $sql = $wpdb->prepare( $sql, AWPCP_Attachment_Status::STATUS_APPROVED );
            $sql = $this->database_helper->replace_charset_and_collate( $sql );
            $wpdb->query( $sql );
        }

        if ( get_awpcp_option( 'imagesapprove' ) ) {
            update_option( 'awpcp-update-media-status', true );
            update_option( 'awpcp-pending-manual-upgrade', true );
        }
    }

    private function upgrade_to_3_3_2( $oldversion ) {
        // create tasks table
        dbDelta( $this->plugin_tables->get_tasks_table_definition() );
    }

    private function upgrade_to_3_3_3( $oldversion ) {
        update_option( 'awpcp-flush-rewrite-rules', true );
    }

    private function upgrade_to_3_4( $oldversion ) {
        $show_currency_symbol = awpcp()->settings->get_option( 'show-currency-symbol' );
        if ( is_numeric( $show_currency_symbol ) && $show_currency_symbol ) {
            awpcp()->settings->update_option( 'show-currency-symbol', 'show-currency-symbol-on-left' );
        } else if ( is_numeric( $show_currency_symbol ) ) {
            awpcp()->settings->update_option( 'show-currency-symbol', 'do-not-show-currency-symbol' );
        }
    }

    /**
     */
    private function upgrade_to_3_5_3( $oldversion ) {
        global $wpdb;

        $plugin_pages = get_option( 'awpcp-plugin-pages', array() );

        if ( empty( $plugin_pages ) ) {
            // move plugin pages info from PAGES table to awpcp-plugin-pages option
            $pages = $wpdb->get_results( 'SELECT page, id FROM ' . AWPCP_TABLE_PAGES, OBJECT_K );
            foreach ( $pages as $page_ref => $page_info ) {
                awpcp_update_plugin_page_id( $page_ref, $page_info->id );
            }
        }

        // make sure there are entries for 'view-categories-page-name' in the plugin pages info
        $plugin_pages = get_option( 'awpcp-plugin-pages', array() );

        if ( isset( $plugin_pages['view-categories-page-name'] ) ) {
            unset( $plugin_pages['view-categories-page-name'] );
            awpcp_update_plugin_pages_info( $plugin_pages );
        }

        // drop no longer used PAGENAME table
        $wpdb->query( 'DROP TABLE IF EXISTS ' . AWPCP_TABLE_PAGENAME );
    }

    private function create_tasks_table( $oldversion ) {
        // create tasks table if missing
        // https://github.com/drodenbaugh/awpcp/issues/1246
        dbDelta( $this->plugin_tables->get_tasks_table_definition() );
    }

    private function create_metadata_column_in_media_table( $oldversion ) {
        global $wpdb;

        if ( ! awpcp_column_exists( AWPCP_TABLE_MEDIA, 'metadata' ) ) {
            $sql = $this->database_helper->replace_charset_and_collate( 'ALTER TABLE ' . AWPCP_TABLE_MEDIA . " ADD `metadata` TEXT CHARACTER SET <charset> COLLATE <collate> NOT NULL DEFAULT '' AFTER `is_primary`" );
            $wpdb->query( $sql );
        }
    }

    private function create_regions_column_in_fees_table( $oldversion ) {
        global $wpdb;

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADFEES, 'regions' ) ) {
            $query = 'ALTER TABLE ' . AWPCP_TABLE_ADFEES . ' ADD `regions` INT(10) NOT NULL DEFAULT 1 AFTER `imagesallowed`';
            $wpdb->query( $query );
        }
    }

    private function create_description_column_in_fees_table( $oldversion ) {
        global $wpdb;

        if ( ! awpcp_column_exists( AWPCP_TABLE_ADFEES, 'description' ) ) {
            $sql = $this->database_helper->replace_charset_and_collate( 'ALTER TABLE ' . AWPCP_TABLE_ADFEES . ' ADD `description` TEXT CHARACTER SET <charset> COLLATE <collate> NOT NULL AFTER `adterm_name`' );
            $wpdb->query( $sql );
        }
    }

    private function try_to_convert_tables_to_utf8mb4( $oldversion ) {
        global $wpdb;

        if ( $wpdb->charset !== 'utf8mb4' ) {
            return;
        }

        if ( ! function_exists( 'maybe_convert_table_to_utf8mb4' ) ) {
            return;
        }

        $plugin_tables = $wpdb->get_col( "SHOW TABLES LIKE '%awpcp_%'" );

        foreach ( $plugin_tables as $table_name ) {
            maybe_convert_table_to_utf8mb4( $table_name );
        }
    }

    private function allow_null_values_in_user_id_column_in_payments_table( $oldversion ) {
        global $wpdb;

        if ( awpcp_column_exists( AWPCP_TABLE_PAYMENTS, 'user_id' ) ) {
            $wpdb->query(  'ALTER TABLE ' . AWPCP_TABLE_PAYMENTS . ' CHANGE user_id user_id INT( 10 ) NULL'  );
        }
    }

    private function create_phone_number_digits_column( $oldversion ) {
        if ( ! awpcp_table_exists( AWPCP_TABLE_ADS ) ) {
            return;
        }

        $this->columns->create(
            AWPCP_TABLE_ADS,
            'phone_number_digits',
            $this->database_helper->replace_charset_and_collate(
                "VARCHAR(25) CHARACTER SET <charset> COLLATE <collate> NOT NULL DEFAULT '' AFTER `ad_contact_phone`"
            )
        );
    }

    private function enable_upgrade_task_to_store_phone_number_digits() {
        if ( ! awpcp_table_exists( AWPCP_TABLE_ADS ) ) {
            return;
        }

        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-phone-number-digits' );
    }

    private function set_flag_to_store_browse_categories_page_information() {
        update_option( 'awpcp-store-browse-categories-page-information', true, false );
    }

    private function set_flag_to_maybe_fix_browse_categories_page_information() {
        update_option( 'awpcp-maybe-fix-browse-categories-page-information', true, false );
    }

    private function set_flag_to_show_missing_paypal_merchant_id_setting_notice() {
        update_option( 'awpcp-show-missing-paypal-merchant-id-setting-notice', true, false );
    }

    private function remove_fulltext_index_from_listings_table() {
        global $wpdb;

        $indexes = $wpdb->get_results( "SHOW INDEX FROM wp_awpcp_ads WHERE key_name = 'titdes'" );

        if ( is_array( $indexes ) && count( $indexes ) ) {
            $wpdb->query( 'ALTER TABLE ' . AWPCP_TABLE_ADS . '  DROP INDEX `titdes`' );
        }
    }

    private function convert_tables_to_innodb() {
        global $wpdb;

        $tables = $wpdb->get_col( "SHOW TABLES LIKE '%_awpcp_%'" );

        foreach ( $tables as $table ) {
            $wpdb->query( sprintf( 'ALTER TABLE %s ENGINE=InnoDB', $table ) );
        }
    }

    private function create_listings_table_if_missing() {
        if ( ! awpcp_table_exists( AWPCP_TABLE_ADS ) ) {
            dbDelta( $this->plugin_tables->get_listings_table_definition() );
        }
    }

    /**
     * @since 3.8.6
     */
    private function migrate_facebook_integration_settings() {
        $settings = awpcp()->settings;
        $config   = get_option( 'awpcp-facebook-config', array() );

        if ( ! empty( $config['app_id'] ) ) {
            $settings->set_or_update_option( 'facebook-app-id', $config['app_id'] );
        }

        if ( ! empty( $config['app_secret'] ) ) {
            $settings->set_or_update_option( 'facebook-app-secret', $config['app_secret'] );
        }

        if ( ! empty( $config['user_token'] ) ) {
            $settings->set_or_update_option( 'facebook-user-access-token', $config['user_token'] );
        }

        if ( ! empty( $config['page_id'] ) ) {
            $settings->set_or_update_option( 'facebook-page', $config['page_id'] . '|' . $config['page_token'] );
        }

        if ( ! empty( $config['page_token'] ) ) {
            $settings->set_or_update_option( 'facebook-page-access-token', $config['page_token'] );
        }

        if ( ! empty( $config['group_id'] ) ) {
            $settings->set_or_update_option( 'facebook-group', $config['group_id'] );
        }

        if ( ! empty( $config['app_id'] ) && ! empty( $config['app_secret'] ) && ! empty( $config['user_token'] ) ) {
            $settings->set_or_update_option( 'clear-facebook-cache-for-ads-pages', true );
        }

        if ( ! empty( $config['app_id'] ) && ! empty( $config['app_secret'] ) ) {
            $settings->set_or_update_option( 'facebook-integration-method', 'facebook-api' );
        }
    }

    private function create_old_listing_id_column_in_listing_regions_table() {
        global $wpdb;

        if ( ! awpcp_column_exists( AWPCP_TABLE_AD_REGIONS, 'old_listing_id' ) ) {
            $wpdb->query( 'ALTER TABLE ' . AWPCP_TABLE_AD_REGIONS . ' ADD `old_listing_id` INT(10) NOT NULL AFTER `ad_id`' );
        }

        $wpdb->query( 'UPDATE ' . AWPCP_TABLE_AD_REGIONS . ' SET `old_listing_id` = `ad_id` WHERE old_listing_id = 0' );
    }

    private function migrate_wordpress_page_settings() {
        $pages = get_option( 'awpcp-plugin-pages' );

        if ( empty( $pages ) ) {
            return;
        }

        foreach ( $pages as $page_ref => $page_info ) {
            if ( empty( $page_info['page_id'] ) ) {
                continue;
            }

            awpcp_update_plugin_page_id( $page_ref, $page_info['page_id'] );
        }
    }

    /**
     * @since 4.0.0
     */
    private function migrate_reply_to_ad_email_template() {
        $previous_subject = $this->settings->get_option( 'contactformsubjectline', __( 'Response to your AWPCP Demo Ad', 'another-wordpress-classifieds-plugin' ) );
        $previous_body    = $this->settings->get_option( 'contactformbodymessage', __( 'Someone has responded to your AWPCP Demo Ad', 'another-wordpress-classifieds-plugin' ) );

        $template = $this->settings->get_option( 'contact-form-user-notification-email-template' );

        if ( ! empty( $template ) ) {
            // We already migrated the settings or someone provided a new value first. Abort.
            return;
        }

        $template = [
            'subject' => _x( "{__previous_subject__} regarding: {listing_title}", 'reply to ad email', 'another-wordpress-classifieds-plugin' ),
            'body'    => _x( "{__previous_body__}\n\nContact name: {sender_name}\nContact email: {sender_email}\n\nContacting about {listing_title}\n{listing_url}\n\nMessage:\n\n{message}\n\n{website_title}\n{website_url}", 'reply to ad email', 'another-wordpress-classifieds-plugin' ),
            'version' => '4.0.0',
        ];

        $template['subject'] = str_replace( '{__previous_subject__}', $previous_subject, $template['subject'] );
        $template['body']    = str_replace( '{__previous_body__}', $previous_body, $template['body'] );

        $this->settings->set_or_update_option( 'contact-form-user-notification-email-template', $template );
    }

    /**
     * @since 4.0.0
     */
    private function migrate_verify_email_message_email_template() {
        $previous_subject = $this->settings->get_option( 'verifyemailsubjectline', __( 'Verify the email address used for Ad $title', 'another-wordpress-classifieds-plugin' ) );
        $previous_body    = $this->settings->get_option( 'verifyemailbodymessage', _x( "Hello \$author_name \n\nYou recently posted the Ad \$title to \$website_name. \n\nIn order to complete the posting process you have to verify your email address. Please click the link below to complete the verification process. You will be redirected to the website where you can see your Ad. \n\n\$verification_link \n\nAfter you verify your email address, the administrator will be notified about the new Ad. If moderation is enabled, your Ad will remain in a disabled status until the administrator approves it.\n\n\$website_name\n\n\$website_url", 'another-wordpress-classifieds-plugin' ) );

        $template = $this->settings->get_option( 'verify-email-message-email-template' );

        if ( ! empty( $template ) ) {
            // We already migrated the settings or someone provided a new value first. Abort.
            return;
        }

        $template = [
            'subject'  => $previous_subject,
            'body'     => $previous_body,
            'version'  => '4.0.0',
        ];

        $template['subject'] = str_replace( '$title', '{listing_title}', $template['subject'] );

        $template['body'] = str_replace( '$title', '{listing_title}', $template['body'] );
        $template['body'] = str_replace( '$author_name', '{author_name}', $template['body'] );
        $template['body'] = str_replace( '$verification_link', '{verification_link}', $template['body'] );
        $template['body'] = str_replace( '$website_name', '{website_title}', $template['body'] );
        $template['body'] = str_replace( '$website_url', '{website_url}', $template['body'] );

        $this->settings->set_or_update_option( 'verify-email-message-email-template', $template );
    }

    /**
     * @since 4.0.0
     */
    private function keep_legacy_url_structure() {
        $this->settings->set_or_update_option( 'display-listings-as-single-posts', false );

        $main_plugin_page = awpcp_get_page_by_ref( 'main-page-name' );
        $show_listing_page = awpcp_get_page_by_ref( 'show-ads-page-name' );

        if ( $main_plugin_page && $show_listing_page && $show_listing_page->post_parent == $main_plugin_page->ID ) {
            $this->settings->set_or_update_option( 'listings-slug', $show_listing_page->post_name );
            $this->settings->set_or_update_option( 'include-main-page-slug-in-listing-url', true );
        } else if ( $show_listing_page ) {
            $this->settings->set_or_update_option( 'listings-slug', get_page_uri( $show_listing_page ) );
            $this->settings->set_or_update_option( 'include-main-page-slug-in-listing-url', false );
        }
    }

    /**
     * @since 4.0.0
     */
    private function remove_old_capabilities() {
        $roles_and_capabilities = awpcp()->container['RolesAndCapabilities'];
        $capabilities           = [
            'manage_classifieds',
            'manage_classifieds_listings',
        ];

        $roles_and_capabilities->remove_capabilities( $capabilities );
    }

    private function enable_upgrade_routine_to_migrate_listing_categories() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-listing-categories-as-custom-taxonomies' );
    }

    private function enable_upgrade_routine_to_migrate_listings() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-listings-as-custom-post-types' );
    }

    /**
     * @since 4.0.0
     */
    private function maybe_enable_upgrade_routines_to_migrate_media( $oldversion ) {
        // These upgrade routines were first introduced in 4.0.0beta1 and modified
        // for 4.0.0beta11 (See https://github.com/drodenbaugh/awpcp/issues/2201)
        // and 4.0.0beta13 (See https://github.com/drodenbaugh/awpcp/issues/2370).
        //
        // The routines continued to be included in other beta releases and enqueued
        // last, so that all blocking routines were always executed first, even those
        // introduced after 4.0.0beta1, and media migration (when necessary) was
        // always performed as a non-blocking routine.
        //
        // However, if the website is already using 4.0.0beta1 or superior, then there
        // is no need to enable the routine again.
        if ( version_compare( $oldversion, '4.0.0beta1', '>=' ) ) {
            return;
        }

        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-media-as-attachments-upgrade-task-handler' );
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-generate-thumbnails-for-migrated-media' );
    }

    /**
     * Version 4.0.0beta1 can create listing_category terms having an ID equal to
     * the ID of one of the categories stored in the awpcp_categories table.
     *
     * When that happens, that listing_cateogry becomes inaccessible because
     * the plugin will automatically redirect to the listing_category assocaited
     * with the category from the awpcp_categories table that has the same ID.
     *
     * This upgrade routine fixes that problem by replacing affected terms with
     * identical ones that have a different, non-conflicting ID.
     *
     * @since 4.0.0
     */
    private function enable_routine_to_fix_id_collision_for_listing_categories() {
        // TODO: We need to make each upgrade routine its own class so that we
        // can inject dependencies through the constructor.
        $collisions = awpcp_categories_registry()->get_id_collisions();

        if ( $collisions ) {
            $this->upgrade_tasks->enable_upgrade_task( 'awpcp-fix-id-collision-for-listing-categories' );
            delete_option( 'awpcp-ficflc-last-listing-id' );
        }
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_store_categories_order_as_term_meta() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-store-categories-order-as-term-meta' );
    }

    /**
     * Many versions of the plugin used AWPCP as the textdomain for translations,
     * the filename prefix for PO and MO files, both official and custom.
     *
     * As we move towards providing translations using Language Packs exclusively,
     * we want to stop loading translation files using the old textdomain in their
     * names. This upgrade routine attempts to rename those files using the new
     * textdomain and to move them to the  wp-languages/another-wordpress-classifieds-plugin
     * directory.
     *
     * @since 4.0.0
     */
    private function rename_translation_files_using_outdated_textdomain() {
        if ( ! function_exists( 'glob' ) ) {
            return;
        }

        $basename = dirname( plugin_basename( AWPCP_FILE ) );

        // Historically we have loaded custom and official translation files from these directories.
        $directories = [
            WP_PLUGIN_DIR . "/$basename",
            WP_PLUGIN_DIR . "/$basename/languages",
            WP_LANG_DIR . '/another-wordpress-classifieds-plugin',
            WP_LANG_DIR . '/plugins',
        ];

        $files_to_move   = [];
        $files_not_moved = [];

        foreach ( $directories as $directory ) {
            $files_found   = glob( "$directory/AWPCP-*.{po,mo}", GLOB_BRACE );
            $files_to_move = array_merge( $files_to_move, $files_found );
        }

        foreach ( $files_to_move as $file ) {
            $filename = basename( $file );
            $filename = str_replace( 'AWPCP', 'another-wordpress-classifieds-plugin', $filename );

            $path = WP_LANG_DIR . "/another-wordpress-classifieds-plugin/$filename";

            if ( file_exists( $path ) ) {
                $files_not_moved[] = $file;
                continue;
            }

            if ( ! rename( $file, $path ) ) {
                $files_not_moved[] = $file;
                continue;
            }
        }

        if ( $files_not_moved ) {
            update_option( 'awpcp_translation_files_with_outdated_textdomain', $files_not_moved, false );
        }
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_fix_id_collision_for_listings() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-fix-id-collision-for-listings' );
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_force_post_id() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-maybe-force-post-id' );

        update_option( 'awpcp_mfpi_maybe_force_post_id', true );
    }

    /**
     * Fix the name of the post meta that holds the old ID of the listing.
     *
     * Version 4.0.0beta1-4.0.0beta12 used an _awpcp_old_id meta to store the
     * ID that the listing was using in the custom tables. To improve the
     * performance of SQL queries trying to find listings by their old ID,
     * 4.0.0beta13 started including the old ID in the meta_key (_awpcp_old_id_1234).
     * That way queries no longer need to find a meta whose value matches the
     * old ID but can check whether a specifc meta_key exists instead.
     *
     * @since 4.0.0
     */
    private function fix_old_listing_id_metadata() {
        $this->db->query( "UPDATE {$this->db->postmeta} SET meta_key = CONCAT('_awpcp_old_id_', meta_value) WHERE meta_key = '_awpcp_old_id'" );
    }

    /**
     * @since 4.0.0
     */
    private function maybe_enable_routine_to_update_categories_term_count( $oldversion ) {
        // We started using wp_defer_term_counting() in 4.0.0beta13, but anyone
        // who is running 4.0.0beta1 or older already migrated the ads and
        // categories using the old version of the routine, so there is no need
        // to update term counts again (it was done automatically by WordPress
        // every time a categories were associated with ads).
        if ( version_compare( $oldversion, '4.0.0beta1', '>=' ) ) {
            return;
        }

        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-update-categories-term-count' );
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_add_missing_is_paid_meta() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-missing-is-paid-meta' );
    }

    /**
     * @since 4.0.0
     */
    private function enable_routine_to_add_missing_views_meta() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-missing-views-meta' );
    }

    /**
     * @since 4.0.0
     */
    private function delete_settings_table() {
        $this->db->query( "DROP TABLE IF EXISTS {$this->db->prefix}awpcp_adsettings" );
    }

    // phpcs:enable

    /**
     * Remove pending data and validation errors stored by mistake.
     *
     * Versions 4.0.0 and 4.0.1 stored invalid pending data and validation errors
     * for the admin editor every time the post was saved during an admin request.
     * The method responsible for saving the information entered in the Listing
     * Fields metabox was being executed without checking whether the metabox was
     * actually being saved or not.
     *
     * This routine removes the invalid data.
     *
     * @since 4.0.2
     *
     * @see https://github.com/drodenbaugh/awpcp/issues/2557
     */
    private function remove_invalid_admin_editor_metadata() {
        $results = $this->db->get_results( "SELECT * FROM {$this->db->postmeta} WHERE meta_key = '__awpcp_admin_editor_pending_data'" );

        foreach ( $results as $postmeta ) {
            $meta_value = maybe_unserialize( $postmeta->meta_value );

            /*
             * If the metadata inclues values for at least one of the following
             * fields then it was created when the metabox was really being
             * saved and we don't need to remove it.
             */
            if ( ! empty( $meta_value['metadata']['_awpcp_contact_name'] ) ) {
                continue;
            }

            if ( ! empty( $meta_value['metadata']['_awpcp_contact_email'] ) ) {
                continue;
            }

            if ( ! empty( $meta_value['metadata']['_awpcp_contact_phone'] ) ) {
                continue;
            }

            if ( ! empty( $meta_value['metadata']['_awpcp_website_url'] ) ) {
                continue;
            }

            delete_post_meta( $postmeta->post_id, '__awpcp_admin_editor_pending_data' );
            delete_post_meta( $postmeta->post_id, '__awpcp_admin_editor_validation_errors' );
        }
    }

    /**
     * @since 4.0.5
     */
    private function enable_routine_to_update_most_recent_date() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-update-most-recent-date' );
    }

    /**
     * @since 4.0.6
     */
    private function enable_routine_to_add_default_awpcp_order() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-missing-categories-order' );
    }

    /**
     * @since 4.0.7
     */
    private function enable_routine_to_add_awpcp_contact_phone_number_digits() {
        $this->upgrade_tasks->enable_upgrade_task( 'awpcp-add-contact-phone-number-digits' );
    }



    /**
     * Increase pending data and validation errors stored by mistake.
     *
     * @since 4.0.14
     *
     * @see https://github.com/drodenbaugh/awpcp/issues/2970
     */
    private function increase_adfee_table_amount_field_max_value() {
        global $wpdb;

        if ( awpcp_column_exists( AWPCP_TABLE_ADFEES, 'amount' ) ) {
            $sql = $this->database_helper->replace_charset_and_collate( 'ALTER TABLE ' . AWPCP_TABLE_ADFEES . ' MODIFY `amount` FLOAT(10,2) UNSIGNED NOT NULL DEFAULT 0.00' );
            $wpdb->query( $sql );
        }
    }
}

// phpcs:disable PEAR.Functions.FunctionCallSignature.SpaceBeforeCloseBracket
// phpcs:disable PEAR.Functions.FunctionCallSignature.SpaceAfterOpenBracket
// phpcs:disable Generic.Formatting.MultipleStatementAlignment.NotSameWarning
// phpcs:disable WordPress.WhiteSpace.OperatorSpacing.NoSpaceBefore
// phpcs:disable WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceAfterOpenParenthesis
// phpcs:disable WordPress.WhiteSpace.ControlStructureSpacing.NoSpaceBeforeCloseParenthesis
// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.NoSpaceBeforeArrayCloser
// phpcs:disable WordPress.Arrays.ArrayDeclarationSpacing.NoSpaceAfterArrayOpener
// phpcs:disable Squiz.Commenting.InlineComment.InvalidEndChar
// phpcs:disable Generic.Formatting.MultipleStatementAlignment.IncorrectWarning
// phpcs:disable Squiz.Strings.DoubleQuoteUsage.NotRequired
// phpcs:disable Squiz.Functions.FunctionDeclarationArgumentSpacing.SpacingAfterOpen

/**
 * Set tables charset to utf8 and text-based columns collate to utf8_general_ci.
 */
function awpcp_fix_table_charset_and_collate($tables) {
    global $wpdb;

    $tables = is_array($tables) ? $tables : array($tables);

    $types = array('varchar', 'char', 'text', 'enum', 'set');

    foreach ($tables as $table) {
        $sql = "ALTER TABLE `$table` CHARACTER SET utf8 COLLATE utf8_general_ci";
        $wpdb->query($sql);

        $sql = "SHOW COLUMNS FROM `$table`";
        $columns = $wpdb->get_results($sql, ARRAY_N);

        $parts = array();
        foreach ($columns as $col) {
            foreach ($types as $type) {
                if (strpos($col[1], $type) !== false) {
                    $definition = "CHANGE `$col[0]` `$col[0]` $col[1] ";
                    $definition.= "CHARACTER SET utf8 COLLATE utf8_general_ci ";
                    $definition.= strcasecmp($col[2], 'NO') === 0 ? 'NOT NULL ' : '';

                    // TEXT columns can't have a default value in Strict mode.
                    if ( $type !== 'text' ) {
                        $definition.= strcasecmp($col[4], 'NULL') === 0 ? 'DEFAULT NULL' : "DEFAULT '$col[4]'";
                    }
                    $parts[] = $definition;
                    break;
                }
            }
        }

        $sql = "ALTER TABLE `$table` " . join(', ', $parts);
        $wpdb->query($sql);
    }
}

// phpcs:enable
