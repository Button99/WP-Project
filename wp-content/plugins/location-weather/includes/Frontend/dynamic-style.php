<?php
/**
 *  Dynamic CSS
 *
 * @package    Location_weather
 * @subpackage Location_weather/public
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


	$splw_option = get_option( $splw_id, 'location_weather_settings', true );
	$splw_meta   = get_post_meta( $splw_id, 'sp_location_weather_generator', true );

	$icon_color      = isset( $splw_meta['lw-icon-color'] ) ? $splw_meta['lw-icon-color'] : '';
	$background_type = isset( $splw_meta['lw-background-type'] ) ? $splw_meta['lw-background-type'] : '';
	$bg_solid        = isset( $splw_meta['lw-bg-solid'] ) ? $splw_meta['lw-bg-solid'] : '';

	$lw_bg_border           = isset( $splw_meta['lw_bg_border'] ) ? $splw_meta['lw_bg_border']['all'] . 'px ' . $splw_meta['lw_bg_border']['style'] . ' ' . $splw_meta['lw_bg_border']['color'] : '';
	$bg_border_radius       = isset( $splw_meta['lw_bg_border_radius'] ) ? $splw_meta['lw_bg_border_radius']['all'] . $splw_meta['lw_bg_border_radius']['unit'] : '8px';
	$text_color             = isset( $splw_meta['lw-text-color'] ) ? $splw_meta['lw-text-color'] : '';
	$weather_title_margin   = isset( $splw_meta['lw_title_margin'] ) && ! empty( $splw_meta['lw_title_margin'] ) ? $splw_meta['lw_title_margin'] : ' ';
	$lw_title_margin_top    = isset( $weather_title_margin['top'] ) ? $weather_title_margin['top'] : '0';
	$lw_title_margin_right  = isset( $weather_title_margin['right'] ) ? $weather_title_margin['right'] : '0';
	$lw_title_margin_bottom = isset( $weather_title_margin['bottom'] ) ? $weather_title_margin['bottom'] : '20';
	$lw_title_margin_left   = isset( $weather_title_margin['left'] ) ? $weather_title_margin['left'] : '0';

	$custom_css .= "#splw-location-weather-{$splw_id}.splw-main-wrapper {
		max-width: 320px;
		margin   : auto;
		margin-bottom: 2em;
	}";
	$custom_css .= '#splw-location-weather-' . $splw_id . ' .splw-lite-wrapper,#splw-location-weather-' . $splw_id . ' .splw-forecast-weather select,#splw-location-weather-' . $splw_id . ' .splw-forecast-weather option,#splw-location-weather-' . $splw_id . ' .splw-lite-wrapper .splw-weather-attribution a{
	   color:' . $text_color . ';
	   text-decoration: none;
	}';

	$custom_css .= "#splw-location-weather-{$splw_id} .splw-lite-wrapper{ border: {$lw_bg_border}}";

	$custom_css .= "#splw-location-weather-{$splw_id} .splw-lite-wrapper{
		border-radius: {$bg_border_radius};
	}";
	$custom_css .= '#splw-location-weather-' . $splw_id . ' .splw-weather-title {
		margin-top :' . $lw_title_margin_top . 'px;
		margin-right :' . $lw_title_margin_right . 'px;
		margin-bottom: ' . $lw_title_margin_bottom . 'px;
		margin-left: ' . $lw_title_margin_left . 'px;
    }';

	$custom_css .= '#splw-location-weather-' . $splw_id . ' .splw-weather-icons div svg path{
		fill:' . $icon_color . ';
	}';
switch ( $background_type ) {
	case 'solid':
		$custom_css .= '#splw-location-weather-' . $splw_id . ' .splw-lite-wrapper,#splw-location-weather-' . $splw_id . ' .splw-forecast-weather option{background:' . $bg_solid . '}';
		break;
}


