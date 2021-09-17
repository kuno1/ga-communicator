<?php

namespace Kunoichi\GaCommunicator\Pattern;


use Kunoichi\GaCommunicator\Screen\Settings;

/**
 * Base for REST API
 *
 * @package ga-communicator
 * @property-read Settings $settings
 */
abstract class RestApiOptions extends RestApiPattern {

	/**
	 * @inheritDoc
	 */
	protected function should_register() {
		return ! $this->settings->should_network_activate() || is_main_site();
	}

	/**
	 * Get permission.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( $this->settings->capability );
	}

	/**
	 * Getter.
	 *
	 * @param string $name Name.
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'settings':
				return Settings::get_instance();
			default:
				return null;
		}
	}
}
