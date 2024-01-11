<?php

namespace Kunoichi\GaCommunicator\Api;


/**
 * Profiles list.
 *
 * @package ga-communicator
 * @deprecated 3.5.0
 */
class Profiles extends Properties {

	/**
	 * @inheritDoc
	 */
	protected function route() {
		return 'profiles/(?P<account>[^/]+)/(?P<property>[^/]+)';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_args( $method ) {
		return array_merge( parent::get_args( $method ), [
			'property' => [
				'required'          => true,
				'type'              => 'string',
				'description'       => 'Web property ID',
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
		$property = $request->get_param( 'property' );
		$response = $this->settings->ga()->profiles( $account, $property );
		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( ! $response ) {
			return new \WP_Error( 'ga_communicator_error', __( 'Failed to get profiles. Please check permission.', 'ga-communicator' ) );
		} else {
			$choices = [];
			foreach ( $response as $profile ) {
				$choices[] = [
					'id'   => $profile['id'],
					'name' => $profile['name'],
				];
			}
			return new \WP_REST_Response( $choices );
		}
	}
}
