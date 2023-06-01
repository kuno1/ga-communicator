<?php

namespace Kunoichi\GaCommunicator\Services;

use GuzzleHttp\Exception\GuzzleException;

/**
 * Abstract layer for GA4.
 */
trait Ga4Connector {

	use UniversalAnalyticsConnector;

	/**
	 * Get ednpoint for GA4 API.
	 *
	 * @param string $method Method name.
	 *
	 * @return string|\WP_Error
	 */
	private function ga4_endpoint( $method ) {
		$ga4_property = $this->setting->get_option( 'ga4-property' );
		if ( ! $ga4_property ) {
			return new \WP_Error( 'ga_communicator_api_error', __( 'GA4 property is not set.', 'ga-communicator' ), [
				'response' => 400,
			] );
		}
		return sprintf( 'https://analyticsdata.googleapis.com/v1beta/properties/%s:%s', $ga4_property, $method );
	}

	/**
	 * Get report from Google Analytics.
	 *
	 * @param array    $request  Request JSON to override.
	 * @param callable $callback Callback function.
	 *
	 * @return array[]|\WP_Error
	 */
	public function ga4_get_report( $request, $callback = null ) {
		try {
			$endpoint = $this->ga4_endpoint( 'runReport' );
			if ( is_wp_error( $endpoint ) ) {
				return $endpoint;
			}
			// GA4 is active.
			$json     = array_replace_recursive( $this->ga4_default_json(), $request );
			$response = $this->client->post( $endpoint, [
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'json'    => $json,
			] );
			$result   = json_decode( (string) $response->getBody(), true );
			if ( ! $result ) {
				return [];
			}
			if ( is_null( $callback ) ) {
				$callback = [ $this, 'ga4_parse_report_result' ];
			}
			$results = empty( $result['rows'] ) ? [] : $result['rows'];
			return array_map( $callback, $results );
		} catch ( GuzzleException $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getResponse()->getBody()->getContents(), [
				'response' => $e->getCode(),
			] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getMessage(), [
				'response' => $e->getCode(),
			] );
		}
	}

	/**
	 * Get request object.
	 *
	 * @see https://developers.google.com/analytics/devguides/reporting/data/v1/realtime-api-schema?hl=en
	 *
	 * @param array    $request  Request object. [ 'dimensions' => '', 'metrics' => '' ] Values should be array or csv value.
	 *
	 * @return array[]|\WP_Error
	 */
	public function ga4_realtime_report( $request = [] ) {
		try {
			$endpoint = $this->ga4_endpoint( 'runRealtimeReport' );
			if ( is_wp_error( $endpoint ) ) {
				return $endpoint;
			}
			$request = wp_parse_args( $request, [
				'dimensions' => 'country',
				'metrics'    => 'activeUsers,screenPageViews',
			] );
			foreach ( [ 'dimensions', 'metrics' ] as $key ) {
				$values = $request[ $key ];
				if ( ! is_array( $request[ $key ] ) ) {
					$values = array_filter( array_map( 'trim', explode( ',', $values ) ) );
				}
				$request_value = [];
				foreach ( $values as $v ) {
					$request_value[] = [
						'name' => $v,
					];
				}
				$request[ $key ] = $request_value;
			}
			$response = $this->client->post( $endpoint, [
				'headers' => [
					'Content-Type' => 'application/json',
				],
				'json'    => $request,
			] );
			$result   = json_decode( (string) $response->getBody(), true );
			if ( ! $result['rows'] ) {
				return [];
			}
			$response = [];
			foreach ( $result['rows'] as $row ) {
				$parsed = [];
				foreach ( [
					'dimensionValues' => 'dimensions',
					'metricValues'    => 'metrics',
				] as $key => $label ) {
					$parsed[ $label ] = array_map( function( $v ) {
						return $v['value'];
					}, $row[ $key ] );
				}
				$response[] = $parsed;
			}
			return $response;
		} catch ( GuzzleException $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getResponse()->getBody()->getContents(), [
				'response' => $e->getCode(),
			] );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getMessage(), [
				'response' => $e->getCode(),
			] );
		}
	}

	/**
	 * Parse result from API.
	 *
	 * @param array $row Result.
	 * @return array
	 */
	public function ga4_parse_report_result( $row ) {
		return [ $row['dimensionValues'][0]['value'], $row['dimensionValues'][1]['value'], $row['metricValues'][0]['value'] ];
	}

	/**
	 * Get default JSON request for GA4.
	 *
	 * @return \array[][]
	 */
	public function ga4_default_json() {
		$json = $this->default_json_base();
		return array_merge( $json, [
			'dimensions' => [
				[
					'name' => 'pagePath',
				],
				[
					'name' => 'pageTitle',
				],
			],
			'metrics'    => [
				[
					'name' => 'screenPageViews',
				],
			],
			'orderBys'   => [
				[
					'metric' => [
						'metricName' => 'screenPageViews',
					],
					'desc'   => true,
				],
			],
			'limit'      => 10,
		] );
	}

	/**
	 * Get conditions.
	 *
	 * @see https://developers.google.com/analytics/devguides/reporting/data/v1/rest/v1beta/FilterExpression
	 * @param array $conditions
	 * @return array
	 */
	public function ga4_popular_posts_args( array $conditions ) {
		$conditions = $this->get_date_range_condition( $conditions );
		$args       = [
			'limit'           => (int) $conditions['number'],
			'dateRanges'      => [
				[
					'startDate' => $conditions['start'],
					'endDate'   => $conditions['end'],
				],
			],
			'dimensionFilter' => [
				'filter' => [
					'fieldName'    => 'pagePath',
					'stringFilter' => [
						'matchType' => 'PARTIAL_REGEXP',
						'value'     => $conditions['path_regexp'],
					],
				],
			],
		];
		return apply_filters( 'ga_communicator_ga4_popular_posts_args', $args, $conditions );
	}
}
