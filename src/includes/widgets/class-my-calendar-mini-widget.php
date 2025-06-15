<?php
/**
 * My Calendar Mini Calendar Widget
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
 * My Calendar Mini Calendar widget class.
 *
 * @category  Widgets
 * @package   My Calendar
 * @author    Joe Dolson
 * @copyright 2009
 * @license   GPLv2 or later
 * @version   1.0
 */
class My_Calendar_Mini_Widget extends WP_Widget {

	/**
	 * Contructor.
	 */
	public function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Mini Calendar', 'my-calendar' ),
			array(
				'customize_selective_refresh' => true,
				'description'                 => __( 'Show events in a compact grid.', 'my-calendar' ),
			)
		);
	}

	/**
	 * Build the My Calendar Mini calendar widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	public function widget( $args, $instance ) {
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = str_replace( 'h1', 'h2', $args['before_title'] );
		$after_title   = str_replace( 'h1', 'h2', $args['after_title'] );
		$widget_id     = isset( $args['widget_id'] ) ? $args['widget_id'] : 'my-calendar-mini-widget';
		if ( ! empty( $instance ) ) {
			$the_title   = apply_filters( 'widget_title', $instance['my_calendar_mini_title'], $instance, $args );
			$category    = ( '' === $instance['my_calendar_mini_category'] ) ? array() : (array) $instance['my_calendar_mini_category'];
			$time        = ( '' === $instance['my_calendar_mini_time'] ) ? 'month' : $instance['my_calendar_mini_time'];
			$widget_link = ( ! isset( $instance['mc_link'] ) || '' === $instance['mc_link'] ) ? '' : esc_url( $instance['mc_link'] );
			$above       = ( empty( $instance['above'] ) ) ? 'none' : $instance['above'];
			$below       = ( empty( $instance['below'] ) ) ? 'none' : $instance['below'];
			$author      = ( ! isset( $instance['author'] ) || '' === $instance['author'] ) ? null : $instance['author'];
			$host        = ( ! isset( $instance['host'] ) || '' === $instance['host'] ) ? null : $instance['host'];
			$ltype       = ( ! isset( $instance['ltype'] ) || '' === $instance['ltype'] ) ? '' : $instance['ltype'];
			$lvalue      = ( ! isset( $instance['lvalue'] ) || '' === $instance['lvalue'] ) ? '' : $instance['lvalue'];
			$site        = ( ! isset( $instance['site'] ) || '' === $instance['site'] ) ? false : $instance['site'];
			$months      = ( ! isset( $instance['months'] ) || '' === $instance['months'] ) ? false : $instance['months'];
		} else {
			$the_title   = '';
			$category    = array();
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

		if ( '' !== $the_title ) {
			$title = ( '' !== $widget_link ) ? "<a href='$widget_link'>$the_title</a>" : $the_title;
			$title = ( '' !== $title ) ? $before_title . $title . $after_title : '';
		} else {
			$title = '';
		}
		$enabled = mc_get_option( 'views' );
		$format  = 'mini';
		if ( ! in_array( 'mini', $enabled, true ) ) {
			$format = 'list';
		}

		$calendar = array(
			'name'     => 'mini',
			'format'   => $format,
			'category' => implode( ',', $category ),
			'time'     => $time,
			'ltype'    => $ltype,
			'lvalue'   => $lvalue,
			'id'       => str_replace( 'my_calendar', 'mc', $widget_id ),
			'author'   => $author,
			'host'     => $host,
			'above'    => $above,
			'below'    => $below,
			'site'     => $site,
			'months'   => $months,
			'source'   => 'widget',
			'json'     => 'false',
		);

		$the_events = my_calendar( $calendar );
		if ( '' !== $the_events ) {
			echo wp_kses( $before_widget . $title . $the_events . $after_widget, mc_kses_elements() );
		}
	}

	/**
	 * Edit the mini calendar widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	public function form( $instance ) {
		$title           = empty( $instance['my_calendar_mini_title'] ) ? '' : $instance['my_calendar_mini_title'];
		$widget_time     = empty( $instance['my_calendar_mini_time'] ) ? '' : $instance['my_calendar_mini_time'];
		$widget_category = empty( $instance['my_calendar_mini_category'] ) ? null : $instance['my_calendar_mini_category'];
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
		<div class="my-calendar-widget-wrapper my-calendar-mini-widget">
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_mini_title' ) ); ?>"><?php esc_html_e( 'Title', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_mini_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_mini_title' ) ); ?>" value="<?php echo esc_attr( $title ); ?>"/>
		</p>
		<?php
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'site' ) ); ?>"><?php esc_html_e( 'Blog ID', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'site' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'site' ) ); ?>" value="<?php echo esc_attr( $site ); ?>"/>
		</p>
			<?php
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_link' ) ); ?>"><?php esc_html_e( 'Widget Title Link', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'mc_link' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_link' ) ); ?>" value="<?php echo esc_url( $widget_link ); ?>"/>
		</p>
		<?php
		$all_checked = false;
		if ( empty( $widget_category ) ) {
			$all_checked = true;
		}

		?>
		<fieldset>
			<legend><?php esc_html_e( 'Categories to display:', 'my-calendar' ); ?></legend>
			<ul class="mc-widget-categories">
				<li>
					<input type="checkbox" value="all" <?php checked( true, $all_checked ); ?> name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_mini_category' ) . '[]' ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_mini_category' ) ); ?>"> <label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_mini_category' ) ); ?>"><?php esc_html_e( 'All', 'my-calendar' ); ?></label>
				</li>
			<?php
			$select = mc_category_select( $widget_category, true, true, $this->get_field_name( 'my_calendar_mini_category' ) . '[]', $this->get_field_id( 'my_calendar_mini_category' ) );
			echo wp_kses( $select, mc_kses_elements() );
			?>
			</ul>
		</fieldset>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'above' ) ); ?>"><?php esc_html_e( 'Navigation above calendar', 'my-calendar' ); ?></label>
			<input type="text" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'above' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'above' ) ); ?>" value="<?php echo ( '' === $above ) ? 'nav,jump,print' : esc_attr( $above ); ?>" aria-describedby='<?php echo esc_attr( $this->get_field_id( 'below' ) ); ?>-navigation-fields' />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'below' ) ); ?>"><?php esc_html_e( 'Navigation below calendar', 'my-calendar' ); ?></label>
			<input type="text" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'below' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'below' ) ); ?>" value="<?php echo ( '' === $below ) ? 'key' : esc_attr( $below ); ?>" aria-describedby='<?php echo esc_attr( $this->get_field_id( 'below' ) ); ?>-navigation-fields' /> <span id='<?php echo esc_attr( $this->get_field_id( 'below' ) ); ?>-navigation-fields' class="field-description" style="font-size: 13px;color:#555">
			<?php esc_html_e( 'Navigation options:', 'my-calendar' ); ?> <code>nav,jump,timeframe,print,key,feeds,exports,none</code><?php mc_help_link( __( 'Help', 'my-calendar' ), __( 'Navigation Keywords', 'my-calendar' ), 'navigation keywords', 3 ); ?>
		</span>
		</p>

		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'author' ) ); ?>"><?php esc_html_e( 'Limit by Author', 'my-calendar' ); ?></label><br/>
			<select name="<?php echo esc_attr( $this->get_field_name( 'author' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'author' ) ); ?>" multiple="multiple" class="widefat">
				<option value="all"><?php esc_html_e( 'All authors', 'my-calendar' ); ?></option>
				<option value="current"><?php esc_html_e( 'Active User', 'my-calendar' ); ?></option>
				<?php echo wp_kses( mc_selected_users( $author ), mc_kses_elements() ); ?>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'host' ) ); ?>"><?php esc_html_e( 'Limit by Host', 'my-calendar' ); ?></label><br/>
			<select name="<?php echo esc_attr( $this->get_field_name( 'host' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'host' ) ); ?>" multiple="multiple" class="widefat">
				<option value="all"><?php esc_html_e( 'All hosts', 'my-calendar' ); ?></option>
				<option value="current"><?php esc_html_e( 'Active User', 'my-calendar' ); ?></option>
				<?php echo wp_kses( mc_selected_users( $host ), mc_kses_elements() ); ?>
			</select>
		</p>
		<p>
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'ltype' ) ); ?>"><?php esc_html_e( 'Location (Type)', 'my-calendar' ); ?></label><br/>
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
			<label
				for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_mini_time' ) ); ?>"><?php esc_html_e( 'Mini-Calendar Timespan:', 'my-calendar' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_mini_time' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_mini_time' ) ); ?>">
				<option	value="month"<?php selected( 'month', $widget_time ); ?>><?php esc_html_e( 'Month', 'my-calendar' ); ?></option>
				<option value="month+1"<?php selected( 'month+1', $widget_time ); ?>><?php esc_html_e( 'Next Month', 'my-calendar' ); ?></option>
				<option value="week"<?php selected( 'week', $widget_time ); ?>><?php esc_html_e( 'Week', 'my-calendar' ); ?></option>
			</select>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'months' ) ); ?>"><?php esc_html_e( 'Months to show in list view', 'my-calendar' ); ?></label>
			<input type="number" max="12" step="1" min="1" class="widefat" name="<?php echo esc_attr( $this->get_field_name( 'months' ) ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'months' ) ); ?>" value="<?php echo ( '' === $months ) ? '' : esc_attr( $months ); ?>" />
		</p>
		</div>
		<?php
	}

	/**
	 * Update the My Calendar Mini Widget settings.
	 *
	 * @param array $new_data Widget settings new data.
	 * @param array $instance Widget settings instance.
	 *
	 * @return array $instance Updated instance.
	 */
	public function update( $new_data, $instance ) {
		$instance['my_calendar_mini_title']    = wp_kses( $new_data['my_calendar_mini_title'], 'mycalendar' );
		$instance['my_calendar_mini_time']     = wp_kses( $new_data['my_calendar_mini_time'], 'mycalendar' );
		$instance['my_calendar_mini_category'] = ( in_array( 'all', (array) $new_data['my_calendar_mini_category'], true ) ) ? array() : $new_data['my_calendar_mini_category'];
		$instance['above']                     = ( isset( $new_data['above'] ) && '' !== $new_data['above'] ) ? $new_data['above'] : 'none';
		$instance['mc_link']                   = $new_data['mc_link'];
		$instance['below']                     = ( isset( $new_data['below'] ) && '' !== $new_data['below'] ) ? $new_data['below'] : 'none';
		$author                                = '';
		$host                                  = '';
		if ( isset( $new_data['author'] ) && is_array( $new_data['author'] ) ) {
			$author = implode( ',', $new_data['author'] );
		}
		if ( isset( $new_data['host'] ) && is_array( $new_data['author'] ) ) {
			$host = implode( ',', $new_data['host'] );
		}
		$instance['author'] = $author;
		$instance['host']   = $host;
		$instance['ltype']  = ( '' !== $new_data['ltype'] && '' !== $new_data['lvalue'] ) ? $new_data['ltype'] : '';
		$instance['lvalue'] = ( '' !== $new_data['ltype'] && '' !== $new_data['lvalue'] ) ? $new_data['lvalue'] : '';
		if ( function_exists( 'is_multisite' ) && is_multisite() ) {
			$instance['site'] = $new_data['site'];
		}
		$instance['months'] = $new_data['months'];

		return $instance;
	}
}
