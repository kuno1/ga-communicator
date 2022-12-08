<?php

namespace Kunoichi;


use Kunoichi\GaCommunicator\Api\Accounts;
use Kunoichi\GaCommunicator\Api\BatchGet;
use Kunoichi\GaCommunicator\Api\Dimensions;
use Kunoichi\GaCommunicator\Api\Profiles;
use Kunoichi\GaCommunicator\Api\Properties;
use Kunoichi\GaCommunicator\Pattern\Singleton;
use Kunoichi\GaCommunicator\Screen\Settings;
use Kunoichi\GaCommunicator\Services\Ga4Connector;
use Kunoichi\GaCommunicator\Services\UniversalAnalyticsConnector;
use Kunoichi\GaCommunicator\Utility\ScriptRenderer;

/**
 * Google Analytics Communicator
 *
 * @package ga-communicator
 */
class GaCommunicator extends Singleton {

	use Ga4Connector,
		UniversalAnalyticsConnector;

	/**
	 * {@inheritdoc}
	 */
	protected function init() {
		// Register command for CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'ga', GaCommunicator\Command::class );
		}
		// Load local.
		$this->locale();
		// Load Setting Screen
		Settings::get_instance();
		// Script Renderer.
		ScriptRenderer::get_instance();
		// Register scripts.
		add_action( 'init', [ $this, 'register_assets' ] );
		// API.
		Accounts::get_instance();
		Properties::get_instance();
		Profiles::get_instance();
		Dimensions::get_instance();
		BatchGet::get_instance();
	}

	/**
	 * Load locales.
	 */
	public function locale() {
		// Under plugin execution, return.
		if ( defined( 'GA_COMMUNICATOR_AS_PLUGIN' ) && GA_COMMUNICATOR_AS_PLUGIN ) {
			return;
		}
		// Load locales.
		$locale = get_locale();
		$mo     = dirname( dirname( __DIR__ ) ) . '/languages/ga-communicator-' . $locale . '.mo';
		if ( file_exists( $mo ) ) {
			load_textdomain( 'ga-communicator', $mo );
		}
	}


	/**
	 * Get permalink  structure REGEXP
	 *
	 * @param string $permalink_structure
	 *
	 * @return string
	 */
	public function get_permalink_filter( $permalink_structure = '' ) {
		if ( ! $permalink_structure ) {
			$permalink_structure = get_option( 'permalink_structure' );
		}
		$original_structure = $permalink_structure;
		foreach ( [
			'year'     => '\d{4}',
			'monthnum' => '\d{2}',
			'day'      => '\d{2}',
			'hour'     => '\d{2}',
			'minute'   => '\d{2}',
			'second'   => '\d{2}',
			'post_id'  => '\d+',
			'postname' => '[A-Za-z0-9\-%]+',
			'category' => '[A-Za-z0-9\-%/]+',
			'author'   => '[A-Za-z0-9\-%]+',
		] as $epmask => $regexp ) {
			$permalink_structure = str_replace( "%{$epmask}%", $regexp, $permalink_structure );
		}
		return apply_filters( 'ga_communicator_permalink_regexp', $permalink_structure, $original_structure );
	}

	/**
	 * Get popular posts in condition.
	 *
	 * @param array $query
	 * @param array $conditions
	 *
	 * @return null|\WP_Error|\WP_Query
	 */
	public function popular_posts( $query = [], $conditions = [] ) {
		$conditions = wp_parse_args( $conditions, [
			'path_regexp' => $this->get_permalink_filter(),
			'number'      => 10,
			'days_before' => 30,
			'offset_days' => 0,
			'start'       => '',
			'end'         => '',
		] );
		// Calculate range, number.
		// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		$end         = current_time( 'timestamp' ) - 60 * 60 * 24 * $conditions['offset_days'];
		$start       = $end - 60 * 60 * 24 * $conditions['days_before'];
		$date_ranges = [];
		foreach ( [
			[ 'startDate', $start, $conditions['start'] ],
			[ 'endDate', $end, $conditions['end'] ],
		] as list( $range_key, $timestamp, $specified_date ) ) {
			$date_ranges[ $range_key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/u', $specified_date ) ? $specified_date : date_i18n( 'Y-m-d', $timestamp );
		}
		$request = [
			'pageSize'   => (int) $conditions['number'],
			'dateRanges' => [ $date_ranges ],
		];
		// Create filter.
		$request['dimensionFilterClauses'] = [
			[
				'operator' => 'AND',
				'filters'  => [
					[
						'dimensionName' => 'ga:pagePath',
						'operator'      => 'REGEXP',
						'expressions'   => [
							$conditions['path_regexp'],
						],
					],
				],
			],
		];
		// Check response.
		$response = $this->get_report( $request );
		if ( ! $response ) {
			return null;
		}
		if ( is_wp_error( $response ) ) {
			return $response;
		}
		// Build results array.
		$post_ids = [];
		foreach ( $response as list( $path, $title, $pv ) ) {
			$id              = url_to_postid( $this->path_to_url( $path ) );
			$value           = [
				'pv'   => $pv,
				'rank' => 0,
			];
			$post_ids[ $id ] = $value;
		}
		foreach ( $post_ids as &$post ) {
			$more = 0;
			foreach ( $post_ids as $p ) {
				if ( $post['pv'] < $p['pv'] ) {
					$more++;
				}
			}
			$post['rank'] = $more + 1;
		}
		$query    = wp_parse_args( $query, [
			'post_type'           => 'post',
			'post_status'         => 'publish',
			'ignore_sticky_posts' => true,
		] );
		$query    = array_merge( $query, [
			'post__in' => array_keys( $post_ids ),
			'orderby'  => 'post__in',
		] );
		$wp_query = new \WP_Query( $query );
		if ( ! $wp_query->have_posts() ) {
			return null;
		}
		foreach ( $wp_query->posts as &$post ) {
			$post->pv   = $post_ids[ $post->ID ]['pv'];
			$post->rank = $post_ids[ $post->ID ]['rank'];
		}
		return $wp_query;
	}

	/**
	 * Convert path to post id.
	 *
	 * @param string $path
	 * @return string
	 */
	public function path_to_url( $path ) {
		list( $protocol, $domain ) = array_values( array_filter( explode( '/', home_url( '/' ) ) ) );
		return sprintf( '%s//%s/%s', $protocol, $domain, ltrim( $path, '/' ) );
	}

	/**
	 * Register all assets.
	 */
	public function register_assets() {
		$base_dir = dirname( dirname( __DIR__ ) );
		$config   = $base_dir . '/wp-dependencies.json';
		if ( ! file_exists( $config ) ) {
			return;
		}
		$json       = (array) json_decode( file_get_contents( $config ), true );
		$theme_root = get_theme_root();
		if ( false !== strpos( $base_dir, $theme_root ) ) {
			// This is inside theme.
			$base_url = str_replace( $theme_root, get_theme_root_uri(), $base_dir );
		} elseif ( false !== strpos( $base_dir, WP_PLUGIN_DIR ) ) {
			// This is inside plugins.
			$base_url = str_replace( WP_PLUGIN_DIR, WP_PLUGIN_URL, $base_dir );
		} elseif ( false !== strpos( $base_dir, WPMU_PLUGIN_DIR ) ) {
			// This is inside mu-plugins
			$base_url = str_replace( WPMU_PLUGIN_DIR, WPMU_PLUGIN_URL, $base_dir );
		} else {
			// Other, replace ABS Path.
			$base_url = str_replace( ABSPATH, home_url( '/' ), $base_dir );
		}
		$base_url = apply_filters( 'ga_communicator_assets_base_dir_url', $base_url, $base_dir );
		$base_url = untrailingslashit( $base_url );
		foreach ( $json as $setting ) {
			if ( ! $setting ) {
				continue;
			}
			$handle  = $setting['handle'];
			$url     = $base_url . '/' . $setting['path'];
			$version = $setting['hash'];
			$deps    = $setting['deps'];
			switch ( $setting['ext'] ) {
				case 'css':
					wp_register_style( $handle, $url, $deps, $version, $setting['media'] );
					break;
				case 'js':
					wp_register_script( $handle, $url, $deps, $version, $setting['footer'] );
					break;
			}
		}
	}

}
