<?php

namespace Kunoichi\GaCommunicator\Pattern;

/**
 * Abstract widget class
 *
 * @package ga-communicator
 */
abstract class Widget extends \WP_Widget {

	protected $has_title = true;

	/**
	 * Widget constructor.
	 *
	 * @param string $id_base
	 * @param string $name
	 * @param array $widget_options
	 * @param array $control_options
	 */
	public function __construct( $id_base = '', $name = '', $widget_options = [], $control_options = [] ) {
		parent::__construct( $this->get_id(), $this->get_name(), $this->get_widget_option(), $this->get_widget_option() );
		add_action( 'admin_footer', [ $this, 'admin_footer' ] );
	}

	abstract protected function get_id();

	abstract protected function get_name();

	/**
	 * Get widget description.
	 *
	 * @return string
	 */
	protected function get_description() {
		return '';
	}

	/**
	 * Render form fields.
	 *
	 * Override this to add original form fields.
	 *
	 * @param array $instance
	 * @return void
	 */
	public function form( $instance ) {
		if ( $this->has_title ) {
			?>
			<p>
				<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title', 'ga-communicator' ); ?></label>
				<input name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
					id="<?php echo $this->get_field_id( 'title' ); ?>" class="widefat"
					value="<?php echo esc_attr( isset( $instance['title'] ) ? $instance['title'] : '' ); ?>"/>
			</p>
			<?php
		}
	}

	/**
	 * Render widget content.
	 *
	 * @param array $args
	 * @param array $instance
	 *
	 * @return string
	 */
	abstract protected function widget_content( $args, $instance );

	/**
	 * Render widget.
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance ) {
		$content = $this->widget_content( $args, $instance );
		if ( ! $content ) {
			// Skip widget if no content.
			return;
		}
		echo $args['before_widget'];
		if ( $this->has_title && ! empty( $instance['title'] ) ) {
			echo $args['before_title'];
			echo wp_kses_post( $instance['title'] );
			echo $args['after_title'];
		}
		do_action( 'ga_communicator_before_widget_content', $args, $instance, $this->get_id() );
		echo $content;
		do_action( 'ga_communicator_after_widget_content', $args, $instance, $this->get_id() );
		echo $args['after_widget'];
	}

	/**
	 * Get widget options.
	 *
	 * Override this method to customize options.
	 *
	 * @return array
	 */
	protected function get_widget_option() {
		$options     = [];
		$description = $this->get_description();
		if ( $description ) {
			$options['description'] = $description;
		}

		return apply_filters( 'ga_communicator_widget_option', $options, $this->get_id() );
	}

	/**
	 * Get widget control option.
	 *
	 * @return array
	 */
	protected function get_control_option() {
		return apply_filters( 'ga_communicator_widget_control_option', [], $this->get_id() );
	}

	/**
	 * Do something on admin footer
	 */
	public function admin_footer() {
		// Do something.
	}
}
