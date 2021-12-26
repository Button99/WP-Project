<?php
/**
 * Update options for the version 1.2.0
 *
 * @link       https://shapedplugin.com
 * @since      1.2.0
 *
 * @package    Location_Weather
 * @subpackage Location_Weather/Admin/updates
 */

/**
 * Update DB version.
 */
update_option( 'location_weather_db_version', '1.2.0' );
update_option( 'location_weather_version', '1.2.0' );

/**
 * Location Weather query for id.
 */
$lw_old_options = get_option( '_sp_options', true );
$lw_old_api_key = $lw_old_options['lw_api_key'];
if ( ! empty( $lw_old_api_key ) ) {
	$splw_new_options  = get_option( 'location_weather_settings', true );
	$new_options_array = array(
		'open-api-key' => $lw_old_api_key,
	);
	$all_options       = array_merge( $splw_new_options, $new_options_array );
	update_option( 'location_weather_settings', $all_options );
	// delete_option( '_sp_options' );
}
