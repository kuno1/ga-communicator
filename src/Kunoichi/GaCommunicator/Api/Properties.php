<?php

namespace Kunoichi\GaCommunicator\Api;

/**
 * Accounts api.
 *
 * @package ga-communicator
 */
class Properties extends Accounts {

	/**
	 * @inheritDoc
	 */
	protected function route() {
		return 'properties/(?P<account>[^/]+)';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_args( $method ) {
		return array_merge( parent::get_args( $method ), [
			'account' => [
				'required'          => true,
				'type'              => 'string',
				'description'       => 'Account ID.',
				'validate_callback' => function( $var ) {
					return ! empty( $var );
				},
			],
		] );
	}

	/**
	 * @inheritDoc
	 */
	public function callback( $request ) {
		$account  = $request->get_param( 'account' );
		$response = $this->settings->ga()->properties( $account );
		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( ! $response ) {
			return new \WP_Error( 'ga_communicator_error', __( 'Failed to get properties. Please check permission.', 'ga-communicator' ) );
		} else {
			$choices = [];
			foreach ( $response as $property ) {
				$choices[] = [
					'id'   => $property['id'],
					'name' => $property['name'],
				];
			}
			return new \WP_REST_Response( $choices );
		}
	}
}
