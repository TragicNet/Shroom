<?php
/**
 * @package AWPCP\Templates\Frontend
 */

?><div class="awpcp-listing-actions-component">
<?php foreach ( $actions as $action ) : ?>
    <?php echo $action->render( $listing ); // XSS Ok. ?>
<?php endforeach; ?>
</div>
