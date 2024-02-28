<?php
/**
 * My Calendar Filters Widget
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
 * My Calendar Event Filters class.
 *
 * @category  Widgets
 * @package   My Calendar
 * @author    Joe Dolson
 * @copyright 2009
 * @license   GPLv2 or later
 * @version   1.0
 */
class My_Calendar_Filters extends WP_Widget {

	/**
	 * Contructor.
	 */
	public function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Event Filters', 'my-calendar' ),
			array(
				'customize_selective_refresh' => true,
				'description'                 => __( 'Filter displayed events.', 'my-calendar' ),
			)
		);
	}

	/**
	 * Build the My Calendar Event filters widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	public function widget( $args, $instance ) {
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = str_replace( 'h1', 'h2', $args['before_title'] );
		$after_title   = str_replace( 'h1', 'h2', $args['after_title'] );
		$widget_title  = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		$widget_title  = apply_filters( 'widget_title', $widget_title, $instance, $args );
		$widget_title  = ( '' !== $widget_title ) ? $before_title . $widget_title . $after_title : '';
		$widget_url    = ( isset( $instance['url'] ) ) ? $instance['url'] : mc_get_uri();
		$ltype         = ( isset( $instance['ltype'] ) ) ? $instance['ltype'] : false;
		$show          = ( isset( $instance['show'] ) ) ? $instance['show'] : array();
		$show          = implode( ',', $show );

		$output = $before_widget . $widget_title . mc_filters( $show, $widget_url, $ltype ) . $after_widget;
		echo wp_kses( $output, mc_kses_elements() );
	}

	/**
	 * Edit the filters widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	public function form( $instance ) {
		$widget_title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		$widget_url   = ( isset( $instance['url'] ) ) ? $instance['url'] : mc_get_uri();
		$ltype        = ( isset( $instance['ltype'] ) ) ? $instance['ltype'] : false;
		$show         = ( isset( $instance['show'] ) ) ? $instance['show'] : array();
		?>
		<div class="my-calendar-widget-wrapper my-calendar-filters-widget">
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php esc_html_e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $widget_title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Target Calendar Page', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo esc_url( $widget_url ); ?>"/>
		</p>
		<ul>
			<?php $locations = in_array( 'locations', $show, true ) ? 'checked="checked"' : ''; ?>
			<li>
				<input type="checkbox" id="<?php echo $this->get_field_id( 'show' ); ?>_locations" name="<?php echo $this->get_field_name( 'show' ); ?>[]" value="locations" <?php echo $locations; ?> /> <label for="<?php echo $this->get_field_id( 'show' ); ?>_locations"><?php _e( 'Locations', 'my-calendar' ); ?></label>
			</li>
			<?php $categories = in_array( 'categories', $show, true ) ? 'checked="checked"' : ''; ?>
			<li>
				<input type="checkbox" id="<?php echo $this->get_field_id( 'show' ); ?>_categories" name="<?php echo $this->get_field_name( 'show' ); ?>[]" value="categories" <?php echo $categories; ?> /> <label for="<?php echo $this->get_field_id( 'show' ); ?>_categories"><?php _e( 'Categories', 'my-calendar' ); ?></label>
			</li>
			<?php $access = in_array( 'access', $show, true ) ? 'checked="checked"' : ''; ?>
			<li>
				<input type="checkbox" id="<?php echo $this->get_field_id( 'show' ); ?>_access" name="<?php echo $this->get_field_name( 'show' ); ?>[]" value="access" <?php echo $access; ?> /> <label for="<?php echo $this->get_field_id( 'show' ); ?>_access"><?php esc_html_e( 'Accessibility Features', 'my-calendar' ); ?></label>
			</li>
		</ul>
		<p>
			<label for="<?php echo $this->get_field_id( 'ltype' ); ?>"><?php esc_html_e( 'Filter locations by', 'my-calendar' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'ltype' ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'ltype' ) ); ?>">
				<option value="name" <?php selected( $ltype, 'name' ); ?>><?php esc_html_e( 'Location Name', 'my-calendar' ); ?></option>
				<option value="state" <?php selected( $ltype, 'state' ); ?>><?php esc_html_e( 'State/Province', 'my-calendar' ); ?></option>
				<option value="city" <?php selected( $ltype, 'city' ); ?>><?php esc_html_e( 'City', 'my-calendar' ); ?></option>
				<option value="region" <?php selected( $ltype, 'region' ); ?>><?php esc_html_e( 'Region', 'my-calendar' ); ?></option>
				<option value="zip" <?php selected( $ltype, 'zip' ); ?>><?php esc_html_e( 'Postal Code', 'my-calendar' ); ?></option>
				<option value="country" <?php selected( $ltype, 'country' ); ?>><?php esc_html_e( 'Country', 'my-calendar' ); ?></option>
			</select>
		</p>
		</div>
		<?php
	}

	/**
	 * Update the My Calendar Event Filters Widget settings.
	 *
	 * @param array $new_settings Widget settings new data.
	 * @param array $instance Widget settings instance.
	 *
	 * @return array $instance Updated instance.
	 */
	public function update( $new_settings, $instance ) {
		$instance['title'] = esc_html( $new_settings['title'] );
		$instance['url']   = esc_url_raw( $new_settings['url'] );
		$instance['ltype'] = sanitize_text_field( $new_settings['ltype'] );
		$show              = ( isset( $new_settings['show'] ) ) ? (array) $new_settings['show'] : array();
		$instance['show']  = array_map( 'sanitize_text_field', $show );

		return $instance;
	}
}
