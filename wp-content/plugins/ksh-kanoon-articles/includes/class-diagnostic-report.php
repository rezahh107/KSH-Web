<?php
/**
 * Read-only operational diagnostic report.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Builds a bounded support bundle from plugin-owned local state and safe runtime facts.
 */
final class Diagnostic_Report {

	const SCHEMA = 'ksh-kanoon-articles-diagnostic-v1';

	/**
	 * Local snapshot/status store.
	 *
	 * @var Snapshot_Store
	 */
	private $store;

	/**
	 * Native schedule owner.
	 *
	 * @var Scheduler
	 */
	private $scheduler;

	/**
	 * UTC timestamp producer.
	 *
	 * @var callable
	 */
	private $clock;

	/**
	 * Safe runtime-fact producer.
	 *
	 * @var callable
	 */
	private $runtime_facts;

	/**
	 * Build the read-only reporter.
	 *
	 * @param Snapshot_Store $store         Local plugin state.
	 * @param Scheduler      $scheduler     Native schedule owner.
	 * @param callable|null  $clock         UTC timestamp producer for deterministic tests.
	 * @param callable|null  $runtime_facts Safe runtime-fact producer for deterministic tests.
	 */
	public function __construct( Snapshot_Store $store, Scheduler $scheduler, $clock = null, $runtime_facts = null ) {
		$this->store         = $store;
		$this->scheduler     = $scheduler;
		$this->clock         = $clock ? $clock : array( __CLASS__, 'utc_now' );
		$this->runtime_facts = $runtime_facts ? $runtime_facts : array( __CLASS__, 'read_runtime_facts' );
	}

	/**
	 * Build the complete report without remote acquisition or state mutation.
	 *
	 * @return array<string,mixed>
	 */
	public function build() {
		$clock              = $this->clock;
		$runtime_facts      = $this->runtime_facts;
		$generated_at       = (string) $clock();
		$next_run           = $this->scheduler->next_run();
		$scheduled          = false !== $next_run;
		$last_manual        = $this->run_summary_report( $this->store->get_run_summary( 'manual' ), 'manual' );
		$last_cron          = $this->run_summary_report( $this->store->get_run_summary( 'cron' ), 'cron' );
		$cron_observed      = null !== $last_cron;
		$latest             = $this->snapshot_report( 'latest' );
		$weekly             = $this->snapshot_report( 'weekly_popular' );
		$latest_attempt     = $this->attempt_report( $this->store->get_attempt( 'latest' ) );
		$weekly_attempt     = $this->attempt_report( $this->store->get_attempt( 'weekly_popular' ) );
		$manual_summary_gap = $this->attempts_without_matching_summary( $latest_attempt, $weekly_attempt, 'manual', $last_manual );
		$cron_summary_gap   = $this->attempts_without_matching_summary( $latest_attempt, $weekly_attempt, 'cron', $last_cron );

		return array(
			'report'     => array(
				'schema'           => self::SCHEMA,
				'generated_at_utc' => $generated_at,
			),
			'plugin'     => array(
				'name'                       => 'KSH Kanoon Articles',
				'slug'                       => 'ksh-kanoon-articles',
				'loaded'                     => true,
				'version'                    => Plugin::VERSION,
				'expected_version'           => Plugin::VERSION,
				'version_matches_expected'   => true,
				'snapshot_schema_version'    => Snapshot_Store::SCHEMA_VERSION,
				'attempt_schema_version'     => Snapshot_Store::ATTEMPT_SCHEMA_VERSION,
				'run_summary_schema_version' => Snapshot_Store::RUN_SUMMARY_SCHEMA_VERSION,
			),
			'runtime'    => (array) $runtime_facts(),
			'scheduler'  => array(
				'hook'                    => Scheduler::HOOK,
				'event_registered'        => $scheduled,
				'recurrence'              => $scheduled ? $this->scheduler->recurrence() : null,
				'next_run_unix'           => $scheduled ? (int) $next_run : null,
				'next_run_utc'            => $scheduled ? gmdate( 'c', (int) $next_run ) : null,
				'cron_execution_observed' => $cron_observed,
				'last_cron_run'           => $last_cron,
			),
			'refresh'    => array(
				'last_manual_run' => $last_manual,
				'last_cron_run'   => $last_cron,
			),
			'lists'      => array(
				'latest'         => array(
					'current_snapshot' => $latest,
					'latest_attempt'   => $latest_attempt,
				),
				'weekly_popular' => array(
					'current_snapshot' => $weekly,
					'latest_attempt'   => $weekly_attempt,
				),
			),
			'assessment' => array(
				'local_latest_available'                   => ! empty( $latest['available'] ),
				'local_latest_valid'                       => ! empty( $latest['valid'] ),
				'local_weekly_available'                   => ! empty( $weekly['available'] ),
				'local_weekly_valid'                       => ! empty( $weekly['valid'] ),
				'scheduler_registered'                     => $scheduled,
				'cron_execution_observed'                  => $cron_observed,
				'last_cron_overall_status'                 => $cron_observed ? $last_cron['overall_status'] : null,
				'manual_attempts_without_matching_summary' => $manual_summary_gap,
				'cron_attempts_without_matching_summary'   => $cron_summary_gap,
				'observability_incomplete'                 => $manual_summary_gap || $cron_summary_gap,
				'diagnostic_state'                         => $this->diagnostic_state( $scheduled, $cron_observed ),
			),
		);
	}

