<?php

namespace Kunoichi\GaCommunicator\Utility;


use Hametuha\SingletonPattern\Singleton;
use Kunoichi\GaCommunicator\Screen\Settings;

/**
 * Script Renderer
 *
 * @package ga-communicator
 * @property-read Settings $setting Setting class instance.
 */
class ScriptRenderer extends Singleton {

	/**
	 * Constructor.
	 */
	protected function init() {
		add_action( 'wp_head', [ $this, 'render' ], 9999 );
		add_action( 'admin_head', [ $this, 'admin_render' ], 9999 );
		add_action( 'login_head', [ $this, 'login_render' ], 9999 );
	}

	/**
	 * Render analytics scripts.
	 */
	public function render() {
		$type = $this->setting->get_option( 'tag' );
		if ( ! $type ) {
			// No output.
			return;
		}
		$id  = $this->setting->get_option( 'property' );
		$additional = $this->setting->get_option( 'extra' );
		$tag = $this->setting->placeholder->tag( $type, $id, $additional );
		$replaced = $this->setting->placeholder->replace( $tag );
		echo $replaced;
	}

	/**
	 * Render script in admin screen.
	 */
	public function admin_render() {
		if ( in_array( 'admin', $this->get_places(), true ) ) {
			$this->render();
		}
	}

	/**
	 * Render script in login screen.
	 */
	public function login_render() {
		if ( in_array( 'login', $this->get_places(), true ) ) {
			$this->render();
		}
	}

	/**
	 * Get places to display.
	 *
	 * @return string[]
	 */
	public function get_places() {
		return array_filter( array_map( 'trim', explode( ',', $this->setting->get_option( 'place' ) ) ) );
	}

	/**
	 * Getter.
	 *
	 * @param string $name
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'setting':
				return Settings::get_instance();
			default:
				return null;
		}
	}


}
