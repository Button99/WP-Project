<?php
/**
 * Functions file
 *
 * @package Location_Weather.
 */

/**
 * Plugin dashboard access capability.
 *
 * @return manage_options
 */
function location_weather_dashboard_capability() {
	return apply_filters( 'location_weather_access_capability', 'manage_options' );
}
