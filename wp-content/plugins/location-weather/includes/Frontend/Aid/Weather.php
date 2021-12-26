<?php
/**
 * Weather class file.
 *
 * @package Location_Weather
 */

namespace ShapedPlugin\Weather\Frontend\Aid;

/**
 * The weather class representing a weather object.
 */
class Weather {
	/**
	 * The weather id.
	 *
	 * @var int
	 */
	public $id;

	/**
	 * The weather description.
	 *
	 * @var string
	 */
	public $description;

	/**
	 * The icon name.
	 *
	 * @var string
	 */
	public $icon;

	/**
	 * The url for icons.
	 *
	 * @var string
	 *
	 * @see self::getIconUrl() to see how it is used.
	 */
	private static $iconUrl = '//openweathermap.org/img/w/%s.png';

	/**
	 * Create a new weather object.
	 *
	 * @param int    $id          The icon id.
	 * @param string $description The weather description.
	 * @param string $icon        The icon name.
	 *
	 * @internal
	 */
	public function __construct( $id, $description, $icon ) {
		$this->id          = (int) $id;
		$this->description = (string) $description;
		$this->icon        = (string) $icon;
	}

	/**
	 * Get the weather description.
	 *
	 * @return string
	 */
	public function __toString() {
		return $this->description;
	}

	/**
	 * Get the icon url.
	 *
	 * @return string The icon url.
	 */
	public function getIconUrl() {
		return sprintf( self::$iconUrl, $this->icon );
	}

	/**
	 * Change the url template for icon urls. Please note: This will change the url template for
	 * all instances of this library.
	 *
	 * @param string $iconUrl The url template to build the icon urls.
	 */
	public static function setIconUrlTemplate( $iconUrl ) {
		self::$iconUrl = $iconUrl;
	}
}
