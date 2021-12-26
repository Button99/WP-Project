<?php
/**
 * Frontend file
 *
 * @package Location_Weather.
 */

namespace ShapedPlugin\Weather;

if ( ! defined( 'ABSPATH' ) ) {
	die; } // Cannot access directly.

/**
 * Frontend class
 */
class Frontend {

	/**
	 * The constructor of the class.
	 */
	public function __construct() {
		new Frontend\Shortcode();
		new Frontend\Scripts();
	}
}
