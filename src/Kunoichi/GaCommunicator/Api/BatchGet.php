<?php

namespace Kunoichi\GaCommunicator\Api;


use Kunoichi\GaCommunicator\Pattern\RestApiOptions;

/**
 * REST API for Batch Get
 *
 * @package
 */
class BatchGet extends RestApiOptions {

	/**
	 * @inheritDoc
	 */
	protected function route() {
		return 'batch';
	}

	/**
	 * @inheritDoc
	 */
	public function permission_callback( $request ) {
		return apply_filters( 'ga_api_permission_batch_get', parent::permission_callback( $request ), $request );
	}

	/**
	 * @inheritDoc
	 */
	protected function get_methods() {
		return [ 'POST' ];
	}

	/**
	 * @inheritDoc
	 */
	protected function get_args( $method ) {
		return [
			'data' => [
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => function( $var ) {
					return (bool) json_decode( $var, true ) ? true : new \WP_Error( 'ga_rest_api_error', __( 'data attributes must be valid JSON format.', 'ga-communicator' ), [
						'status' => 400,
					] );
				},
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function callback( $request ) {
		$data = json_decode( $request->get_param( 'data' ), true );
		if ( $this->settings->using_ga4 ) {
			$report = $this->settings->ga()->ga4_get_report( $data, function ( $row ) {
				return $row;
			} );
		} else {
			$report = $this->settings->ga()->get_report( $data, function ( $row ) {
				return $row;
			} );
		}
		return is_wp_error( $report ) ? $report : new \WP_REST_Response( $report );
	}
}
