<?php

namespace Kunoichi\GaCommunicator\Services;


/**
 * Connect to Universal analytics.
 *
 * @deprecated Google deprecates Universal Analytics in June 2023.
 */
trait UniversalAnalyticsConnector {

	use AbstractApiConnector;

	/**
	 * Get account information.
	 *
	 * @deprecated 3.0.0
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
	 * @deprecated 3.0.0
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
	 * @deprecated 3.0.0
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
	 * Get dimensions.
	 *
	 * @deprecated 3.0.0
	 * @retur array
	 */
	public function dimensions( $account, $property ) {
		try {
			$result = $this->request( sprintf( 'https://www.googleapis.com/analytics/v3/management/accounts/%s/webproperties/%s/customDimensions', $account, $property ) );
			return isset( $result['items'] ) ? $result['items'] : [];
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getMessage(), [
				'code' => $e->getCode(),
			] );
		}
	}


	/**
	 * Get analytics report
	 *
	 * @deprecated 3.0.0
	 * @param array $request
	 * @param callable $callback
	 * @return array|\WP_Error
	 */
	public function get_report( $request = [], $callback = null ) {
		try {
			$json    = array_merge( $this->default_json(), $request );
			$headers = [
				'Content-Type' => 'application/json',
			];
			// Old API.
			$endpoint       = 'https://analyticsreporting.googleapis.com/v4/reports:batchGet';
			$json['viewId'] = $this->setting->get_option( 'profile' );
			$response       = $this->client->post( $endpoint, [
				'headers' => $headers,
				'json'    => [
					'reportRequests' => $json,
				],
			] );
			$result         = json_decode( (string) $response->getBody(), true );
			if ( ! $result ) {
				return [];
			}
			if ( is_null( $callback ) ) {
				$callback = [ $this, 'parse_report_result' ];
			}
			$results = empty( $result['reports'][0]['data']['rows'] ) ? [] : $result['reports'][0]['data']['rows'];
			return array_map( $callback, $results );
		} catch ( \Exception $e ) {
			return new \WP_Error( 'ga_communicator_api_error', $e->getResponse()->getBody()->getContents(), [
				'response' => $e->getCode(),
			] );
		}
	}


	/**
	 * Parser report result.
	 *
	 * @deprecated 3.0.0
	 * @param array $row    Row.
	 * @return array
	 */
	public function parse_report_result( $row ) {
		return [ $row['dimensions'][0], $row['dimensions'][1], $row['metrics'][0]['values'][0] ];
	}

	/**
	 * Default JSON.
	 *
	 * @deprecated 3.0.0
	 * @return array
	 */
	public function default_json() {
		$json = array_merge( $this->default_json_base(), [
			'dimensions' => [
				[
					'name' => 'ga:pagePath',
				],
				[
					'name' => 'ga:pageTitle',
				],
			],
			'metrics'    => [
				[
					'expression'     => 'ga:pageviews',
					'formattingType' => 'INTEGER',
				],
			],
			'orderBys'   => [
				[
					'fieldName' => 'ga:pageviews',
					'orderType' => 'VALUE',
					'sortOrder' => 'DESCENDING',
				],
			],
			'pageSize'   => 10,
		] );
		return $json;
	}


	/**
	 * Get conditions.
	 *
	 * @deprecated 3.0.0
	 * @param array $conditions
	 * @return array
	 */
	public function popular_posts_args( array $conditions ) {
		$conditions = $this->get_date_range_condition( $conditions );
		$args       = [
			'pageSize'               => (int) $conditions['number'],
			'dateRanges'             => [
				[
					'startDate' => $conditions['start'],
					'endDate'   => $conditions['end'],
				],
			],
			'dimensionFilterClauses' => [
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
			],
		];
		return apply_filters( 'ga_communicator_popular_posts_args', $args, $conditions );
	}
}
