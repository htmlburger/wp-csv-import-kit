<?php
/**
 *  Plugin Name: Carbon CSV Import Kit
 *  Description: A small library for creating CSV import pages in the WordPress dashboard
 *  Version 0.0.1
 *  License: GPL2
 */


require __DIR__ . '/vendor/autoload.php';

define( 'CRB_CSV_IK_ROOT_PATH', dirname( __FILE__ ) . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR );

# Define root URL
if ( ! defined( 'CRB_CSV_IK_ROOT_URL' ) ) {
    $url = trailingslashit( CRB_CSV_IK_ROOT_PATH );
    $count = 0;

    # Sanitize directory separator on Windows
    $url = str_replace( '\\' ,'/', $url );

    # If installed as a plugin
    $wp_plugin_dir = str_replace( '\\' ,'/', WP_PLUGIN_DIR );
    $url = str_replace( $wp_plugin_dir, plugins_url(), $url, $count );

    if ( $count < 1 ) {
        # If anywhere in wp-content
        $wp_content_dir = str_replace( '\\' ,'/', WP_CONTENT_DIR );
        $url = str_replace( $wp_content_dir, content_url(), $url, $count );
    }

    if ( $count < 1 ) {
        # If anywhere else within the WordPress installation
        $wp_dir = str_replace( '\\' ,'/', ABSPATH );
        $url = str_replace( $wp_dir, site_url( '/' ), $url );
    }

    define( 'CRB_CSV_IK_ROOT_URL', untrailingslashit( $url ) );
}
