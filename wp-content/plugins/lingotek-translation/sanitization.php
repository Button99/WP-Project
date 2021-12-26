<?php
/**
 * Collection of functions that needs to be available everywhere in Lingotek Translation plugin.
 *
 * @package Lingotek
 */

/**
 * Sanitizes a Lingotek locale.
 *
 * Lower and uppercase alphanumeric characters, dashes, and underscores are allowed.
 *
 * @since 1.4.14
 *
 * @param string $locale String locale.
 * @return string Sanitized locale.
 */
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedFunctionFound
function sanitize_lingotek_locale( $locale ) {
	$raw_locale = $locale;
	$locale     = preg_replace( '/[^a-zA-Z0-9_\-]/', '', $locale );

	/**
	 * Filters a sanitized key string.
	 *
	 * @since 1.4.14
	 *
	 * @param string $key     Sanitized key.
	 * @param string $raw_key The key prior to sanitization.
	 */
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound
	return apply_filters( 'sanitize_lingotek_locale', $locale, $raw_locale );
}
