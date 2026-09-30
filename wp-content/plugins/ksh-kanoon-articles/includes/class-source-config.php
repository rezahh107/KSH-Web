<?php
/**
 * Canonical source ownership.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Owns the approved remote source URLs and acquisition-contract identity.
 */
final class Source_Config {

	const HOMEPAGE_URL = 'https://www.kanoon.ir/';
	const LATEST_URL   = self::HOMEPAGE_URL;
	const WEEKLY_URL   = self::HOMEPAGE_URL;

	/**
	 * Explicit identity for the exact acquisition/parser contract that requires
	 * real-host qualification before writable Manual/Cron refresh is admitted.
	 *
	 * This is intentionally independent from the plugin version. Any material
	 * source/parser semantic change must use a new identity so historical
	 * qualification cannot carry forward automatically.
	 */
	const ACQUISITION_CONTRACT_ID = 'kanoon-homepage-semantic-lists-v1';

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
