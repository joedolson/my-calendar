<?php
/**
 * My Calendar Simple Search Widget
 *
 * @category Widgets
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * My Calendar Simple Search class.
 *
 * @category  Widgets
 * @package   My Calendar
 * @author    Joe Dolson
 * @copyright 2009
 * @license   GPLv2 or later
 * @version   1.0
 */
class My_Calendar_Simple_Search extends WP_Widget {

	/**
	 * Contructor.
	 */
	public function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Simple Event Search', 'my-calendar' ),
			array(
				'customize_selective_refresh' => true,
				'description'                 => __( 'Search your events.', 'my-calendar' ),
			)
		);
	}

	/**
	 * Build the My Calendar Event Search widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	public function widget( $args, $instance ) {
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = str_replace( 'h1', 'h2', $args['before_title'] );
		$after_title   = str_replace( 'h1', 'h2', $args['after_title'] );

		$widget_title = apply_filters( 'widget_title', $instance['title'], $instance, $args );
		$widget_title = ( '' !== $widget_title ) ? $before_title . $widget_title . $after_title : '';
		$widget_url   = ( isset( $instance['url'] ) ) ? $instance['url'] : false;
		echo wp_kses( $before_widget . $widget_title . my_calendar_searchform( 'simple', $widget_url, 'widget' ) . $after_widget, mc_kses_elements() );
	}

	/**
	 * Edit the search widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	public function form( $instance ) {
		$widget_title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		$widget_url   = ( isset( $instance['url'] ) ) ? $instance['url'] : '';
		?>
		<div class="my-calendar-widget-wrapper my-calendar-search-widget">
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $widget_title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Search Results Page', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo esc_url( $widget_url ); ?>"/>
		</p>
		</div>
		<?php
	}

	/**
	 * Update the My Calendar Search Widget settings.
	 *
	 * @param array $new_settings Widget settings new data.
	 * @param array $instance Widget settings instance.
	 *
	 * @return array $instance Updated instance.
	 */
	public function update( $new_settings, $instance ) {
		$instance['title'] = mc_kses_post( $new_settings['title'] );
		$instance['url']   = esc_url_raw( $new_settings['url'] );

		return $instance;
	}
}
