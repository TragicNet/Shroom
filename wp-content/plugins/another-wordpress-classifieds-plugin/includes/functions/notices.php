<?php
/**
 * @package AWPCP\Functions
 */

/**
 * Displays a message explaining that the XML Sitemap module is no longer
 * available and users should install or configure a SEO or XML Sitemap plugin
 * that supports Custom Post Types.
 *
 * @since 4.0.0
 */
function awpcp_xml_sitemap_module_removed_notice() {
    $content = '';

    $content .= '<p>';
    $content .= esc_html__( 'The AWPCP XML Sitemap module is no longer required with AWPCP 4.0 and above. You should deactivate and DELETE the XML Sitemap plugin and install one of the following:', 'another-wordpress-classifieds-plugin' );
    $content .= '</p>';

    $content .= '<ul>';
    $content .= '<li><a href="https://wordpress.org/plugins/wordpress-seo/">' . esc_html__( 'Yoast SEO (recommended)', 'another-wordpress-classifieds-plugin' ) . '</a></li>';
    $content .= '<li><a href="https://wordpress.org/plugins/all-in-one-seo-pack/">All in One SEO Pack</a>';
    $content .= '<li><a href="https://wordpress.org/plugins/google-sitemap-generator/">Google XML Sitemaps</a>';
    $content .= '</ul>';

    $content .= '<p>';
    $content .= esc_html__( 'Any of those plugins should work to generate the XML sitemap the "normal" way now since AWPCP is using Custom Post Types and will look like a post to WordPress now.', 'another-wordpress-classifieds-plugin' );
    $content .= '</p>';

    $content .= '<p>';
    $content .= esc_html__( 'Because of this change, there will be no upgrade of the XML Sitemap plugin for 4.0.', 'another-wordpress-classifieds-plugin' );
    $content .= '</p>';

    return $content;
}
