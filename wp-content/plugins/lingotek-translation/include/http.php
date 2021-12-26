<?php

/*
 * a simple wrapper to wp http functions
 *
 * @since 0.1
 */
class Lingotek_HTTP {
	protected $headers = array();

	const TIMEOUT = 30;
	/*
	 * formats a request as multipart
	 * greatly inspired from mailgun WordPress plugin
	 *
	 * @since 0.1
	 */
	public function format_as_multipart( &$body ) {
		// Arbitrary boundary.
		$boundary = '----------------------------32052ee8fd2c';

		$this->headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;

		$data = '';

		foreach ( $body as $key => $value ) {
			if ( is_array( $value ) ) {
				foreach ( $value as $k => $v ) {
					$data .= '--' . $boundary . "\r\n";
					$data .= 'Content-Disposition: form-data; name="' . $key;
					// Request targets cannot be in an array, as they need to have the same key.
					if ( $key !== 'translation_locale_code' && $key !== 'translation_workflow_id' ) {
						$data .= '[' . $k . ']';
					}
					$data .= "\"\r\n\r\n";
					$data .= $v . "\r\n";
				}
			} else {
				$data .= '--' . $boundary . "\r\n";
				$data .= 'Content-Disposition: form-data; name="' . $key . '"' . "\r\n\r\n";
				$data .= $value . "\r\n";
			}
		}

		$body = $data . '--' . $boundary . '--';
	}

	/*
	 * send a POST request
	 *
	 * @since 0.1
	 */
	public function post( $url, $args = array(), $custom_timeout = false ) {
		Lingotek::log( "POST $url" );
		if ( ! empty( $args ) ) {
			Lingotek::log( $args );
		}
		return wp_remote_post(
			$url,
			array(
				'headers' => $this->headers,
				'body'    => $args,
				'timeout' => ( $custom_timeout ) ? $custom_timeout : self::TIMEOUT,
			)
		);
	}

	/*
	 * send a GET request
	 *
	 * @since 0.1
	 */
	public function get( $url, $args = array() ) {
		Lingotek::log( "GET $url" );
		if ( ! empty( $args ) ) {
			Lingotek::log( $args );
		}
		return wp_remote_get(
			$url,
			array(
				'headers' => $this->headers,
				'body'    => $args,
				'timeout' => self::TIMEOUT,
			)
		);
	}

	/*
	 * send a DELETE request
	 *
	 * @since 0.1
	 */
	public function delete( $url, $args = array() ) {
		Lingotek::log( "DELETE $url" );
		if ( ! empty( $args ) ) {
			Lingotek::log( $args );
		}
		return wp_remote_request(
			$url,
			array(
				'method'  => 'DELETE',
				'headers' => $this->headers,
				'body'    => $args,
				'timeout' => self::TIMEOUT,
			)
		);
	}

	/*
	 * send a PATCH request
	 *
	 * @since 0.1
	 */
	public function patch( $url, $args = array() ) {
		Lingotek::log( "PATCH $url" );
		if ( ! empty( $args ) ) {
			Lingotek::log( $args );
		}
		return wp_remote_request(
			$url,
			array(
				'method'  => 'PATCH',
				'headers' => $this->headers,
				'body'    => $args,
				'timeout' => self::TIMEOUT,
			)
		);
	}
}
