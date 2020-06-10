<?php

namespace Kunoichi\GaCommunicator;


use cli\Table;
use Kunoichi\GaCommunicator\Utility\GaClientHolder;

class Command extends \WP_CLI_Command {
	
	use GaClientHolder;
	
	/**
	 * Get account information.
	 */
	public function accounts() {
		$accounts = $this->ga()->accounts();
		if ( is_wp_error( $accounts ) ) {
			\WP_CLI::error( $accounts->get_error_message() );
		}
		if ( empty( $accounts ) ) {
			\WP_CLI::error( __( 'No results found.', 'ga-communicator' ) );
		}
		$table = new Table();
		$table->setHeaders( [ 'ID', 'Name', 'Created' ] );
		$table->setRows( array_map( function( $account ) {
			return [ $account['id'], $account['name'], $account['created'] ];
		}, $accounts ) );
		$table->display();
		print_r( $accounts );
	}
	
	/**
	 * Get web properties.
	 *
	 * @synopsis <id>
	 * @param array $args
	 */
	public function properties( $args ) {
		list( $id ) = $args;
		\WP_CLI::line( sprintf( __( 'Getting web properties of %s...', 'ga-communicator' ), $id ) );
		$properties = $this->ga()->properties( $id );
		if ( is_wp_error( $properties ) ) {
			\WP_CLI::error( $properties->get_error_message() );
		}
		if ( empty( $properties ) ) {
			\WP_CLI::error( __( 'No results found.', 'ga-communicator' ) );
		}
		$table = new Table();
		$table->setHeaders( [ 'ID', 'Name', 'URL', 'Created' ] );
		$table->setRows( array_map( function( $property ) {
			return [ $property['id'], $property['name'], $property['websiteUrl'], $property['created'] ];
		}, $properties ) );
		$table->display();
	}
	
	/**
	 * Get profiles.
	 *
	 * @synopsis <account> <profile>
	 * @param array $args
	 */
	public function profiles( $args ) {
		list( $account, $profile ) = $args;
		$profiles = $this->ga()->profiles( $account, $profile );
		if ( is_wp_error( $profiles ) ) {
			\WP_CLI::error( $profiles->get_error_message() );
		}
		if ( empty( $profiles ) ) {
			\WP_CLI::error( __( 'No results found.', 'ga-communicator' ) );
		}
		$table = new Table();
		$table->setHeaders( [ 'ID', 'Name', 'URL', 'Created' ] );
		$table->setRows( array_map( function( $profile ) {
			return [ $profile['id'], $profile['name'], $profile['websiteUrl'], $profile['created'] ];
		}, $profiles ) );
		$table->display();
	}
	
	public function report() {
		$view_id = get_option( 'ga-profile' );
		if ( ! $view_id ) {
			\WP_CLI::error( __( 'Profile is not set.', 'ga-communicator' ) );
		}
		print_r( $this->ga()->get_report( [] ) );
	}
}
