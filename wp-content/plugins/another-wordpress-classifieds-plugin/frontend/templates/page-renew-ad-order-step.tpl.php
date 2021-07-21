<?php
/**
 * @package AWPCP\Templates
 */

// phpcs:disable

?><h2><?php _ex('Select Payment Term', 'renew ad order step', 'another-wordpress-classifieds-plugin') ?></h2>

<?php foreach ($messages as $message): ?>
    <?php echo awpcp_print_message($message) ?>
<?php endforeach ?>

<?php foreach ($transaction_errors as $error): ?>
    <?php echo awpcp_print_message($error, array('error')) ?>
<?php endforeach ?>

<?php if (!awpcp_current_user_is_admin()): ?>
<?php echo $payments->render_account_balance() ?>
<?php endif ?>

<form class="awpcp-order-form" method="post">
    <?php echo awpcp_form_error( 'payment-term', $form_errors ); ?>
    <?php echo $payment_terms_list->render( null, $payment_terms_list_options ); ?>

    <?php echo $payments->render_credit_plans_table( $transaction ); ?>

    <p class="awpcp-form-submit">
        <input class="button" type="submit" value="<?php echo esc_attr( __( 'Continue', 'another-wordpress-classifieds-plugin' ) ); ?>" id="submit" name="submit">
        <?php if (!is_null($transaction)): ?>
        <input type="hidden" value="<?php echo esc_attr( $transaction->id ); ?>" name="transaction_id">
        <?php endif; ?>
        <input type="hidden" value="order" name="step">
    </p>
</form>
