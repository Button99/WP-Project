<?php
/**
 * Fired when the plugin is uninstalled.
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://shapedplugin.com/
 * @since      1.1.0
 *
 * @package    Location_weather
 * @author     ShapedPlugin <support@shapedplugin.com>
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/**
 * Delete plugin data function.
 *
 * @return void
 */
function sp_lw_delete_plugin_data() {

	// Delete plugin option settings.
	$option_name = 'location_weather_settings';
	delete_option( $option_name );
	delete_site_option( $option_name ); // For site options in Multisite.

	// Delete weather post type.
	$lw_posts = get_posts(
		array(
			'numberposts' => -1,
			'post_type'   => 'location_weather',
			'post_status' => 'any',
		)
	);
	foreach ( $lw_posts as $post ) {
		wp_delete_post( $post->ID, true );
	}

	// Delete weather post meta.
	delete_post_meta_by_key( 'sp_location_weather_generator' );
}

// Load splw file.
require plugin_dir_path( __FILE__ ) . '/main.php';
$sp_lw_options     = get_option( 'location_weather_settings' );
$sp_lw_plugin_data = $sp_lw_options['splw_delete_on_remove'];
if ( $sp_lw_plugin_data ) {
	sp_lw_delete_plugin_data();
}
