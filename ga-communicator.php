<?php
/**
 * Plugin Name: Google Analytics Communicator
 * Plugin URI:  https://github.com/kuno1/ga-communicator
 * Description: Communicate with Google Analytics.
 * Version:     0.0.0
 * Author:      Kunoichi INC.
 * Author URI:  https://kunoichiwp.com
 * License:     GPLv3 or later
 * License URI: http://www.gnu.org/licenses/old-licenses/gpl-3.0.html
 * Text Domain: ga-communicator
 * Domain Path: /languages
 */

// This file actually do nothing.
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Invalid request.' );
}
require __DIR__ . '/vendor/autoload.php';

if ( is_multisite() && in_array( __FILE__, wp_get_active_network_plugins(), true ) ) {
	define( 'GA_COMMUNICATOR_NETWORK_ACTIVE', true );
}
\Kunoichi\GaCommunicator::get_instance();

add_action( 'widgets_init', function() {
	register_widget( \Kunoichi\GaCommunicator\Widgets\PopularPosts::class );
} );
