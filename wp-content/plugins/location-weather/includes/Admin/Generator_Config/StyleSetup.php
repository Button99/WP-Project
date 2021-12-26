<?php
/**
 * The style setup configuration.
 *
 * @package Location_Weather
 */

if ( ! defined( 'ABSPATH' ) ) {
	die;
} // Cannot access directly.


		SPLW::createSection(
			'sp_location_weather_generator',
			array(
				'title'  => __( 'Style Options', 'location-weather' ),
				'icon'   => '<span><svg version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" width="14px" height="14px" viewBox="0 0 512 512" style="enable-background:new 0 0 512 512;" xml:space="preserve"><g><g><path d="M344.476,105.328L1.004,448.799L64.205,512l343.472-343.471L344.476,105.328z M290.594,201.792l53.882-53.882l20.619,20.619l-53.882,53.882L290.594,201.792z"/></g></g><g><g><rect x="413.735" y="55.578" transform="matrix(0.7071 -0.7071 0.7071 0.7071 79.0342 332.0713)" width="53.255" height="30.11"/></g></g><g><g><rect x="420.768" y="255.551" transform="matrix(0.7071 -0.7071 0.7071 0.7071 -72.2351 390.9691)" width="30.11" height="54.259"/></g></g><g><g><rect x="213.158" y="48.098" transform="matrix(0.7071 -0.7071 0.7071 0.7071 13.767 183.3558)" width="30.11" height="53.922"/></g></g><g><g><polygon points="510.735,163.868 456.446,163.868 456.446,193.979 510.735,193.979 510.996,193.979 510.996,163.868"/></g></g><g><g><polygon points="317.017,0.018 317.017,54.307 347.128,54.307 347.128,0.018 347.128,0"/></g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g><g></g></svg></span>',
				'class'  => 'splw-weather-settings-meta-box',
				'fields' => array(
					array(
						'id'    => 'lw-display-heading',
						'class' => 'splw-display-heading',
						'type'  => 'subheading',
						'title' => __( 'Style Options', 'location-weather' ),
					),
					array(
						'id'      => 'lw-background-type',
						'class'   => 'splw_background_type',
						'type'    => 'select',
						'desc'    => __( 'To unlock the gradient, image, and video background settings, <a href="https://shapedplugin.com/plugin/location-weather-pro/?ref=1" target="_blank"><b>Upgrade To Pro!</b></a>', 'location-weather' ),
						'title'   => __( ' Background Type ', 'location-weather' ),
						'options' => array(
							'solid' => __( 'Solid Color', 'location-weather' ),
							'1'     => __( 'Gradient Color (Pro)', 'location-weather' ),
							'2'     => __( 'Image based on Weather (Pro)', 'location-weather' ),
							'3'     => __( 'Video (HTML5 & YouTube) (Pro)', 'location-weather' ),
						),
						'default' => 'solid',
					),
					array(
						'id'         => 'lw-bg-solid',
						'type'       => 'color',
						'title'      => __( 'Solid Color', 'location-weather' ),
						'default'    => '#f4812d',
						'dependency' => array( 'lw-background-type', '==', 'solid', true ),
					),
					array(
						'id'      => 'lw-text-color',
						'type'    => 'color',
						'title'   => __( 'Text Color', 'location-weather' ),
						'default' => '#fff',
					),
					array(
						'id'      => 'lw_bg_border',
						'type'    => 'border',
						'title'   => __( 'Border', 'location-weather' ),
						'all'     => true,
						'default' => array(
							'all'   => '0',
							'style' => 'solid',
							'color' => '#e2e2e2',
						),
					),
					array(
						'id'      => 'lw_bg_border_radius',
						'type'    => 'spacing',
						'title'   => __( 'Radius', 'location-weather' ),
						'all'     => true,
						'min'     => 0,
						'max'     => 100,
						'units'   => array( 'px', '%' ),
						'default' => array(
							'all' => '8',
						),
					),
				),
			)
		);

