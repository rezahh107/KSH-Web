<?php
/**
 * Local last-known-good snapshot storage.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Persists only validated normalized list metadata and bounded attempt status.
 */
final class Snapshot_Store {

	const SCHEMA_VERSION = 1;

	const OPTION_LATEST_SNAPSHOT = 'ksh_kanoon_articles_latest_snapshot';
	const OPTION_WEEKLY_SNAPSHOT = 'ksh_kanoon_articles_weekly_snapshot';
	const OPTION_LATEST_ATTEMPT  = 'ksh_kanoon_articles_latest_attempt';
	const OPTION_WEEKLY_ATTEMPT  = 'ksh_kanoon_articles_weekly_attempt';

	/**
	 * Option reader.
	 *
	 * @var callable
	 */
	private $get_option;

	/**
	 * Option creator.
	 *
	 * @var callable
	 */
	private $add_option;

	/**
	 * Option updater.
	 *
	 * @var callable
	 */
	private $update_option;

	/**
	 * Build the store. Callables are injectable for deterministic tests.
	 *
	 * @param callable|null $get_option    Option reader.
	 * @param callable|null $add_option    Option creator.
	 * @param callable|null $update_option Option updater.
	 */
	public function __construct( $get_option = null, $add_option = null, $update_option = null ) {
		$this->get_option    = $get_option ? $get_option : 'get_option';
		$this->add_option    = $add_option ? $add_option : 'add_option';
		$this->update_option = $update_option ? $update_option : 'update_option';
	}

	/**
	 * Read one validated local snapshot without exposing option names to consumers.
	 *
	 * @param string $source List identity.
	 * @return array<string,mixed>|null
	 */
	public function get_snapshot( $source ) {
		$option = $this->snapshot_option( $source );
		if ( '' === $option ) {
			return null;
		}

		$get   = $this->get_option;
		$value = $get( $option, null );

		return is_array( $value ) ? $value : null;
	}

	/**
	 * Read the latest bounded refresh attempt for one list.
	 *
	 * @param string $source List identity.
	 * @return array<string,mixed>|null
	 */
	public function get_attempt( $source ) {
		$option = $this->attempt_option( $source );
		if ( '' === $option ) {
			return null;
		}

		$get   = $this->get_option;
		$value = $get( $option, null );

		return is_array( $value ) ? $value : null;
	}

	/**
	 * Replace one list snapshot only from a successful validated candidate.
	 *
	 * @param string              $source     List identity.
	 * @param array<string,mixed> $candidate  Preview/parser candidate.
	 * @param string              $updated_at UTC ISO-8601 timestamp.
	 * @return bool
	 */
	public function save_snapshot( $source, $candidate, $updated_at ) {
		$snapshot = $this->snapshot_from_candidate( $source, $candidate, $updated_at );
		if ( null === $snapshot ) {
			return false;
		}

		return $this->write_option( $this->snapshot_option( $source ), $snapshot );
	}

	/**
	 * Record bounded attempt metadata independently of the last-known-good snapshot.
	 *
	 * @param string $source           List identity.
	 * @param string $attempted_at     UTC ISO-8601 timestamp.
	 * @param string $candidate_status Candidate status.
	 * @param mixed  $http_code        HTTP status code when available.
	 * @param string $reason           Bounded reason code.
	 * @param string $action           Persistence action taken.
	 * @return bool
	 */
	public function save_attempt( $source, $attempted_at, $candidate_status, $http_code, $reason, $action ) {
		$option = $this->attempt_option( $source );
		if ( '' === $option ) {
			return false;
		}

		$attempt = array(
			'schema_version'   => self::SCHEMA_VERSION,
			'source'           => $source,
			'attempted_at'     => (string) $attempted_at,
			'candidate_status' => (string) $candidate_status,
			'http_code'        => is_numeric( $http_code ) ? (int) $http_code : null,
			'reason'           => (string) $reason,
			'action'           => (string) $action,
		);

		return $this->write_option( $option, $attempt );
	}

	/**
	 * Build a minimal persisted snapshot from an already validated candidate.
	 *
	 * @param string              $source     List identity.
	 * @param array<string,mixed> $candidate  Candidate result.
	 * @param string              $updated_at UTC ISO-8601 timestamp.
	 * @return array<string,mixed>|null
	 */
	private function snapshot_from_candidate( $source, $candidate, $updated_at ) {
		if (
			'success' !== ( isset( $candidate['status'] ) ? $candidate['status'] : '' ) ||
			( isset( $candidate['source'] ) ? $candidate['source'] : '' ) !== $source ||
			empty( $candidate['items'] ) ||
			! is_array( $candidate['items'] )
		) {
			return null;
		}

		$items = array();
		foreach ( $candidate['items'] as $item ) {
			if (
				! is_array( $item ) ||
				! isset( $item['title'], $item['url'], $item['date_context'] ) ||
				'' === (string) $item['title'] ||
				'' === (string) $item['url']
			) {
				return null;
			}

			$items[] = array(
				'title'        => (string) $item['title'],
				'url'          => (string) $item['url'],
				'date_context' => (string) $item['date_context'],
			);
		}

		if ( isset( $candidate['count'] ) && count( $items ) !== (int) $candidate['count'] ) {
			return null;
		}

		return array(
			'schema_version' => self::SCHEMA_VERSION,
			'source'         => $source,
			'source_url'     => isset( $candidate['source_url'] ) ? (string) $candidate['source_url'] : '',
			'items'          => $items,
			'count'          => count( $items ),
			'updated_at'     => (string) $updated_at,
			'date_context'   => isset( $candidate['date_context'] ) ? (string) $candidate['date_context'] : '',
		);
	}

	/**
	 * Write one plugin option as non-autoloaded and verify the stored value.
	 *
	 * @param string $name  Option name.
	 * @param mixed  $value Serializable option value.
	 * @return bool
	 */
	private function write_option( $name, $value ) {
		if ( '' === $name ) {
			return false;
		}

		$get    = $this->get_option;
		$add    = $this->add_option;
		$update = $this->update_option;

		$existing = $get( $name, null );
		if ( null === $existing ) {
			$add( $name, $value, '', false );
		} else {
			$update( $name, $value, false );
		}

		return $value === $get( $name, null );
	}

	/**
	 * Resolve snapshot option for a supported list.
	 *
	 * @param string $source List identity.
	 * @return string
	 */
	private function snapshot_option( $source ) {
		if ( 'latest' === $source ) {
			return self::OPTION_LATEST_SNAPSHOT;
		}

		if ( 'weekly_popular' === $source ) {
			return self::OPTION_WEEKLY_SNAPSHOT;
		}

		return '';
	}

	/**
	 * Resolve attempt option for a supported list.
	 *
	 * @param string $source List identity.
	 * @return string
	 */
	private function attempt_option( $source ) {
		if ( 'latest' === $source ) {
			return self::OPTION_LATEST_ATTEMPT;
		}

		if ( 'weekly_popular' === $source ) {
			return self::OPTION_WEEKLY_ATTEMPT;
		}

		return '';
	}
}
