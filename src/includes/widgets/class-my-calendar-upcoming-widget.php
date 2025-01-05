<?php
/**
 * My Calendar Upcoming Events Widget
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
 * My Calendar Upcoming Events class.
 *
 * @category  Widgets
 * @package   My Calendar
 * @author    Joe Dolson
 * @copyright 2009
 * @license   GPLv2 or later
 * @version   1.0
 */
class My_Calendar_Upcoming_Widget extends WP_Widget {

	/**
	 * Contructor.
	 */
	public function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Upcoming Events', 'my-calendar' ),
			array(
				'customize_selective_refresh' => true,
				'description'                 => __( 'List recent and future events.', 'my-calendar' ),
			)
		);
	}

	/**
	 * Build the My Calendar Upcoming Events widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	public function widget( $args, $instance ) {
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = str_replace( 'h1', 'h2', $args['before_title'] );
		$after_title   = str_replace( 'h1', 'h2', $args['after_title'] );

		$title      = ( isset( $instance['my_calendar_upcoming_title'] ) ) ? $instance['my_calendar_upcoming_title'] : '';
		$before     = ( isset( $instance['my_calendar_upcoming_before'] ) ) ? $instance['my_calendar_upcoming_before'] : '';
		$after      = ( isset( $instance['my_calendar_upcoming_after'] ) ) ? $instance['my_calendar_upcoming_after'] : '';
		$skip       = ( isset( $instance['my_calendar_upcoming_skip'] ) ) ? $instance['my_calendar_upcoming_skip'] : '';
		$show_recur = ( isset( $instance['my_calendar_upcoming_show_recurring'] ) ) ? $instance['my_calendar_upcoming_show_recurring'] : '';
		$type       = ( isset( $instance['my_calendar_upcoming_type'] ) ) ? $instance['my_calendar_upcoming_type'] : '';
		$order      = ( isset( $instance['my_calendar_upcoming_order'] ) ) ? $instance['my_calendar_upcoming_order'] : '';
		$cat        = ( isset( $instance['my_calendar_upcoming_category'] ) ) ? (array) $instance['my_calendar_upcoming_category'] : array();

		$the_title      = apply_filters( 'widget_title', $title, $instance, $args );
		$the_template   = ( isset( $instance['my_calendar_upcoming_template'] ) ) ? $instance['my_calendar_upcoming_template'] : '';
		$the_substitute = ( isset( $instance['my_calendar_no_events_text'] ) ) ? $instance['my_calendar_no_events_text'] : '';
		$before         = ( '' !== $before ) ? esc_attr( $instance['my_calendar_upcoming_before'] ) : 3;
		$after          = ( '' !== $after ) ? esc_attr( $instance['my_calendar_upcoming_after'] ) : 3;
		$skip           = ( '' !== $skip ) ? esc_attr( $instance['my_calendar_upcoming_skip'] ) : 0;
		$show_recurring = ( 'no' === $show_recur ) ? 'no' : 'yes';
		$type           = esc_attr( $type );
		$order          = esc_attr( $order );
		$the_category   = ( empty( $cat ) ) ? array() : (array) $instance['my_calendar_upcoming_category'];
		$author         = ( ! isset( $instance['my_calendar_upcoming_author'] ) || '' === $instance['my_calendar_upcoming_author'] ) ? 'default' : esc_attr( $instance['my_calendar_upcoming_author'] );
		$host           = ( ! isset( $instance['mc_host'] ) || '' === $instance['mc_host'] ) ? 'default' : esc_attr( $instance['mc_host'] );
		$ltype          = ( ! isset( $instance['ltype'] ) || '' === $instance['ltype'] ) ? '' : esc_attr( $instance['ltype'] );
		$lvalue         = ( ! isset( $instance['lvalue'] ) || '' === $instance['lvalue'] ) ? '' : esc_attr( $instance['lvalue'] );
		$widget_link    = ( isset( $instance['my_calendar_upcoming_linked'] ) && 'yes' === $instance['my_calendar_upcoming_linked'] ) ? mc_get_uri( false, $instance ) : '';
		$widget_link    = ( ! empty( $instance['mc_link'] ) ) ? esc_url( $instance['mc_link'] ) : $widget_link;
		$widget_title   = empty( $the_title ) ? '' : $the_title;
		$widget_title   = ( '' === $widget_link ) ? $widget_title : "<a href='$widget_link'>$widget_title</a>";
		$widget_title   = ( '' !== $widget_title ) ? $before_title . $widget_title . $after_title : '';
		$month          = ( 0 === strpos( $type, 'month+' ) ) ? date_i18n( 'F', strtotime( $type ) ) : date_i18n( 'F' );
		$widget_title   = str_replace( '{month}', $month, $widget_title );
		$from           = ( isset( $instance['mc_from'] ) ) ? $instance['mc_from'] : false;
		$to             = ( isset( $instance['mc_to'] ) ) ? $instance['mc_to'] : false;
		$site           = ( isset( $instance['mc_site'] ) ) ? $instance['mc_site'] : false;

		$args = array(
			'before'         => $before,
			'after'          => $after,
			'type'           => $type,
			'category'       => implode( ',', $the_category ),
			'template'       => $the_template,
			'fallback'       => $the_substitute,
			'order'          => $order,
			'skip'           => $skip,
			'show_recurring' => $show_recurring,
			'author'         => $author,
			'host'           => $host,
			'from'           => $from,
			'ltype'          => $ltype,
			'lvalue'         => $lvalue,
			'to'             => $to,
			'site'           => $site,
		);

		$the_events = my_calendar_upcoming_events( $args );
		if ( '' !== $the_events ) {
			echo wp_kses( $before_widget . $widget_title . $the_events . $after_widget, mc_kses_elements() );
		}
	}

	/**
	 * Edit the upcoming events widget.
	 *
	 * @param array $instance Current widget settings.
	 *
	 * @return void
	 */
	public function form( $instance ) {
		$defaults = mc_widget_defaults();
		$title    = ( isset( $instance['my_calendar_upcoming_title'] ) ) ? esc_attr( $instance['my_calendar_upcoming_title'] ) : '';
		$template = ( isset( $instance['my_calendar_upcoming_template'] ) ) ? esc_attr( $instance['my_calendar_upcoming_template'] ) : '';
		if ( ! $template ) {
			$template = $defaults['upcoming']['template'];
		}
		$text       = ( isset( $instance['my_calendar_no_events_text'] ) ) ? $instance['my_calendar_no_events_text'] : '';
		$category   = ( isset( $instance['my_calendar_upcoming_category'] ) ) ? (array) $instance['my_calendar_upcoming_category'] : null;
		$author     = ( isset( $instance['my_calendar_upcoming_author'] ) ) ? $instance['my_calendar_upcoming_author'] : '';
		$host       = ( isset( $instance['mc_host'] ) ) ? $instance['mc_host'] : '';
		$ltype      = ( isset( $instance['ltype'] ) ) ? $instance['ltype'] : '';
		$lvalue     = ( isset( $instance['lvalue'] ) ) ? $instance['lvalue'] : '';
		$before     = ( isset( $instance['my_calendar_upcoming_before'] ) ) ? $instance['my_calendar_upcoming_before'] : 3;
		$after      = ( isset( $instance['my_calendar_upcoming_after'] ) ) ? $instance['my_calendar_upcoming_after'] : 3;
		$show_recur = ( isset( $instance['my_calendar_upcoming_show_recurring'] ) ) ? $instance['my_calendar_upcoming_show_recurring'] : 'yes';
		$type       = ( isset( $instance['my_calendar_upcoming_type'] ) ) ? $instance['my_calendar_upcoming_type'] : 'events';
		$order      = ( isset( $instance['my_calendar_upcoming_order'] ) ) ? $instance['my_calendar_upcoming_order'] : 'asc';
		$linked     = ( isset( $instance['my_calendar_upcoming_linked'] ) ) ? $instance['my_calendar_upcoming_linked'] : '';
		$from       = ( isset( $instance['mc_from'] ) ) ? $instance['mc_from'] : '';
		$to         = ( isset( $instance['mc_to'] ) ) ? $instance['mc_to'] : '';
		$site       = ( isset( $instance['mc_site'] ) ) ? $instance['mc_site'] : false;

		if ( 'yes' === $linked ) {
			$default_link = mc_get_uri( false, $instance );
		} else {
			$default_link = '';
		}
		$link = ( ! empty( $instance['mc_link'] ) ) ? $instance['mc_link'] : $default_link;
		$skip = ( isset( $instance['my_calendar_upcoming_skip'] ) ) ? $instance['my_calendar_upcoming_skip'] : 0;
		?>
		<div class="my-calendar-widget-wrapper my-calendar-upcoming-widget">
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_title' ) ); ?>"><?php esc_html_e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
		</p>
		<?php
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_site' ) ); ?>"><?php esc_html_e( 'Blog ID', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'mc_site' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_site' ) ); ?>" value="<?php echo esc_attr( $site ); ?>"/>
		</p>
			<?php
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_template' ) ); ?>"><?php esc_html_e( 'Template', 'my-calendar' ); ?></label><br/>
			<textarea class="widefat" rows="6" cols="20" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_template' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_template' ) ); ?>"><?php echo esc_textarea( $template ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_link' ) ); ?>"><?php _e( 'Widget title links to:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'mc_link' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_link' ) ); ?>" value="<?php echo esc_url( $link ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_type' ) ); ?>"><?php esc_html_e( 'Display upcoming events by:', 'my-calendar' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_type' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_type' ) ); ?>">
				<option value="events" <?php selected( 'events', $type ); ?>><?php esc_html_e( 'Events (e.g. 2 past, 3 future)', 'my-calendar' ); ?></option>
				<option value="days" <?php selected( 'days', $type ); ?>><?php esc_html_e( 'Dates (e.g. 4 days past, 5 forward)', 'my-calendar' ); ?></option>
				<option value="month" <?php selected( 'month', $type ); ?>><?php esc_html_e( 'Show current month', 'my-calendar' ); ?></option>
				<option value="month+1" <?php selected( 'month+1', $type ); ?>><?php esc_html_e( 'Show next month', 'my-calendar' ); ?></option>
				<option value="month+2" <?php selected( 'month+2', $type ); ?>><?php esc_html_e( 'Show 2nd month out', 'my-calendar' ); ?></option>
				<option value="month+3" <?php selected( 'month+3', $type ); ?>><?php esc_html_e( 'Show 3rd month out', 'my-calendar' ); ?></option>
				<option value="month+4" <?php selected( 'month+4', $type ); ?>><?php esc_html_e( 'Show 4th month out', 'my-calendar' ); ?></option>
				<option value="month+5" <?php selected( 'month+5', $type ); ?>><?php esc_html_e( 'Show 5th month out', 'my-calendar' ); ?></option>
				<option value="month+6" <?php selected( 'month+6', $type ); ?>><?php esc_html_e( 'Show 6th month out', 'my-calendar' ); ?></option>
				<option value="month+7" <?php selected( 'month+7', $type ); ?>><?php esc_html_e( 'Show 7th month out', 'my-calendar' ); ?></option>
				<option value="month+8" <?php selected( 'month+8', $type ); ?>><?php esc_html_e( 'Show 8th month out', 'my-calendar' ); ?></option>
				<option value="month+9" <?php selected( 'month+9', $type ); ?>><?php esc_html_e( 'Show 9th month out', 'my-calendar' ); ?></option>
				<option value="month+10" <?php selected( 'month+10', $type ); ?>><?php esc_html_e( 'Show 10th month out', 'my-calendar' ); ?></option>
				<option value="month+11" <?php selected( 'month+11', $type ); ?>><?php esc_html_e( 'Show 11th month out', 'my-calendar' ); ?></option>
				<option value="month+12" <?php selected( 'month+12', $type ); ?>><?php esc_html_e( 'Show 12th month out', 'my-calendar' ); ?></option>
				<option value="year" <?php selected( 'year', $type ); ?>><?php esc_html_e( 'Show current year', 'my-calendar' ); ?></option>
				<option value="custom" <?php selected( 'custom', $type ); ?>><?php esc_html_e( 'Custom Dates', 'my-calendar' ); ?></option>
			</select>
		</p>
		<?php
		if ( 'custom' === $type ) {
			?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_from' ) ); ?>"><?php esc_html_e( 'Start date', 'my-calendar' ); ?>:</label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'mc_from' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_from' ) ); ?>" value="<?php echo esc_attr( $from ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_to' ) ); ?>"><?php esc_html_e( 'End date', 'my-calendar' ); ?>:</label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'mc_to' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_to' ) ); ?>" value="<?php echo esc_attr( $to ); ?>"/>
		</p>
			<?php
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_skip' ) ); ?>"><?php esc_html_e( 'Skip the first <em>n</em> events', 'my-calendar' ); ?></label>
			<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_skip' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_skip' ) ); ?>" value="<?php echo esc_attr( $skip ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_order' ) ); ?>"><?php esc_html_e( 'Events sort order:', 'my-calendar' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_order' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_order' ) ); ?>">
				<option value="asc" <?php selected( 'asc', $order ); ?>><?php esc_html_e( 'Ascending (near to far)', 'my-calendar' ); ?></option>
				<option value="desc" <?php selected( 'desc', $order ); ?>><?php esc_html_e( 'Descending (far to near)', 'my-calendar' ); ?></option>
			</select>
		</p>
		<?php
		if ( ! ( 'month' === $type || 'month+1' === $type || 'year' === $type ) ) {
			?>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_after' ) ); ?>">
				<?php
				// Translators: "days" or "events".
				printf( esc_html__( '%s into the future', 'my-calendar' ), ucfirst( $type ) );
				?>
				</label>
				<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_after' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_after' ) ); ?>" value="<?php echo esc_attr( $after ); ?>" size="1" maxlength="3" />
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_before' ) ); ?>">
				<?php
				// Translators: "days" or "events".
				printf( esc_html__( '%s from the past', 'my-calendar' ), ucfirst( $type ) );
				?>
				</label>
				<input type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_before' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_before' ) ); ?>" value="<?php echo esc_attr( $before ); ?>" size="1" maxlength="3" /> 
			</p>
			<?php
		}
		?>
		<p>
			<input type="checkbox" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_show_recurring' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_show_recurring' ) ); ?>" value="no"<?php selected( 'no', $show_recur ); ?> />
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_show_recurring' ) ); ?>"><?php esc_html_e( 'Show only the first recurring event in a series', 'my-calendar' ); ?></label>
		</p>
		<?php
		$all_checked = '';
		if ( empty( $category ) ) {
			$all_checked = true;
		}
		?>
		<fieldset>
			<legend><?php _e( 'Categories to display:', 'my-calendar' ); ?></legend>
			<ul style="padding:0;margin:0;list-style-type:none;display:flex;flex-wrap:wrap;gap:12px;">
				<li>
					<input type="checkbox" value="all" <?php checked( true, $all_checked ); ?> name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_category' ) ) . '[]'; ?>" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_category' ) ); ?>"> <label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_category' ) ); ?>"><?php esc_html_e( 'All', 'my-calendar' ); ?></label>
				</li>
			<?php
			$select = mc_category_select( $category, true, true, $this->get_field_name( 'my_calendar_upcoming_category' ) . '[]', $this->get_field_id( 'my_calendar_upcoming_category' ) );
			echo wp_kses( $select, mc_kses_elements() );
			?>
			</ul>
		</fieldset>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_author' ) ); ?>"><?php esc_html_e( 'Author or authors to show:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_upcoming_author' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_upcoming_author' ) ); ?>" value="<?php echo esc_attr( $author ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_host' ) ); ?>"><?php esc_html_e( 'Host or hosts to show:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'mc_host' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_host' ) ); ?>" value="<?php echo esc_attr( $host ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'ltype' ) ); ?>"><?php esc_html_e( 'Location (Type)', 'my-calendar' ); ?></label><br/>
			<select name="<?php echo esc_attr( $this->get_field_name( 'ltype' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'ltype' ) ); ?>" class="widefat">
				<option value=''><?php esc_html_e( 'All locations', 'my-calendar' ); ?></option>
				<option value='event_label' <?php selected( $ltype, 'event_label' ); ?>><?php esc_html_e( 'Location Name', 'my-calendar' ); ?></option>
				<option value='event_city' <?php selected( $ltype, 'event_city' ); ?>><?php esc_html_e( 'City', 'my-calendar' ); ?></option>
				<option value='event_state' <?php selected( $ltype, 'event_state' ); ?>><?php esc_html_e( 'State', 'my-calendar' ); ?></option>
				<option value='event_postcode' <?php selected( $ltype, 'event_postcode' ); ?>><?php esc_html_e( 'Postal Code', 'my-calendar' ); ?></option>
				<option value='event_country' <?php selected( $ltype, 'event_country' ); ?>><?php esc_html_e( 'Country', 'my-calendar' ); ?></option>
				<option value='event_region' <?php selected( $ltype, 'event_region' ); ?>><?php esc_html_e( 'Region', 'my-calendar' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'lvalue' ) ); ?>"><?php esc_html_e( 'Location (Value)', 'my-calendar' ); ?></label><br/>
			<input type="text" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'lvalue' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'lvalue' ) ); ?>" value="<?php echo esc_attr( $lvalue ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_no_events_text' ) ); ?>"><?php esc_html_e( 'No events text', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_no_events_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_no_events_text' ) ); ?>" value="<?php echo esc_attr( $text ); ?>"/>
		</p>
		</div>
		<?php
	}

	/**
	 * Update the My Calendar Upcoming Widget settings.
	 *
	 * @param array $new_settings Widget settings new data.
	 * @param array $instance Widget settings instance.
	 *
	 * @return array Updated instance.
	 */
	public function update( $new_settings, $instance ) {
		$instance                                  = array_map( 'mc_kses_post', array_merge( $instance, $new_settings ) );
		$instance['my_calendar_upcoming_category'] = ( in_array( 'all', (array) $new_settings['my_calendar_upcoming_category'], true ) ) ? array() : $new_settings['my_calendar_upcoming_category'];

		return $instance;
	}
}
