<?php
/**
 * Admin file
 *
 * @package Location_Weather.
 */

namespace ShapedPlugin\Weather;

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.
/**
 * The admin handler class.
 */
class Admin {

	/**
	 * The Constructor of the class.
	 */
	public function __construct() {
		new Admin\Post_Type();
		new Admin\Splw_Help();
		new Admin\Scripts();
		new Admin\Updater();
		$this->init_filters_actions();
	}

	/**
	 * Initialize WordPress filter hooks
	 *
	 * @return void
	 */
	public function init_filters_actions() {
		add_filter( 'manage_location_weather_posts_columns', array( $this, 'add_lw_shortcode_column' ), 10 );
		add_action( 'manage_location_weather_posts_custom_column', array( $this, 'add_lw_shortcode_form' ), 10, 2 );
		add_action( 'admin_menu', array( $this, 'admin_publish_button_remove' ) );
		add_filter( 'post_updated_messages', array( $this, 'admin_publish_update_notice' ) );
		add_filter( 'get_user_option_screen_layout_location_weather', array( $this, 'remove_lw_post_column' ), 10, 3 );
	}

	/**
	 * ShortCode Column
	 *
	 * @return array
	 */
	public function add_lw_shortcode_column() {
		$new_columns['cb']        = '<input type="checkbox" />';
		$new_columns['title']     = __( 'Title', 'location-weather' );
		$new_columns['shortcode'] = __( 'Shortcode', 'location-weather' );
		$new_columns['date']      = __( 'Date', 'location-weather' );

		return $new_columns;
	}

	/**
	 * Display admin columns for the carousels.
	 *
	 * @param mix    $column The columns.
	 * @param string $post_id The post ID.
	 * @return void
	 */
	public function add_lw_shortcode_form( $column, $post_id ) {

		switch ( $column ) {

			case 'shortcode':
				echo '<div class="splw-after-copy-text"><i class="fa fa-check-circle"></i>  Shortcode  Copied to Clipboard! </div><input class="splw__shortcode" style="width:205px;padding:6px;text-align:left;padding-left:15px;cursor:pointer;" type="text" onClick="this.select();" readonly="readonly" value="[location-weather id=&quot;' . esc_attr( $post_id ) . '&quot;]"/>';
				break;
			default:
				break;

		} // end switch
	}

	/**
	 * Default button hide from the plugin.
	 *
	 * @return void
	 */
	public function admin_publish_button_remove() {
		remove_meta_box( 'submitdiv', 'location_weather', 'side' );
	}

	/**
	 *
	 * The dynamic portion of the hook name, `$option`, refers to the user option name.
	 *
	 * @param mixed   $result Value for the user's option.
	 * @param string  $option Name of the option being retrieved.
	 * @param WP_User $user   WP_User object of the user whose option is being retrieved.
	 */
	public function remove_lw_post_column( $result, $option, $user ) {
		$result = 1;
		return $result;
	}

	/**
	 * Default button hide from the plugin.
	 *
	 * @param string $messages give custom notice of publish and update.
	 */
	public function admin_publish_update_notice( $messages ) {
		$messages['location_weather'][6] = __( 'The shortcode has been published.', 'location-weather' );
		$messages['location_weather'][1] = __( 'Weather updated.', 'location-weather' );
		return $messages;
	}
}
