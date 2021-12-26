<?php

/**
 * Helper class to model an action interacting with Lingotek.
 *
 * @since 1.5.0
 */
class Lingotek_Action_Url {

	/**
	 * The uri.
	 *
	 * @var string
	 */
	protected $uri;

	/**
	 * The title.
	 *
	 * @var string
	 */
	protected $title;

	public function __construct( $uri, $title ) {
		$this->uri   = $uri;
		$this->title = $title;
	}

	public function getTitle() {
		return $this->title;
	}

	public function getUri() {
		return $this->uri;
	}

	public function render() {
		return sprintf(
			'
	      <li>
	         <a href="%s">%s</a>
	      </li>
			',
			$this->getUri(),
			$this->getTitle()
		);
	}

}
