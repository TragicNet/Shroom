<?php
/**
 * @package AWPCP\Templates
 */

?><div class="awpcp-pagination pager">
    <?php if ( $pages > 1 ) : ?>
    <div class="awpcp-pagination-links"><?php echo implode( '', $items ); // XSS Ok. ?></div>
    <?php endif; ?>

    <?php if ( $show_dropdown ) : ?>
    <form class="awpcp-pagination-form" method="get">
        <?php echo awpcp_html_hidden_fields( $params ); // XSS Ok. ?>

        <?php if ( count( $options ) > 1 ) : ?>

        <label for="awpcp-pagination-results-<?php echo esc_attr( $unique_id ); ?>"><?php echo esc_html( $dropdown_label ); ?></label>

        <select id="awpcp-pagination-results-<?php echo esc_attr( $unique_id ); ?>" name="<?php echo esc_attr( $dropdown_name ); ?>">
        <?php foreach ( $options as $option ) : ?>
            <?php if ( $results === (int) $option ) : ?>
            <option value="<?php echo esc_attr( $option ); ?>" selected="selected"><?php echo esc_html( $option ); ?></option>
            <?php else : ?>
            <option value="<?php echo esc_attr( $option ); ?>"><?php echo esc_html( $option ); ?></option>
            <?php endif; ?>
        <?php endforeach; ?>
        </select>

        <?php endif; ?>
    </form>
    <?php endif; ?>
</div>
