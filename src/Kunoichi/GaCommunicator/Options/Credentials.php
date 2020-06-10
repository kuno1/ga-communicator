<?php

namespace Kunoichi\GaCommunicator\Options;


use Hametuha\OptionPattern\Settings;

/**
 * Credential information.
 *
 * @package ga-communicator
 */
class Credentials extends Settings {

	public function page_setting() {
		return [
			'title' => __( 'Google Analytics Setting', 'ga-communicator' ),
			'parent' => 'options-general.php',
		];
	}
	
	protected function get_page() {
		return 'ga-communicator';
	}
	
	protected function get_section() {
		return 'ga-communicator';
	}
	
	protected function section_setting() {
		return [
			'label'    => __( 'Credentials', 'ga-communicator' ),
			'callback' => function () {
				printf( '<p class="description">%s</p>', esc_html__( 'Please enter Google Analytics Credentials', 'ga-communicator' ) );
			},
		];
	}
	
	protected function get_fields() {
		return [
			[
				'id' => 'ga-service-key',
				'title' => __( 'Service Account Key', 'ga-communicator' ),
				'type' => 'textarea',
				'help' => sprintf( __( 'You can get a service account key in JSON format from Google API Library. For more detail, please check the <a href="">document</a>.', 'ga-communicator' ), 'https://developers.google.com/analytics/devguides/reporting/core/v4/authorization' ),
				'placeholder' => 'e.g. {"type": "service_account", "project_id": "example.com:api-project-000000","private_key_id": "bf8ea16a0978be19b5ce9780c3482202c145e9eb892c"......',
			],
		];
	}
	
	
}
