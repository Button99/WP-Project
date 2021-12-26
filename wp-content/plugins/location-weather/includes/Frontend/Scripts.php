<?php
/**
 * Script class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend;

/**
 * Script class used to hold the style and script for frontend.
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
		add_action( 'wp_enqueue_scripts', array( $this, 'scripts_handler' ) );
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
		wp_enqueue_style( 'splw-styles', LOCATION_WEATHER_ASSETS . '/css/splw-style' . $this->suffix . '.css', array(), LOCATION_WEATHER_VERSION, 'all' );
		wp_enqueue_style( 'splw-old-styles', LOCATION_WEATHER_ASSETS . '/css/old-style' . $this->suffix . '.css', array(), LOCATION_WEATHER_VERSION, 'all' );
		wp_enqueue_script( 'splw-old-script', LOCATION_WEATHER_ASSETS . '/js/Old-locationWeather' . $this->suffix . '.js', array( 'jquery' ), LOCATION_WEATHER_VERSION, true );

		$splw_posts = new \WP_Query(
			array(
				'post_type'      => 'location_weather',
				'posts_per_page' => 500,
			)
		);

		$splw_ids        = wp_list_pluck( $splw_posts->posts, 'ID' );
		$custom_css      = '';
		$setting_options = get_option( 'location_weather_settings', true );
		$splw_custom_css = $setting_options['splw_custom_css'];

		foreach ( $splw_ids as $splw_id ) {
			include LOCATION_WEATHER_PATH . '/includes/Frontend/dynamic-style.php';
		}
		if ( ! empty( $splw_custom_css ) ) {
			$custom_css .= $splw_custom_css;
		}
		// Add dynamic style.
		wp_add_inline_style( 'splw-styles', $custom_css );
	}
}
