<?php
/**
 * Weather class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

/**
 * The temperature class representing a temperature object.
 */
class Temperature {
	/**
	 * The current temperature.
	 *
	 * @var Unit
	 */
	public $now;

	/**
	 * The minimal temperature.
	 *
	 * @var Unit
	 */
	public $min;

	/**
	 * The maximal temperature.
	 *
	 * @var Unit
	 */
	public $max;

	/**
	 * The day temperature. Might not be null.
	 *
	 * @var Unit
	 */
	public $day;

	/**
	 * The morning temperature. Might not be null.
	 *
	 * @var Unit
	 */
	public $morning;

	/**
	 * The evening temperature. Might not be null.
	 *
	 * @var Unit
	 */
	public $evening;

	/**
	 * The night temperature. Might not be null.
	 *
	 * @var Unit
	 */
	public $night;

	/**
	 * Returns the current temperature as formatted string.
	 *
	 * @return string The current temperature as a formatted string.
	 */
	public function __toString() {
		return $this->now->__toString();
	}

	/**
	 * Returns the current temperature's unit.
	 *
	 * @return string The current temperature's unit.
	 */
	public function getUnit() {
		return $this->now->getUnit();
	}

	/**
	 * Returns the current temperature.
	 *
	 * @return string The current temperature.
	 */
	public function getValue() {
		return $this->now->getValue();
	}

	/**
	 * Returns the current temperature's description.
	 *
	 * @return string The current temperature's description.
	 */
	public function getDescription() {
		return $this->now->getDescription();
	}

	/**
	 * Returns the current temperature as formatted string.
	 *
	 * @return string The current temperature as formatted string.
	 */
	public function getFormatted() {
		return $this->now->getFormatted();
	}

	/**
	 * Create a new temperature object.
	 *
	 * @param Unit $now The current temperature.
	 * @param Unit $min The minimal temperature.
	 * @param Unit $max The maximal temperature.
	 * @param Unit $feels The feels temperature.
	 * @param Unit $day The day temperature. Might not be null.
	 * @param Unit $morning The morning temperature. Might not be null.
	 * @param Unit $evening The evening temperature. Might not be null.
	 * @param Unit $night The night temperature. Might not be null.
	 *
	 * @internal
	 */
	public function __construct( Unit $now, Unit $min, Unit $max, Unit $feels = null, Unit $day = null, Unit $morning = null, Unit $evening = null, Unit $night = null ) {
		$this->now     = $now;
		$this->min     = $min;
		$this->max     = $max;
		$this->feels   = $feels;
		$this->day     = $day;
		$this->morning = $morning;
		$this->evening = $evening;
		$this->night   = $night;
	}
}
