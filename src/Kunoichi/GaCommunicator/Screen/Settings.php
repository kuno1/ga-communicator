<?php

namespace Kunoichi\GaCommunicator\Screen;

use Kunoichi\GaCommunicator\Pattern\Singleton;
use Kunoichi\GaCommunicator\Utility\GaClientHolder;
use Kunoichi\GaCommunicator\Utility\PlaceHolders;

/**
 * Setting screen for Ga Communicator
 *
 * @package ga-communicator
 * @property-read string       $capability  Capability to access.
 * @property-read string       $title       Title.
 * @property-read string       $account     Will be deprecated.
 * @property-read string       $property    Will be deprecated.
 * @property-read string       $profile     Will be deprecated.
 * @property-read PlaceHolders $placeholder Placeholder controller.
 * @property-read bool         $using_ga4   Using GA4 for API?
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
		'ga4-property',
		'ga4-tracking-id',
		'ga4-both-tracking',
		'ga4-api-use',
		'ga4-api-secret',
		'tag',
		'extra',
		'place',
		'meta',
		'body-open',
	];

	/**
	 * Constructor
	 */
	protected function init() {
		if ( defined( 'GA_COMMUNICATOR_NO_UI' ) && GA_COMMUNICATOR_NO_UI ) {
			// NO UI flag is on. Stop rendering admin screen.
			return;
		}
		add_action( 'admin_init', [ $this, 'register_setting_fields' ] );
		if ( $this->should_network_activate() ) {
			add_action( 'network_admin_menu', [ $this, 'network_admin_menu' ] );
			add_action( 'network_admin_edit_update_' . $this->slug, [ $this, 'network_update' ] );
		} else {
			add_action( 'admin_menu', [ $this, 'admin_menu' ] );
			add_action( 'admin_init', [ $this, 'register_setting' ], 11 );
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
	 * Render admin body.
	 */
	public function admin_screen() {
		wp_enqueue_style( 'ga-communicator-setting' );
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
				<span class="dashicons dashicons-chart-line"></span> <?php echo esc_html( $this->title ); ?>
			</h1>
			<nav class="nav-tab-wrapper">
				<a href="#ga-setting-form" class="ga-nav-tab nav-tab nav-tab-active"><?php esc_html_e( 'Setting', 'ga-communicator' ); ?></a>
				<a href="#ga-sandbox" class="ga-nav-tab nav-tab"><?php esc_html_e( 'Sandbox', 'ga-communicator' ); ?></a>
			</nav>
			<div id="ga-setting-form" class="ga-nav-tab-content">
				<form method="post" action="<?php echo esc_url( $action ); ?>">
					<?php
					settings_fields( $this->slug );
					do_settings_sections( $this->slug );
					submit_button();
					?>
				</form>
			</div>
			<div id="ga-sandbox" style="display: none;" class="ga-nav-tab-content ga-sandbox-wrap">
				<p class="description">
					<?php
						// translators: %s is document url.
						echo wp_kses_post( __( 'Try to interact GA data. Check <a href="%s" target="_blank" rel="noopener noreferrer">the documentation</a> and confirm what you get with your JSON. WP-CLI is also helpful.', 'ga-communicator' ), 'https://developers.google.com/analytics/devguides/reporting/core/v4/rest/v4/reports/batchGet' );
					?>
				</p>
				<h2><?php esc_html_e( 'JSON to API', 'ga-communicator' ); ?></h2>
				<textarea class="ga-sandbox-inner" id="ga-sandbox-inner"><?php echo esc_textarea( $this->placeholder->sandbox() ); ?></textarea>
				<p>
					<button class="components-button is-secondary" id="ga-sandbox-exec">
						<?php esc_html_e( 'Execute', 'ga-communicator' ); ?>
					</button>
				</p>
				<h2><?php esc_html_e( 'API Result', 'ga-communicator' ); ?></h2>
				<textarea class="ga-sandbox-result" id="ga-sandbox-result" readonly placeholder="<?php esc_attr_e( 'Here comes the result.', 'ga-communicator' ); ?>"></textarea>
			</div>
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
		// At least we redirect back to our options page.
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
		add_settings_section( $cred_section, __( 'Credentials', 'ga-communicator' ), function () {
			printf( '<p class="description">%s</p>', esc_html__( 'Please enter Google Analytics Credentials.', 'ga-communicator' ) );
		}, $this->slug );
		add_settings_field( 'ga-service-key', __( 'Service Account Key', 'ga-communicator' ), function () {
			$predefined = $this->predefined_key();
			if ( $predefined ) :
				?>
				<input type="hidden" name="ga-service-key" value="<?php echo esc_attr( $this->service_key( true ) ); ?>" />
				<textarea readonly class="widefat"><?php echo esc_textarea( $predefined ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Service account is defined programmatically.', 'ga-communicator' ); ?></p>
			<?php else : ?>
				<textarea id="ga-service-key" name="ga-service-key" rows="5" class="widefat"
					placeholder="<?php echo esc_attr( 'e.g. {"type": "service_account", "project_id": "example.com:api-project-000000","private_key_id": "bf8ea16a0978be19b5ce9780c3482202c145e9eb892c"......' ); ?>"
				><?php echo esc_textarea( $this->service_key( true ) ); ?></textarea>
				<p class="description">
					<?php
						// translators: %s is URL.
						echo wp_kses_post( sprintf( __( 'You can get a service account key in JSON format from Google API Library. For more detail, please check the <a href="%s" target="_blank" rel="noopener,noreferrer">document</a>.', 'ga-communicator' ), 'https://developers.google.com/analytics/devguides/reporting/core/v4/authorization' ) );
					?>
				</p>
				<?php
			endif;
		}, $this->slug, $cred_section );

		// Register GA4 property,
		$ga4_account_section = $this->slug . '-ga4-accounts';
		add_settings_section( $ga4_account_section, __( 'GA4 Account Setting', 'ga-communicator' ), function () {
		}, $this->slug );
		foreach ( [
			'ga-ga4-property'      => [
				'label'       => __( 'Property ID', 'ga-communicator' ),
				'description' => __( 'A numeric ID of GA4 property like <code>12345678</code>.', 'ga-communicator' ),
				'options'     => [],
			],
			'ga-ga4-tracking-id'   => [
				'label'       => __( 'Tracking ID', 'ga-communicator' ),
				'description' => __( 'GA4 tracking ID like <code>G-ABCDEFGH100</code>.', 'ga-communicator' ),
				'options'     => [],
			],
			'ga-ga4-both-tracking' => [
				'label'       => __( 'Double Tracking', 'ga-communicator' ),
				'description' => __( 'To keep universal analytics tracking until the API deprecation, enable double tracking. The tag type should be gtag.js.', 'ga-communicator' ),
				'options'     => [
					''  => __( 'Only track GA4', 'ga-communicator' ),
					'1' => __( 'Enable Double Tracking', 'ga-communicator' ),
				],
			],
			'ga-ga4-api-use'       => [
				'label'       => __( 'API to Use', 'ga-communicator' ),
				'description' => __( 'If you choose double tracking, specify which API to use.', 'ga-communicator' ),
				'options'     => [
					''   => __( 'Use Google Analytic 4', 'ga-communicator' ),
					'ga' => __( 'Use Universal Analytics(Deprecated)', 'ga-communicator' ),
				],
			],
			'ga-ga4-api-secret'    => [
				'label'       => __( 'API Secret', 'ga-communicator' ),
				'description' => __( 'API Secret for measurement protocol. Go to Setting > Data Stream > Measurement Protocol API Secret.', 'ga-communicator' ),
				'options'     => [],
			],
		] as $key => $setting ) {
			add_settings_field(
				$key,
				$setting['label'],
				function ( $args ) {
					$option_key = str_replace( 'ga-', '', $args['key'] );
					$value      = $this->get_option( $option_key, true );
					if ( ! empty( $args['options'] ) ) {
						?>
						<select name="<?php echo esc_attr( $args['key'] ); ?>">
							<?php
							foreach ( $args['options'] as $v => $label ) {
								printf(
									'<option value="%s" %s>%s</option>',
									esc_attr( $v ),
									selected( $v, $value, false ),
									esc_html( $label )
								);
							}
							?>
						</select>
						<?php
					} else {
						printf(
							'<input name="%s" type="text" value="%s" class="regular-text" />',
							esc_attr( $args['key'] ),
							esc_attr( $value )
						);
					}
					if ( $args['description'] ) {
						printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
					}
				},
				$this->slug,
				$ga4_account_section,
				[
					'key'         => $key,
					'description' => $setting['description'],
					'options'     => $setting['options'],
				]
			);
		}

		// Render analytics tag.
		$tag_section = $this->slug . 'tags';
		add_settings_section( $tag_section, __( 'Analytics Tag', 'ga-communicator' ), function () {
			printf( '<p class="description">%s</p>', esc_html__( 'Select analytics tag to render. If you user other plugins like Yoast, leave empty.', 'ga-communicator' ) );
		}, $this->slug );
		$choices = [
			''          => __( 'No Output', 'ga-communicator' ),
			'gtag'      => 'gtag.js',
			'universal' => 'Universal Analytics(Deprecated)',
			'manual'    => __( 'Manual Code(for GTM)', 'ga-communicator' ),
		];
		add_settings_field( 'ga-tag', __( 'Tag Type', 'ga-communicator' ), function () use ( $choices ) {
			$predefined = $this->get_predefined_option( 'tag' );
			$cur_value  = $this->get_option( 'tag', true );
			?>
			<select name="ga-tag" id="ga-tag">
				<?php foreach ( $choices as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $cur_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<h4><?php esc_html_e( 'Output Example', 'ga-communicator' ); ?></h4>
			<p class="description"><?php esc_html_e( 'Property should be properly set. It\'ll be inserted as measurement ID.', 'ga-communicator' ); ?></p>
			<?php
			foreach ( $choices as $key => $label ) {
				printf(
					'<pre class="ga-setting-example" data-sample="%s">%s</pre>',
					esc_attr( $key ),
					( $key ? esc_html( $this->placeholder->tag( $key, 'UA-00000-1', __( '[Additional Scripts Here]', 'ga-communicator' ) ) ) : esc_html__( '[No Output]', 'ga-communicator' ) )
				);
			}
			?>
			<?php if ( $predefined ) : ?>
				<p class="description">
					<?php esc_html_e( 'Tag type is defined programmatically.', 'ga-communicator' ); ?>
					<code><?php esc_html( $choices[ $predefined ] ); ?></code>
				</p>
				<?php
			endif;
		}, $this->slug, $tag_section );

		// Additional scrips.
		add_settings_field( 'ga-extra', __( 'Additional Scripts', 'ga-communicator' ), function () use ( $choices ) {
			$predefined = $this->get_predefined_option( 'extra' );
			$value      = $this->get_option( 'extra', true );
			if ( $predefined ) :
				?>
				<input type="hidden" name="ga-extra" value="<?php echo esc_attr( $value ); ?>" />
				<textarea id="ga-extra" readonly class="widefat"><?php echo esc_textarea( $predefined ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Extra scripts are defined programmatically.', 'ga-communicator' ); ?></p>
			<?php else : ?>
				<textarea id="ga-extra" name="ga-extra" rows="5" class="widefat"><?php echo esc_textarea( $value ); ?></textarea>
			<?php endif; ?>
			<?php
			printf(
				'<p class="description">%s</p>',
				esc_html__( 'You can define an additional script. Works fine with custom dimension.', 'ga-communicator' )
			);
			?>
			<table class="ga-setting-table">
				<thead>
				<tr>
					<th><?php esc_html_e( 'Placeholder', 'ga-communicator' ); ?></th>
					<th><?php esc_html_e( 'Replaced With', 'ga-communicator' ); ?></th>
				</tr>
				</thead>
				<tbody>
					<?php foreach ( $this->placeholder->get() as $placeholder ) : ?>
					<tr>
						<th>%<?php echo esc_html( $placeholder['name'] ); ?>%</th>
						<td><?php echo wp_kses_post( $placeholder['description'] ); ?></td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php
			foreach ( [
				'gtag'      => "gtagConfig = { custom_map: { dimension1: 'post_id' }, post_id: %post_id% };",
				'universal' => "ga( 'set', 'dimension1', %post_id% );",
				'manual'    => 'window.GaValues = { post: %post_id%, author: %author_id% };',
			] as $key => $example ) {
				printf( '<pre style="display: none;" data-example="%s">%s</pre>', esc_attr( $key ), esc_html( $example ) );
			}
		}, $this->slug, $tag_section );

		// Meta tags.
		add_settings_field( 'ga-meta', __( 'Meta tags', 'ga-communicator' ), function () use ( $choices ) {
			$predefined = $this->get_predefined_option( 'meta' );
			$cur_value  = $this->get_option( 'meta', true );
			?>
			<textarea name="ga-meta" id="ga-meta" class="widefat" rows="5" placeholder="e.g. post_id, post_type, term"><?php echo esc_textarea( $cur_value ); ?></textarea>
			<p class="description">
				<?php
				echo wp_kses_post( sprintf(
					// translators: %s is link URL.
					__( 'These tags are rendered as meta tags for Google Tag Manager. See <a href="%s" target="_blank" rel="noopener noreferrer">our wiki</a> for more details.', 'ga-communicator' ),
					'https://github.com/kuno1/ga-communicator/wiki/meta%E3%82%BF%E3%82%B0%E3%81%B8%E3%81%AE%E5%87%BA%E5%8A%9B'
				) );
				?>
			</p>
			<?php if ( $predefined ) : ?>
				<p class="description">
					<?php esc_html_e( 'Meta tags are defined programmatically.', 'ga-communicator' ); ?>
				</p>
				<pre><?php esc_html( $predefined ); ?></pre>
				<?php
			endif;
		}, $this->slug, $tag_section );

		// Body open.
		add_settings_field( 'ga-body-open', __( 'Tag after <body> open tag', 'ga-communicator' ), function () use ( $choices ) {
			$predefined = $this->get_predefined_option( 'body-open' );
			$cur_value  = $this->get_option( 'body-open', true );
			?>
			<textarea name="ga-body-open" id="ga-body-open" class="widefat" rows="5"><?php echo esc_textarea( $cur_value ); ?></textarea>
			<p class="description"><?php esc_html_e( 'This will be rendered just after <body> tag open. For Google Tag Manager.', 'ga-communicator' ); ?></p>
			<?php if ( $predefined ) : ?>
				<p class="description">
					<?php esc_html_e( 'Tag place to be output is defined programmatically.', 'ga-communicator' ); ?>
				</p>
				<pre><?php esc_html( $predefined ); ?></pre>
				<?php
			endif;
		}, $this->slug, $tag_section );

		// Tag to be output.
		add_settings_field( 'ga-place', __( 'Tag Appears In', 'ga-communicator' ), function () use ( $choices ) {
			$predefined = $this->get_predefined_option( 'place' );
			$cur_value  = $this->get_option( 'place', true );
			$choices    = [
				''            => __( 'Only Public Pages', 'ga-communicator' ),
				'admin'       => __( 'Public Pages and Admin Screen', 'ga-communicator' ),
				'login'       => __( 'Public Pages and Login Screen', 'ga-communicator' ),
				'admin,login' => __( 'Public, Admin, and Login.', 'ga-communicator' ),
			];
			?>
			<select name="ga-place" id="ga-place">
				<?php foreach ( $choices as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $cur_value, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
			<?php if ( $predefined ) : ?>
				<p class="description">
					<?php esc_html_e( 'Tag place to be output is defined programmatically.', 'ga-communicator' ); ?>
					<code><?php esc_html( $choices[ $predefined ] ); ?></code>
				</p>
				<?php
			endif;
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
		<div class="ga-setting-row" data-key="<?php echo esc_attr( $key ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( $value ); ?>" />
			<select class="ga-setting-choices">
				<option class="ga-setting-choices-default" value="" <?php selected( $value, '' ); ?>><?php esc_html_e( 'Please select', 'ga-communicator' ); ?></option>
			</select>
			<span class="dashicons dashicons-update"></span>
		</div>
		<?php
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
		}
		if ( $predefined ) :
			?>
			<p class="ga-setting-predefined description">
				<?php esc_html_e( 'This option is predefined programmatically', 'tokai-univ' ); ?>:
				<code class="predefined" data-predefined="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $predefined ); ?></code>
			</p>
			<?php
		endif;
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
		$key_name = 'ga-' . $key;
		if ( $raw ) {
			if ( $this->should_network_activate() ) {
				return get_site_option( $key_name, '' );
			} else {
				return get_option( $key_name, '' );
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
			case 'using_ga4':
				return $this->get_option( 'ga4-property' ) && ! $this->get_option( 'ga4-api-use' );
			default:
				return null;
		}
	}
}
