<?php

namespace Kunoichi\GaCommunicator\Services;


trait Ga4Connector {

	use AbstractApiConnector;

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
			$ga4_property = $this->setting->get_option( 'ga4-property' );
			$json         = array_replace_recursive( $this->ga4_default_json(), $request );
			$headers      = [
				'Content-Type' => 'application/json',
			];
			// GA4 is active.
			$endpoint = sprintf( 'https://analyticsdata.googleapis.com/v1beta/properties/%s:runReport', $ga4_property );
			$response = $this->client->post( $endpoint, [
				'headers' => $headers,
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
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getResponse()->getBody()->getContents(), [
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
		return $args;
	}
}
