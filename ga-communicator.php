<?php
/**
 * Plugin Name: Google Analytics Communicator
 * Plugin URI:  https://github.com/kuno1/ga-communicator
 * Description: Let your WordPress communicate with Google Analytics API.
 * Version:     nightly
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

// This is plugin start point.
const GA_COMMUNICATOR_AS_PLUGIN = true;

// If in multisite, activate network mode.
if ( is_multisite() && in_array( __FILE__, wp_get_active_network_plugins(), true ) ) {
	define( 'GA_COMMUNICATOR_NETWORK_ACTIVE', true );
}

// Initialize plugin.
add_action( 'plugin_loaded', function() {
	// Add i18n
	load_plugin_textdomain( 'ga-communicator', false, basename( __DIR__ ) . '/languages' );
	// Load composer.
	$composer = __DIR__ . '/vendor/autoload.php';
	if ( ! file_exists( $composer ) ) {
		trigger_error( __( 'Composer file is missing. Please run composer install.', 'ga-communicator' ), E_USER_ERROR );
	}
	require_once  $composer;
	// Load Bootstrap.
	\Kunoichi\GaCommunicator::get_instance();
} );

// Register Widgets.
add_action( 'widgets_init', function() {
	register_widget( \Kunoichi\GaCommunicator\Widgets\PopularPosts::class );
} );

/**
 * Get report from Google Analytics.
 *
 * @since 2.0.0
 * @see \Kunoichi\GaCommunicator::get_report()
 * @param array         $request  Request array.
 * @param null|callable $callback Callback handler if set.
 * @return array|\WP_Error
 */
function ga_communicator_get_report( $request = [], $callback = null ) {
	return \Kunoichi\GaCommunicator::get_instance()->get_report( $request, $callback );
}

/**
 * @param array $query      Posts query to override result.
 * @param array $conditions Condition to get "popular" post.
 *
 * @return array|WP_Error|WP_Query|null
 */
function ga_communicator_popular_posts( $query = [], $conditions = [] ) {
	return \Kunoichi\GaCommunicator::get_instance()->popular_posts( $query, $conditions );
}
