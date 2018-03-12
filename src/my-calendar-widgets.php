<?php
/**
 * Widgets.
 *
 * @category Calendar
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class My_Calendar_Simple_Search extends WP_Widget {
	function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Simple Event Search', 'my-calendar' ),
			array( 'customize_selective_refresh' => true )
		);
	}

	/**
	 * Build the My Calendar Event Search widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	function widget( $args, $instance ) {
		extract( $args );
		$widget_title = apply_filters( 'widget_title', $instance['title'], $instance, $args );
		$widget_title = ( '' != $widget_title ) ? $before_title . $widget_title . $after_title : '';
		$widget_url   = ( isset( $instance['url'] ) ) ? $instance['url'] : false;
		echo $before_widget;
		echo ( '' != $instance['title'] ) ? $widget_title : '';

		echo my_calendar_searchform( 'simple', $widget_url );
		echo $after_widget;
	}

	/**
	 * Edit the search widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	function form( $instance ) {
		$widget_title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		$widget_url   = ( isset( $instance['url'] ) ) ? $instance['url'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $widget_title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Search Results Page', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo esc_url( $widget_url ); ?>"/>
		</p>
	<?php
	}

	/**
	 * Update the My Calendar Search Widget settings.
	 *
	 * @param object $new Widget settings new data.
	 * @param object $instance Widget settings instance.
	 *
	 * @return $instance Updated instance.
	 */
	function update( $new, $instance ) {
		$instance['title'] = mc_kses_post( $new['title'] );
		$instance['url']   = esc_url_raw( $new['url'] );

		return $instance;
	}
}


class My_Calendar_Filters extends WP_Widget {
	function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Event Filters', 'my-calendar' ),
			array( 'customize_selective_refresh' => true )
		);
	}

	/**
	 * Build the My Calendar Event filters widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	function widget( $args, $instance ) {
		extract( $args );
		$widget_title = apply_filters( 'widget_title', $instance['title'], $instance, $args );
		$widget_title = ( '' != $widget_title ) ? $before_title . $widget_title . $after_title : '';
		$widget_url   = ( isset( $instance['url'] ) ) ? $instance['url'] : mc_get_uri();
		$ltype        = ( isset( $instance['ltype'] ) ) ? $instance['ltype'] : false;
		$show         = ( isset( $instance['show'] ) ) ? $instance['show'] : array();
		$show         = implode( $show, ',' );

		echo $before_widget;
		echo ( '' != $instance['title'] ) ? $widget_title : '';

		echo mc_filters( $show, $widget_url, $ltype );
		echo $after_widget;
	}

	/**
	 * Edit the filters widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	function form( $instance ) {
		$widget_title = ( isset( $instance['title'] ) ) ? $instance['title'] : '';
		$widget_url   = ( isset( $instance['url'] ) ) ? $instance['url'] : mc_get_uri();
		$ltype        = ( isset( $instance['ltype'] ) ) ? $instance['ltype'] : false;
		$show         = ( isset( $instance['show'] ) ) ? $instance['show'] : array();

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $widget_title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Target Calendar Page', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'url' ); ?>" name="<?php echo $this->get_field_name( 'url' ); ?>" value="<?php echo esc_url( $widget_url ); ?>"/>
		</p>
		<ul>
			<?php $locations = in_array( 'locations', $show ) ? 'checked="checked"' : ''; ?>
			<li>
				<input type="checkbox" id="<?php echo $this->get_field_id( 'show' ); ?>_locations" name="<?php echo $this->get_field_name( 'show' ); ?>[]" value="locations" <?php echo $locations; ?> /> <label for="<?php echo $this->get_field_id( 'show' ); ?>_locations"><?php _e( 'Locations', 'my-calendar' ); ?></label>
			</li>
			<?php $categories = in_array( 'categories', $show ) ? 'checked="checked"' : ''; ?>
			<li>
				<input type="checkbox" id="<?php echo $this->get_field_id( 'show' ); ?>_categories" name="<?php echo $this->get_field_name( 'show' ); ?>[]" value="categories" <?php echo $categories; ?> /> <label for="<?php echo $this->get_field_id( 'show' ); ?>_categories"><?php _e( 'Categories', 'my-calendar' ); ?></label>
			</li>
			<?php $access = in_array( 'access', $show ) ? 'checked="checked"' : ''; ?>
			<li>
				<input type="checkbox" id="<?php echo $this->get_field_id( 'show' ); ?>_access" name="<?php echo $this->get_field_name( 'show' ); ?>[]" value="access" <?php echo $access; ?> /> <label for="<?php echo $this->get_field_id( 'show' ); ?>_access"><?php _e( 'Accessibility Features', 'my-calendar' ); ?></label>
			</li>
		</ul>
		<p>
			<label for="<?php echo $this->get_field_id( 'ltype' ); ?>"><?php _e( 'Filter locations by', 'my-calendar' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'ltype' ); ?>" name="<?php echo $this->get_field_name( 'ltype' ); ?>">
				<option value="name" <?php selected( $ltype, 'name' ); ?>><?php _e( 'Location Name', 'my-calendar' ); ?></option>
				<option value="state" <?php selected( $ltype, 'state' ); ?>><?php _e( 'State/Province', 'my-calendar' ); ?></option>
				<option value="city" <?php selected( $ltype, 'city' ); ?>><?php _e( 'City', 'my-calendar' ); ?></option>
				<option value="region" <?php selected( $ltype, 'region' ); ?>><?php _e( 'Region', 'my-calendar' ); ?></option>
				<option value="zip" <?php selected( $ltype, 'zip' ); ?>><?php _e( 'Postal Code', 'my-calendar' ); ?></option>
				<option value="country" <?php selected( $ltype, 'country' ); ?>><?php _e( 'Country', 'my-calendar' ); ?></option>
			</select>
		</p>
	<?php
	}

	/**
	 * Update the My Calendar Event Filters Widget settings.
	 *
	 * @param object $new Widget settings new data.
	 * @param object $instance Widget settings instance.
	 *
	 * @return $instance Updated instance.
	 */
	function update( $new, $instance ) {
		$instance['title'] = mc_kses_post( $new['title'] );
		$instance['url']   = esc_url_raw( $new['url'] );
		$instance['ltype'] = sanitize_title( $new['ltype'] );
		$instance['show']  = array_map( 'sanitize_title', $new['show'] );

		return $instance;
	}
}

class My_Calendar_Today_Widget extends WP_Widget {

