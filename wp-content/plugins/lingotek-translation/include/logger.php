<?php

/*
 * This class handles logging events by giving the option to report / subscribe to logging events
 *
 * @since 1.3.1
 */
class Lingotek_Logger {

	public static function fatal( $message, $extra_data = null, $error = null ) {
		self::log( 'fatal', $message, $extra_data, $error ); }
	public static function error( $message, $extra_data = null, $error = null ) {
		self::log( 'error', $message, $extra_data, $error ); }
	public static function warning( $message, $extra_data = null, $error = null ) {
		self::log( 'warning', $message, $extra_data, $error ); }
	public static function info( $message, $extra_data = null ) {
		self::log( 'info', $message, $extra_data ); }
	public static function debug( $message, $extra_data = null ) {
		self::log( 'debug', $message, $extra_data ); }

	private static function log( $level, $message, $extra_data, $error = null ) {
		try {
			do_action( 'lingotek_log', $level, $message, $extra_data, $error );
			do_action( 'lingotek_log_' . $level, $message, $extra_data, $error );
			$prefs      = Lingotek_Model::get_prefs();
			$logging    = isset( $prefs['enable_lingotek_logs'] ) && is_array( $prefs['enable_lingotek_logs'] ) && '1' === $prefs['enable_lingotek_logs']['enabled'] ? true : false;
			$is_logging = $logging ? 'true' : 'false';
			if ( $logging ) {
				$serialized_extra_data = self::serialize_extra_data( $extra_data );
				$errorMessage          = isset( $error ) && ! isempty( $error ) ? ', Error: ' . $error : '';
				error_log( $level . ': ' . $message . $errorMessage . ', ExtraData: ' . $serialized_extra_data );
			}
		} catch ( Exception $e ) {
			error_log( 'error occured while trying to write Lingotek log entry' );
		}
	}

	private static function serialize_extra_data( $extra_data ) {
		try {
			if ( is_string( $extra_data ) ) {
				return $extra_data;
			}
			return json_encode( $extra_data, JSON_PRETTY_PRINT );
		} catch ( Exception $e ) {
			return '';
		}
	}
}
