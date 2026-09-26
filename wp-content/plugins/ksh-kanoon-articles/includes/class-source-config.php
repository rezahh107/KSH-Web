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

	const LATEST_URL = 'https://www.kanoon.ir/Article/Days';
	const WEEKLY_URL = 'https://www.kanoon.ir/';

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