	function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Today\'s Events', 'my-calendar' ),
			array( 'customize_selective_refresh' => true )
		);
	}

	/**
	 * Build the My Calendar Today's Events widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 *
	 * @return string Widget output.
	 */
	function widget( $args, $instance ) {
		extract( $args );
		$the_title      = apply_filters( 'widget_title', $instance['my_calendar_today_title'], $instance, $args );
		$the_template   = $instance['my_calendar_today_template'];
		$the_substitute = $instance['my_calendar_no_events_text'];
		$the_category   = ( '' == $instance['my_calendar_today_category'] ) ? 'default' : esc_attr( $instance['my_calendar_today_category'] );
		$author         = ( ! isset( $instance['my_calendar_today_author'] ) || '' == $instance['my_calendar_today_author'] ) ? 'all' : esc_attr( $instance['my_calendar_today_author'] );
		$host           = ( ! isset( $instance['mc_host'] ) || '' == $instance['mc_host'] ) ? 'all' : esc_attr( $instance['mc_host'] );
		$default_link   = mc_get_uri( false, $args );
		$widget_link    = ( ! empty( $instance['my_calendar_today_linked'] ) && 'yes' == $instance['my_calendar_today_linked'] ) ? $default_link : '';
		$widget_link    = ( ! empty( $instance['mc_link'] ) ) ? esc_url( $instance['mc_link'] ) : $widget_link;
		$widget_title   = empty( $the_title ) ? '' : $the_title;
		$date           = ( ! empty( $instance['mc_date'] ) ) ? $instance['mc_date'] : false;
		$site           = ( isset( $instance['mc_site'] ) ) ? $instance['mc_site'] : false;

		if ( false !== strpos( $widget_title, '{date}' ) ) {
			$widget_title = str_replace( '{date}', date_i18n( get_option( 'mc_date_format' ), current_time( 'timestamp' ) ), $widget_title );
		}
		$widget_title = ( '' == $widget_link ) ? $widget_title : "<a href='$widget_link'>$widget_title</a>";
		$widget_title = ( '' != $widget_title ) ? $before_title . $widget_title . $after_title : '';

		$args = array(
			'category' => $the_category,
			'template' => $the_template,
			'fallback' => $the_substitute,
			'author'   => $author,
			'host'     => $host,
			'date'     => $date,
			'site'     => $site,
		);

		$the_events   = my_calendar_todays_events( $args );
		if ( '' != $the_events ) {
			echo $before_widget;
			echo $widget_title;
			echo $the_events;
			echo $after_widget;
		}
	}

	/**
	 * Edit the today's events widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	function form( $instance ) {
		$defaults        = mc_widget_defaults();
		$widget_title    = ( isset( $instance['my_calendar_today_title'] ) ) ? esc_attr( $instance['my_calendar_today_title'] ) : '';
		$widget_template = ( isset( $instance['my_calendar_today_template'] ) ) ? esc_attr( $instance['my_calendar_today_template'] ) : '';
		if ( ! $widget_template ) {
			$widget_template = $defaults['today']['template'];
		}
		$widget_text     = ( isset( $instance['my_calendar_no_events_text'] ) ) ? esc_attr( $instance['my_calendar_no_events_text'] ) : '';
		$widget_category = ( isset( $instance['my_calendar_today_category'] ) ) ? esc_attr( $instance['my_calendar_today_category'] ) : '';
		$widget_linked   = ( isset( $instance['my_calendar_today_linked'] ) ) ? esc_attr( $instance['my_calendar_today_linked'] ) : '';
		$date            = ( isset( $instance['mc_date'] ) ) ? esc_attr( $instance['mc_date'] ) : '';
		if ( 'yes' == $widget_linked ) {
			$default_link = mc_get_uri( false, $instance );
		} else {
			$default_link = '';
		}
		$widget_link   = ( ! empty( $instance['mc_link'] ) ) ? esc_url( $instance['mc_link'] ) : $default_link;
		$widget_author = ( isset( $instance['my_calendar_today_author'] ) ) ? esc_attr( $instance['my_calendar_today_author'] ) : '';
		$widget_host   = ( isset( $instance['mc_host'] ) ) ? esc_attr( $instance['mc_host'] ) : '';
		$site          = ( isset( $instance['mc_site'] ) ) ? esc_attr( $instance['mc_site'] ) : '';

		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_today_title' ); ?>"><?php _e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_today_title' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_today_title' ); ?>" value="<?php echo $widget_title; ?>"/>
		</p>
		<?php
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_site' ); ?>"><?php _e( 'Blog ID', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_site' ); ?>" name="<?php echo $this->get_field_name( 'mc_site' ); ?>" value="<?php echo esc_attr( $site ); ?>"/>
		</p>
		<?php
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_today_template' ); ?>"><?php _e( 'Template', 'my-calendar' ); ?></label><br/>
			<textarea class="widefat" rows="8" cols="20" id="<?php echo $this->get_field_id( 'my_calendar_today_template' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_today_template' ); ?>"><?php echo $widget_template; ?></textarea>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_link' ); ?>"><?php _e( 'Widget title links to:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_link' ); ?>" name="<?php echo $this->get_field_name( 'mc_link' ); ?>" value="<?php echo $widget_link; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_no_events_text' ); ?>"><?php _e( 'Show this text if there are no events today:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_no_events_text' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_no_events_text' ); ?>" value="<?php echo $widget_text; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_date' ); ?>"><?php _e( 'Custom date', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_date' ); ?>" name="<?php echo $this->get_field_name( 'mc_date' ); ?>" value="<?php echo $date; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_today_category' ); ?>"><?php _e( 'Category or categories to display:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_today_category' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_today_category' ); ?>" value="<?php echo $widget_category; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_today_author' ); ?>"><?php _e( 'Author or authors to show:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_today_author' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_today_author' ); ?>" value="<?php echo $widget_author; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_host' ); ?>"><?php _e( 'Host or hosts to show:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_host' ); ?>" name="<?php echo $this->get_field_name( 'mc_host' ); ?>" value="<?php echo $widget_host; ?>"/>
		</p>
	<?php
	}

	/**
	 * Update the My Calendar Today's Events Widget settings.
	 *
	 * @param object $new Widget settings new data.
	 * @param object $instance Widget settings instance.
	 *
	 * @return $instance Updated instance.
	 */
	function update( $new, $instance ) {
		$instance = array_map( 'mc_kses_post', array_merge( $instance, $new ) );

		return $instance;
	}
}

class My_Calendar_Upcoming_Widget extends WP_Widget {

	function __construct() {
		parent::__construct( false, $name = __( 'My Calendar: Upcoming Events', 'my-calendar' ), array( 'customize_selective_refresh' => true ) );
	}

