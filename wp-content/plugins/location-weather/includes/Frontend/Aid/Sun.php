<?php
/**
 * Weather class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

/**
 * The sun class representing a sun object.
 */
class Sun {

	/**
	 * Sun rising time.
	 *
	 * @var \DateTime The time of the sun rise.
	 */
	public $rise;

	/**
	 * Sun setting time.
	 *
	 * @var \DateTime The time of the sun set.
	 */
	public $set;

	/**
	 * Create a new sun object.
	 *
	 * @param \DateTime $rise The time of the sun rise.
	 * @param \DateTime $set  The time of the sun set.
	 *
	 * @throws \LogicException If sunset is before sunrise.
	 * @internal
	 */
	public function __construct( \DateTime $rise, \DateTime $set ) {
		if ( $set < $rise ) {
			throw new \LogicException( 'Sunset cannot be before sunrise!' );
		}
		$this->rise = $rise;
		$this->set  = $set;
	}
}
