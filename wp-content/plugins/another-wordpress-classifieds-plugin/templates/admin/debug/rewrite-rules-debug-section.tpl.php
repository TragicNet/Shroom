<?php
/**
 * @package AWPCP\Templates\Admin\Debug
 */

?><table class="widefat striped">
    <thead>
        <tr>
            <th><?php echo esc_html__( 'Pattern', 'another-wordpress-classifieds-plugin' ); ?></th>
            <th><?php echo esc_html( 'Replacement', 'another-wordpress-classifieds-plugin' ); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ( $rules as $pattern => $rule ) : ?>
        <tr>
            <td><?php echo esc_html( $pattern ); ?></td>
            <td><?php echo esc_html( $rule ); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