	/**
	 * Build the My Calendar Upcoming Events widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	function widget( $args, $instance ) {
		extract( $args );
		$the_title      = apply_filters( 'widget_title', $instance['my_calendar_upcoming_title'], $instance, $args );
		$the_template   = $instance['my_calendar_upcoming_template'];
		$the_substitute = $instance['my_calendar_no_events_text'];
		$before         = ( '' != $instance['my_calendar_upcoming_before'] ) ? esc_attr( $instance['my_calendar_upcoming_before'] ) : 3;
		$after          = ( '' != $instance['my_calendar_upcoming_after'] ) ? esc_attr( $instance['my_calendar_upcoming_after'] ) : 3;
		$skip           = ( '' != $instance['my_calendar_upcoming_skip'] ) ? esc_attr( $instance['my_calendar_upcoming_skip'] ) : 0;
		$show_today     = ( 'no' == $instance['my_calendar_upcoming_show_today'] ) ? 'no' : 'yes';
		$type           = esc_attr( $instance['my_calendar_upcoming_type'] );
		$order          = esc_attr( $instance['my_calendar_upcoming_order'] );
		$the_category   = ( '' == $instance['my_calendar_upcoming_category'] ) ? 'default' : esc_attr( $instance['my_calendar_upcoming_category'] );
		$author         = ( ! isset( $instance['my_calendar_upcoming_author'] ) || '' == $instance['my_calendar_upcoming_author'] ) ? 'default' : esc_attr( $instance['my_calendar_upcoming_author'] );
		$host           = ( ! isset( $instance['mc_host'] ) || '' == $instance['mc_host'] ) ? 'default' : esc_attr( $instance['mc_host'] );
		$ltype          = ( ! isset( $instance['ltype'] ) || '' == $instance['ltype'] ) ? '' : esc_attr( $instance['ltype'] );
		$lvalue         = ( ! isset( $instance['lvalue'] ) || '' == $instance['lvalue'] ) ? '' : esc_attr( $instance['lvalue'] );
		$widget_link    = ( isset( $instance['my_calendar_upcoming_linked'] ) && 'yes' == $instance['my_calendar_upcoming_linked'] ) ? mc_get_uri( false, $instance ) : '';
		$widget_link    = ( ! empty( $instance['mc_link'] ) ) ? esc_url( $instance['mc_link'] ) : $widget_link;
		$widget_title   = empty( $the_title ) ? '' : $the_title;
		$widget_title   = ( '' == $widget_link ) ? $widget_title : "<a href='$widget_link'>$widget_title</a>";
		$widget_title   = ( '' != $widget_title ) ? $before_title . $widget_title . $after_title : '';
		$month          = ( 0 === strpos( $type, 'month+' ) ) ? date_i18n( 'F', strtotime( $type ) ) : date_i18n( 'F', current_time( 'timestamp' ) );
		$widget_title   = str_replace( '{month}', $month, $widget_title );
		$from           = ( isset( $instance['mc_from'] ) ) ? $instance['mc_from'] : false;
		$to             = ( isset( $instance['mc_to'] ) ) ? $instance['mc_to'] : false;
		$site           = ( isset( $instance['mc_site'] ) ) ? $instance['mc_site'] : false;

		$args = array(
			'before'     => $before,
			'after'      => $after,
			'type'       => $type,
			'category'   => $the_category,
			'template'   => $the_template,
			'substitute' => $the_substitute,
			'order'      => $order,
			'skip'       => $skip,
			'show_today' => $show_today,
			'author'     => $author,
			'host'       => $host,
			'from'       => $from,
			'ltype'      => $ltype,
			'lvalue'     => $lvalue,
			'to'         => $to,
			'site'       => $site,
		);

		$the_events = my_calendar_upcoming_events( $args );
		if ( '' != $the_events ) {
			echo $before_widget;
			echo $widget_title;
			echo $the_events;
			echo $after_widget;
		}
	}

	/**
	 * Edit the upcoming events widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	function form( $instance ) {
		$defaults = mc_widget_defaults();
		$title    = ( isset( $instance['my_calendar_upcoming_title'] ) ) ? esc_attr( $instance['my_calendar_upcoming_title'] ) : '';
		$template = ( isset( $instance['my_calendar_upcoming_template'] ) ) ? esc_attr( $instance['my_calendar_upcoming_template'] ) : '';
		if ( ! $template ) {
			$template = $defaults['upcoming']['template'];
		}
		$text       = ( isset( $instance['my_calendar_no_events_text'] ) ) ? esc_attr( $instance['my_calendar_no_events_text'] ) : '';
		$category   = ( isset( $instance['my_calendar_upcoming_category'] ) ) ? esc_attr( $instance['my_calendar_upcoming_category'] ) : '';
		$author     = ( isset( $instance['my_calendar_upcoming_author'] ) ) ? esc_attr( $instance['my_calendar_upcoming_author'] ) : '';
		$host       = ( isset( $instance['mc_host'] ) ) ? esc_attr( $instance['mc_host'] ) : '';
		$ltype      = ( isset( $instance['ltype'] ) ) ? esc_attr( $instance['ltype'] ) : '';
		$lvalue     = ( isset( $instance['lvalue'] ) ) ? esc_attr( $instance['lvalue'] ) : '';
		$before     = ( isset( $instance['my_calendar_upcoming_before'] ) ) ? esc_attr( $instance['my_calendar_upcoming_before'] ) : 3;
		$after      = ( isset( $instance['my_calendar_upcoming_after'] ) ) ? esc_attr( $instance['my_calendar_upcoming_after'] ) : 3;
		$show_today = ( isset( $instance['my_calendar_upcoming_show_today'] ) ) ? esc_attr( $instance['my_calendar_upcoming_show_today'] ) : 'no';
		$type       = ( isset( $instance['my_calendar_upcoming_type'] ) ) ? esc_attr( $instance['my_calendar_upcoming_type'] ) : 'events';
		$order      = ( isset( $instance['my_calendar_upcoming_order'] ) ) ? esc_attr( $instance['my_calendar_upcoming_order'] ) : 'asc';
		$linked     = ( isset( $instance['my_calendar_upcoming_linked'] ) ) ? esc_attr( $instance['my_calendar_upcoming_linked'] ) : '';
		$from       = ( isset( $instance['mc_from'] ) ) ? esc_attr( $instance['mc_from'] ) : '';
		$to         = ( isset( $instance['mc_to'] ) ) ? esc_attr( $instance['mc_to'] ) : '';
		$site       = ( isset( $instance['mc_site'] ) ) ? esc_attr( $instance['mc_site'] ) : false;

		if ( 'yes' == $linked ) {
			$default_link = mc_get_uri( false, $instance );
		} else {
			$default_link = '';
		}
		$link = ( ! empty( $instance['mc_link'] ) ) ? esc_url( $instance['mc_link'] ) : $default_link;
		$skip = ( isset( $instance['my_calendar_upcoming_skip'] ) ) ? esc_attr( $instance['my_calendar_upcoming_skip'] ) : 0;
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_title' ); ?>"><?php _e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_title' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
		</p>
		<?php
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_site' ); ?>"><?php _e( 'Blog ID', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_site' ); ?>" name="<?php echo $this->get_field_name( 'mc_site' ); ?>" value="<?php echo esc_attr( $site ); ?>"/>
		</p>
		<?php
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_template' ); ?>"><?php _e( 'Template', 'my-calendar' ); ?></label><br/>
			<textarea class="widefat" rows="6" cols="20" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_template' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_template' ); ?>"><?php echo esc_attr( $template ); ?></textarea>
		</p>
		<fieldset>
		<legend><?php _e( 'Widget Options', 'my-calendar' ); ?></legend>
		<?php $config_url = admin_url( 'admin.php?page=my-calendar-config' ); ?>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_link' ); ?>"><?php _e( 'Widget title links to:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_link' ); ?>" name="<?php echo $this->get_field_name( 'mc_link' ); ?>" value="<?php echo $link; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_type' ); ?>"><?php _e( 'Display upcoming events by:', 'my-calendar' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'my_calendar_upcoming_type' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_type' ); ?>">
				<option value="events" <?php echo ( $type == 'events' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Events (e.g. 2 past, 3 future)', 'my-calendar' ) ?></option>
				<option value="days" <?php echo ( $type == 'days' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Dates (e.g. 4 days past, 5 forward)', 'my-calendar' ) ?></option>
				<option value="month" <?php echo ( $type == 'month' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show current month', 'my-calendar' ) ?></option>
				<option value="month+1" <?php echo ( $type == 'month+1' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show next month', 'my-calendar' ) ?></option>
				<option value="month+2" <?php echo ( $type == 'month+2' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 2nd month out', 'my-calendar' ) ?></option>
				<option value="month+3" <?php echo ( $type == 'month+3' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 3rd month out', 'my-calendar' ) ?></option>
				<option value="month+4" <?php echo ( $type == 'month+4' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 4th month out', 'my-calendar' ) ?></option>
				<option value="month+5" <?php echo ( $type == 'month+5' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 5th month out', 'my-calendar' ) ?></option>
				<option value="month+6" <?php echo ( $type == 'month+6' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 6th month out', 'my-calendar' ) ?></option>
				<option value="month+7" <?php echo ( $type == 'month+7' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 7th month out', 'my-calendar' ) ?></option>
				<option value="month+8" <?php echo ( $type == 'month+8' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 8th month out', 'my-calendar' ) ?></option>
				<option value="month+9" <?php echo ( $type == 'month+9' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 9th month out', 'my-calendar' ) ?></option>
				<option value="month+10" <?php echo ( $type == 'month+10' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 10th month out', 'my-calendar' ) ?></option>
				<option value="month+11" <?php echo ( $type == 'month+11' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 11th month out', 'my-calendar' ) ?></option>
				<option value="month+12" <?php echo ( $type == 'month+12' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show 12th month out', 'my-calendar' ) ?></option>
				<option value="year" <?php echo ( $type == 'year' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Show current year', 'my-calendar' ) ?></option>
				<option value="custom" <?php echo ( $type == 'custom' ) ? 'selected="selected"' : ''; ?>><?php _e( 'Custom Dates', 'my-calendar' ) ?></option>
			</select>
		</p>
		<?php
		if ( 'custom' == $type ) {
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_from' ); ?>"><?php _e( 'Start date', 'my-calendar' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'mc_from' ); ?>" name="<?php echo $this->get_field_name( 'mc_from' ); ?>" value="<?php echo $from; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_to' ); ?>"><?php _e( 'End date', 'my-calendar' ); ?>:</label>
			<input type="text" id="<?php echo $this->get_field_id( 'mc_to' ); ?>" name="<?php echo $this->get_field_name( 'mc_to' ); ?>" value="<?php echo $to; ?>"/>
		</p>
		<?php
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_skip' ); ?>"><?php _e( 'Skip the first <em>n</em> events', 'my-calendar' ); ?></label>
			<input type="text" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_skip' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_skip' ); ?>" value="<?php echo $skip; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_order' ); ?>"><?php _e( 'Events sort order:', 'my-calendar' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'my_calendar_upcoming_order' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_order' ); ?>">
				<option value="asc" <?php echo ( 'asc' == $order ) ? 'selected="selected"' : ''; ?>><?php _e( 'Ascending (near to far)', 'my-calendar' ) ?></option>
				<option value="desc" <?php echo ( 'desc' == $order ) ? 'selected="selected"' : ''; ?>><?php _e( 'Descending (far to near)', 'my-calendar' ) ?></option>
			</select>
		</p>
		<?php
		if ( ! ( 'month' == $type || 'month+1' == $type || 'year' == $type ) ) {
		?>
			<p>
				<input type="text" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_after' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_after' ); ?>" value="<?php echo $after; ?>" size="1" maxlength="3"/>
				<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_after' ); ?>"><?php printf( __( "%s into the future;", 'my-calendar' ), $type ); ?></label><br/>
				<input type="text" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_before' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_before' ); ?>" value="<?php echo $before; ?>" size="1" maxlength="3"/> <label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_before' ); ?>"><?php printf( __( "%s from the past", 'my-calendar' ), $type ); ?></label>
			</p>
		<?php
		}
		?>
		<p>
			<input type="checkbox" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_show_today' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_show_today' ); ?>" value="yes"<?php echo ( 'yes' == $show_today ) ? ' checked="checked"' : ''; ?> />
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_show_today' ); ?>"><?php _e( "Include today's events", 'my-calendar' ); ?></label>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_no_events_text' ); ?>"><?php _e( 'Show this text if there are no events meeting your criteria:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_no_events_text' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_no_events_text' ); ?>" value="<?php echo $text; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_category' ); ?>"><?php _e( 'Category or categories to display:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_category' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_category' ); ?>" value="<?php echo $category; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_upcoming_author' ); ?>"><?php _e( 'Author or authors to show:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_upcoming_author' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_upcoming_author' ); ?>" value="<?php echo $author; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_host' ); ?>"><?php _e( 'Host or hosts to show:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_host' ); ?>" name="<?php echo $this->get_field_name( 'mc_host' ); ?>" value="<?php echo $host; ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'ltype' ); ?>"><?php _e( 'Location (Type)', 'my-calendar' ); ?></label><br/>
			<select name="<?php echo $this->get_field_name( 'ltype' ); ?>" id="<?php echo $this->get_field_id( 'ltype' ); ?>" class="widefat">
				<option value=''><?php _e( 'All locations', 'my-calendar' ); ?></option>
				<option value='event_label' <?php selected( $ltype, 'event_label' ); ?>><?php _e( 'Location Name', 'my-calendar' ); ?></option>
				<option value='event_city' <?php selected( $ltype, 'event_city' ); ?>><?php _e( 'City', 'my-calendar' ); ?></option>
				<option value='event_state' <?php selected( $ltype, 'event_state' ); ?>><?php _e( 'State', 'my-calendar' ); ?></option>
				<option value='event_postcode' <?php selected( $ltype, 'event_postcode' ); ?>><?php _e( 'Postal Code', 'my-calendar' ); ?></option>
				<option value='event_country' <?php selected( $ltype, 'event_country' ); ?>><?php _e( 'Country', 'my-calendar' ); ?></option>
				<option value='event_region' <?php selected( $ltype, 'event_region' ); ?>><?php _e( 'Region', 'my-calendar' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'lvalue' ); ?>"><?php _e( 'Location (Value)', 'my-calendar' ); ?></label><br/>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'lvalue' ); ?>" id="<?php echo $this->get_field_id( 'lvalue' ); ?>" value="<?php echo esc_attr( $lvalue ); ?>" />
		</p>
	<?php
	}

	/**
	 * Update the My Calendar Upcoming Widget settings.
	 *
	 * @param object $new Widget settings new data.
	 * @param object $instance Widget settings instance.
	 *
	 * @return $instance Updated instance.
	 */
	function update( $new, $instance ) {
		$instance = array_map( 'mc_kses_post', array_merge( $instance, $new ) );
		if ( ! isset( $new['my_calendar_upcoming_show_today'] ) ) {
			$instance['my_calendar_upcoming_show_today'] = 'no';
		}

		return $instance;
	}
}

