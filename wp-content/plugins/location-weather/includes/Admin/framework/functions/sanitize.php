<?php
/**
 *  Framework sanitize file.
 *
 * @package    Location_weather
 * @subpackage Location_weather/framework
 */

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

if ( ! function_exists( 'splwt_sanitize_replace_a_to_b' ) ) {
	/**
	 *
	 * Sanitize
	 * Replace letter a to letter b
	 *
	 * @param  string $value string.
	 *
	 * @return string
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	function splwt_sanitize_replace_a_to_b( $value ) {
		return str_replace( 'a', 'b', $value );
	}
}

if ( ! function_exists( 'splwt_sanitize_title' ) ) {
	/**
	 *
	 * Sanitize title
	 *
	 * @param  string $value string.
	 *
	 * @return string
	 * @since 1.0.0
	 * @version 1.0.0
	 */
	function splwt_sanitize_title( $value ) {
		return sanitize_title( $value );
	}
}

/**
 *
 * Sanitize text
 *
 * @param  string $value string.
 *
 * @return string
 * @since 1.0.0
 * @version 1.0.0
 */
function lw_sanitize_text( $value ) {

	$safe_text = filter_var( $value, FILTER_SANITIZE_STRING );
	return $safe_text;

}
