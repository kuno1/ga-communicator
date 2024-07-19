<?php

namespace Kunoichi\GaCommunicator\Pattern;


/**
 * Rest API Pattern.
 *
 * @package ga-communicator
 */
abstract class RestApiPattern extends Singleton {

	protected $namespace = 'ga/v1';

	/**
	 * Get route.
	 *
	 * @return string
	 */
	abstract protected function route();

	/**
	 * Register hooks.
	 */
	protected function init() {
		add_action( 'rest_api_init', [ $this, 'register_rest' ] );
	}

	/**
	 * Should register rest?
	 *
	 * @return bool
	 */
	protected function should_register() {
		return true;
	}

	/**
	 * Register rest route.
	 */
	public function register_rest() {
		if ( ! $this->should_register() ) {
			return;
		}
		// Register REST API.
		register_rest_route( $this->namespace, $this->route(), array_map( function ( $method ) {
			return [
				'methods'             => $method,
				'args'                => $this->get_args( $method ),
				'permission_callback' => [ $this, 'permission_callback' ],
				'callback'            => [ $this, 'callback' ],
			];
		}, $this->get_methods() ) );
	}

	/**
	 * Get methods.
	 *
	 * @return string[]
	 */
	protected function get_methods() {
		return [ 'GET' ];
	}

	/**
	 * Get arguments.
	 *
	 * @param string $method GET, POST, DELETE, PUT
	 * @return array
	 */
	abstract protected function get_args( $method );

	/**
	 * Callback.
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return \WP_Error|\WP_REST_Response
	 */
	abstract public function callback( $request );

	/**
	 * Permission callback
	 *
	 * @param \WP_REST_Request $request Request object.
	 * @return bool|\WP_Error
	 */
	public function permission_callback( $request ) {
		return true;
	}
}
