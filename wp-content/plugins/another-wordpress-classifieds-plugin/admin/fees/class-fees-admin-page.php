<?php
/**
 * Admin screen that allows administrators to manage Fees.
 *
 * @package AWPCP\Admin\Fees
 */

/**
 * Constructor function for AWPCP_AdminFees class.
 */
function awpcp_fees_admin_page() {
    return new AWPCP_AdminFees( awpcp_listings_collection() );
}

/**
 * @since 2.1.4
 */
class AWPCP_AdminFees extends AWPCP_AdminPageWithTable {

    private $listings;

    public function __construct( $listings ) {
        parent::__construct(
            'awpcp-admin-fees',
            awpcp_admin_page_title( __( 'Manage Listing Fees', 'another-wordpress-classifieds-plugin' ) ),
            __( 'Fees', 'another-wordpress-classifieds-plugin' )
        );

        $this->listings = $listings;
    }

    public function enqueue_scripts() {
        wp_enqueue_script( 'awpcp-admin-fees' );
    }

    public function get_table() {
        if ( ! is_null( $this->table ) ) {
            return $this->table;
        }

        $this->table = new AWPCP_FeesTable( $this, array( 'screen' => 'classifieds_page_awpcp-admin-fees' ) );

        return $this->table;
    }

    public function page_url( $params = array() ) {
        $base = add_query_arg( 'page', $this->page, admin_url( 'admin.php' ) );
        return $this->url( $params, $base );
    }

    /**
     * @SuppressWarnings(PHPMD.BooleanArgumentFlag)
     */
    public function actions( $fee, $filter = false ) {
        $actions = array();

        $actions['edit'] = array(
            __( 'Edit', 'another-wordpress-classifieds-plugin' ),
            $this->url(
                array(
                    'awpcp-action' => 'edit-fee',
                    'id'           => $fee->id,
                )
            ),
        );

        $actions['trash'] = array(
            __( 'Delete', 'another-wordpress-classifieds-plugin' ),
            $this->url(
                array(
                    'action' => 'delete',
                    'id'     => $fee->id,
                )
            ),
        );

        // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores
        $actions = apply_filters( 'awpcp-admin-fees-table-actions', $actions, $this, $fee, $filter );

        if ( is_array( $filter ) ) {
            $actions = array_intersect_key( $actions, array_combine( $filter, $filter ) );
        }

        return $actions;
    }

    public function dispatch() {
        $this->get_table();

        $action = $this->get_current_action();

        switch ( $action ) {
            case 'delete':
                return $this->delete();
            case 'transfer':
                return $this->transfer();
            case 'index':
                return $this->index();
            default:
                awpcp_flash( "Unknown action: $action", 'error' );
                return $this->index();
        }
    }

    /**
     * There is no UI to initiate this action anymore since version 4.0.0, but
     * the implementation may be useful in the future. Now fees cannot be
     * deleted if there are ads still associated with them.
     *
     * Please test thoroughly before enabling this feature again,
     * to make sure it works with recent modifications.
     *
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function transfer() {
        // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
        // phpcs:disable WordPress.Security.NonceVerification.Missing
        $fee = AWPCP_Fee::find_by_id( awpcp_request_param( 'id', 0 ) );
        if ( is_null( $fee ) ) {
            awpcp_flash( __( "The specified Fee doesn't exists.", 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->index();
        }

        $recipient = AWPCP_Fee::find_by_id( awpcp_request_param( 'payment_term', 0 ) );
        if ( is_null( $recipient ) ) {
            awpcp_flash( __( "The selected Fee doesn't exists.", 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->index();
        }

        if ( isset( $_POST['transfer'] ) ) {
            $errors = array();
            if ( $fee->transfer_ads_to( $recipient->id, $errors ) ) {
                /* translators: %1$s is the name of the original Fee and %2$s is the name of the fee that is associated with the ads now. */
                $message = __( 'All Ads associated to Fee %1$s have been associated with Fee %2$s.', 'another-wordpress-classifieds-plugin' );
                $message = sprintf( $message, '<strong>' . $fee->name . '</strong>', '<strong>' . $recipient->name . '</strong>' );
                awpcp_flash( $message );
            } else {
                foreach ( $errors as $error ) {
                    awpcp_flash( $error, 'error' );
                }
            }
            return $this->index();

        }

        if ( isset( $_POST['cancel'] ) ) {
            return $this->index();
        }

        $params = array(
            'fee'  => $fee,
            'fees' => AWPCP_Fee::query(),
        );

        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        $template = AWPCP_DIR . '/admin/templates/admin-panel-fees-delete.tpl.php';

        echo $this->render( $template, $params );
        // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
        // phpcs:enable WordPress.Security.NonceVerification.Missing
    }

    /**
     * @SuppressWarnings(PHPMD.ElseExpression)
     */
    public function delete() {
        $id  = awpcp_request_param( 'id', 0 );
        $fee = AWPCP_Fee::find_by_id( $id );

        if ( is_null( $fee ) ) {
            awpcp_flash( __( "The specified Fee doesn't exists.", 'another-wordpress-classifieds-plugin' ), 'error' );
            return $this->index();
        }

        $errors = array();

        if ( AWPCP_Fee::delete( $fee->id, $errors ) ) {
            awpcp_flash( __( 'The Fee was successfully deleted.', 'another-wordpress-classifieds-plugin' ) );
        } else {
            $ads = $this->listings->find_listings(
                array(
                    'meta_query' => array(
                        '_awpcp_payment_term_id'   => $fee->id,
                        '_awpcp_payment_term_type' => 'fee',
                    ),
                )
            );

            if ( empty( $ads ) ) {
                foreach ( $errors as $error ) {
                    awpcp_flash( $error, 'error' );
                }
            } else {
                $fees = AWPCP_Fee::query();

                if ( count( $fees ) > 1 ) {
                    $message = __( "The Fee couldn't be deleted because there are active Ads in the system that are associated with the Fee ID. You need to switch the Ads to a different Fee before you can delete the plan.", 'another-wordpress-classifieds-plugin' );
                    awpcp_flash( $message, 'error' );

                    $params = array(
                        'fee'  => $fee,
                        'fees' => $fees,
                    );

                    $template = AWPCP_DIR . '/admin/templates/admin-panel-fees-delete.tpl.php';

                    // phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
                    echo $this->render( $template, $params );
                    // phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped

                    return;
                }

                $message = __( "The Fee couldn't be deleted because there are active Ads in the system that are associated with the Fee ID. Please create a new Fee and try the delete operation again. AWPCP will help you to switch existing Ads to the new fee.", 'another-wordpress-classifieds-plugin' );

                awpcp_flash( $message, 'error' );
            }
        }

        return $this->index();
    }

    public function index() {
        $this->table->prepare_items();

        $params = array(
            'page'  => $this,
            'table' => $this->table,
        );

        $template = AWPCP_DIR . '/admin/templates/admin-panel-fees.tpl.php';

        return awpcp_render_template( $template, $params );
    }
}
