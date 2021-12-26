<?php
/**
 * Weather class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

/**
 * The wind class representing a wind object.
 */
class Wind {
	/**
	 * The wind speed.
	 *
	 * @var Unit
	 */
	public $speed;

	/**
	 * The wind direction.
	 *
	 * @var Unit|null
	 */
	public $direction;

	/**
	 * Create a new wind object.
	 *
	 * @param Unit $speed     The wind speed.
	 * @param Unit $direction The wind direction.
	 *
	 * @internal
	 */
	public function __construct( Unit $speed, Unit $direction = null ) {
		$this->speed     = $speed;
		$this->direction = $direction;
	}
}
