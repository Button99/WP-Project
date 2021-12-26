<?php
/**
 * Shortcode class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend;

use ShapedPlugin\Weather\Frontend\WeatherData\CurrentWeather;
use ShapedPlugin\Weather\Frontend\WeatherData\Exception as LWException;

/**
 * Shortcode handler class.
 */
class Shortcode {

	/**
	 * The basic api URL.
	 *
	 * @var string The basic api url to fetch weather data from.
	 */
	private $weather_url = 'https://api.openweathermap.org/data/2.5/weather?';

	/**
	 * The api key.
	 *
	 * @var string
	 */
	private $api_key = '';

	/**
	 * Class constructor.
	 */
	public function __construct() {
		add_shortcode( 'location-weather', array( $this, 'render_shortcode' ) );
	}

	/**
	 * Shortcode render class.
	 *
	 * @param array  $attribute The shortcode attributes.
	 * @param string $content Shortcode content.
	 * @return void
	 */
	public function render_shortcode( $attribute, $content = '' ) {

		if ( empty( $attribute['id'] ) ) {
			return;
		}
		$shortcode_id = $attribute['id']; // Location Weather Pro global ID for Shortcode metabox.

		$splw_option = get_option( 'location_weather_settings', true );
		$splw_meta   = get_post_meta( $shortcode_id, 'sp_location_weather_generator', true );

		// Weather option meta area.
		$open_api_key = $splw_option['open-api-key'];
		$appid        = ! empty( $open_api_key ) ? $open_api_key : '';
		if ( ! $appid ) {
			return;
		}

		// Weather setup meta area .
		$custom_name     = isset( $splw_meta['lw-custom-name'] ) ? $splw_meta['lw-custom-name'] : '';
		$pressure_unit   = isset( $splw_meta['lw-pressure-unit'] ) ? $splw_meta['lw-pressure-unit'] : '';
		$wind_speed_unit = isset( $splw_meta['lw-wind-speed-unit'] ) ? $splw_meta['lw-wind-speed-unit'] : '';
		$lw_language     = isset( $splw_meta['lw-language'] ) ? $splw_meta['lw-language'] : 'en';

		// Display options meta section.
		$show_weather_title    = isset( $splw_meta['lw-title'] ) ? $splw_meta['lw-title'] : '';
		$time_format           = isset( $splw_meta['lw-time-format'] ) ? $splw_meta['lw-time-format'] : '';
		$lw_client_date_format = isset( $splw_meta['lw_client_date_format'] ) ? $splw_meta['lw_client_date_format'] : 'g:i a, F j, Y';
		$show_time             = isset( $splw_meta['lw-date'] ) ? $splw_meta['lw-date'] : '';
		$show_icon             = isset( $splw_meta['lw-icon'] ) ? $splw_meta['lw-icon'] : '';

		// Temperature show hide meta.
		$show_temperature  = isset( $splw_meta['lw-temperature'] ) ? $splw_meta['lw-temperature'] : '';
		$temperature_scale = isset( $splw_meta['lw-display-temp-scale'] ) ? $splw_meta['lw-display-temp-scale'] : '';
		$short_description = isset( $splw_meta['lw-short-description'] ) ? $splw_meta['lw-short-description'] : '';

		// Units show hide meta.
		$weather_units     = isset( $splw_meta['lw-units'] ) ? $splw_meta['lw-units'] : '';
		$show_pressure     = isset( $splw_meta['lw-pressure'] ) ? $splw_meta['lw-pressure'] : '';
		$show_wind         = isset( $splw_meta['lw-wind'] ) ? $splw_meta['lw-wind'] : '';
		$show_visibility   = isset( $splw_meta['lw-visibility'] ) ? $splw_meta['lw-visibility'] : '';
		$show_sunrise      = isset( $splw_meta['lw-sunrise'] ) ? $splw_meta['lw-sunrise'] : '';
		$show_sunset       = isset( $splw_meta['lw-sunset'] ) ? $splw_meta['lw-sunset'] : '';
		$show_weather_attr = isset( $splw_meta['lw-attribution'] ) ? $splw_meta['lw-attribution'] : '';

		$weather_by = $splw_meta['get-weather-by'];
		switch ( $weather_by ) {
			case 'city_name':
				$city  = isset( $splw_meta['lw-city-name'] ) ? $splw_meta['lw-city-name'] : '';
				$query = ! empty( $city ) ? trim( $city ) : 'london';
				break;
		}

		$data = $this->get_weather( $query, $weather_units, $lw_language, $appid );

		if( is_array($data) && isset($data["code"] ) && ( $data["code"] == 401 || $data["code"] == 404 ) ) {
			$weather_output = sprintf( '<div id="splw-location-weather-%1$s" class="splw-main-wrapper"><div class="splw-weather-title">%2$s</div><div class="splw-lite-wrapper"><div class="splw-warning">%3$s</div> <div class="splw-weather-attribution"><a href = "https://openweathermap.org/" target="_blank">Weather from OpenWeatherMap </a></div></div></div>', esc_attr( $shortcode_id ), esc_html( get_the_title( $shortcode_id ) ), $data['message'] );
			
			return $weather_output;
		} 
		
		// $weather_data                = $this->current_weather_data( $data, $time_format, $temperature_scale, $wind_speed_unit, $weather_units );
		$weather_data                = $this->current_weather_data( $data, $time_format, $temperature_scale, $wind_speed_unit, $weather_units, $lw_client_date_format );
		$current_weather_html_output = ! empty( $data->city->name ) ? $this->current_weather_html( $weather_data, $custom_name, $show_icon, $show_temperature, $short_description, $show_visibility, $show_sunrise, $show_sunset, $show_pressure, $show_wind, $show_time ) : '<div class="splw-warning">Please set your valid city name and country code.</div>';
		$show_section_title          = $show_weather_title ? '<div class="splw-weather-title">' . esc_html( get_the_title( $shortcode_id ) ) . '</div>' : '';
		$show_weather_attrs          = $show_weather_attr && $open_api_key ? apply_filters( 'splw_weather_attribution', '<div class="splw-weather-attribution"><a href = "https://openweathermap.org/" target="_blank">Weather from OpenWeatherMap </a></div>' ) : '';

		// Wrapper area start.
		$weather_output = sprintf( '<div id="splw-location-weather-%1$s" class="splw-main-wrapper"><div class="splw-weather-title">%2$s</div><div class="splw-lite-wrapper">%3$s %4$s</div></div>', esc_attr( $shortcode_id ), $show_section_title, $current_weather_html_output, $show_weather_attrs );
		// End wrapper .
		return $weather_output;
	} //Shortcode render method end.

