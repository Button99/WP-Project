<?php
/**
 * Weather class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

use JsonSerializable;

/**
 * The unit class representing a unit object.
 */
class Unit implements JsonSerializable {
	/**
	 * The value.
	 *
	 * @var float
	 *
	 * @internal
	 */
	public $value;

	/**
	 * The value's unit.
	 *
	 * @var string
	 *
	 * @internal
	 */
	private $unit;

	/**
	 * The value's description.
	 *
	 * @var string
	 *
	 * @internal
	 */
	private $description;

	/**
	 * The value's measurement precision. Can be null if unknown.
	 *
	 * @var float|null
	 */
	private $precision;

	/**
	 * Create a new unit object.
	 *
	 * @param float      $value The value.
	 * @param string     $unit The unit of the value.
	 * @param string     $description The description of the value.
	 * @param float|null $precision The precision of the value, or null if unknown.
	 *
	 * @internal
	 */
	public function __construct( $value = 0.0, $unit = '', $description = '', $precision = null ) {
		$this->value       = (float) $value;
		$this->unit        = (string) $unit;
		$this->description = (string) $description;
		$this->precision   = null === $precision ? null : (float) $precision;
	}

	/**
	 * Get the value as formatted string with unit.
	 *
	 * @return string The value as formatted string with unit.
	 *
	 * The unit is not included if it is empty.
	 */
	public function __toString() {
		return $this->getFormatted();
	}

	/**
	 * Get the value's unit.
	 *
	 * @return string The value's unit.
	 *
	 * This also converts 'celsius' to '°C' and 'fahrenheit' to '°F'.
	 */
	public function getUnit() {
		// Units are inconsistent. Only celsius and fahrenheit are not abbreviated. This check fixes that.
		// Also, the API started to return "metric" as temperature unit recently. Also fix that.
		if ( 'celsius' === $this->unit || 'metric' === $this->unit ) {
			return '°C';
		} elseif ( 'fahrenheit' === $this->unit || 'imperial' === $this->unit ) {
			return '°F';
		} else {
			return $this->unit;
		}
	}

	/**
	 * Get the value.
	 *
	 * @return float The value.
	 */
	public function getValue() {
		return $this->value;
	}

	/**
	 * Get the value's description.
	 *
	 * @return string The value's description.
	 */
	public function getDescription() {
		return $this->description;
	}

	/**
	 * Get the value's precision.
	 *
	 * @return float|null The value's precision. Can be null if unknown.
	 */
	public function getPrecision() {
		return $this->precision;
	}

	/**
	 * Get the value as formatted string with unit.
	 *
	 * @return string The value as formatted string with unit.
	 *
	 * The unit is not included if it is empty.
	 */
	public function getFormatted() {
		if ( '' !== $this->getUnit() ) {
			return $this->getValue() . ' ' . $this->getUnit();
		} else {
			return (string) $this->getValue();
		}
	}

	/**
	 * Get Unit properties when encoding to JSON
	 *
	 * @return array
	 */
	public function jsonSerialize() {
		return array(
			'value'       => $this->getValue(),
			'unit'        => $this->getUnit(),
			'description' => $this->getDescription(),
			'precision'   => $this->getPrecision(),
		);
	}
}
