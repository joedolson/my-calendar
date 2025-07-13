<?php
/**
 * My Calendar Today's Events Widget
 *
 * @category Widgets
 * @package  My Calendar
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-calendar/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * My Calendar Today's Events class.
 *
 * @category  Widgets
 * @package   My Calendar
 * @author    Joe Dolson
 * @copyright 2009
 * @license   GPLv3
 * @version   1.0
 */
class My_Calendar_Today_Widget extends WP_Widget {

	/**
	 * Contructor.
	 */
	public function __construct() {
		parent::__construct(
			false,
			$name = __( 'My Calendar: Today\'s Events', 'my-calendar' ),
			array(
				'customize_selective_refresh' => true,
				'description'                 => __( 'A list of events today.', 'my-calendar' ),
			)
		);
	}

	/**
	 * Build the My Calendar Today's Events widget output.
	 *
	 * @param array $args Widget arguments.
	 * @param array $instance This instance settings.
	 */
	public function widget( $args, $instance ) {
		$before_widget = $args['before_widget'];
		$after_widget  = $args['after_widget'];
		$before_title  = str_replace( 'h1', 'h2', $args['before_title'] );
		$after_title   = str_replace( 'h1', 'h2', $args['after_title'] );
		$today_title   = isset( $instance['my_calendar_today_title'] ) ? $instance['my_calendar_today_title'] : '';
		$template      = isset( $instance['my_calendar_today_template'] ) ? $instance['my_calendar_today_template'] : '';
		$no_events     = isset( $instance['my_calendar_no_events_text'] ) ? $instance['my_calendar_no_events_text'] : '';
		$category      = isset( $instance['my_calendar_today_category'] ) ? $instance['my_calendar_today_category'] : '';

		$the_title      = apply_filters( 'widget_title', $today_title, $instance, $args );
		$the_template   = $template;
		$the_substitute = $no_events;
		$the_category   = ( '' === $category ) ? array() : (array) $instance['my_calendar_today_category'];
		$author         = ( ! isset( $instance['my_calendar_today_author'] ) || '' === $instance['my_calendar_today_author'] ) ? 'all' : esc_attr( $instance['my_calendar_today_author'] );
		$host           = ( ! isset( $instance['mc_host'] ) || '' === $instance['mc_host'] ) ? 'all' : esc_attr( $instance['mc_host'] );
		$default_link   = mc_get_uri( false, $args );
		$widget_link    = ( ! empty( $instance['my_calendar_today_linked'] ) && 'yes' === $instance['my_calendar_today_linked'] ) ? $default_link : '';
		$widget_link    = ( ! empty( $instance['mc_link'] ) ) ? esc_url( $instance['mc_link'] ) : $widget_link;
		$widget_title   = empty( $the_title ) ? '' : $the_title;
		$date           = ( ! empty( $instance['mc_date'] ) ) ? $instance['mc_date'] : false;
		$site           = ( isset( $instance['mc_site'] ) ) ? $instance['mc_site'] : false;

		if ( false !== strpos( $widget_title, '{date}' ) ) {
			$widget_title = str_replace( '{date}', date_i18n( mc_date_format() ), $widget_title );
		}
		$widget_title = ( '' === $widget_link ) ? $widget_title : "<a href='$widget_link'>$widget_title</a>";
		$widget_title = ( '' !== $widget_title ) ? $before_title . $widget_title . $after_title : '';

		$args = array(
			'category' => implode( ',', $the_category ),
			'template' => $the_template,
			'fallback' => $the_substitute,
			'author'   => $author,
			'host'     => $host,
			'date'     => $date,
			'site'     => $site,
		);

		$the_events = my_calendar_todays_events( $args );
		if ( '' !== $the_events ) {
			echo wp_kses( $before_widget . $widget_title . $the_events . $after_widget, mc_kses_elements() );
		}
	}