	/**
	 * Get the current weather data.
	 *
	 * @param object $data The place to get weather information for.
	 * @param string $time_format Can be either '24' or '12' (default).
	 * @param string $temperature_scale Can be either 'F' or 'C' (default).
	 * @param string $wind_speed_unit Can be either 'mph', 'kmh','kts'  or 'mph' (default).
	 * @param string $weather_units Can be either 'metric' or 'imperial' (default).
	 * @param string $lw_client_date_format Can be either 'metric' or 'imperial' (default).
	 * @return CurrentWeather The weather object.
	 */
	public function current_weather_data( $data, $time_format, $temperature_scale, $wind_speed_unit, $weather_units, $lw_client_date_format ) {
		$city        = $data->city->name;
		$country     = $data->city->country;
		$scale       = $this->temperature_scale( $temperature_scale, $weather_units );
		$temp        = round( $data->temperature->now->value ) . $scale;
		$visibility  = $data->visibility;
		$desc        = $data->weather->description;
		$sunrise     = $data->sun->rise;
		$sunset      = $data->sun->set;
		$icon        = $data->weather->icon;
		$last_update = $data->last_update;
		$pressure    = $data->pressure;
		$timezone    = $data->timezone;
		$wind        = $this->get_wind_speed( $weather_units, $wind_speed_unit, $data );
		if ( '12' === $time_format ) {
			// 24-hour time to 12-hour time
			$time_format  = gmdate( $lw_client_date_format, strtotime( $last_update->format( 'Y-m-d g:i:sa' ) ) + $timezone );
			$sunrise_time = gmdate( 'g:i a', strtotime( $sunrise->format( 'Y-m-d g:i:sa' ) ) + $timezone );
			$sunset_time  = gmdate( 'g:i a', strtotime( $sunset->format( 'Y-m-d g:i:sa' ) ) + $timezone );

		} else {
			// 12-hour time to 24-hour time
			$time_format  = gmdate( $lw_client_date_format, strtotime( $last_update->format( 'Y-m-d g:i:sa' ) ) + $timezone );
			$sunrise_time = gmdate( 'H:i', strtotime( $sunrise->format( 'Y-m-d g:i:sa' ) ) + $timezone );
			$sunset_time  = gmdate( 'H:i', strtotime( $sunset->format( 'Y-m-d g:i:sa' ) ) + $timezone );
		}
		return array(
			'city'         => $city,
			'country'      => $country,
			'temp'         => $temp,
			'pressure'     => $pressure,
			'wind'         => $wind,
			'visibility'   => $visibility,
			'desc'         => $desc,
			'icon'         => $icon,
			'time_format'  => $time_format,
			'sunrise_time' => $sunrise_time,
			'sunset_time'  => $sunset_time,
		);
	}

