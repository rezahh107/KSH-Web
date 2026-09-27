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

	/**
	 * Qualified candidate producer.
	 *
	 * @var Preview_Service
	 */
	private $preview;

	/**
	 * Local snapshot store.
	 *
	 * @var Snapshot_Store
	 */
	private $store;

	/**
	 * UTC timestamp producer.
	 *
	 * @var callable
	 */
	private $clock;

	/**
	 * Bounded run-identity producer.
	 *
	 * @var callable
	 */
	private $run_id_factory;

	/**
	 * Build the refresh service.
	 *
	 * @param Preview_Service $preview        Qualified candidate producer.
	 * @param Snapshot_Store  $store          Local snapshot store.
	 * @param callable|null   $clock          UTC timestamp producer for deterministic tests.
	 * @param callable|null   $run_id_factory Run identity producer for deterministic tests.
	 */
	public function __construct( Preview_Service $preview, Snapshot_Store $store, $clock = null, $run_id_factory = null ) {
		$this->preview        = $preview;
		$this->store          = $store;
		$this->clock          = $clock ? $clock : array( __CLASS__, 'utc_now' );
		$this->run_id_factory = $run_id_factory ? $run_id_factory : array( __CLASS__, 'make_run_id' );
	}

	/**
	 * Acquire, validate, and independently persist both lists.
	 *
	 * Production callers must pass an explicit Manual or Cron origin. Unknown is
	 * retained only for legacy/unattributed compatibility and is not summarized
	 * as either Manual or Cron evidence.
	 *
	 * @param string $trigger Refresh origin.
	 * @return array<string,mixed>
	 */
	public function run( $trigger = 'unknown' ) {
		$trigger        = $this->normalize_trigger( $trigger );
		$clock          = $this->clock;
		$run_id_factory = $this->run_id_factory;
		$started_at     = (string) $clock();
		$run_id         = (string) $run_id_factory( $trigger, $started_at );
		$candidates     = $this->preview->run();
		$latest         = $this->apply_candidate( 'latest', $candidates['latest'], $trigger, $run_id, $started_at );
		$weekly         = $this->apply_candidate( 'weekly_popular', $candidates['weekly_popular'], $trigger, $run_id, $started_at );
		$overall_status = $this->overall_status( $latest, $weekly );
		$completed_at   = (string) $clock();
		$summary        = $this->build_run_summary( $trigger, $run_id, $started_at, $completed_at, $overall_status, $latest, $weekly );
		$summary_saved  = false;
		$summary_reason = '';

		if ( 'manual' === $trigger || 'cron' === $trigger ) {
			$summary_saved = $this->store->save_run_summary( $trigger, $summary );
			if ( ! $summary_saved ) {
				$summary_reason = 'run_summary_write_failed';
			}
		} else {
			$summary_reason = 'unknown_trigger_not_persisted';
		}

		return array(
			'schema_version'       => Snapshot_Store::RUN_SUMMARY_SCHEMA_VERSION,
			'trigger'              => $trigger,
			'run_id'               => $run_id,
			'started_at'           => $started_at,
			'completed_at'         => $completed_at,
			'overall_status'       => $overall_status,
			'latest'               => $latest,
			'weekly_popular'       => $weekly,
			'run_summary_recorded' => $summary_saved,
			'run_summary_reason'   => $summary_reason,
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
	 * @param string              $source       List identity.
	 * @param array<string,mixed> $candidate    Candidate result.
	 * @param string              $trigger      Refresh origin.
	 * @param string              $run_id       Refresh run identity.
	 * @param string              $attempted_at Shared run start timestamp.
	 * @return array<string,mixed>
	 */
	private function apply_candidate( $source, $candidate, $trigger, $run_id, $attempted_at ) {
		$before      = $this->store->get_snapshot( $source );
		$status      = isset( $candidate['status'] ) ? (string) $candidate['status'] : 'failure';
		$reason      = isset( $candidate['reason'] ) ? (string) $candidate['reason'] : '';
		$http_code   = isset( $candidate['request']['http_code'] ) ? $candidate['request']['http_code'] : null;
		$action      = null === $before ? 'no_valid_snapshot_available' : 'preserved_previous';
		$write_error = '';

		if ( 'success' === $status ) {
			if ( $this->store->save_snapshot( $source, $candidate, $attempted_at ) ) {
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
			$attempted_at,
			$status,
			$http_code,
			$reason,
			$action,
			$trigger,
			$run_id
		);
		$attempt_reason   = $attempt_recorded ? '' : 'attempt_write_failed';

		return array(
			'source'           => $source,
			'trigger'          => $trigger,
			'run_id'           => $run_id,
			'candidate_status' => $status,
			'candidate_count'  => isset( $candidate['count'] ) ? (int) $candidate['count'] : 0,
			'http_code'        => is_numeric( $http_code ) ? (int) $http_code : null,
			'reason'           => $reason,
			'action'           => $action,
			'local_available'  => null !== $current,
			'local_count'      => is_array( $current ) && isset( $current['count'] ) ? (int) $current['count'] : 0,
			'updated_at'       => is_array( $current ) && isset( $current['updated_at'] ) ? (string) $current['updated_at'] : '',
			'attempted_at'     => $attempted_at,
			'attempt_recorded' => $attempt_recorded,
			'attempt_reason'   => $attempt_reason,
		);
	}

	/**
	 * Build the bounded run summary persisted separately by explicit origin.
	 *
	 * @param string              $trigger        Refresh origin.
	 * @param string              $run_id         Refresh run identity.
	 * @param string              $started_at     Run start timestamp.
	 * @param string              $completed_at   Run completion timestamp.
	 * @param string              $overall_status Combined result.
	 * @param array<string,mixed> $latest         Latest outcome.
	 * @param array<string,mixed> $weekly         Weekly outcome.
	 * @return array<string,mixed>
	 */
	private function build_run_summary( $trigger, $run_id, $started_at, $completed_at, $overall_status, $latest, $weekly ) {
		return array(
			'schema_version' => Snapshot_Store::RUN_SUMMARY_SCHEMA_VERSION,
			'trigger'        => $trigger,
			'run_id'         => $run_id,
			'started_at'     => $started_at,
			'completed_at'   => $completed_at,
			'overall_status' => $overall_status,
			'lists'          => array(
				'latest'         => $this->summarize_outcome( $latest ),
				'weekly_popular' => $this->summarize_outcome( $weekly ),
			),
		);
	}

	/**
	 * Keep only bounded outcome evidence in a persisted run summary.
	 *
	 * @param array<string,mixed> $outcome Refresh list outcome.
	 * @return array<string,mixed>
	 */
	private function summarize_outcome( $outcome ) {
		return array(
			'candidate_status' => isset( $outcome['candidate_status'] ) ? (string) $outcome['candidate_status'] : 'failure',
			'candidate_count'  => isset( $outcome['candidate_count'] ) ? (int) $outcome['candidate_count'] : 0,
			'http_code'        => isset( $outcome['http_code'] ) && is_numeric( $outcome['http_code'] ) ? (int) $outcome['http_code'] : null,
			'reason'           => isset( $outcome['reason'] ) ? (string) $outcome['reason'] : '',
			'action'           => isset( $outcome['action'] ) ? (string) $outcome['action'] : 'no_valid_snapshot_available',
			'local_available'  => ! empty( $outcome['local_available'] ),
			'local_count'      => isset( $outcome['local_count'] ) ? (int) $outcome['local_count'] : 0,
			'updated_at'       => isset( $outcome['updated_at'] ) ? (string) $outcome['updated_at'] : '',
			'attempt_recorded' => ! empty( $outcome['attempt_recorded'] ),
			'attempt_reason'   => isset( $outcome['attempt_reason'] ) ? (string) $outcome['attempt_reason'] : '',
		);
	}

	/**
	 * Describe the combined refresh outcome without hiding operational write failures.
	 *
	 * @param array<string,mixed> $latest Latest outcome.
	 * @param array<string,mixed> $weekly Weekly outcome.
	 * @return string
	 */
	private function overall_status( $latest, $weekly ) {
		if ( empty( $latest['attempt_recorded'] ) || empty( $weekly['attempt_recorded'] ) ) {
			return 'degraded';
		}

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
	 * Normalize refresh origin without inventing attribution.
	 *
	 * @param string $trigger Refresh origin.
	 * @return string
	 */
	private function normalize_trigger( $trigger ) {
		return in_array( $trigger, array( 'manual', 'cron' ), true ) ? $trigger : 'unknown';
	}

	/**
	 * Produce a bounded run identifier from origin, time, and current microsecond.
	 *
	 * @param string $trigger    Refresh origin.
	 * @param string $started_at ISO-8601 run start.
	 * @return string
	 */
	public static function make_run_id( $trigger, $started_at ) {
		$time    = microtime( true );
		$micros  = (int) round( ( $time - floor( $time ) ) * 1000000 );
		$compact = preg_replace( '/[^0-9]/', '', (string) $started_at );

		return sprintf( '%s-%s-%06d', $trigger, (string) $compact, $micros );
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