/**
 * Generate the widget output for upcoming events.
 *
 * @param array $args Event selection arguments.
 *
 * @return String HTML output list.
 */
function my_calendar_upcoming_events( $args ) {
	$before     = ( isset( $args['before'] ) ) ? $args['before'] : 'default';
	$after      = ( isset( $args['after'] ) ) ? $args['after'] : 'default';
	$type       = ( isset( $args['type'] ) ) ? $args['type'] : 'default';
	$category   = ( isset( $args['category'] ) ) ? $args['category'] : 'default';
	$template   = ( isset( $args['template'] ) ) ? $args['template'] : 'default';
	$substitute = ( isset( $args['fallback'] ) ) ? $args['fallback'] : '';
	$order      = ( isset( $args['order'] ) ) ? $args['order'] : 'asc';
	$skip       = ( isset( $args['skip'] ) ) ? $args['skip'] : 0;
	$show_today = ( isset( $args['show_today'] ) ) ? $args['show_today'] : 'yes';
	$author     = ( isset( $args['author'] ) ) ? $args['author'] : 'default';
	$host       = ( isset( $args['host'] ) ) ? $args['host'] : 'default';
	$ltype      = ( isset( $args['ltype'] ) ) ? $args['ltype'] : '';
	$lvalue     = ( isset( $args['lvalue'] ) ) ? $args['lvalue'] : '';
	$from       = ( isset( $args['from'] ) ) ? $args['from'] : '';
	$to         = ( isset( $args['to'] ) ) ? $args['to'] : '';
	$site       = ( isset( $args['site'] ) ) ? $args['site'] : false;

	if ( $site ) {
		$site = ( 'global' == $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}

	$hash         = md5( implode( ',', $args ) );
	$output       = '';
	$defaults     = mc_widget_defaults();
	$display_type = ( 'default' == $type ) ? $defaults['upcoming']['type'] : $type;
	$display_type = ( '' == $display_type ) ? 'events' : $display_type;

	// Get number of units we should go into the future.
	$after = ( 'default' == $after ) ? $defaults['upcoming']['after'] : $after;
	$after = ( ''== $after ) ? 10 : $after;

	// Get number of units we should go into the past.
	$before   = ( 'default' == $before ) ? $defaults['upcoming']['before'] : $before;
	$before   = ( '' == $before ) ? 0 : $before;
	$category = ( 'default' == $category ) ? '' : $category;

	// allow reference by file to external template.
	if ( '' != $template && mc_file_exists( $template ) ) {
		$template = @file_get_contents( mc_get_file( $template ) );
	}

	$template      = ( ! $template || 'default' == $template ) ? $defaults['upcoming']['template'] : $template;
	if ( mc_key_exists( $template ) ) {
		$template = mc_get_custom_template( $template );
	}

	$template      = apply_filters( 'mc_upcoming_events_template', $template );
	$no_event_text = ( '' == $substitute ) ? $defaults['upcoming']['text'] : $substitute;
	$header        = "<ul id='upcoming-events-$hash' class='upcoming-events'>";
	$footer        = '</ul>';
	$display_events = ( 'events' == $display_type || 'event' == $display_type ) ? true : false;
	if ( ! $display_events ) {
		$temp_array = array();
		if ( 'days' == $display_type ) {
			$from = date( 'Y-m-d', strtotime( "-$before days" ) );
			$to   = date( 'Y-m-d', strtotime( "+$after days" ) );
		}
		if ( 'month' == $display_type ) {
			$from = date( 'Y-m-1' );
			$to   = date( 'Y-m-t' );
		}
		if ( 'custom' == $display_type  && '' != $from && '' != $to ) {
			$from = date( 'Y-m-d', strtotime( $from ) );
			$to = ( 'today' == $to ) ? date( 'Y-m-d', current_time( 'timestamp' ) ) : date( 'Y-m-d', strtotime( $to ) );
		}
		/* Yes, this is crude. But sometimes simplicity works best. There are only 12 possibilities, after all. */
		if ( 'month+1' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+1 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+1 month' ) );
		}
		if ( 'month+2' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+2 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+2 month' ) );
		}
		if ( 'month+3' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+3 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+3 month' ) );
		}
		if ( 'month+4' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+4 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+4 month' ) );
		}
		if ( 'month+5' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+5 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+5 month' ) );
		}
		if ( 'month+6' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+6 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+6 month' ) );
		}
		if ( 'month+7' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+7 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+7 month' ) );
		}
		if ( 'month+8' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+8 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+8 month' ) );
		}
		if ( 'month+9' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+9 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+9 month' ) );
		}
		if ( 'month+10' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+10 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+10 month' ) );
		}
		if ( 'month+11' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+11 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+11 month' ) );
		}
		if ( 'month+12' == $display_type ) {
			$from = date( 'Y-m-1', strtotime( '+12 month' ) );
			$to   = date( 'Y-m-t', strtotime( '+12 month' ) );
		}
		if ( $display_type == 'year' ) {
			$from = date( 'Y-1-1' );
			$to   = date( 'Y-12-31' );
		}
		$from = apply_filters( 'mc_upcoming_date_from', $from, $args );
		$to   = apply_filters( 'mc_upcoming_date_to', $to, $args );

		$query = array(
			'from'     => $from,
			'to'       => $to,
			'category' => $category,
			'ltype'    => $ltype,
			'lvalue'   => $lvalue,
			'author'   => $author,
			'host'     => $host,
			'search'   => '',
			'source'   => 'upcoming',
			'site'     => $site,
		);
		$query       = apply_filters( 'mc_upcoming_attributes', $query, $args );
		$event_array = my_calendar_events( $query );

		if ( 0 != count( $event_array ) ) {
			foreach ( $event_array as $key => $value ) {
				if ( is_array( $value ) ) {
					foreach ( $value as $k => $v ) {
						if ( mc_private_event( $v ) ) {
							// this event is private.
						} else {
							$temp_array[] = $v;
						}
					}
				}
			}
		}
		$i         = 0;
		$last_item = '';
		$last_id   = '';
		$last_date = '';
		$skips     = array();
		foreach ( reverse_array( $temp_array, true, $order ) as $event ) {
			$details = mc_create_tags( $event );
			$item    = apply_filters( 'mc_draw_upcoming_event', '', $details, $template, $args );
			if ( '' == $item  ) {
				$item = mc_draw_template( $details, $template );
			}
			if ( $i < $skip && 0 != $skip ) {
				$i ++;
			} else {
				$today    = date( 'Y-m-d H:i', current_time( 'timestamp' ) );
				$date     = date( 'Y-m-d H:i', strtotime( $details['dtstart'] ) );
				$class    = ( true === my_calendar_date_comp( $date, $today ) ) ? "past-event" : "future-event";
				$category = mc_category_class( $details, 'mc_' );
				$classes  = mc_event_classes( $event, $event->occur_id, 'upcoming' );

				$prepend = apply_filters( 'mc_event_upcoming_before', "<li class='$class $category $classes'>", $class, $category );
				$append  = apply_filters( 'mc_event_upcoming_after', '</li>', $class, $category );
				// If same group, and same date, use it.
				if ( ( $details['group'] !== $last_id || $details['date'] == $last_date ) || '0' == $details['group'] ) {
					if ( ! in_array( $details['dateid'], $skips ) ) {
						$output .= ( $item == $last_item ) ? '' : $prepend . $item . $append;
					}
				}
			}
			$skips[]   = $details['dateid']; // Prevent the same event from showing more than once.
			$last_id   = $details['group']; // Prevent group events from displaying in a row. Not if there are intervening events.
			$last_item = $item;
			$last_date = $details['date'];
		}
	} else {
		$query = array(
			'category' => $category,
			'before'   => $before,
			'after'    => $after,
			'today'    => $show_today,
			'author'   => $author,
			'host'     => $host,
			'ltype'    => $ltype,
			'lvalue'   => $lvalue,
			'site'     => $site,
		);
		$events = mc_get_all_events( $query );

		$holidays      = mc_get_all_holidays( $before, $after, $show_today );
		$holiday_array = mc_set_date_array( $holidays );

		if ( is_array( $events ) && ! empty( $events ) ) {
			$event_array = mc_set_date_array( $events );
			if ( is_array( $holidays ) && count( $holidays ) > 0 ) {
				$event_array = mc_holiday_limit( $event_array, $holiday_array ); // if there are holidays, rejigger.
			}
		}
		if ( ! empty( $event_array ) ) {
			$output .= mc_produce_upcoming_events( $event_array, $template, 'list', $order, $skip, $before, $after, $show_today );
		} else {
			$output = '';
		}
	}
	if ( '' != $output ) {
		$output = apply_filters( 'mc_upcoming_events_header', $header ) . $output . apply_filters( 'mc_upcoming_events_footer', $footer );
		$return = mc_run_shortcodes( $output );
	} else {
		$return = stripcslashes( $no_event_text );
	}

	if ( $site ) {
		restore_current_blog();
	}

	return $return;
}

/**
 * For a set of grouped events, get the total time spanned by the group of events.
 *
 * @param int $group_id Event Group ID.
 *
 * @return array beginning and ending dates
 */
function mc_span_time( $group_id ) {
	global $wpdb;
	$mcdb = $wpdb;
	if ( 'true' == get_option( 'mc_remote' ) && function_exists( 'mc_remote_db' ) ) {
		$mcdb = mc_remote_db();
	}
	$group_id = (int) $group_id;
	$sql      = 'SELECT event_begin, event_time, event_end, event_endtime FROM ' . my_calendar_table() . ' WHERE event_group_id = %d ORDER BY event_begin ASC';
	$dates    = $mcdb->get_results( $wpdb->prepare( $sql, $group_id ) );
	$count    = count( $dates );
	$last     = $count - 1;
	$begin    = $dates[0]->event_begin . ' ' . $dates[0]->event_time;
	$end      = $dates[ $last ]->event_end . ' ' . $dates[ $last ]->event_endtime;

	return array( $begin, $end );
}

/**
 * Generates the list of upcoming events when counting by events rather than a date pattern
 *
 * @param array  $events (Array of events to analyze).
 * @param string $template Custom template to use for display.
 * @param string $type Usually 'list', but also RSS or export.
 * @param string $order 'asc' or 'desc'.
 * $param int    $skip Number of events to skip over.
 * @param int    $before How many past events to show.
 * @param int    $after How many future events to show.
 * @param string string $show_today 'yes' (anything else is false); whether to include events happening today.
 * @param string $context Display context.
 *
 * @return string; HTML output of list
 */
function mc_produce_upcoming_events( $events, $template, $type = 'list', $order = 'asc', $skip = 0, $before, $after, $show_today = 'yes', $context = 'filters' ) {
	// $events has +5 before and +5 after if those values are non-zero.
	// $events equals array of events based on before/after queries. Nothing skipped, order is not set, holiday conflicts removed.
	$output      = array();
	$near_events = $temp_array = array();
	$past        = 1;
	$future      = 1;
	$now         = current_time( 'timestamp' );
	$today       = date( 'Y-m-d', $now );
	@uksort( $events, "mc_timediff_cmp" ); // Sort all events by proximity to current date.
	$count = count( $events );
	$group = array();
	$spans = array();
	$occur = array();
	$extra = 0;
	$i     = 0;
	// Create near_events array.
	$last_events = array();
	$last_group  = array();
	if ( is_array( $events ) ) {
		foreach ( $events as $k => $event ) {
			if ( $i < $count ) {
				if ( is_array( $event ) ) {
					foreach ( $event as $e ) {
						if ( mc_private_event( $e ) ) {

						} else {
							$beginning = $e->occur_begin;
							$end       = $e->occur_end;
							// Store span time in an array to avoid repeating database query.
							if ( 1 == $e->event_span && ( ! isset( $spans[ $e->occur_group_id ] ) ) ) {
								// This is a multi-day event: treat each event as if it spanned the entire range of the group.
								$span_time                   = mc_span_time( $e->occur_group_id );
								$beginning                   = $span_time[0];
								$end                         = $span_time[1];
								$spans[ $e->occur_group_id ] = $span_time;
							} elseif ( 1 == $e->event_span && ( isset( $spans[ $e->occur_group_id ] ) ) ) {
								$span_time = $spans[ $e->occur_group_id ];
								$beginning = $span_time[0];
								$end       = $span_time[1];
							}
							$current = date( 'Y-m-d H:i:00', current_time( 'timestamp' ) );
							if ( $e ) {
							// If a multi-day event, show only once.
								if ( 0 != $e->occur_group_id && 1 == $e->event_span && in_array( $e->occur_group_id, $group ) || in_array( $e->occur_id, $occur ) ) {
									$md = true;
								} else {
									$group[] = $e->occur_group_id;
									$occur[] = $e->occur_id;
									$md      = false;
								}
								// end multi-day reduction
								if ( ! $md ) {
									// check if this event instance or this event group has already been displayed
									$same_event = ( in_array( $e->occur_id, $last_events ) ) ? true : false;
									$same_group = ( in_array( $e->occur_group_id, $last_group ) ) ? true : false;
									if ( 'yes' == $show_today && my_calendar_date_equal( $beginning, $current ) ) {
										$in_total = apply_filters( 'mc_include_today_in_total', 'yes' ); // count todays events in total
										if ( 'no' != $in_total ) {
											$near_events[] = $e;
											if ( $before > $after ) {
												$future ++;
											} else {
												$past ++;
											}
										} else {
											$near_events[] = $e;
										}
									} elseif ( ( $past <= $before && $future <= $after ) ) {
										$near_events[] = $e; // If neither limit is reached, split off ly.
									} elseif ( $past <= $before && ( my_calendar_date_comp( $beginning, $current ) ) ) {
										$near_events[] = $e; // Split off another past event.
									} elseif ( $future <= $after && ( ! my_calendar_date_comp( $end, $current ) ) ) {
										$near_events[] = $e; // Split off another future event.
									}

									if ( my_calendar_date_comp( $beginning, $current ) ) {
										$past ++;
									} elseif ( my_calendar_date_equal( $beginning, $current ) ) {
										if ( 'yes' == $show_today ) {
											$extra ++;
										}
									} elseif ( ! my_calendar_date_comp( $end, $current ) ) {
										$future ++;
									}

									$last_events[] = $e->occur_id;
									$last_group[]  = $e->occur_group_id;
									$last_date     = $beginning;
								}
								if ( $past > $before && $future > $after && 'yes' != $show_today ) {
									break;
								}
							}
						}
					}
				}
			}
		}
	}
	$events = $near_events;
	@usort( $events, "mc_datetime_cmp" ); // sort split events by date

	if ( is_array( $events ) ) {
		foreach ( array_keys( $events ) as $key ) {
			$event =& $events[ $key ];
			$temp_array[] = $event;
		}
		$i      = 0;
		$groups = array();
		$skips  = array();

		foreach ( reverse_array( $temp_array, true, $order ) as $event ) {
			$details = mc_create_tags( $event, $context );
			if ( ! in_array( $details['group'], $groups ) ) {
				$date     = date( 'Y-m-d H:i:s', strtotime( $details['dtstart'] ) );
				$class    = ( true === my_calendar_date_comp( $date, $today . ' ' . date( 'H:i', current_time( 'timestamp' ) ) ) ) ? "past-event" : "future-event";
				$category = mc_category_class( $details, 'mc_' );
				$classes  = mc_event_classes( $event, $event->occur_id, 'upcoming' );

				if ( my_calendar_date_equal( $date, $today ) ) {
					$class = 'today';
				}
				if ( $details['event_span'] == 1 ) {
					$class = 'multiday';
				}
				if ( 'list' == $type ) {
					$prepend = "\n<li class=\"$class $category $classes\">";
					$append  = "</li>\n";
				} else {
					$prepend = '';
					$append  = '';
				}
				$prepend = apply_filters( 'mc_event_upcoming_before', $prepend, $class, $category, $date );
				$append  = apply_filters( 'mc_event_upcoming_after', $append, $class, $category, $date );

				if ( $i < $skip && 0 != $skip ) {
					$i ++;
				} else {
					if ( ! in_array( $details['dateid'], $skips ) ) {

						$item = apply_filters( 'mc_draw_upcoming_event', '', $details, $template, $type );
						if ( '' == $item ) {
							$item = mc_draw_template( $details, $template, $type );
						}

						$output[] = apply_filters( 'mc_event_upcoming', $prepend . $item . $append, $event );
						$skips[]  = $details['dateid'];
					}
				}
				if ( 1 == $details['event_span'] ) {
					$groups[] = $details['group'];
				}
			}
		}
	}
	// If more items than there should be (due to handling of current-day's events), pop off.
	$intended = $before + $after + $extra;
	$actual   = count( $output );
	if ( $actual > $intended ) {
		for ( $i = 0; $i < ( $actual - $intended ); $i ++ ) {
			array_pop( $output );
		}
	}
	$html = '';
	foreach ( $output as $out ) {
		$html .= $out;
	}

	return $html;
}

/**
 * Process the Today's Events widget.
 *
 * @param array $args Event & output construction parameters.
 *
 * @return string HTML.
 */
function my_calendar_todays_events( $args ) {
	$category   = ( isset( $args['category'] ) ) ? $args['category'] : 'default';
	$template   = ( isset( $args['template'] ) ) ? $args['template'] : 'default';
	$substitute = ( isset( $args['fallback'] ) ) ? $args['fallback'] : '';
	$author     = ( isset( $args['author'] ) ) ? $args['author'] : 'all';
	$host       = ( isset( $args['host'] ) ) ? $args['host'] : 'all';
	$date       = ( isset( $args['date'] ) ) ? $args['date'] : false;
	$site       = ( isset( $args['site'] ) ) ? $args['site'] : false;

	if ( $site ) {
		$site = ( 'global' == $site ) ? BLOG_ID_CURRENT_SITE : $site;
		switch_to_blog( $site );
	}

	$params = array(
		'category'   =>$category,
		'template'   =>$template,
		'substitute' =>$substitute,
		'author'     =>$author,
		'host'       =>$host,
		'date'       =>$date,
	);
	$hash   = md5( implode( ',', $params ) );
	$output = '';

	// allow reference by file to external template.
	if ( '' != $template && mc_file_exists( $template ) ) {
		$template = @file_get_contents( mc_get_file( $template ) );
	}
	$defaults = mc_widget_defaults();
	$template = ( ! $template || 'default' == $template ) ? $defaults['today']['template'] : $template;

	if ( mc_key_exists( $template ) ) {
		$template = mc_get_custom_template( $template );
	}

	$category      = ( 'default' == $category ) ? $defaults['today']['category'] : $category;
	$no_event_text = ( '' == $substitute ) ? $defaults['today']['text'] : $substitute;
	if ( $date ) {
		$from   = date( 'Y-m-d', strtotime( $date ) );
		$to     = date( 'Y-m-d', strtotime( $date ) );
	} else {
		$from   = date( 'Y-m-d', current_time( 'timestamp' ) );
		$to     = date( 'Y-m-d', current_time( 'timestamp' ) );
	}

	$args     = array(
		'from'     => $from,
		'to'       => $to,
		'category' => $category,
		'ltype'    => '',
		'lvalue'   => '',
		'author'   => $author,
		'host'     => $host,
		'search'   => '',
		'source'   => 'upcoming',
		'site'     => $site,
	);
	$args   = apply_filters( 'mc_upcoming_attributes', $args, $params );
	$events = my_calendar_events( $args );

	$today         = ( isset( $events[ $from ] ) ) ? $events[ $from ] : false;
	$header        = "<ul id='todays-events-$hash' class='todays-events'>";
	$footer        = '</ul>';
	$groups        = array();
	$todays_events = array();
	// quick loop through all events today to check for holidays
	if ( is_array( $today ) ) {
		foreach ( $today as $e ) {
			if ( ! mc_private_event( $e ) && ! in_array( $e->event_group_id, $groups ) ) {
				$event_details = mc_create_tags( $e );
				$ts            = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $e->ts_occur_begin ) ) );
				$end           = strtotime( get_date_from_gmt( date( 'Y-m-d H:i:s', $e->ts_occur_end ) ) );
				$now           = current_time( 'timestamp' );
				$category      = mc_category_class( $e, 'mc_' );
				if ( $ts < $now && $end > $now ) {
					$class = 'on-now';
				} elseif ( $now < $ts ) {
					$class = 'future-event';
				} elseif ( $now > $ts ) {
					$class = 'past-event';
				}

				$prepend = apply_filters( 'mc_todays_events_before', "<li class='$class $category'>", $class, $category );
				$append  = apply_filters( 'mc_todays_events_after', '</li>' );

				$item = apply_filters( 'mc_draw_todays_event', '', $event_details, $template );
				if ( '' == $item ) {
					$item = mc_draw_template( $event_details, $template );
				}
				$todays_events[ $ts ][] = $prepend . $item . $append;
			}
		}
		$todays_events = apply_filters( 'mc_event_today', $todays_events, $events );
		foreach ( $todays_events as $k => $t ) {
			foreach ( $t as $now ) {
				$output .= $now;
			}
		}
		if ( 0 != count( $events ) ) {
			$return = apply_filters( 'mc_todays_events_header', $header ) . $output . apply_filters( 'mc_todays_events_footer', $footer );
		} else {
			$return = stripcslashes( $no_event_text );
		}
	} else {
		$return = stripcslashes( $no_event_text );
	}

	if ( $site ) {
		restore_current_blog();
	}

	return mc_run_shortcodes( $return );
}

