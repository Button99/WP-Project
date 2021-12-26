<?php
/**
 * This is to plugin help page.
 *
 * @since      1.2.0
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Admin;

// don't call the file directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Update metabox and options settings during update.
 *
 * @package    Location_Weather
 *
 * @subpackage Location_Weather/Admin
 * @author     ShapedPlugin <shapedplugin@gmail.com>
 */
class Updater {

	/**
	 * DB updates that need to be run
	 *
	 * @var array
	 */
	private static $updates = array(
		'1.2.0' => 'updates/update-1.2.0.php',
	);

	/**
	 * Binding all events
	 *
	 * @since 1.2.0
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'admin_init', array( $this, 'do_updates' ) );
	}

	/**
	 * Check if need any update.
	 *
	 * @since 1.2.0
	 *
	 * @return boolean
	 */
	public function does_need_update() {
		$installed_version = get_option( 'location_weather_version' );
		$first_version     = get_option( 'location_weather_first_version' );
		$activation_date   = get_option( 'location_weather_activation_date' );

		if ( ! $installed_version ) {
			update_option( 'location_weather_version', '1.1.2' );
			update_option( 'location_weather_db_version', '1.1.2' );
		}
		if ( false === $first_version ) {
			update_option( 'location_weather_first_version', LOCATION_WEATHER_VERSION );
		}
		if ( false === $activation_date ) {
			update_option( 'location_weather_activation_date', current_time( 'timestamp' ) );
		}

		if ( version_compare( $installed_version, LOCATION_WEATHER_VERSION, '<' ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Do updates.
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function do_updates() {
		$this->perform_updates();
	}

	/**
	 * Perform all updates
	 *
	 * @since 1.2
	 *
	 * @return void
	 */
	public function perform_updates() {
		if ( ! $this->does_need_update() ) {
			return;
		}

		if ( ! current_user_can( 'update_plugins' ) ) {
			return;
		}

		$installed_version = get_option( 'location_weather_version' );

		foreach ( self::$updates as $version => $path ) {
			if ( version_compare( $installed_version, $version, '<' ) ) {
				include $path;
				update_option( 'location_weather_version', $version );
			}
		}
		update_option( 'location_weather_version', LOCATION_WEATHER_VERSION );
	}

}
