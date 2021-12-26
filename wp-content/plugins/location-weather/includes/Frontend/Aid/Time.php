<?php
/**
 * Weather class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

/**
 * The time class representing a time object.
 */
class Time {
	/**
	 * The start time for the forecast.
	 *
	 * @var \DateTime
	 */
	public $from;

	/**
	 * The end time for the forecast.
	 *
	 * @var \DateTime
	 */
	public $to;

	/**
	 * The day of the forecast.
	 *
	 * @var \DateTime
	 */
	public $day;

	/**
	 * Create a new time object.
	 *
	 * @param string|\DateTime      $from The start time for the forecast.
	 * @param string|\DateTime|null $to   The end time for the forecast.
	 *
	 * @internal
	 */
	public function __construct( $from, $to = null ) {
		$UtcTimeZone = new \DateTimeZone( 'UTC' );
		if ( isset( $to ) ) {
			$from = ( $from instanceof \DateTime ) ? $from : new \DateTime( (string) $from, $UtcTimeZone );
			$to   = ( $to instanceof \DateTime ) ? $to : new \DateTime( (string) $to, $UtcTimeZone );
			$day  = new \DateTime( $from->format( 'Y-m-d' ), $UtcTimeZone );
		} else {
			$from = ( $from instanceof \DateTime ) ? $from : new \DateTime( (string) $from, $UtcTimeZone );
			$day  = $from = new \DateTime( $from->format( 'Y-m-d' ), $UtcTimeZone );
			$to   = clone $from;
			$to   = $to->add( new \DateInterval( 'PT23H59M59S' ) );
		}

		$this->from = $from;
		$this->to   = $to;
		$this->day  = $day;
	}
}
