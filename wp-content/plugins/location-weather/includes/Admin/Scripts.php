<?php
/**
 * Script class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Admin;

/**
 * Script class used to hold the style and script for admin.
 */
class Scripts {

	/**
	 * Script and style suffix
	 *
	 * @var string
	 */
	protected $suffix;

	/**
	 * The constructor of the class.
	 */
	public function __construct() {

		$this->suffix = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) || ( defined( 'WP_DEBUG' ) && WP_DEBUG ) ? '' : '.min';
		add_action( 'admin_enqueue_scripts', array( $this, 'scripts_handler' ) );
	}

	/**
	 * Frontend script handler.
	 *
	 * @return void
	 */
	public function scripts_handler() {
		$this->lw_styles();
	}

	/**
	 * Register the scripts for the public-facing side of the site.
	 *
	 * @since    2.0.0
	 */
	public function lw_styles() {
		/**
		 * This function is provided for demonstration purposes only.
		 */

		wp_enqueue_style( 'splw-admin', LOCATION_WEATHER_ASSETS . '/css/admin' . $this->suffix . '.css', array(), LOCATION_WEATHER_VERSION, 'all' );

	}
}
