<?php
/**
 * @package AWPCP\Admin\Templates
 */

?>
<div class="awpcp-admin-sidebar awpcp-postbox-container postbox-container" style="<?php echo esc_attr( $float ); ?>">
    <div class="metabox-holder">
        <div class="meta-box-sortables">

            <div class="postbox">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput
                echo awpcp_html_postbox_handle( array( 'content' => __( 'Like this plugin?', 'another-wordpress-classifieds-plugin' ) ) );
                ?>
                <div class="inside">
                    <ul>
                        <li class="li_link">
                            <a href="https://wordpress.org/support/plugin/another-wordpress-classifieds-plugin/reviews/?filter=5#new-post">
                                <?php esc_html_e( 'Give a 5 star rating on WordPress.org.', 'another-wordpress-classifieds-plugin' ); ?>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            <?php if ( count( $modules['premium']['not-installed'] ) !== 0 ) : ?>
                <div class="awpcp-get-a-premium-module postbox" style="background-color:#FFFFCF; border-color:#0EAD00; border-width:3px;">
                    <?php
                    $params = array(
                        'heading_attributes' => array(
                            'style' => 'color:#145200',
                        ),
                        'span_attributes'    => array(
                            'class' => 'red',
                        ),
                        'content'            => '<strong>' . __( 'Get a Premium Module!', 'another-wordpress-classifieds-plugin' ) . '</strong>',
                    );
                    // phpcs:ignore WordPress.Security.EscapeOutput
                    echo awpcp_html_postbox_handle( $params );
                    ?>

                    <div class="inside">
                        <ul>
                            <?php foreach ( $modules['premium']['not-installed'] as $module ) : ?>
                                <li class="li_link">
                                    <a style="color:#145200;" href="<?php echo esc_url( $module['url'] ); ?>" target="_blank">
                                        <?php echo esc_html( $module['name'] ); ?>
                                    </a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            <?php endif; ?>

            <div class="postbox">
                <?php
                // phpcs:ignore WordPress.Security.EscapeOutput
                echo awpcp_html_postbox_handle(
                    array(
                        'content' => __( 'Found a bug?', 'another-wordpress-classifieds-plugin' ) . '&nbsp;' . __( 'Need Support?', 'another-wordpress-classifieds-plugin' ),
                    )
                );
                ?>
                <?php $tpl = '<a href="%s" target="_blank">%s</a>'; ?>
                <div class="inside">
                    <ul>

                        <?php
                        $atag = sprintf( $tpl, 'https://awpcp.com/knowledge-base/quick-start-guide/', __( 'Quick Start Guide', 'another-wordpress-classifieds-plugin' ) );
                        ?>
                        <li>
                            <?php
                            /* translators: %s: translated link */
                            $text = sprintf( esc_html_x( 'Browse the %s.', 'Browse the <a>Quick Start Guide</a>', 'another-wordpress-classifieds-plugin' ), $atag );
                            echo wp_kses_post( $text );
                            ?>
                        </li>
                        <?php
                        $atag = sprintf( $tpl, 'https://awpcp.com/knowledge-base/', __( 'Documentation', 'another-wordpress-classifieds-plugin' ) );
                        ?>
                        <li>
                            <?php
                            /* translators: %s: translated link */
                            $text = sprintf( esc_html_x( 'Read the full %s.', 'Read the full <a>Documentation</a>', 'another-wordpress-classifieds-plugin' ), $atag );
                            echo wp_kses_post( $text );
                            ?>
                        </li>
                        <li>
	                        <?php
	                        printf( $tpl, 'https://awpcp.com/get-help/', esc_html__( 'Get Help', 'another-wordpress-classifieds-plugin' ) );
	                        ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
