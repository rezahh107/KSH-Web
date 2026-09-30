<?php
/**
 * Canonical source ownership.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Owns the two approved remote source URLs.
 */
final class Source_Config {

	const HOMEPAGE_URL = 'https://www.kanoon.ir/';
	const LATEST_URL   = self::HOMEPAGE_URL;
	const WEEKLY_URL   = self::HOMEPAGE_URL;

	/**
	 * Get the approved source URL for a list identity.
	 *
	 * @param string $source List identity.
	 * @return string
	 */
	public static function url( $source ) {
		if ( 'latest' === $source ) {
			return self::LATEST_URL;
		}

		if ( 'weekly_popular' === $source ) {
			return self::WEEKLY_URL;
		}

		return '';
	}
}
