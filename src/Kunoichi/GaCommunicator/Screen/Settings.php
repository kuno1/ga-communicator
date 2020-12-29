<?php

namespace Kunoichi\GaCommunicator\Screen;

use \Hametuha\SingletonPattern\Singleton;
use Kunoichi\GaCommunicator\Utility\GaClientHolder;
use Kunoichi\GaCommunicator\Utility\PlaceHolders;

/**
 * Setting screen for Ga Communicator
 *
 * @package ga-communicator
 * @property-read string       $capability
 * @property-read string       $title
 * @property-read string       $account
 * @property-read string       $property
 * @property-read string       $profile
 * @property-read PlaceHolders $placeholder
 */
class Settings extends Singleton {

	use GaClientHolder;

	protected $slug = 'ga-communicator';

	protected $service_account_option = 'ga-service-key';

	protected $options = [
		'service-key',
		'account',
		'property',
		'profile',
		'tag',
		'extra',
	];

	/**
	 * Constructor
	 */
	protected function init() {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_init', [ $this, 'register_setting_fields' ] );
		if ( $this->should_network_activate() ) {
			add_action( 'network_admin_menu', [ $this, 'network_admin_menu' ] );
			add_action( 'network_admin_edit_update_' . $this->slug, [ $this, 'network_update' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
			add_action( 'admin_init', [ $this, 'register_setting' ], 11 );
		}
		if ( ! $this->should_network_activate() || is_main_site() ) {
			add_action( 'rest_api_init', [ $this, 'register_rest' ] );
		}
	}

	/**
	 * Detect if network
	 *
	 * @return bool
	 */
	public function should_network_activate() {
		$network_active = defined( 'GA_COMMUNICATOR_NETWORK_ACTIVE' ) && GA_COMMUNICATOR_NETWORK_ACTIVE;
		return apply_filters( 'ga_communicator_network_active', $network_active );
	}

	/**
	 * Register admin menu
	 */
	public function admin_menu() {
		// Register menu page.
		add_options_page( $this->title, $this->title, $this->capability, $this->slug, [ $this, 'admin_screen' ], 100 );
	}

	/**
	 * Register network admin menu
	 */
	public function network_admin_menu() {
		// Register menu page.
		add_submenu_page( 'settings.php', $this->title, $this->title, $this->capability, $this->slug, [ $this, 'admin_screen' ] );
	}

	/**
	 * Admin header.
	 */
	public function enqueue_scripts() {
		wp_enqueue_style( 'ga-communicator-setting' );
	}

	/**
	 * Render admin body.
	 */
	public function admin_screen() {
		wp_enqueue_script( 'ga-communicator-setting' );
		if ( is_multisite() ) {
			$action = add_query_arg( [
				'action' => 'update_' . $this->slug,
			], network_admin_url( 'edit.php' ) );
		} else {
			$action = admin_url( 'options.php' );
		}
		?>
		<div class="wrap">
			<h1 class="wp-heading ga-heading">
				<span class="dashicons dashicons-chart-line"></span> <?php echo esc_html( $this->title ) ?>
			</h1>
			<form method="post" action="<?php echo esc_url( $action ); ?>">
				<?php
				settings_fields( $this->slug );
				do_settings_sections( $this->slug );
				submit_button();
				?>
			</form>
		</div>
		<?php
	}

	/**
	 * Return predefined key.
	 *
	 * @return string
	 */
	protected function predefined_key() {
		return apply_filters( 'ga_communicator_predefined_key', '' );
	}
	/**
	 * Register settings.
	 */
	public function register_setting() {
		// Register credential section.
		foreach ( $this->options as $key ) {
			register_setting( $this->slug, 'ga-' . $key );
		}
	}

	/**
	 * Register network setting.
	 */
	public function network_update() {
		check_admin_referer( $this->slug . '-options' );
		foreach ( $this->options as $key ) {
			$name = 'ga-' . $key;
			update_site_option( $name, (string) filter_input( INPUT_POST, $name ) );
		}
		// At last we redirect back to our options page.
		wp_redirect( add_query_arg( [
			'page'    => $this->slug,
			'updated' => 'true',
		], network_admin_url( 'settings.php' ) ) );
		exit;
	}

	/**
	 * Register settings.
	 */
	public function register_setting_fields() {
		// Register credential section.
		$cred_section = $this->slug . '-credentials';
		add_settings_section( $cred_section, __( 'Credentials', 'ga-communicator' ), function() {
			printf( '<p class="description">%s</p>', esc_html__( 'Please enter Google Analytics Credentials.', 'ga-communicator' ) );
		}, $this->slug );
		add_settings_field( 'ga-service-key', __( 'Service Account Key', 'ga-communicator' ), function() {
			$predefined = $this->predefined_key();
			if ( $predefined ) : ?>
				<input type="hidden" name="ga-service-key" value="<?php echo esc_attr( $this->service_key( true ) ) ?>" />
				<textarea readonly class="widefat"><?php echo esc_textarea( $predefined ) ?></textarea>
				<p class="description"><?php esc_html_e( 'Service account is defined programmatically.', 'ga-communicator' ) ?></p>
			<?php else : ?>
				<textarea id="ga-service-key" name="ga-service-key" rows="5" class="widefat"
					placeholder="<?php echo esc_attr( 'e.g. {"type": "service_account", "project_id": "example.com:api-project-000000","private_key_id": "bf8ea16a0978be19b5ce9780c3482202c145e9eb892c"......' ) ?>"
				><?php echo esc_textarea( $this->service_key( true ) ) ?></textarea>
				<p class="description">
					<?php echo wp_kses_post( sprintf( __( 'You can get a service account key in JSON format from Google API Library. For more detail, please check the <a href="%s" target="_blank" rel="noopener,noreferrer">document</a>.', 'ga-communicator' ), 'https://developers.google.com/analytics/devguides/reporting/core/v4/authorization' ) ); ?>
				</p>
			<?php endif;
		}, $this->slug, $cred_section );

		// Register profiles,
		$account_section = $this->slug . '-accounts';
		add_settings_section( $account_section, __( 'Account Setting', 'ga-communicator' ), function() {
			printf( '<p class="description">%s</p>', esc_html__( 'If you set credentials, please choose Google Analytics account of your site. Account, property, and profile are required.', 'ga-communicator' ) );
		}, $this->slug );
		foreach ( [
			'ga-account' => [
				'label'       => __( 'Account', 'ga-communicator' ),
				'description' => __( 'Google Analytics account.', 'ga-communicator' ),
			],
			'ga-property' => [
				'label' =>  __( 'Property', 'ga-communicator' ),
				'description' => __( 'Google Analytics property like <code>UA-0000000-11</code>.', 'ga-communicator' ),
			],
			'ga-profile' => [
				'label' => __( 'Profile', 'ga-communicator' ),
				'description' => __( 'Profile formerly known as "View". e.g. All website data.', 'ga-communicator' ),
			],
		] as $key => $setting ) {
			add_settings_field( $key, $setting['label'], [ $this, 'account_field' ], $this->slug, $account_section, [
				'key'         => $key,
				'description' => $setting['description'],
			] );
		}

		// Render analytics tag.
		$tag_section = $this->slug . 'tags';
		add_settings_section( $tag_section, __( 'Analytics Tag', 'ga-communicator' ), function() {
			printf( '<p class="description">%s</p>', esc_html__( 'Select analytics tag to render. If you user other plugins like Yoast, leave empty.', 'ga-communicator' ) );
		}, $this->slug );
		$choices = [
			''          => __( 'No input', 'ga-communicator' ),
			'gtag'      => 'gtag.js',
			'universal' => 'Universal Analytics(ga.js)',
			'manual'    => __( 'Manual Code', 'ga-communicator' ),
		];
		add_settings_field( 'ga-tag', __( 'Tag Type', 'ga-communicator' ), function() use ( $choices ) {
			$predefined = $this->get_predefined_option( 'tag' );
			$cur_value  = $this->get_option( 'tag', true );
			?>
			<select name="ga-tag" id="ga-tag">
				<?php foreach ( $choices as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $cur_value, $value ) ?>><?php echo esc_html( $label ) ?></option>
				<?php endforeach; ?>
			</select>
			<h4><?php esc_html_e( 'Output Example', 'ga-communicator' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Property should be properly set. It\'ll be inserted as measurement ID.', 'ga-communicator' ) ?></p>
			<?php foreach ( $choices as $key => $label ) : ?>
				<pre class="ga-setting-example" data-sample="<?php echo esc_attr( $key ); ?>"><?php
					if ( $key ) {
						echo esc_html( $this->placeholder->tag( $key, 'UA-00000-1', __( '[Additional Scripts Here]', 'ga-communicator' ) ) );
					} else {
						esc_html_e( '[No Output]', 'ga-communicator' );
					}
				?></pre>
			<?php endforeach; ?>
			<?php if ( $predefined ) : ?>
				<p class="description">
					<?php esc_html_e( 'Tag type is defined programmatically.', 'ga-communicator' ) ?>
					<code><?php esc_html( $choices[ $predefined ] ); ?></code>
				</p>
			<?php endif;
		}, $this->slug, $tag_section );
		add_settings_field( 'ga-extra', __( 'Additional Scripts', 'ga-communicator' ), function() use ( $choices ) {
			$predefined = $this->get_predefined_option( 'extra' );
			$value      = $this->get_option( 'extra', true );
			if ( $predefined ) : ?>
				<input type="hidden" name="ga-extra" value="<?php echo esc_attr( $value ) ?>" />
				<textarea readonly class="widefat"><?php echo esc_textarea( $predefined ) ?></textarea>
				<p class="description"><?php esc_html_e( 'Extra scripts are defined programmatically.', 'ga-communicator' ) ?></p>
			<?php else : ?>
				<textarea id="ga-extra" name="ga-extra" rows="5" class="widefat"><?php
					echo esc_textarea( $value );
				?></textarea>
			<?php endif; ?>
			<table class="ga-setting-table">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Placeholder', 'ga-communicator' ) ?></th>
					<th><?php esc_html_e( 'Replaced With', 'ga-communicator' ) ?></th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ( $this->placeholder->get() as $placeholder ) : ?>
					<tr>
						<th>%<?php echo esc_html( $placeholder['name'] ) ?>%</th>
						<td><?php echo esc_html( $placeholder['description'] ) ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
		}, $this->slug, $tag_section );
	}

	/**
	 * Render account.
	 *
	 * @param array $args
	 */
	public function account_field( $args ) {
		$key        = $args['key'];
		$no_prefix  = str_replace( 'ga-', '', $args['key'] );
		$value      = $this->get_option( $no_prefix, true );
		$predefined = $this->get_predefined_option( $no_prefix );
		?>
		<div class="ga-setting-row" data-key="<?php echo esc_attr( $key ) ?>">
			<input type="hidden" name="<?php echo esc_attr( $key ) ?>" value="<?php echo esc_attr( $value ) ?>" />
			<select class="ga-setting-choices">
				<option class="ga-setting-choices-default" value="" <?php selected( $value, '' ) ?>><?php esc_html_e( 'Please select', 'ga-communicator' ) ?></option>
			</select>
			<span class="dashicons dashicons-update"></span>
		</div>
		<?php
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
		}
		if ( $predefined ) : ?>
			<p class="ga-setting-predefined description">
				<?php esc_html_e( 'This option is predefined programmatically', 'tokai-univ' ) ?>:
				<code class="predefined" data-predefined="<?php echo esc_attr( $key ) ?>"><?php echo esc_html( $predefined ) ?></code>
			</p>
		<?php endif;
	}

	/**
	 * Register REST API
	 */
	public function register_rest() {
		register_rest_route( 'ga/v1', 'accounts', [
			[
				'methods' => 'GET',
				'args' => [],
				'callback' => function( \WP_REST_Request $request ) {
					$response = $this->ga()->accounts();
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
				},
				'permission_callback' => [ $this, 'permission_callback' ],
			],
		] );
		$property_args = [
			'account' => [
				'required' => true,
				'type'     => 'string',
				'description' => 'Account ID.',
				'validate_callback' => function( $var ) {
					return ! empty( $var );
				},
			],
		];
		register_rest_route( 'ga/v1', 'properties/(?P<account>[^/]+)', [
			[
				'methods' => 'GET',
				'args' => $property_args,
				'callback' => function( \WP_REST_Request $request ) {
					$account = $request->get_param( 'account' );
					$response = $this->ga()->properties( $account );
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
				},
				'permission_callback' => [ $this, 'permission_callback' ],
			],
		] );
		register_rest_route( 'ga/v1', 'profiles/(?P<account>[^/]+)/(?P<property>[^/]+)', [
			[
				'methods' => 'GET',
				'args' => array_merge( $property_args, [
					'property' => [
						'required' => true,
						'type'     => 'string',
						'description' => 'Web property ID',
						'validate_callback' => function( $var ) {
							return ! empty( $var );
						},
					],
				] ),
				'callback' => function( \WP_REST_Request $request ) {
					$account  = $request->get_param( 'account' );
					$property = $request->get_param( 'property' );
					$response = $this->ga()->profiles( $account, $property );
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
				},
				'permission_callback' => [ $this, 'permission_callback' ],
			],
		] );
	}

	/**
	 * Get permission.
	 *
	 * @param \WP_REST_Request $request
	 * @return bool
	 */
	public function permission_callback( $request ) {
		return current_user_can( $this->capability );
	}

	/**
	 * Get service key.
	 *
	 * @param bool $raw If true, returns db stored value without filter.
	 * @return string
	 */
	public function service_key( $raw = false ) {
		if ( $raw ) {
			if ( $this->should_network_activate() ) {
				return get_site_option( $this->service_account_option, '' );
			} else {
				return get_option( $this->service_account_option, '' );
			}
		} else {
			$key = $this->predefined_key();
			if ( $key ) {
				return $key;
			} else {
				return $this->service_key( true );
			}
		}
	}

	/**
	 * Get predefined options.
	 *
	 * @param string $key
	 * @return string
	 */
	public function get_predefined_option( $key ) {
		return apply_filters( 'ga_communicator_predefined_option', '', $key );
	}

	/**
	 * Get saved values.
	 *
	 * @param string $key account, profile, property, tag
	 * @param bool   $raw If set to true, always grab data.
	 * @return string
	 */
	public function get_option( $key, $raw = false ) {
		$key = 'ga-' . $key;
		if ( $raw ) {
			if ( $this->should_network_activate() ) {
				return get_site_option( $key, '' );
			} else {
				return get_option( $key, '' );
			}
		} else {
			$predefined = $this->get_predefined_option( $key );
			if ( $predefined ) {
				return $predefined;
			} else {
				return $this->get_option( $key, true );
			}
		}
	}

	/**
	 * Getter
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		switch ( $name ) {
			case 'capability':
				$default_cap = $this->should_network_activate() ? 'manage_sites' : 'manage_options';
				return apply_filters( 'ga_communicator_admin_capability', $default_cap );
			case 'title':
				return apply_filters( 'ga_communicator_menu_title', __( 'Google Analytics Setting', 'ga-communicator' ) );
			case 'account':
			case 'property':
			case 'profile':
				return $this->get_option( $name );
			case 'placeholder':
				return PlaceHolders::get_instance();
			default:
				return null;
		}
	}
}