	/**
	 * Read only safe bounded WordPress/PHP runtime facts.
	 *
	 * @return array<string,mixed>
	 */
	public static function read_runtime_facts() {
		global $wp_version;

		return array(
			'wordpress_version' => isset( $wp_version ) ? (string) $wp_version : '',
			'php_version'       => PHP_VERSION,
			'site_timezone'     => function_exists( 'wp_timezone_string' ) ? (string) wp_timezone_string() : null,
			'disable_wp_cron'   => array(
				'defined' => defined( 'DISABLE_WP_CRON' ),
				'enabled' => defined( 'DISABLE_WP_CRON' ) ? (bool) constant( 'DISABLE_WP_CRON' ) : false,
			),
			'alternate_wp_cron' => array(
				'defined' => defined( 'ALTERNATE_WP_CRON' ),
				'enabled' => defined( 'ALTERNATE_WP_CRON' ) ? (bool) constant( 'ALTERNATE_WP_CRON' ) : false,
			),
		);
	}

	/**
	 * Produce a stable UTC report timestamp.
	 *
	 * @return string
	 */
	public static function utc_now() {
		return gmdate( 'c' );
	}

	/**
	 * Build a bounded snapshot projection and validate the stored structure locally.
	 *
	 * @param string $source List identity.
	 * @return array<string,mixed>
	 */
	private function snapshot_report( $source ) {
		$snapshot  = $this->store->get_snapshot( $source );
		$available = is_array( $snapshot );
		$items     = array();

		if ( $available && isset( $snapshot['items'] ) && is_array( $snapshot['items'] ) ) {
			foreach ( array_slice( $snapshot['items'], 0, 50 ) as $item ) {
				if ( ! is_array( $item ) ) {
					continue;
				}

				$items[] = array(
					'title'        => isset( $item['title'] ) ? (string) $item['title'] : '',
					'url'          => isset( $item['url'] ) ? (string) $item['url'] : '',
					'date_context' => isset( $item['date_context'] ) ? (string) $item['date_context'] : '',
				);
			}
		}

		return array(
			'available'      => $available,
			'valid'          => $this->snapshot_is_valid( $snapshot, $source ),
			'schema_version' => $available && isset( $snapshot['schema_version'] ) ? (int) $snapshot['schema_version'] : null,
			'count'          => $available && isset( $snapshot['count'] ) ? (int) $snapshot['count'] : 0,
			'updated_at'     => $available && ! empty( $snapshot['updated_at'] ) ? (string) $snapshot['updated_at'] : null,
			'source_url'     => $available && ! empty( $snapshot['source_url'] ) ? (string) $snapshot['source_url'] : null,
			'date_context'   => $available && isset( $snapshot['date_context'] ) ? (string) $snapshot['date_context'] : '',
			'items'          => $items,
		);
	}

