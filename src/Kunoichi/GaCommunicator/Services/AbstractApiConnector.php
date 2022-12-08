<?php

namespace Kunoichi\GaCommunicator\Services;


use Google\Auth\Credentials\ServiceAccountCredentials;
use Google\Auth\Middleware\AuthTokenMiddleware;
use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use Kunoichi\GaCommunicator\Screen\Settings;

/**
 * Google API connector.
 *
 * @property-read Client   $client
 * @property-read Settings $setting
 */
trait AbstractApiConnector {

	private $_client = null;

	private $client_initialized = false;

	/**
	 * Make request.
	 *
	 * @param string $url
	 *
	 * @return array
	 */
	protected function request( $url ) {
		$response = $this->client->get( $url );
		$body     = (string) $response->getBody();
		$json     = json_decode( $body, true );
		if ( ! $json ) {
			throw new \Exception( __( 'Failed to get API response. Please try again later.', 'ga-communicator' ), 500 );
		}
		return $json;
	}

	/**
	 * Default JSON base.
	 *
	 * @return \array[][]
	 */
	protected function default_json_base() {
		return [
			'dateRanges' => [
				[
					// phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
					'startDate' => date_i18n( 'Y-m-d', current_time( 'timestamp' ) - 60 * 60 * 24 * 30 ),
					'endDate'   => date_i18n( 'Y-m-d' ),
				],
			],
		];
	}

	/**
	 * Getter
	 *
	 * @param string $name Property name.
	 *
	 * @return mixed
	 * @throws \Exception
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'client':
				if ( ! $this->client_initialized ) {
					$scopes = apply_filters( 'ga_communicator_api_scopes', [ 'https://www.googleapis.com/auth/analytics.readonly' ] );
					$key    = $this->setting->service_key();
					if ( ! $key ) {
						throw new \Exception( __( 'Service key is not set.', 'ga-communicator' ), 500 );
					}
					$json = json_decode( $key, true );
					if ( ! $json ) {
						throw new \Exception( __( 'Invalid API service key.', 'ga-communicator' ), 500 );
					}
					$sa         = new ServiceAccountCredentials( $scopes, $json );
					$middleware = new AuthTokenMiddleware( $sa );
					$stack      = HandlerStack::create();
					$stack->push( $middleware );
					$this->_client = new Client( [
						'handler'  => $stack,
						'base_uri' => 'https://www.googleapis.com',
						'auth'     => 'google_auth',
					]);
				}
				return $this->_client;
				break;
			case 'setting':
				return Settings::get_instance();
			default:
				return null;
		}
	}
}
