<?php
/**
 * Location class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

/**
 * The location class representing a location object.
 */
class Location {
	/**
	 * Location latitude.
	 *
	 * @var float The latitude of the city.
	 */
	public $lat;

	/**
	 * Location longitude.
	 *
	 * @var float The longitude of the city.
	 */
	public $lon;

	/**
	 * Create a new location object.
	 *
	 * @param float $lat The latitude of the city.
	 * @param float $lon The longitude of the city.
	 *
	 * @internal
	 */
	public function __construct( $lat = null, $lon = null ) {
		$this->lat = isset( $lat ) ? (float) $lat : null;
		$this->lon = isset( $lon ) ? (float) $lon : null;
	}
}
