<?php

namespace Kunoichi\GaCommunicator;


use cli\Table;
use Kunoichi\GaCommunicator;
use Kunoichi\GaCommunicator\Screen\Settings;
use Kunoichi\GaCommunicator\Utility\GaClientHolder;

/**
 * Command utility for GA Communicator for debugging.
 *
 * @package ga-communicator
 */
class Command extends \WP_CLI_Command {

	use GaClientHolder;

	/**
	 * Raise error.
	 *
	 * @return void
	 */
	protected function do_it_wrong() {
		\WP_CLI::warning( __( 'Universal Analytics stops on June 2023. Please consider switching to GA4.', 'ga-communicator' ) );
	}

	/**
	 * Get account information.
	 *
	 * @deprecated Google stops universal analytics at June 2023.
	 */
	public function accounts() {
		$this->do_it_wrong();
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
	}

	/**
	 * Get web properties.
	 *
	 * @deprecated Google stops universal analytics at June 2023.
	 * @synopsis <id>
	 * @param array $args
	 */
	public function properties( $args ) {
		$this->do_it_wrong();
		list( $id ) = $args;
		\WP_CLI::line( sprintf(
			// translators: %s is property id.
			__( 'Getting web properties of %s...', 'ga-communicator' ),
			$id
		) );
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
	 * @deprecated Google stops universal analytics at June 2023.
	 * @synopsis <account> <profile>
	 * @param array $args
	 */
	public function profiles( $args ) {
		$this->do_it_wrong();
		list( $account, $profile ) = $args;
		$profiles                  = $this->ga()->profiles( $account, $profile );
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

	/**
	 * Get custom dimensions.
	 *
	 * @deprecated Google stops universal analytics at June 2023.
	 * @synopsis <account> <profile>
	 * @param array $args
	 */
	public function dimensions( $args ) {
		$this->do_it_wrong();
		list( $account, $profile ) = $args;
		$dimensions                = $this->ga()->dimensions( $account, $profile );
		if ( is_wp_error( $dimensions ) ) {
			\WP_CLI::error( $dimensions->get_error_message() );
		}
		if ( empty( $dimensions ) ) {
			\WP_CLI::warning( __( 'No custom dimensions set.', 'ga-communicator' ) );
			exit;
		}
		$table = new Table();
		$table->setHeaders( [ 'ID', 'Label', 'Index', 'Scope', 'Created' ] );
		foreach ( $dimensions as $dimension ) {
			$table->addRow( [ $dimension['id'], $dimension['name'], $dimension['index'], $dimension['scope'], $dimension['created'] ] );
		}
		$table->display();
	}

	/**
	 * Get report and display it in table.
	 *
	 * @deprecated Google stops universal analytics at June 2023.
	 * @synopsis [--start=<start>] [--end=<end>] [--filter=<filter>]
	 * @param array $args
	 * @param array $assoc
	 */
	public function report( $args, $assoc ) {
		$this->do_it_wrong();
		$view_id = Settings::get_instance()->get_option( 'profile' );
		if ( ! $view_id ) {
			\WP_CLI::error( __( 'Profile is not set.', 'ga-communicator' ) );
		}
		$replace = [];
		// Set date ranges.
		$date_ranges = [];
		if ( ! empty( $assoc['start'] ) ) {
			$date_ranges['startDate'] = $assoc['start'];
		}
		if ( ! empty( $assoc['end'] ) ) {
			$date_ranges['endDate'] = $assoc['end'];
		}
		if ( ! empty( $date_ranges ) ) {
			$replace['dateRanges'] = [ $date_ranges ];
		}
		// Set filter
		if ( ! empty( $assoc['filter'] ) ) {
			list( $dimension, $operator, $expressions ) = explode( ';', $assoc['filter'] );
			$replace['dimensionFilterClauses']          = [
				[
					'operator' => 'AND',
					'filters'  => [
						[
							'dimensionName' => $dimension,
							'operator'      => $operator,
							'expressions'   => explode( ',', $expressions ),
						],
					],
				],
			];
		}
		$this->response_to_table( $this->ga()->get_report( $replace ) );
	}

	/**
	 * Get GA4 data for connection test.
	 *
	 * @synopsis [--start=<start>] [--end=<end>]
	 * @param array $args
	 * @param array $assoc
	 */
	public function ga4_report( $args, $assoc ) {
		$replace = [];
		// Set date ranges.
		$date_ranges = [];
		if ( ! empty( $assoc['start'] ) ) {
			$date_ranges['startDate'] = $assoc['start'];
		}
		if ( ! empty( $assoc['end'] ) ) {
			$date_ranges['endDate'] = $assoc['end'];
		}
		if ( ! empty( $date_ranges ) ) {
			$replace['dateRanges'] = [ $date_ranges ];
		}
		$this->response_to_table( $this->ga()->ga4_get_report( $replace ) );
	}

	/**
	 * Display response to table.
	 *
	 * @param array|\WP_Error $response Response object.
	 *
	 * @return void
	 * @throws \WP_CLI\ExitException
	 */
	protected function response_to_table( $response ) {
		if ( is_wp_error( $response ) ) {
			\WP_CLI::error( $response->get_error_message() );
		}
		$table = new Table();
		$table->setHeaders( [ '#', 'Title', 'Path', 'PV', 'Valid' ] );
		foreach ( $response as $index => $row ) {
			list( $path, $title, $pv ) = $row;
			$table->addRow( [ $index + 1, $title, $path, $pv, url_to_postid( home_url( $path ) ) ? 'Yes' : '---' ] );
		}
		$table->display();
	}

	/**
	 * Retrieve popular posts list.
	 *
	 * ## OPTIONS
	 *
	 * : [<regexp>]
	 * Regular expression to filter path.
	 * Default permalink structure.
	 *
	 * : [--start=<start>]
	 * Start days in YYYY-MM-DD format. Default 30 days ago.
	 *
	 * : [--end=<end>]
	 * End days in YYYY-MM-DD format. Default today.
	 *
	 * : [--days_before=<days_before>]
	 * Default 30 days ago.
	 *
	 * : [--offset_days=<offset_days>]
	 * Offset days. Default 0.
	 *
	 * @deprecated Google stops universal analytics at June 2023.
	 * @param array $args
	 * @param array $assoc
	 * @synopsis [<regexp>] [--start=<start>] [--end=<end>] [--days_before=<days_before>]  [--offset_days=<offset_days>]
	 */
	public function popular_posts( $args, $assoc ) {
		$request = [];
		if ( ! empty( $args[0] ) ) {
			$request['path_regexp'] = $args[0];
		}
		foreach ( [ 'start', 'end', 'days_before', 'offset_days' ] as $key ) {
			if ( ! empty( $assoc[ $key ] ) ) {
				$request[ $key ] = $assoc[ $key ];
			}
		}
		$query = $this->ga()->popular_posts( [], $request );
		if ( is_wp_error( $query ) ) {
			\WP_CLI::error( $query->get_error_message() );
		}
		if ( ! $query || ! $query->have_posts() ) {
			\WP_CLI::error( __( 'No post found.', 'ga-communicator' ) );
		}
		$table = new Table();
		$table->setHeaders( [ '#', 'Title', 'URL', 'PV' ] );
		while ( $query->have_posts() ) {
			$query->the_post();
			$table->addRow( [ get_post()->rank, get_the_title(), get_permalink(), get_post()->pv ] );
		}
		$table->display();
	}

	/**
	 * Send measurement protocol.
	 *
	 * @synopsis <event> [--dry-run]
	 * @param array $args  Command arguments.
	 * @param array $assoc Options.
	 * @subcommand measurement-protocol
	 * @return void
	 */
	public function measurement_protocol( $args = [], $assoc = [] ) {
		list( $event ) = $args;
		$payload = [
			'client_id' => '12345.67890',
			'events'    => [
				[
					'name' => $event,
				],
			],
		];
		$is_test = $assoc['dry-run'] ?? false;
		$response = GaCommunicator::get_instance()->ga4_measurement_protocol( $payload, $is_test );
		if ( is_wp_error( $response ) ) {
			\WP_CLI::error( $response->get_error_message() );
		}
		var_dump( $response );
	}
}
