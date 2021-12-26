<?php
/**
 * CurrentWeather class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

/**
 * The city class representing a city object.
 */
class City extends Location {
	/**
	 * The city id.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The name of the city.
	 *
	 * @var string
	 */
	public $name;

	/**
	 * The abbreviation of the country the city is located in.
	 *
	 * @var string
	 */
	public $country;

	/**
	 * The city's population.
	 *
	 * @var int
	 */
	public $population;

	/**
	 * The shift in seconds from UTC.\
	 *
	 * @var \DateTimeZone|null
	 */
	public $timezone;

	/**
	 * Create a new city object.
	 *
	 * @param int    $id             The city id.
	 * @param string $name           The name of the city.
	 * @param float  $lat            The latitude of the city.
	 * @param float  $lon            The longitude of the city.
	 * @param string $country        The abbreviation of the country the city is located in.
	 * @param int    $population     The city's population.
	 * @param int    $timezoneOffset The shift in seconds from UTC.
	 *
	 * @internal
	 */
	public function __construct( $id, $name = null, $lat = null, $lon = null, $country = null, $population = null, $timezoneOffset = null ) {
		$this->id         = (int) $id;
		$this->name       = isset( $name ) ? (string) $name : null;
		$this->country    = isset( $country ) ? (string) $country : null;
		$this->population = isset( $population ) ? (int) $population : null;
		$this->timezone   = isset( $timezoneOffset ) ? new \DateTimeZone( self::timezoneOffsetInSecondsToHours( (int) $timezoneOffset ) ) : null;

		parent::__construct( $lat, $lon );
	}

	/**
	 * The timezone offset in seconds to hours.
	 *
	 * @param int $offset The timezone offset in seconds from UTC.
	 * @return int        The timezone offset in +/-HH:MM form.
	 */
	private static function timezoneOffsetInSecondsToHours( $offset ) {
		$minutes = floor( abs( $offset ) / 60 ) % 60;
		$hours   = floor( abs( $offset ) / 3600 );

		$result  = $offset < 0 ? '-' : '+';
		$result .= str_pad( $hours, 2, '0', STR_PAD_LEFT );
		$result .= str_pad( $minutes, 2, '0', STR_PAD_LEFT );

		return $result;
	}
}
