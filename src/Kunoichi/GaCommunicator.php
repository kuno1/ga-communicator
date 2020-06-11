<?php

namespace Kunoichi;


use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Hametuha\SingletonPattern\Singleton;
use Hametuha\SingletonPattern\BulkRegister;

/**
 * Google Analytics Communicator
 *
 * @property-read Client $client
 * @package ga-communicator
 */
class GaCommunicator extends Singleton {

	private $_client = null;
	
	private $client_initialized = false;
	
	protected function init() {
		// Register command for CLI.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'ga', GaCommunicator\Command::class );
		}
		BulkRegister::enable( "Kunoichi\\GaCommunicator\\Options", __DIR__ . '/GaCommunicator/Options' );
	}
	
	/**
	 * Make request.
	 *
	 * @param string $url
	 *
	 * @return array
	 */
	protected function request( $url ) {
		$response = $this->client->get( $url );
		$body = (string) $response->getBody();
		$json = json_decode( $body, true );
		if ( ! $json ) {
			throw new \Exception( __( 'Failed to API response. Please try again later.', 'ga-communicator' ), 500 );
		}
		return $json;
	}
	
	/**
	 * Get analytics report
	 *
	 * @param array $request
	 * @param callable $callback
	 * @return array|\WP_Error
	 */
	public function get_report( $request = [], $callback = null ) {
		try {
			$json = array_replace_recursive( [
				'viewId' => get_option( 'ga-profile' ),
				'dimensions' => [
					[
						'name' => 'ga:pagePath',
					],
					[
						'name' => 'ga:pageTitle',
					],
				],
				'metrics' => [
					[
						'expression' => 'ga:pageviews',
						'formattingType' => 'INTEGER',
					],
				],
				'orderBys' => [
					[
						'fieldName' => 'ga:pageviews',
						'orderType' => 'VALUE',
						'sortOrder' => 'DESCENDING',
					]
				],
				'pageSize' => 10,
				'dateRanges' => [
					[
						'startDate' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) - 60 * 60 * 24 * 30 ),
						'endDate' =>  date_i18n( 'Y-m-d' ),
					],
				],
			], $request );
			$response = $this->client->post( 'https://analyticsreporting.googleapis.com/v4/reports:batchGet', [
				'json' => [
					'reportRequests' => $json,
				],
			] );
			$result = json_decode( (string) $response->getBody(), true );
			if ( ! $result ) {
				return [];
			}
			if ( is_null( $callback ) ) {
				$callback = [ $this, 'parse_report_result' ];
			}
			return array_map( $callback, $result['reports'][0]['data']['rows'] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getMessage(), [
				'response' => $e->getCode(),
			] );
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
			'year' => '\d{4}',
			'monthnum' => '\d{2}',
			'day' => '\d{2}',
			'hour' => '\d{2}',
			'minute' => '\d{2}',
			'second' => '\d{2}',
			'post_id' => '\d+',
			'postname' => '[A-Za-z0-9\-%]+',
			'category' => '[A-Za-z0-9\-%/]+',
			'author' => '[A-Za-z0-9\-%]+',
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
		$end   = current_time( 'timestamp' ) - 60 * 60 * 24 * $conditions['offset_days'];
		$start = $end - 60 * 60 * 24 * $conditions['days_before'];
		$date_ranges = [];
		foreach ( [
			[ 'startDate', $start, $conditions['start'] ],
			[ 'endDate', $end, $conditions['end'] ],
		] as list( $range_key, $timestamp, $specified_date ) ) {
			$date_ranges[ $range_key ] = preg_match( '/^\d{4}-\d{2}-\d{2}$/u', $specified_date ) ? $specified_date : date_i18n( 'Y-m-d', $timestamp );
		}
		$request = [
			'pageSize' => (int) $conditions['number'],
			'dateRanges' => [ $date_ranges ],
		];
		// Create filter.
		$request['dimensionFilterClauses'] = [
			[
				'operator' => 'AND',
				'filters' => [
					[
						'dimensionName' => 'ga:pagePath',
						'operator' => 'REGEXP',
						'expressions' => [
							$conditions['path_regexp']
						],
					],
				],
			],
		];
		$response = $this->get_report( $request );
		if ( ! $response || is_wp_error( $response ) ) {
			return $response ? null : $response;
		}
		// Build results array.
		$post_ids = [];
		foreach( $response as list( $path, $title, $pv ) ) {
			$id = url_to_postid( home_url( $path ) );
			$value = [
				'pv' => $pv,
				'rank' => 0,
			];
			$post_ids[] = $value;
			// $post_ids[ $id ] = $value;
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
		$query = wp_parse_args( $query, [
			'post_type' => 'post',
			'post_status' => 'publish',
		] );
		$query = array_merge( $query, [
			'post__in' => array_keys( $post_ids ),
			'orderby' => 'post__in',
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
	 * Parser report result.
	 *
	 * @param array $row
	 * @return array
	 */
	public function parse_report_result( $row ) {
		return [ $row['dimensions'][0], $row['dimensions'][1], $row['metrics'][0]['values'][0] ];
	}
	
	/**
	 * Get account information.
	 *
	 * @return array[]|\WP_Error
	 */
	public function accounts() {
		try {
			$result = $this->request( 'https://www.googleapis.com/analytics/v3/management/accounts' );
			return $result['items'];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getMessage(), [
				'code' => $e->getCode(),
			] );
		}
	}
	
	/**
	 * Get web properties.
	 *
	 * @param string $account
	 *
	 * @return array[]|\WP_Error
	 */
	public function properties( $account ) {
		try {
			$result = $this->request( sprintf( 'https://www.googleapis.com/analytics/v3/management/accounts/%s/webproperties', $account ) );
			return $result['items'];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getMessage(), [
				'code' => $e->getCode(),
			] );
		}
	}
	
	/**
	 * Get profiles
	 *
	 * @param string $account
	 * @param string $property
	 *
	 * @return array[]|\WP_Error
	 */
	public function profiles( $account, $property ) {
		try {
			$result = $this->request( sprintf( 'https://www.googleapis.com/analytics/v3/management/accounts/%s/webproperties/%s/profiles', $account, $property ) );
			return $result['items'];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getMessage(), [
				'code' => $e->getCode(),
			] );
		}
	}
	
	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'client':
				if ( ! $this->client_initialized ) {
					$scopes = apply_filters( 'ga_communicator_api_scopes', [ 'https://www.googleapis.com/auth/analytics.readonly' ] );
					$key    = get_option( 'ga-service-key' );
					if ( ! $key || ! ( $json = json_decode( $key, true ) ) ) {
						throw new \Exception( __( 'Invalid API service key.', 'ga-communicator' ), 500 );
					}
					$sa = new ServiceAccountCredentials( $scopes, $json );
					$middleware = new AuthTokenMiddleware( $sa );
					$stack = HandlerStack::create();
					$stack->push( $middleware );
					$this->_client = new Client( [
						'handler' => $stack,
						'base_uri' => 'https://www.googleapis.com',
						'auth' => 'google_auth',
					]);
				}
				return $this->_client;
				break;
			default:
				return null;
		}
	}
}
