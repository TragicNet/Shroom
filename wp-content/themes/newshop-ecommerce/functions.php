<?php
function newshop_ecommerce_theme_setup() {
    load_child_theme_textdomain( 'newshop-ecommerce', get_stylesheet_directory() . '/languages' );
}
add_action( 'after_setup_theme', 'newshop_ecommerce_theme_setup' );

function newshop_ecommerce_register_scripts() {
    $parent_style = 'newstore-style';
    wp_enqueue_style('newstore-google-fonts', 'https://fonts.googleapis.com/css?family=Nunito:400,800,900|Rubik:400,700&display=swap');
    wp_enqueue_style( $parent_style, get_template_directory_uri() . '/style.css' );
    wp_enqueue_style( 'newshop-ecommerce-style', get_stylesheet_uri(), array( $parent_style ));
}
add_action('wp_enqueue_scripts', 'newshop_ecommerce_register_scripts', 15);