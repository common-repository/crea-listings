<?php
/**
 *
 * @package   CREA Listings
 * @author    Sprytechies <contact@sprytechies.com>
 * @license   GPL-2.0+
 * @link      http://sprytechies.com
 * @copyright 2014 contact@sprytechies.com
 *
 * @wordpress-plugin
 * Plugin Name: CREA Listings
 * Plugin URI:  http://sprytechies.com
 * Description: Amazing wordpress plugin to fetch CREA Listings for a user through authentication.
 * Version:     1.0.0
 * Author:      Sprytechies
 * Author URI:  http://sprytechies.com
 * License:     GPL-2.0+
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


require_once( plugin_dir_path( __FILE__ ) . 'class-crea-listing.php' );

// Register hooks that are fired when the plugin is activated, deactivated, and uninstalled, respectively.
register_activation_hook( __FILE__, array( 'CreaListing', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'CreaListing', 'deactivate' ) );
add_shortcode( 'list-properties', array( 'CreaListing', 'display_properties' ) );
add_shortcode( 'property', array( 'CreaListing', 'featured_properties' ) );

CreaListing::get_instance();