	/**
	 * Get the forecast weather data.
	 *
	 * @param string $temperature_scale Can be either 'F' or 'C' (default).
	 * @param string $weather_units Can be either 'metric' or 'imperial' (default). This affects almost all units returned.
	 *
	 * @return scale The weather temperature scale object.
	 */
	public function temperature_scale( $temperature_scale, $weather_units ) {
		$scale = '°';
		if ( $temperature_scale && 'imperial' === $weather_units ) {
			$scale = '°F';
		} elseif ( $temperature_scale && 'metric' === $weather_units ) {
			$scale = '°C';
		} else {
			$scale = '°';
		}
		return $scale;
	}

	/**
	 * Get the weather wind speed unit.
	 *
	 * @param string            $weather_units Can be either 'metric' or 'imperial' (default). This affects almost all units returned.
	 * @param string            $wind_speed_unit Can be either 'mph', 'kmh','kts'  or 'mph' (default). This affects almost all units returned.
	 * @param object|int|string $data The place to get weather information for. For possible values see below.
	 * @return wind The weather object
	 */
	public function get_wind_speed( $weather_units, $wind_speed_unit, $data ) {
		$winds = $data->wind->speed->value;
		if ( 'imperial' === $weather_units ) {
			switch ( $wind_speed_unit ) {
				case 'mph':
					$wind = round( $winds ) . ' mph';
					break;
				case 'kmh':
					$wind = round( $winds * 1.61 ) . ' Km/h';
					break;
			}
		} else {
			switch ( $wind_speed_unit ) {
				case 'mph':
					$wind = round( $winds * 2.2 ) . ' mph';
					break;
				case 'kmh':
					$wind = round( $winds * 3.6 ) . ' Km/h';
					break;
			}
		}
		return $wind;
	}

	/**
	 * Returns the current weather at the place you specified.
	 *
	 * @param array|int|string $query The place to get weather information for. For possible values see below.
	 * @param string           $units Can be either 'metric' or 'imperial' (default). This affects almost all units returned.
	 * @param string           $lang  The language to use for descriptions, default is 'en'. For possible values see http://openweathermap.org/current#multi.
	 * @param string           $appid Your app id, default ''. See http://openweathermap.org/appid for more details.
	 *
	 * @throws OpenWeatherMap\LWException  If OpenWeatherMap returns an error.
	 *
	 * @return CurrentWeather The weather object.
	 *
	 * There are four ways to specify the place to get weather information for:
	 * - Use the city name: $query must be a string containing the city name.
	 * - Use the city id: $query must be an integer containing the city id.
	 * - Use the coordinates: $query must be an associative array containing the 'lat' and 'lon' values.
	 * - Use the zip code: $query must be a string, prefixed with "zip:"
	 *
	 * Zip code may specify country. e.g., "zip:77070" (Houston, TX, US) or "zip:500001,IN" (Hyderabad, India)
	 *
	 * @api
	 */
	public function get_weather( $query, $units = 'imperial', $lang = 'en', $appid = '' ) {
		$answer = $this->get_raw_weather_data( $query, $units, $lang, $appid, 'xml' );
		$value  = $this->parse_xml( $answer );
		$arr_value = (array) $value;
		if( isset($value['cod']) && $value['cod'] == 401 ){
			$value = array(
				'code' => 401, 'message' => 'Your API key is not activated yet. Within the next couple of hours, it will be activated and ready to use. <br/>Or<br/> Invalid API key. Please see <a href="http://openweathermap.org/faq#error401" target="_blank">http://openweathermap.org/faq#error401</a> for more info.'
			);
			return $value;
		} else if(isset( $arr_value["message"]) && $arr_value["message"] == 'city not found') {
			$value = array(
				'code' => 404, 'message' => esc_html__( 'Please set your valid city name and country code.', 'location-weather' )
			);
			return $value;
		}
		return new CurrentWeather( $value, $units );
	}

