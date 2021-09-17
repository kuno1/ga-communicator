<?php

namespace Kunoichi\GaCommunicator\Api;


use Kunoichi\GaCommunicator\Pattern\RestApiOptions;

/**
 * Rest API.
 *
 * @package ga-communicator
 */
class Accounts extends RestApiOptions {

	/**
	 * @inheritDoc
	 */
	protected function route() {
		return 'accounts';
	}

	/**
	 * @inheritDoc
	 */
	protected function get_args( $method ) {
		return [];
	}

	/**
	 * @inheritDoc
	 */
	public function callback( $request ) {
		$response = $this->settings->ga()->accounts();
		if ( is_wp_error( $response ) ) {
			return $response;
		} elseif ( ! $response ) {
			return new \WP_Error( 'ga_communicator_error', __( 'No account found. Please check your service account is registered as Google Analytics user.', 'ga-communicator' ) );
		} else {
			$choices = [];
			foreach ( $response as $account ) {
				$choices[] = [
					'id'   => $account['id'],
					'name' => $account['name'],
				];
			}
			return new \WP_REST_Response( $choices );
		}
	}
}
