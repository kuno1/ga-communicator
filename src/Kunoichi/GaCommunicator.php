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
	 * @return array
	 */
	public function get_report( $request, $callback = null ) {
		try {
			$response = $this->client->post( 'https://analyticsreporting.googleapis.com/v4/reports:batchGet', [
				'json' => [
					'reportRequests' => array_merge( [
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
					], $request ),
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
			print_r( $e->getMessage() );
		}
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