	/**
	 * Returns the current weather for a group of city ids.
	 *
	 * @param array|int|string $query The place to get weather information for. For possible values see ::getWeather.
	 * @param string           $units Can be either 'metric' or 'imperial' (default). This affects almost all units returned.
	 * @param string           $lang  The language to use for descriptions, default is 'en'. For possible values see http://openweathermap.org/current#multi.
	 * @param string           $appid Your app id, default ''. See http://openweathermap.org/appid for more details.
	 * @param string           $mode  The format of the data fetched. Possible values are 'json', 'html' and 'xml' (default).
	 *
	 * @return CurrentWeather
	 *
	 * @api
	 */
	public function get_raw_weather_data( $query, $units = 'imperial', $lang = 'en', $appid = '', $mode = 'xml' ) {
		$url      = $this->build_url( $query, $units, $lang, $appid, $mode, $this->weather_url );
		$response = wp_remote_get( $url );
		$data     = wp_remote_retrieve_body( $response );
		return $data;
	}

	/**
	 * Directly returns the SimpleXMLElement string returned by OpenWeatherMap.
	 *
	 * @param string $answer The content returned by OpenWeatherMap  OpenWeatherMap.
	 *
	 * @throws LWException If the content isn't valid JSON.
	 */
	private function parse_xml( $answer ) {
		// Disable default error handling of SimpleXML (Do not throw E_WARNINGs).
		libxml_use_internal_errors( true );
		try {
			return new \SimpleXMLElement( $answer );
		} catch ( \Exception $e ) {
			// Invalid xml format. This happens in case OpenWeatherMap returns an error.
			// OpenWeatherMap always uses json for errors, even if one specifies xml as format.
			$error = json_decode( $answer, true );
			if ( isset( $error['message'] ) ) {
				return $error;
				//return false;
			}
		}
		libxml_clear_errors();
	}

	/**
	 * Build the url to fetch weather data from.
	 *
	 * @param array  $query of the URL parameter.
	 * @param string $units of the url parameter.
	 * @param string $lang of the url parameter.
	 * @param string $appid of the url parameter.
	 * @param string $mode of the url parameter.
	 * @param string $url   The url to prepend.
	 *
	 * @return bool|string The fetched url, false on failure.
	 */
	private function build_url( $query, $units, $lang, $appid, $mode, $url ) {
		$query_url = $this->build_query_url_parameter( $query );

		$url  = $url . "$query_url&units=$units&lang=$lang&mode=$mode&APPID=";
		$url .= empty( $appid ) ? $this->api_key : $appid;

		return $url;
	}

	/**
	 * Builds the query string for the url.
	 *
	 * @param mixed $query query of the URL parameter.
	 *
	 * @return string The built query string for the url.
	 *
	 * @throws \InvalidArgumentException If the query parameter is invalid.
	 */
	private function build_query_url_parameter( $query ) {
		switch ( $query ) {
			case is_string( $query ):
				return 'q=' . urlencode( $query );
			default:
				throw new \InvalidArgumentException( 'Error: $query has the wrong format. See the documentation of OpenWeatherMap::getWeather() to read about valid formats.' );
		}
	}