	/**
	 * Edit the today's events widget.
	 *
	 * @param array $instance Current widget settings.
	 */
	public function form( $instance ) {
		$defaults        = mc_widget_defaults();
		$widget_title    = ( isset( $instance['my_calendar_today_title'] ) ) ? $instance['my_calendar_today_title'] : '';
		$widget_template = ( isset( $instance['my_calendar_today_template'] ) ) ? $instance['my_calendar_today_template'] : '';
		if ( ! $widget_template ) {
			$widget_template = $defaults['today']['template'];
		}
		$widget_text     = ( isset( $instance['my_calendar_no_events_text'] ) ) ? $instance['my_calendar_no_events_text'] : '';
		$widget_category = ( isset( $instance['my_calendar_today_category'] ) ) ? (array) $instance['my_calendar_today_category'] : null;
		$widget_linked   = ( isset( $instance['my_calendar_today_linked'] ) ) ? $instance['my_calendar_today_linked'] : '';
		$date            = ( isset( $instance['mc_date'] ) ) ? $instance['mc_date'] : '';
		if ( 'yes' === $widget_linked ) {
			$default_link = mc_get_uri( false, $instance );
		} else {
			$default_link = '';
		}
		$widget_link   = ( ! empty( $instance['mc_link'] ) ) ? $instance['mc_link'] : $default_link;
		$widget_author = ( isset( $instance['my_calendar_today_author'] ) ) ? $instance['my_calendar_today_author'] : '';
		$widget_host   = ( isset( $instance['mc_host'] ) ) ? $instance['mc_host'] : '';
		$site          = ( isset( $instance['mc_site'] ) ) ? $instance['mc_site'] : '';

		?>
		<div class="my-calendar-widget-wrapper my-calendar-today-widget">
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_title' ) ); ?>"><?php esc_html_e( 'Title', 'my-calendar' ); ?>:</label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_title' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_today_title' ) ); ?>" value="<?php echo esc_attr( $widget_title ); ?>"/>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_link' ) ); ?>"><?php esc_html_e( 'Widget title links to:', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="url" id="<?php echo esc_attr( $this->get_field_id( 'mc_link' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_link' ) ); ?>" value="<?php echo esc_url( $widget_link ); ?>"/>
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
		$template_options         = mc_select_preset_templates();
		$template_options['list'] = __( 'Custom', 'my-calendar' );
		if ( in_array( $widget_template, array_keys( $template_options ), true ) ) {
			$preset = $widget_template;
		}
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_preset_template' ) ); ?>"><?php esc_html_e( 'Select Template', 'my-calendar' ); ?></label>
			<select id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_preset_template' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_today_preset_template' ) ); ?>">
			<?php
			foreach ( $template_options as $value => $label ) {
				?>
				<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $value, $preset ); ?>><?php echo esc_html( $label ); ?></option>
				<?php
			}
			?>
			</select>
		</p>
		<p class="mc-custom-template">
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_template' ) ); ?>"><?php esc_html_e( 'Template', 'my-calendar' ); ?></label><br/>
			<textarea class="widefat" rows="4" cols="20" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_template' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_today_template' ) ); ?>"><?php echo esc_textarea( wp_unslash( $widget_template ) ); ?></textarea>
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_no_events_text' ) ); ?>"><?php esc_html_e( 'No events text', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_no_events_text' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_no_events_text' ) ); ?>" value="<?php echo esc_attr( $widget_text ); ?>" />
		</p>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'mc_date' ) ); ?>"><?php esc_html_e( 'Custom date', 'my-calendar' ); ?></label><br/>
			<input class="widefat" type="date" id="<?php echo esc_attr( $this->get_field_id( 'mc_date' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_date' ) ); ?>" value="<?php echo esc_attr( $date ); ?>"/>
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
					<input type="checkbox" value="all" <?php checked( true, $all_checked ); ?> name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_today_category' ) . '[]' ); ?>" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_category' ) ); ?>"> <label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_category' ) ); ?>"><?php esc_html_e( 'All', 'my-calendar' ); ?></label>
				</li>
			<?php
			$select = mc_category_select( $widget_category, true, true, $this->get_field_name( 'my_calendar_today_category' ) . '[]', $this->get_field_id( 'my_calendar_today_category' ) );
			echo wp_kses( $select, mc_kses_elements() );
			?>
			</ul>
		</fieldset>
		<div class="mc-flex" style="display: flex; flex-wrap: wrap; gap: 20px;">
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_author' ) ); ?>"><?php esc_html_e( 'Author or authors to show:', 'my-calendar' ); ?></label><br/>
				<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'my_calendar_today_author' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'my_calendar_today_author' ) ); ?>" value="<?php echo esc_attr( $widget_author ); ?>"/>
			</p>
			<p>
				<label for="<?php echo esc_attr( $this->get_field_id( 'mc_host' ) ); ?>"><?php esc_html_e( 'Host or hosts to show:', 'my-calendar' ); ?></label><br/>
				<input class="widefat" type="text" id="<?php echo esc_attr( $this->get_field_id( 'mc_host' ) ); ?>" name="<?php echo esc_attr( $this->get_field_name( 'mc_host' ) ); ?>" value="<?php echo esc_attr( $widget_host ); ?>"/>
			</p>
		</div>
		</div>
		<?php
	}

	/**
	 * Update the My Calendar Today's Events Widget settings.
	 *
	 * @param array $new_settings Widget settings new data.
	 * @param array $instance Widget settings instance.
	 *
	 * @return array $instance Updated instance.
	 */
	public function update( $new_settings, $instance ) {
		if ( isset( $new_settings['my_calendar_today_preset_template'] ) && 'list' !== $new_settings['my_calendar_today_preset_template'] ) {
			$new_settings['my_calendar_today_template'] = $new_settings['my_calendar_today_preset_template'];
		}
		$instance = array_map( 'mc_kses_post', array_merge( $instance, $new_settings ) );
		// Set special value for category.
		$instance['my_calendar_today_category'] = ( in_array( 'all', (array) $new_settings['my_calendar_today_category'], true ) ) ? array() : $new_settings['my_calendar_today_category'];

		return $instance;
	}
}
