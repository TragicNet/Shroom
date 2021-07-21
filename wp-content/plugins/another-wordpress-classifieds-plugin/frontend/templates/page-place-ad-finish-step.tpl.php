<?php
/**
 * @package AWPCP\Templates
 */

if ( isset( $transaction ) && get_awpcp_option( 'show-create-listing-form-steps' ) ) {
    echo awpcp_render_listing_form_steps( 'finish', $transaction ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

?><?php foreach ( (array) $messages as $message ) : ?>
    <?php echo awpcp_print_message( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
<?php endforeach; ?>

<?php
// TODO: ContentRenderer or the rendered content itself should be available as a parameter for this view.
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo awpcp()->container['ListingsContentRenderer']->render_content_without_notices( apply_filters( 'the_content', $ad->post_content ), $ad );