	/**
	 * Get the Current weather layout.
	 *
	 * @param array  $weather_data The place to get Current weather layout information for.
	 * @param string $custom_name choose location of user.
	 * @param string $show_icon show hide weather icon.
	 * @param string $show_temperature show hide weather temperature.
	 * @param string $short_description show hide weather short description.
	 * @param string $show_visibility show hide weather visibility.
	 * @param string $show_sunrise show hide weather sunrise.
	 * @param string $show_sunset show hide weather sunset.
	 * @param string $show_pressure show hide weather pressure.
	 * @param string $show_wind show hide weather wind.
	 * @param string $show_time show hide weather wind.
	 *
	 *  @return string Current weather layout.
	 */
	public function current_weather_html( $weather_data, $custom_name, $show_icon, $show_temperature, $short_description, $show_visibility, $show_sunrise, $show_sunset, $show_pressure, $show_wind, $show_time ) {
		$city_name    = ! empty( $custom_name ) ? $custom_name : $weather_data['city'];
		$title        = sprintf( '<div class="splw-lite-header-title">%1$s, <span>%2$s</span></div>', $city_name, $weather_data['country'] );
		$current_time = $show_time ? sprintf( '<div class="splw-lite-current-time">%1$s</div>', $weather_data['time_format'] ) : '';
		// Current temperature icon .
		$current_temp_icon = $show_icon ? sprintf( '<img src="https://openweathermap.org/img/w/%1$s.png" class="weather-icon" />', $weather_data['icon'] ) : '';
		// Current temperature unit.
		$current_temp = $show_temperature ? sprintf( '<span class="cur-temp"> %1$s</span>', $weather_data['temp'] ) : '';
		// Weather show hide options elements .
		$show_wind       = $show_wind ? sprintf( '<div class="splw-gusts-wind">' . __( 'Wind', 'location-weather' ) . ': %1$s</div>', $weather_data['wind'] ) : '';
		$show_pressure   = $show_pressure ? sprintf( '<div class="splw-pressure">' . __( 'Pressure', 'location-weather' ) . ': %1$s</div>', $weather_data['pressure'] ) : '';
		$show_visibility = $show_visibility ? sprintf( '<div class="splw-visibility">' . __( 'Visibility', 'location-weather' ) . ': %1$s</div>', $weather_data['visibility'] ) : '';
		$show_sunrise    = $show_sunrise ? sprintf( '<div class="splw-sunrise">' . __( 'Sunrise', 'location-weather' ) . ': %1$s</div>', $weather_data['sunrise_time'] ) : '';
		$show_sunset     = $show_sunset ? sprintf( '<div class="splw-sunset">' . __( 'Sunset', 'location-weather' ) . ': %1$s</div>', $weather_data['sunset_time'] ) : '';
		// Header area.
		$header = sprintf( '<div class="splw-lite-header-title-wrapper">%1$s %2$s</div>', $title, $current_time );
		// Current temp area.
		$current_temp_area = $show_icon || $show_temperature ? sprintf( '<div class="splw-lite-current-temp"><div class="splw-cur-temp">%1$s %2$s</div></div>', $current_temp_icon, $current_temp ) : '';
		// Temperature short descriptions.
		$short_description = $short_description ? sprintf( '<div class="splw-lite-desc"><Span>%1$s</Span></div>', $weather_data['desc'] ) : '';
		// Current Weather options .
		$weather_options = $show_wind || $show_pressure || $show_visibility || $show_sunrise || $show_sunset ? sprintf( '<div class="splw-other-opt">%1$s %2$s %3$s %4$s %5$s</div>', $show_wind, $show_pressure, $show_visibility, $show_sunrise, $show_sunset ) : '';

		// Location weather output of frontend.
		$output = sprintf( '<div class="splw-lite-header">%1$s</div><div class="splw-lite-body">%2$s<div class="splw-lite-current-text">%3$s %4$s</div></div>', $header, $current_temp_area, $short_description, $weather_options );

		return $output;
	}
}
