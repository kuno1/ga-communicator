<?php

namespace Kunoichi\GaCommunicator\Options;


use Hametuha\OptionPattern\Settings;
use Kunoichi\GaCommunicator\Utility\GaClientHolder;

class Accounts extends Settings {
	
	use GaClientHolder;
	
	protected function get_page() {
		return 'ga-communicator';
	}
	
	protected function get_section() {
		return 'ga-communicator-accounts';
	}
	
	protected function section_setting() {
		return [
			'label' => __( 'Account Setting', 'ga-communicator' ),
			'callback' => function() {
				printf( '<p class="description">%s</p>', esc_html__( 'If you set credentials, please choose Google Analytics account of your site.', 'ga-communicator' ) );
			},
			'priority' => 100,
		];
	}
	
	protected function get_fields() {
		$accounts = [
			'id' => 'ga-account',
			'title' => __( 'Account', 'ga-communicator' ),
			'type' => 'select',
			'choices' => [
				'' => __( 'Choose Account', 'ga-communicator' ),
			]
		];
		$response = $this->ga()->accounts();
		if ( ! $accounts || is_wp_error( $response ) ) {
			$accounts['help'] = __( 'Failed to get accounts. Please save valid service account.', 'ga-communicator' );
		} else {
			foreach ( $response as $account ) {
				$accounts['choices'][ $account['id'] ] = $account['name'];
			}
		}
		// Get properties.
		$properties = [
			'id' => 'ga-property',
			'title' => __( 'Property', 'ga-communicator' ),
			'type' => 'select',
			'choices' => [
				'' => __( 'Choose Property', 'ga-communicator' ),
			],
		];
		$account_id = get_option( 'ga-account' );
		if ( ! $account_id ) {
			$properties['help'] = __( 'To display available properties, you should save account id.', 'ga-communicator' );
		} else {
			$response = $this->ga()->properties( $account_id );
			if ( ! $response ) {
				$properties['help'] = __( 'Failed to get properties. Please check permission.', 'ga-communicator' );
			} else {
				foreach ( $response as $property ) {
					$properties['choices'][ $property['id'] ] = $property['name'];
				}
			}
		}
		// Get profile.
		$profiles = [
			'id' => 'ga-profile',
			'title' => __( 'Profile', 'ga-communicator' ),
			'type' => 'select',
			'choices' => [
				'' => __( 'Choose Profile', 'ga-communicator' ),
			],
		];
		$property_id = get_option( 'ga-property' );
		if ( ! $account_id  || ! $property_id) {
			$profiles['help'] = __( 'To display available profiles, you should save account id and property id.', 'ga-communicator' );
		} else {
			$response = $this->ga()->profiles( $account_id, $property_id );
			if ( ! $response ) {
				$profiles['help'] = __( 'Failed to get profiles. Please check permission.', 'ga-communicator' );
			} else {
				foreach ( $response as $profile ) {
					$profiles['choices'][ $profile['id'] ] = $profile['name'];
				}
			}
		}
		
		return [
			$accounts,
			$properties,
			$profiles,
		];
	}
	
	
}