	/**
	 * Determine whether a stored snapshot still satisfies the bounded persisted shape.
	 *
	 * @param mixed  $snapshot Stored snapshot.
	 * @param string $source   Expected list identity.
	 * @return bool
	 */
	private function snapshot_is_valid( $snapshot, $source ) {
		if (
			! is_array( $snapshot ) ||
			! isset( $snapshot['source'], $snapshot['items'], $snapshot['count'], $snapshot['updated_at'] ) ||
			$source !== (string) $snapshot['source'] ||
			! is_array( $snapshot['items'] ) ||
			empty( $snapshot['items'] ) ||
			count( $snapshot['items'] ) !== (int) $snapshot['count'] ||
			'' === (string) $snapshot['updated_at']
		) {
			return false;
		}

		foreach ( $snapshot['items'] as $item ) {
			if (
				! is_array( $item ) ||
				! isset( $item['title'], $item['url'], $item['date_context'] ) ||
				'' === (string) $item['title'] ||
				'' === (string) $item['url']
			) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Project one attempt while preserving truthful legacy/unknown attribution.
	 *
	 * @param mixed $attempt Stored attempt.
	 * @return array<string,mixed>|null
	 */
	private function attempt_report( $attempt ) {
		if ( ! is_array( $attempt ) ) {
			return null;
		}

		$stored_trigger = isset( $attempt['trigger'] ) ? (string) $attempt['trigger'] : '';
		$trigger        = in_array( $stored_trigger, array( 'manual', 'cron' ), true ) ? $stored_trigger : 'unknown';
		$run_id         = isset( $attempt['run_id'] ) && '' !== (string) $attempt['run_id'] ? (string) $attempt['run_id'] : null;
		$explicit       = 'unknown' !== $trigger && null !== $run_id;
		$action         = isset( $attempt['action'] ) ? (string) $attempt['action'] : '';

		return array(
			'schema_version'            => isset( $attempt['schema_version'] ) ? (int) $attempt['schema_version'] : null,
			'trigger'                   => $trigger,
			'run_id'                    => $run_id,
			'attribution'               => $explicit ? 'explicit' : 'legacy_or_unknown',
			'attempted_at'              => isset( $attempt['attempted_at'] ) ? (string) $attempt['attempted_at'] : null,
			'candidate_status'          => isset( $attempt['candidate_status'] ) ? (string) $attempt['candidate_status'] : 'unknown',
			'http_code'                 => isset( $attempt['http_code'] ) && is_numeric( $attempt['http_code'] ) ? (int) $attempt['http_code'] : null,
			'action'                    => $action,
			'reason'                    => isset( $attempt['reason'] ) ? (string) $attempt['reason'] : '',
			'preserved_last_known_good' => 'preserved_previous' === $action,
		);
	}

	/**
	 * Project one persisted run summary through an explicit whitelist.
	 *
	 * @param mixed  $summary          Stored summary.
	 * @param string $expected_trigger Expected explicit origin.
	 * @return array<string,mixed>|null
	 */
	private function run_summary_report( $summary, $expected_trigger ) {
		if (
			! is_array( $summary ) ||
			! isset( $summary['trigger'], $summary['run_id'] ) ||
			$expected_trigger !== (string) $summary['trigger'] ||
			'' === (string) $summary['run_id']
		) {
			return null;
		}

		$lists = isset( $summary['lists'] ) && is_array( $summary['lists'] ) ? $summary['lists'] : array();

		return array(
			'schema_version' => isset( $summary['schema_version'] ) ? (int) $summary['schema_version'] : null,
			'trigger'        => $expected_trigger,
			'run_id'         => (string) $summary['run_id'],
			'started_at'     => isset( $summary['started_at'] ) ? (string) $summary['started_at'] : null,
			'completed_at'   => isset( $summary['completed_at'] ) ? (string) $summary['completed_at'] : null,
			'overall_status' => isset( $summary['overall_status'] ) ? (string) $summary['overall_status'] : 'unknown',
			'lists'          => array(
				'latest'         => $this->run_list_summary_report( isset( $lists['latest'] ) ? $lists['latest'] : null ),
				'weekly_popular' => $this->run_list_summary_report( isset( $lists['weekly_popular'] ) ? $lists['weekly_popular'] : null ),
			),
		);
	}

	/**
	 * Project one list outcome from a persisted run summary.
	 *
	 * @param mixed $outcome Stored list outcome.
	 * @return array<string,mixed>|null
	 */
	private function run_list_summary_report( $outcome ) {
		if ( ! is_array( $outcome ) ) {
			return null;
		}

		$action = isset( $outcome['action'] ) ? (string) $outcome['action'] : '';

		return array(
			'candidate_status'          => isset( $outcome['candidate_status'] ) ? (string) $outcome['candidate_status'] : 'unknown',
			'candidate_count'           => isset( $outcome['candidate_count'] ) ? (int) $outcome['candidate_count'] : 0,
			'http_code'                 => isset( $outcome['http_code'] ) && is_numeric( $outcome['http_code'] ) ? (int) $outcome['http_code'] : null,
			'reason'                    => isset( $outcome['reason'] ) ? (string) $outcome['reason'] : '',
			'action'                    => $action,
			'preserved_last_known_good' => 'preserved_previous' === $action,
			'local_available'           => ! empty( $outcome['local_available'] ),
			'local_count'               => isset( $outcome['local_count'] ) ? (int) $outcome['local_count'] : 0,
			'updated_at'                => isset( $outcome['updated_at'] ) && '' !== (string) $outcome['updated_at'] ? (string) $outcome['updated_at'] : null,
			'attempt_recorded'          => ! empty( $outcome['attempt_recorded'] ),
			'attempt_reason'            => isset( $outcome['attempt_reason'] ) ? (string) $outcome['attempt_reason'] : '',
		);
	}

	/**
	 * Detect explicitly-attributed list attempts without a matching persisted run summary.
	 *
	 * Each per-list attempt slot is persisted independently, so correlation must be evaluated
	 * independently as well. Legacy/unattributed attempts remain outside this check.
	 *
	 * @param array<string,mixed>|null $latest_attempt Latest list attempt projection.
	 * @param array<string,mixed>|null $weekly_attempt Weekly list attempt projection.
	 * @param string                   $trigger        Explicit origin being checked.
	 * @param array<string,mixed>|null $summary        Persisted run summary projection.
	 * @return bool
	 */
	private function attempts_without_matching_summary( $latest_attempt, $weekly_attempt, $trigger, $summary ) {
		$summary_run_id = is_array( $summary ) && ! empty( $summary['run_id'] ) ? (string) $summary['run_id'] : null;

		foreach ( array( $latest_attempt, $weekly_attempt ) as $attempt ) {
			if (
				! is_array( $attempt ) ||
				( isset( $attempt['trigger'] ) ? $attempt['trigger'] : '' ) !== $trigger
			) {
				continue;
			}

			$attempt_run_id = ! empty( $attempt['run_id'] ) ? (string) $attempt['run_id'] : null;
			if ( null === $attempt_run_id || $attempt_run_id !== $summary_run_id ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Derive a factual state without treating schedule registration as execution proof.
	 *
	 * @param bool $scheduled     Whether the owned event is registered.
	 * @param bool $cron_observed Whether a persisted Cron-origin run summary exists.
	 * @return string
	 */
	private function diagnostic_state( $scheduled, $cron_observed ) {
		if ( ! $scheduled ) {
			return 'SCHEDULE_MISSING';
		}

		if ( ! $cron_observed ) {
			return 'SCHEDULED_NOT_YET_OBSERVED';
		}

		return 'CRON_EXECUTION_OBSERVED';
	}
}
