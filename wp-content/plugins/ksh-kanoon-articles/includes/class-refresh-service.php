<?php
/**
 * Canonical local refresh orchestration.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Reuses the qualified candidate producer and applies independent per-list LKG semantics.
 */
final class Refresh_Service {

	/** @var Preview_Service */
	private $preview;

	/** @var Snapshot_Store */
	private $store;

	/** @var callable */
	private $clock;

	/**
	 * Build the refresh service.
	 *
	 * @param Preview_Service $preview Qualified candidate producer.
	 * @param Snapshot_Store  $store   Local snapshot store.
	 * @param callable|null   $clock   UTC timestamp producer for deterministic tests.
	 */
	public function __construct( Preview_Service $preview, Snapshot_Store $store, $clock = null ) {
		$this->preview = $preview;
		$this->store   = $store;
		$this->clock   = $clock ? $clock : array( __CLASS__, 'utc_now' );
	}

	/**
	 * Acquire, validate, and independently persist both lists.
	 *
	 * @return array<string,mixed>
	 */
	public function run() {
		$candidates = $this->preview->run();
		$latest     = $this->apply_candidate( 'latest', $candidates['latest'] );
		$weekly     = $this->apply_candidate( 'weekly_popular', $candidates['weekly_popular'] );

		return array(
			'overall_status' => $this->overall_status( $latest, $weekly ),
			'latest'         => $latest,
			'weekly_popular' => $weekly,
		);
	}

	/**
	 * Return the current local snapshot for future presentation consumers.
	 *
	 * @param string $source List identity.
	 * @return array<string,mixed>|null
	 */
	public function get_snapshot( $source ) {
		return $this->store->get_snapshot( $source );
	}

	/**
	 * Return the latest bounded refresh attempt for an admin/status consumer.
	 *
	 * @param string $source List identity.
	 * @return array<string,mixed>|null
	 */
	public function get_attempt( $source ) {
		return $this->store->get_attempt( $source );
	}

	/**
	 * Apply one candidate without allowing invalid data to replace LKG state.
	 *
	 * @param string              $source    List identity.
	 * @param array<string,mixed> $candidate Candidate result.
	 * @return array<string,mixed>
	 */
	private function apply_candidate( $source, $candidate ) {
		$clock       = $this->clock;
		$attempted   = (string) $clock();
		$before      = $this->store->get_snapshot( $source );
		$status      = isset( $candidate['status'] ) ? (string) $candidate['status'] : 'failure';
		$reason      = isset( $candidate['reason'] ) ? (string) $candidate['reason'] : '';
		$http_code   = isset( $candidate['request']['http_code'] ) ? $candidate['request']['http_code'] : null;
		$action      = null === $before ? 'no_valid_snapshot_available' : 'preserved_previous';
		$write_error = '';

		if ( 'success' === $status ) {
			if ( $this->store->save_snapshot( $source, $candidate, $attempted ) ) {
				$action = 'updated';
			} else {
				$write_error = 'snapshot_write_failed';
			}
		}

		$current = $this->store->get_snapshot( $source );
		if ( '' !== $write_error ) {
			$reason = $write_error;
			$action = null === $current ? 'no_valid_snapshot_available' : 'preserved_previous';
		}

		$attempt_recorded = $this->store->save_attempt(
			$source,
			$attempted,
			$status,
			$http_code,
			$reason,
			$action
		);

		return array(
			'source'           => $source,
			'candidate_status' => $status,
			'candidate_count'  => isset( $candidate['count'] ) ? (int) $candidate['count'] : 0,
			'http_code'        => is_numeric( $http_code ) ? (int) $http_code : null,
			'reason'           => $reason,
			'action'           => $action,
			'local_available'  => null !== $current,
			'local_count'      => is_array( $current ) && isset( $current['count'] ) ? (int) $current['count'] : 0,
			'updated_at'       => is_array( $current ) && isset( $current['updated_at'] ) ? (string) $current['updated_at'] : '',
			'attempted_at'     => $attempted,
			'attempt_recorded' => $attempt_recorded,
		);
	}

	/**
	 * Describe the combined refresh outcome without hiding partial preservation.
	 *
	 * @param array<string,mixed> $latest Latest outcome.
	 * @param array<string,mixed> $weekly Weekly outcome.
	 * @return string
	 */
	private function overall_status( $latest, $weekly ) {
		$updated = 0;
		if ( 'updated' === $latest['action'] ) {
			++$updated;
		}
		if ( 'updated' === $weekly['action'] ) {
			++$updated;
		}

		if ( 2 === $updated ) {
			return 'success';
		}

		if ( 1 === $updated ) {
			return 'partial';
		}

		if ( 'ambiguous' === $latest['candidate_status'] || 'ambiguous' === $weekly['candidate_status'] ) {
			return 'ambiguous';
		}

		return 'failure';
	}

	/**
	 * Produce a stable UTC timestamp without depending on site timezone configuration.
	 *
	 * @return string
	 */
	public static function utc_now() {
		return gmdate( 'c' );
	}
}
