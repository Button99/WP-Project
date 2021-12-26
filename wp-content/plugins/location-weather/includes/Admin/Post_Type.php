<?php
/**
 * The Post_Type file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Admin;

/**
 * The shortcode post type class.
 */
class Post_Type {

	/**
	 * Class constructor class.
	 */
	public function __construct() {
		add_action( 'init', array( $this, 'register_post_type' ) );
	}


	/**
	 * Register Location Weather Post Type.
	 *
	 * @return void
	 */
	public function register_post_type() {

		$_menu_icon = ( LOCATION_WEATHER_ASSETS . '/font/lw-icon.svg' );
		$capability = location_weather_dashboard_capability();

		register_post_type(
			'location_weather',
			array(
				'label'           => __( 'Location Weather', 'location-weather' ),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => true,
				'menu_icon'       => $_menu_icon,
				'capability_type' => 'post',
				'hierarchical'    => false,
				'query_var'       => false,
				'supports'        => array( 'title' ),
				'capabilities'    => array(
					'publish_posts'       => $capability,
					'edit_posts'          => $capability,
					'edit_others_posts'   => $capability,
					'delete_posts'        => $capability,
					'delete_others_posts' => $capability,
					'read_private_posts'  => $capability,
					'edit_post'           => $capability,
					'delete_post'         => $capability,
					'read_post'           => $capability,
				),
				'labels'          => array(
					'name'               => __( 'Manage Weather', 'location-weather' ),
					'singular_name'      => __( 'Manage Weather', 'location-weather' ),
					'menu_name'          => __( 'Location Weather', 'location-weather' ),
					'all_items'          => __( 'Manage Weather', 'location-weather' ),
					'add_new'            => __( 'Add New', 'location-weather' ),
					'add_new_item'       => __( 'Add New Weather', 'location-weather' ),
					'edit'               => __( 'Edit', 'location-weather' ),
					'edit_item'          => __( 'Edit Weather', 'location-weather' ),
					'new_item'           => __( 'New Weather', 'location-weather' ),
					'view'               => __( 'View Weather', 'location-weather' ),
					'view_item'          => __( 'View Weather Found', 'location-weather' ),
					'not_found_in_trash' => __( 'No Weather Found in Trash', 'location-weather' ),
					'parent'             => __( 'Parent Weather', 'location-weather' ),
				),
			)
		);

		flush_rewrite_rules();
	}
}
