<?php
/**
 * Old widget of Location Weather.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Admin;

/**
 * The location weather widget handler class.
 */
class sp_location_weather_widget_content extends \WP_Widget {

	/**
	 * The Constructor of the class.
	 */
	public function __construct() {
		parent::__construct(
			'sp_location_weather_widget_content',
			__( 'Old - Location Weather', 'location-weather' ),
			array(
				'description' => __( 'Please use new widget! We don\'t recommend to use this old widget.', 'location-weather' ),
			)
		);
	}

	/**
	 * Front-end display of widget
	 *
	 * @param array $args widget argument.
	 * @param array $instance widget instance value.
	 * @return void
	 */
	public function widget( $args, $instance ) {
		extract( $args ); // phpcs:ignore

		$title = apply_filters( 'widget_title', $instance['title'] );

		$weather_id               = uniqid();
		$location_weather_city    = $instance['location_weather_city'];
		$location_weather_country = $instance['location_weather_country'];

		echo wp_kses_post( $before_widget );

		$output = '';

		$splw_option = get_option( 'location_weather_settings', true );
		$weather_api = ( ! empty( $splw_option['open-api-key'] ) ? $splw_option['open-api-key'] : '5c7cb751efca8dcb1429d59d96cd9bc9' );

		$output = '';
		if ( $title ) {
			echo wp_kses_post( $before_title . $title . $after_title );
		}
		$output .= '<div class="sp-location-weather-widget">';
		$output .= '<div id="location-weather-widget-' . $weather_id . '" class="sp-hide">
			<div class="sp-location-weather-image"><img class="weather-image" src="" alt="Weather Icon"/></div>
			<span class="weather-type"></span>
			<span class="weather-temp"></span>
			<span class="weather-date"></span>
			<span class="weather-region"></span>
		</div>';
		$output .= '</div><!--/#widget-->';

		$output .= "<script>
		/*
		 * Location weather
		 */
		jQuery(document).ready(function() {
			loadWeatherWidget$weather_id('$location_weather_city','$location_weather_country'); //@params location, woeid
		});

		function loadWeatherWidget$weather_id(location, woeid) {
			if (woeid != '' ) {
				var country = ',' + woeid;
			} else{
				var country = '';
			}
			jQuery('#location-weather-widget-$weather_id .weather-temp').locationWeather({
				key: '$weather_api',
				city: location+''+country,
				units: 'c',
				iconTarget: '#location-weather-widget-$weather_id .weather-image',
				descriptionTarget: '#location-weather-widget-$weather_id .weather-type',
				placeTarget: '#location-weather-widget-$weather_id .weather-region',
				weatherDate: '#location-weather-widget-$weather_id .weather-date',
				success: function(data) {
					// show weather
					jQuery('#location-weather-widget-$weather_id').show();
				},
				error: function(data) {
					jQuery('#location-weather-widget-$weather_id').remove();
				}
			});
		}</script>";

		echo $output;

		echo wp_kses_post( $after_widget );
	}

	/**
	 * Update form display of widget
	 *
	 * @param array $new_instance Update value.
	 * @param array $old_instance Old value.
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {
		$instance                             = $old_instance;
		$instance['title']                    = wp_strip_all_tags( $new_instance['title'] );
		$instance['location_weather_city']    = wp_strip_all_tags( $new_instance['location_weather_city'] );
		$instance['location_weather_country'] = wp_strip_all_tags( $new_instance['location_weather_country'] );

		return $instance;
	}

	/**
	 * Form display of widget
	 *
	 * @param array $instance Value.
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'                    => '',
			'location_weather_city'    => 'london',
			'location_weather_country' => 'uk',
		);
		$instance = wp_parse_args( (array) $instance, $defaults );
		?>
		<p>
			<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php esc_html_e( 'Title', 'location-weather' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'title' ) ); ?>"
				value="<?php echo esc_attr( $instance['title'] ); ?>" class="widefat"/>
		</p>
		<p>
			<label
					for="<?php echo esc_attr( $this->get_field_id( 'location_weather_city' ) ); ?>"><?php esc_html_e( 'City', 'location-weather' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'location_weather_city' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'location_weather_city' ) ); ?>"
				value="<?php echo esc_attr( $instance['location_weather_city'] ); ?>" style="width:100%;"/>
		</p>
		<p>
			<label
					for="<?php echo esc_attr( $this->get_field_id( 'location_weather_country' ) ); ?>"><?php esc_html_e( 'Country', 'location-weather' ); ?></label>
			<input id="<?php echo esc_attr( $this->get_field_id( 'location_weather_country' ) ); ?>"
				name="<?php echo esc_attr( $this->get_field_name( 'location_weather_country' ) ); ?>"
				value="<?php echo esc_attr( $instance['location_weather_country'] ); ?>" style="width:100%;"/>
		</p>

		<?php
	}
}
