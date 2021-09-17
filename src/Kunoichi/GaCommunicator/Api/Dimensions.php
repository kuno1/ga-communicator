<?php

namespace Kunoichi\GaCommunicator\Api;


use Kunoichi\GaCommunicator\Pattern\RestApiOptions;

/**
 * Get dimensions.
 *
 * @package ga-communicator
 */
class Dimensions extends RestApiOptions {

	/**
	 * @inheritDoc
	 */
	protected function route() {
		return 'dimensions';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_args( $method ) {
		return [
			'account'  => [
				'type'    => 'string',
				'default' => '',
			],
			'property' => [
				'type'    => 'string',
				'default' => '',
			],
		];
	}

	/**
	 * @inheritDoc
	 */
	public function callback( $request ) {
		$account  = $request->get_param( 'account' ) ?: $this->settings->get_option( 'account' );
		$property = $request->get_param( 'property' ) ?: $this->settings->get_option( 'property' );
		if ( ! $account || ! $property ) {
			return new \WP_Error(
				'rest_api_error',
				sprintf(
					// translators: %1$s is account, %2$s property.
					__( 'Account and Property is required. (%1$s/%2$s)', 'ga-communicator' ),
					$account,
					$property
				),
				[
					'status' => 400,
				]
			);
		}
		$dimensions = $this->settings->ga()->dimensions( $account, $property );
		if ( is_wp_error( $dimensions ) ) {
			return $dimensions;
		}
		return new \WP_REST_Response( $dimensions );
	}
}