/**
 * Mini calendar widget
 */
class My_Calendar_Mini_Widget extends WP_Widget {

	function __construct() {
		parent::__construct( false, $name = __( 'My Calendar: Mini Calendar', 'my-calendar' ), array( 'customize_selective_refresh' => true ) );
	}

	/**
	 * Build the My Calendar Mini calendar widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	function widget( $args, $instance ) {
		extract( $args );
		if ( ! empty( $instance ) ) {
			$the_title   = apply_filters( 'widget_title', $instance['my_calendar_mini_title'], $instance, $args );
			$category    = ( '' == $instance['my_calendar_mini_category'] ) ? 'all' : $instance['my_calendar_mini_category'];
			$time        = ( '' == $instance['my_calendar_mini_time'] ) ? 'month' : $instance['my_calendar_mini_time'];
			$widget_link = ( ! isset( $instance['mc_link'] ) || '' == $instance['mc_link'] ) ? '' : esc_url( $instance['mc_link'] );
			$above       = ( empty( $instance['above'] ) ) ? 'none' : $instance['above'];
			$below       = ( empty( $instance['below'] ) ) ? 'none' : $instance['below'];
			$author      = ( ! isset( $instance['author'] ) || '' == $instance['author'] ) ? null : $instance['author'];
			$host        = ( ! isset( $instance['host'] ) || '' == $instance['host'] ) ? null : $instance['host'];
			$ltype       = ( ! isset( $instance['ltype'] ) || '' == $instance['ltype'] ) ? '' : $instance['ltype'];
			$lvalue      = ( ! isset( $instance['lvalue'] ) || '' == $instance['lvalue'] ) ? '' : $instance['lvalue'];
			$site        = ( ! isset( $instance['site'] ) || '' == $instance['site'] ) ? false : $instance['site'];
			$months      = ( ! isset( $instance['months'] ) || '' == $instance['months'] ) ? false : $instance['months'];
		} else {
			$the_title   = '';
			$category    = '';
			$time        = '';
			$widget_link = '';
			$above       = '';
			$below       = '';
			$host        = '';
			$author      = '';
			$ltype       = '';
			$lvalue      = '';
			$site        = '';
			$months      = '';
		}

		if ( '' != $the_title ) {
			$title = ( '' != $widget_link ) ? "<a href='$widget_link'>$the_title</a>" : $the_title;
			$title = ( '' != $title ) ? $before_title . $title . $after_title : '';
		} else {
			$title = '';
		}

		$calendar = array(
			'name'     => 'mini',
			'format'   => 'mini',
			'category' => $category,
			'time'     => $time,
			'ltype'    => $ltype,
			'lvalue'   => $lvalue,
			'id'       => str_replace( 'my_calendar', 'mc', $args['widget_id'] ),
			'author'   => $author,
			'host'     => $host,
			'above'    => $above,
			'below'    => $below,
			'site'     => $site,
			'month'    => $months,
			'source'   => 'widget',
		);

		$the_events = my_calendar( $calendar );
		if ( '' != $the_events ) {
			echo $before_widget . $title . $the_events . $after_widget;
		}
	}

	/**
	 * Edit the mini calendar widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	function form( $instance ) {
		$title           = empty( $instance['my_calendar_mini_title'] ) ? '' : $instance['my_calendar_mini_title'];
		$widget_time     = empty( $instance['my_calendar_mini_time'] ) ? '' : $instance['my_calendar_mini_time'];
		$widget_category = empty( $instance['my_calendar_mini_category'] ) ? '' : $instance['my_calendar_mini_category'];
		$above           = ( isset( $instance['above'] ) ) ? $instance['above'] : 'none';
		$below           = ( isset( $instance['below'] ) ) ? $instance['below'] : 'none';
		$widget_link     = ( isset( $instance['mc_link'] ) ) ? esc_url( $instance['mc_link'] ) : '';
		$host            = ( isset( $instance['host'] ) ) ? $instance['host'] : '';
		$ltype           = ( isset( $instance['ltype'] ) ) ? $instance['ltype'] : '';
		$lvalue          = ( isset( $instance['lvalue'] ) ) ? $instance['lvalue'] : '';
		$site            = ( isset( $instance['site'] ) ) ? $instance['site'] : '';
		$months          = ( isset( $instance['months'] ) ) ? $instance['months'] : '';
		$author          = ( isset( $instance['author'] ) ) ? $instance['author'] : '';
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_mini_title' ); ?>"><?php _e( 'Title', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_mini_title' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_mini_title' ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
		</p>
		<?php
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'site' ); ?>"><?php _e( 'Blog ID', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'site' ); ?>" name="<?php echo $this->get_field_name( 'site' ); ?>" value="<?php echo esc_attr( $site ); ?>"/>
		</p>
		<?php
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id( 'mc_link' ); ?>"><?php _e( 'Widget Title Link', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'mc_link' ); ?>" name="<?php echo $this->get_field_name( 'mc_link' ); ?>" value="<?php echo esc_url( $widget_link ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'my_calendar_mini_category' ); ?>"><?php _e( 'Category or categories to display:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo $this->get_field_id( 'my_calendar_mini_category' ); ?>" name="<?php echo $this->get_field_name( 'my_calendar_mini_category' ); ?>" value="<?php echo esc_attr( $widget_category ); ?>"/>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'above' ); ?>"><?php _e( 'Navigation above calendar', 'my-calendar' ); ?></label>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'above' ); ?>" id="<?php echo $this->get_field_id( 'above' ); ?>" value="<?php echo ( $above == '' ) ? 'nav,jump,print' : esc_attr( $above ); ?>" aria-describedby='<?php echo $this->get_field_id( 'below' ); ?>-navigation-fields' />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'below' ); ?>"><?php _e( 'Navigation below calendar', 'my-calendar' ); ?></label>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'below' ); ?>" id="<?php echo $this->get_field_id( 'below' ); ?>" value="<?php echo ( $below == '' ) ? 'key' : esc_attr( $below ); ?>" aria-describedby='<?php echo $this->get_field_id( 'below' ); ?>-navigation-fields' />
		</p>
		<p id='<?php echo $this->get_field_id( 'below' ); ?>-navigation-fields'>
			<?php _e( 'Navigation options:', 'my-calendar' ); ?> <code>nav,jump,print,key,feeds,exports,none</code>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'author' ); ?>"><?php _e( 'Limit by Author', 'my-calendar' ); ?></label><br/>
			<select name="<?php echo $this->get_field_name( 'author' ); ?>" id="<?php echo $this->get_field_id( 'author' ); ?>" multiple="multiple" class="widefat">
				<option value="all"><?php _e( 'All authors', 'my-calendar' ); ?></option>
				<?php echo mc_selected_users( $author ); ?>
			</select>
		</p>
		<p>
			<label
				for="<?php echo $this->get_field_id( 'host' ); ?>"><?php _e( 'Limit by Host', 'my-calendar' ); ?></label><br/>
			<select name="<?php echo $this->get_field_name( 'host' ); ?>"
			        id="<?php echo $this->get_field_id( 'host' ); ?>" multiple="multiple" class="widefat">
				<option value="all"><?php _e( 'All hosts', 'my-calendar' ); ?></option>
				<?php echo mc_selected_users( $host ); ?>
			</select>
		</p>
		<p>
			<label
				for="<?php echo $this->get_field_id( 'ltype' ); ?>"><?php _e( 'Location (Type)', 'my-calendar' ); ?></label><br/>
			<select name="<?php echo $this->get_field_name( 'ltype' ); ?>" id="<?php echo $this->get_field_id( 'ltype' ); ?>" class="widefat">
				<option value=''><?php _e( 'All locations', 'my-calendar' ); ?></option>
				<option value='event_label' <?php selected( $ltype, 'event_label' ); ?>><?php _e( 'Location Name', 'my-calendar' ); ?></option>
				<option value='event_city' <?php selected( $ltype, 'event_city' ); ?>><?php _e( 'City', 'my-calendar' ); ?></option>
				<option value='event_state' <?php selected( $ltype, 'event_state' ); ?>><?php _e( 'State', 'my-calendar' ); ?></option>
				<option value='event_postcode' <?php selected( $ltype, 'event_postcode' ); ?>><?php _e( 'Postal Code', 'my-calendar' ); ?></option>
				<option value='event_country' <?php selected( $ltype, 'event_country' ); ?>><?php _e( 'Country', 'my-calendar' ); ?></option>
				<option value='event_region' <?php selected( $ltype, 'event_region' ); ?>><?php _e( 'Region', 'my-calendar' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo $this->get_field_id( 'lvalue' ); ?>"><?php _e( 'Location (Value)', 'my-calendar' ); ?></label><br/>
			<input type="text" class="widefat" name="<?php echo $this->get_field_name( 'lvalue' ); ?>"
			       id="<?php echo $this->get_field_id( 'lvalue' ); ?>"
			       value="<?php echo esc_attr( $lvalue ); ?>" />
		</p>
		<p>
			<label
				for="<?php echo $this->get_field_id( 'my_calendar_mini_time' ); ?>"><?php _e( 'Mini-Calendar Timespan:', 'my-calendar' ); ?></label>
			<select id="<?php echo $this->get_field_id( 'my_calendar_mini_time' ); ?>"
			        name="<?php echo $this->get_field_name( 'my_calendar_mini_time' ); ?>">
				<option
					value="month"<?php echo ( $widget_time == 'month' ) ? ' selected="selected"' : ''; ?>><?php _e( 'Month', 'my-calendar' ) ?></option>
				<option
					value="month+1"<?php echo ( $widget_time == 'month+1' ) ? ' selected="selected"' : ''; ?>><?php _e( 'Next Month', 'my-calendar' ) ?></option>
				<option
					value="week"<?php echo ( $widget_time == 'week' ) ? ' selected="selected"' : ''; ?>><?php _e( 'Week', 'my-calendar' ) ?></option>
			</select>
		</p>
		<p>
			<label
				for="<?php echo $this->get_field_id( 'months' ); ?>"><?php _e( 'Months to show in list view', 'my-calendar' ); ?></label>
			<input type="number" max="12" step="1" min="1" class="widefat" name="<?php echo $this->get_field_name( 'months' ); ?>"
			       id="<?php echo $this->get_field_id( 'months' ); ?>"
			       value="<?php echo ( $months == '' ) ? '' : esc_attr( $months ); ?>" />
		</p>
	<?php
	}

	/**
	 * Update the My Calendar Mini Widget settings.
	 *
	 * @param object $new Widget settings new data.
	 * @param object $instance Widget settings instance.
	 *
	 * @return $instance Updated instance.
	 */
	function update( $new, $instance ) {
		$instance['my_calendar_mini_title']    = mc_kses_post( $new['my_calendar_mini_title'] );
		$instance['my_calendar_mini_time']     = mc_kses_post( $new['my_calendar_mini_time'] );
		$instance['my_calendar_mini_category'] = mc_kses_post( $new['my_calendar_mini_category'] );
		$instance['above']                     = ( isset( $new['above'] ) && '' != $new['above'] ) ? $new['above'] : 'none';
		$instance['mc_link']                   = $new['mc_link'];
		$instance['below']                     = ( isset( $new['below'] ) && '' != $new['below'] ) ? $new['below'] : 'none';
		$author                                = $host = '';
		if ( isset( $new['author'] ) ) {
			$author = implode( ',', $new['author'] );
		}
		if ( isset( $new['host'] ) ) {
			$host = implode( ',', $new['host'] );
		}
		$instance['author'] = $author;
		$instance['host']   = $host;
		$instance['ltype']  = ( '' != $new['ltype'] && '' != $new['lvalue'] ) ? $new['ltype'] : '';
		$instance['lvalue'] = ( '' != $new['ltype'] && '' != $new['lvalue'] ) ? $new['lvalue'] : '';
		$instance['site']   = $new['site'];
		$instance['months'] = $new['months'];

		return $instance;
	}
}
