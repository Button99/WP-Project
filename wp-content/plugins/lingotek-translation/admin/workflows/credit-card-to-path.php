<?php

class Lingotek_Credit_Card_To_Path {

	private $cc_type_map     = array();
	private $default_cc      = 'Default';
	private static $instance = null;


	public static function get_url( $cc_type ) {
		self::check_instantiated();
		return self::$instance->get_cc_type_asset_url( $cc_type );
	}

	public static function get_cc_map() {
		self::check_instantiated();
		return self::$instance->cc_type_map;
	}

	public static function get_cc_type_asset_url( $cc_type ) {
		self::check_instantiated();
		return isset( self::$instance->cc_type_map[ $cc_type ] ) ? self::$instance->cc_type_map[ $cc_type ] : self::$instance->cc_type_map[ self::$instance->default_cc ];
	}

	public static function get_default_cc_key() {
		self::check_instantiated();
		return self::$instance->default_cc;
	}


	private function __construct() {
		$this->cc_type_map = array(
			'MasterCard'      => LINGOTEK_URL . '/img/credit-cards/mastercard.svg',
			'AmericanExpress' => LINGOTEK_URL . '/img/credit-cards/amex.svg',
			'Discover'        => LINGOTEK_URL . '/img/credit-cards/discover.svg',
			'JCB'             => LINGOTEK_URL . '/img/credit-cards/jcb.svg',
			'DinersClub'      => LINGOTEK_URL . '/img/credit-cards/diners.svg',
			'Visa'            => LINGOTEK_URL . '/img/credit-cards/visa.svg',
			$this->default_cc => LINGOTEK_URL . '/img/credit-cards/default.svg',
		);
	}

	private static function check_instantiated() {
		if ( ! self::$instance ) {
			self::$instance = new Lingotek_Credit_Card_To_Path();
		}
	}
}
