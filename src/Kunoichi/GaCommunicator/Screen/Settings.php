<?php

namespace Kunoichi\GaCommunicator\Screen;

use \Hametuha\SingletonPattern\Singleton;
use Kunoichi\GaCommunicator\Utility\GaClientHolder;

/**
 * Setting screen for Ga Communicator
 *
 * @package ga-communicator
 * @property-read string $capability
 * @property-read string $title
 * @property-read string $account
 * @property-read string $property
 * @property-read string $profile
 */
class Settings extends Singleton {

	use GaClientHolder;
	
	protected $slug = 'ga-communicator';
	
	/**
	 * Constructor
	 */
	protected function init() {
		add_action( 'admin_init', [ $this, 'register_setting' ] );
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
		add_action( 'admin_head', [ $this, 'render_style' ] );
	}
	
	/**
	 * Register admin menu
	 */
	public function admin_menu() {
		add_options_page( $this->title, $this->title, $this->capability, $this->slug, [ $this, 'admin_screen' ], 100 );
	}
	
	/**
	 * Admin header.
	 */
	public function render_style() {
		?>
		<style>
			.ga-heading span{
				vertical-align: middle;
				font-size: 1em;
				width: 1em;
				height: 1em;
			}
			.ga-error {
				color: #e24949;
				font-weight: bold;
			}
		</style>
		<?php
	}
	
	/**
	 * Render admin body.
	 */
	public function admin_screen() {
		?>
		<div class="wrap">
			<h1 class="wp-heading ga-heading">
				<span class="dashicons dashicons-chart-line"></span> <?php echo esc_html( $this->title ) ?>
			</h1>
			<form method="post" action="<?php echo admin_url( 'options.php' ) ; ?>">
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
	 * Register settings.
	 */
	public function register_setting() {
		// Register credential section.
		$cred_section = $this->slug . '-credentials';
		add_settings_section( $cred_section, __( 'Credentials', 'ga-communicator' ), function() {
			printf( '<p class="description">%s</p>', esc_html__( 'Please enter Google Analytics Credentials.', 'ga-communicator' ) );
		}, $this->slug );
		add_settings_field( 'ga-service-key', __( 'Service Account Key', 'ga-communicator' ), function() {
			?>
			<textarea id="ga-service-key" name="ga-service-key" rows="5" class="widefat"
				placeholder="<?php echo esc_attr( 'e.g. {"type": "service_account", "project_id": "example.com:api-project-000000","private_key_id": "bf8ea16a0978be19b5ce9780c3482202c145e9eb892c"......' ) ?>"
			><?php echo esc_textarea( get_option( 'ga-service-key', '' ) ) ?></textarea>
			<p class="description">
				<?php echo wp_kses_post( sprintf( __( 'You can get a service account key in JSON format from Google API Library. For more detail, please check the <a href="%s" target="_blank" rel="noopener,noreferrer">document</a>.', 'ga-communicator' ), 'https://developers.google.com/analytics/devguides/reporting/core/v4/authorization' ) ); ?>
			</p>
			<?php
		}, $this->slug, $cred_section );
		register_setting( $this->slug, 'ga-service-key' );
		
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
			register_setting( $this->slug, $key );
		}
	}
	
	/**
	 * Render account.
	 *
	 * @param $args
	 */
	public function account_field( $args ) {
		$key = $args['key'];
		$value = get_option( $key, '' );
		$choices = [];
		$error_msg = '';
		try {
			switch ( $key ) {
				case 'ga-account':
					$response = $this->ga()->accounts();
					if ( is_wp_error( $response ) ) {
						throw new \Exception( $response->get_error_message() );
					} elseif ( ! $response ) {
						throw new \Exception( __( 'No account found. Please check your service account is registered as Google Analytics user.', 'ga-communicator' ) );
					} else {
						foreach ( $response as $account ) {
							$choices[ $account['id'] ] = $account['name'];
						}
					}
					break;
				case 'ga-property':
					if ( ! $this->account ) {
						throw new \Exception( __( 'To display available properties, you should save account id.', 'ga-communicator' ) );
					}
					$response = $this->ga()->properties( $this->account );
					if ( is_wp_error( $response ) ) {
						throw new \Exception( $response->get_error_message() );
					} elseif ( ! $response ) {
						throw new \Exception( __( 'Failed to get properties. Please check permission.', 'ga-communicator' ) );
					} else {
						foreach ( $response as $property ) {
							$choices[ $property['id'] ] = $property['name'];
						}
					}
					break;
				case 'ga-profile':
					if ( ! $this->account || ! $this->property ) {
						throw new \Exception( __( 'To display available profiles, you should save account id and property id.', 'ga-communicator' ) );
					}
					$response = $this->ga()->profiles( $this->account, $this->property );
					if ( is_wp_error( $response ) ) {
						throw new \Exception( $response->get_error_message() );
					} elseif ( ! $response ) {
						throw new \Exception( __( 'Failed to get profiles. Please check permission.', 'ga-communicator' ) );
					} else {
						foreach ( $response as $profile ) {
							$choices[ $profile['id'] ] = $profile['name'];
						}
					}
					break;
			}
		} catch ( \Exception $e ) {
			$error_msg = $e->getMessage();
		} finally {
			if ( $choices ) {
				?>
				<select name="<?php echo esc_attr( $key ) ?>" id="<?php echo esc_attr( $key ) ?>">
					<option value="" <?php selected( ! $value ) ?>><?php esc_html_e( 'Please select', 'ga-communicator' ) ?></option>
					<?php foreach ( $choices as $v => $label ) : ?>
						<option value="<?php echo esc_attr( $v ) ?>" <?php selected( $v, $value ) ?>><?php echo esc_html( $label ) ?></option>
					<?php endforeach; ?>
				</select>
				<?php
			} else {
				printf( '<input type="text" class="widefat" name="%1$s" id="%1$s" value="%2$s" />', esc_attr( $key ), esc_attr( $value ) );
			}
		}
		if ( $error_msg ) {
			printf( '<p class="ga-error">%s</p>', wp_kses_post( $e->getMessage() ) );
		}
		if ( ! empty( $args['description'] ) ) {
			printf( '<p class="description">%s</p>', wp_kses_post( $args['description'] ) );
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
				return apply_filters( 'ga_communicator_admin_capability', 'manage_options' );
			case 'title':
				return apply_filters( 'ga_communicator_menu_title', __( 'Google Analytics Setting', 'ga-communicator' ) );
			case 'account':
			case 'property':
			case 'profile':
				return get_option( 'ga-' . $name, '' );
			default:
				return null;
		}
	}
}
