<?php

namespace Kunoichi\GaCommunicator\Widgets;


use Kunoichi\GaCommunicator\Pattern\Widget;
use Kunoichi\GaCommunicator\Utility\GaClientHolder;

/**
 * Popular posts widget.
 *
 * @package ga-communicator
 */
class PopularPosts extends Widget {
	
	use GaClientHolder;
	
	protected function get_id() {
		return 'ga-popular-posts';
	}
	
	protected function get_name() {
		return __( 'Google Analytics Popular Posts', 'ga-communicator' );
	}
	
	public function form( $instance ) {
		parent::form( $instance );
		$instance = wp_parse_args( $instance, [
			'filter'    => '',
			'days'      => '',
			'layout'    => '',
			'post_type' => '',
			'number'    => '',
			'start'     => '',
			'end'       => '',
		] );
		// Range, advanced option(regexp), post type, permalink, days,
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'number' ) ?>"><?php esc_html_e( 'Number of Posts', 'ga-communicator' ) ?></label>
			<input type="number" class="widefat"
				   name="<?php echo $this->get_field_name( 'number' ) ?>" id="<?php echo $this->get_field_id( 'number' ) ?>"
				   value="<?php echo esc_attr( $instance['number'] ) ?>" placeholder="10"
			/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'days' ) ?>"><?php esc_html_e( 'Target Period', 'ga-communicator' ) ?></label><br />
			<?php echo esc_html_x( 'Recent', 'recent-days', 'ga-communicator' ) ?>
			<input type="number" style="width: 3em; box-sizing: border-box; text-align: right;" min="0"
				   name="<?php echo $this->get_field_name( 'days' ) ?>" id="<?php echo $this->get_field_id( 'days' ) ?>"
				   value="<?php echo esc_attr( $instance['days'] ) ?>" placeholder="30"
			/>
			<?php echo esc_html_x( 'Days', 'recent-days', 'ga-communicator' ) ?>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'layout' ) ?>">
				<?php esc_html_e( 'Layout', 'ga-communicator' ) ?>
			</label>
			<select class="widefat"
					name="<?php echo $this->get_field_name( 'layout' ) ?>"
					id="<?php echo $this->get_field_id( 'layout' ) ?>">
				<?php foreach ( $this->get_styles() as $value => $label ) {
					printf(
						'<option value="%s" %s>%s</option>',
						esc_attr( $value ),
						selected( $instance['layout'], $value, false ),
						esc_html( $label )
					);
				} ?>
			</select>
		</p>
		<p style="text-align: right;">
			<button class="button button-ga-communicator-widget-toggle">
				<i class="dashicons dashicons-admin-generic"></i> <?php esc_html_e( 'Advanced Setting', 'ga-communicator' ) ?>
			</button>
		</p>
		<div class="advanced-setting" style="display: none;">
			<hr />
			<p>
				<label for="<?php echo $this->get_field_id( 'filter' ) ?>"><?php esc_html_e( 'Filter Expression', 'ga-communicator' ) ?></label>
				<input type="text" class="widefat"
					   name="<?php echo $this->get_field_name( 'filter' ) ?>"
					   id="<?php echo $this->get_field_id( 'filter' ) ?>"
					   value="<?php echo esc_attr( $instance[ 'filter' ] ) ?>" placeholder="e.g. article/([^/]+/\d+)"/>
				<span class="description">
				<?php esc_html_e( 'Default: ', 'ga-communicator' ); ?><code><?php echo esc_html( $this->ga()->get_permalink_filter() ) ?></code><br/>
				<?php esc_html_e( 'Filtering Regular Expression for URL. If you are not familiar with RegExp, stay empty. It will filter alongside the permalink structure.', 'ga-communicator' ) ?>
			</span>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id( 'post_type' ) ?>">
					<?php esc_html_e( 'Post Type', 'ga-communicator' ) ?>
				</label>
				<select class="widefat"
					   name="<?php echo $this->get_field_name( 'post_type' ) ?>"
						id="<?php echo $this->get_field_id( 'post_type' ) ?>">
					<?php foreach ( get_post_types( [ 'public' => true ], OBJECT ) as $post_type ) {
						$post_type_value = 'post' === $post_type->name ? '' : $post_type->name;
						printf(
							'<option value="%s" %s>%s</option>',
							esc_attr( $post_type_value ),
							selected( $instance['post_type'], $post_type_value, false ),
							esc_html( $post_type->label )
						);
					} ?>
				</select>
				<span class="description">
					<?php esc_html_e( 'If you change post type to one except post, you also have to change filter expression.', 'ga-communicator' ) ?>
				</span>
			</p>
			<?php foreach ( [
				'start' => __( 'Start Date', 'ga-communicator' ),
				'end'   => __( 'End Date', 'ga-communicator' ),
			] as $key => $label ) : ?>
				<p>
					<label for="<?php echo $this->get_field_id( $key ) ?>"><?php echo esc_html( $label ) ?></label>
					<input type="date" class="widefat"
						   name="<?php echo $this->get_field_name( $key ) ?>"
						   id="<?php echo $this->get_field_id( $key ) ?>"
						   value="<?php echo esc_attr( $instance[ $key ] ) ?>"
						   placeholder="e.g. <?php date_i18n( 'Y-m-d', current_time( 'timestamp' ) - 60 * 60 * 24 * ( 'start' === $key ? 0 : 7) ) ?>"/>
					<span class="description">
						<?php esc_html_e( 'This will override relative days setting above.', 'ga-communicator' ) ?>
					</span>
				</p>
			
			<?php endforeach; ?>
		</div>
		<?php
	}
	
	/**
	 * Get styles.
	 *
	 * @return string[]
	 */
	public function get_styles() {
		return apply_filters( 'ga_communicator_widget_styles', [
			'' => __( 'Default', 'ga-communicator' ),
		] );
	}
	
	protected function widget_content( $args, $instance ) {
		$instance = wp_parse_args( $instance, [
			'filter'    => '',
			'days'      => '',
			'layout'    => '',
			'post_type' => '',
			'number'    => '',
			'start'     => '',
			'end'       => '',
		] );
		$request = [
			'path_regexp' => $instance['filter'] ?: $this->ga()->get_permalink_filter(),
			'number'      => $instance['number'] ?: 10,
			'days_before' => $instance['days'] ?: 30,
			'start' => $instance['start'],
			'end' => $instance['end'],
		];
		$query = $this->ga()->popular_posts( [
			'post_type' => $instance['post_type'] ?: 'post',
		], $request );
		if ( ! $query || ! $query->have_posts() ) {
			return '';
		}
		ob_start();
		while( $query->have_posts() ) {
			$query->the_post();
			$path = apply_filters( 'ga_communicator_loop_template', 'template-parts/loop-ga', $instance['layout'] );
			get_template_part( $path, $instance['layout'] );
		}
		wp_reset_postdata();
		$widget = ob_get_contents();
		ob_end_clean();
		return $widget;
	}
	
	/**
	 * Render scripts.
	 */
	public function admin_footer() {
		$screen = get_current_screen();
		echo <<<HTML
<script>
!function($) {
	$( document ).on( 'click', '.button-ga-communicator-widget-toggle', function( e ) {
		e.preventDefault();
		$( this ).parent( 'p' ).next( 'div' ).toggle();
	} );
} ( jQuery )
</script>
HTML;
	}
}
