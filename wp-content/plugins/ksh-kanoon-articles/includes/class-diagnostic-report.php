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

	const SCHEMA = 'ksh-kanoon-articles-diagnostic-v2';

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
	 * Current acquisition-contract qualification state when available.
	 *
	 * @var Acquisition_Qualification|null
	 */
	private $qualification;

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
	 * @param Snapshot_Store                 $store         Local plugin state.
	 * @param Scheduler                      $scheduler     Native schedule owner.
	 * @param callable|null                  $clock         UTC timestamp producer for deterministic tests.
	 * @param callable|null                  $runtime_facts Safe runtime-fact producer for deterministic tests.
	 * @param Acquisition_Qualification|null $qualification Current contract qualification state.
	 */
	public function __construct( Snapshot_Store $store, Scheduler $scheduler, $clock = null, $runtime_facts = null, Acquisition_Qualification $qualification = null ) {
		$this->store         = $store;
		$this->scheduler     = $scheduler;
		$this->clock         = $clock ? $clock : array( __CLASS__, 'utc_now' );
		$this->runtime_facts = $runtime_facts ? $runtime_facts : array( __CLASS__, 'read_runtime_facts' );
		$this->qualification = $qualification;

		if ( null === $this->qualification && function_exists( 'get_option' ) ) {
			$this->qualification = new Acquisition_Qualification();
		}
	}

	/**
	 * Build the complete report without remote acquisition or state mutation.
	 *
	 * @return array<string,mixed>
	 */
	public function build() {
		$clock                      = $this->clock;
		$runtime_facts              = $this->runtime_facts;
		$generated_at               = (string) $clock();
		$current_contract_id        = Source_Config::ACQUISITION_CONTRACT_ID;
		$qualification              = $this->qualification_report( $current_contract_id );
		$next_run                   = $this->scheduler->next_run();
		$scheduled                  = false !== $next_run;
		$last_manual                = $this->run_summary_report( $this->store->get_run_summary( 'manual' ), 'manual', $current_contract_id );
		$last_cron                  = $this->run_summary_report( $this->store->get_run_summary( 'cron' ), 'cron', $current_contract_id );
		$manual_observed            = is_array( $last_manual ) && ! empty( $last_manual['belongs_to_current_contract'] );
		$cron_observed              = is_array( $last_cron ) && ! empty( $last_cron['belongs_to_current_contract'] );
		$historical_cron_evidence   = null !== $last_cron && ! $cron_observed;
		$latest                     = $this->snapshot_report( 'latest' );
		$weekly                     = $this->snapshot_report( 'weekly_popular' );
		$latest_attempt             = $this->attempt_report( $this->store->get_attempt( 'latest' ), $current_contract_id );
		$weekly_attempt             = $this->attempt_report( $this->store->get_attempt( 'weekly_popular' ), $current_contract_id );
		$manual_summary_gap         = $this->attempts_without_matching_summary( $latest_attempt, $weekly_attempt, 'manual', $last_manual );
		$cron_summary_gap           = $this->attempts_without_matching_summary( $latest_attempt, $weekly_attempt, 'cron', $last_cron );
		$current_manual_summary_gap = $this->current_contract_attempts_without_matching_summary( $latest_attempt, $weekly_attempt, 'manual', $last_manual );
		$current_cron_summary_gap   = $this->current_contract_attempts_without_matching_summary( $latest_attempt, $weekly_attempt, 'cron', $last_cron );
		$current_observability_gap  = $current_manual_summary_gap || $current_cron_summary_gap;

		return array(
			'report'      => array(
				'schema'           => self::SCHEMA,
				'generated_at_utc' => $generated_at,
			),
			'plugin'      => array(
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
			'acquisition' => $qualification,
			'runtime'     => (array) $runtime_facts(),
			'scheduler'   => array(
				'hook'                    => Scheduler::HOOK,
				'event_registered'        => $scheduled,
				'recurrence'              => $scheduled ? $this->scheduler->recurrence() : null,
				'next_run_unix'           => $scheduled ? (int) $next_run : null,
				'next_run_utc'            => $scheduled ? gmdate( 'c', (int) $next_run ) : null,
				'cron_execution_observed' => $cron_observed,
				'historical_cron_execution_evidence_available' => $historical_cron_evidence,
				'last_cron_run'           => $last_cron,
			),
			'refresh'     => array(
				'manual_execution_observed' => $manual_observed,
				'cron_execution_observed'   => $cron_observed,
				'last_manual_run'           => $last_manual,
				'last_cron_run'             => $last_cron,
			),
			'lists'       => array(
				'latest'         => array(
					'current_snapshot' => $latest,
					'latest_attempt'   => $latest_attempt,
				),
				'weekly_popular' => array(
					'current_snapshot' => $weekly,
					'latest_attempt'   => $weekly_attempt,
				),
			),
			'assessment'  => array(
				'local_latest_available'                   => ! empty( $latest['available'] ),
				'local_latest_valid'                       => ! empty( $latest['valid'] ),
				'local_weekly_available'                   => ! empty( $weekly['available'] ),
				'local_weekly_valid'                       => ! empty( $weekly['valid'] ),
				'scheduler_registered'                     => $scheduled,
				'manual_execution_observed'                => $manual_observed,
				'cron_execution_observed'                  => $cron_observed,
				'last_cron_overall_status'                 => $cron_observed ? $last_cron['overall_status'] : null,
				'manual_attempts_without_matching_summary' => $manual_summary_gap,
				'cron_attempts_without_matching_summary'   => $cron_summary_gap,
				'observability_incomplete'                 => $manual_summary_gap || $cron_summary_gap,
				'current_contract_manual_attempts_without_matching_summary' => $current_manual_summary_gap,
				'current_contract_cron_attempts_without_matching_summary' => $current_cron_summary_gap,
				'current_contract_observability_incomplete' => $current_observability_gap,
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
	 * Build bounded qualification/provenance context without mutation.
	 *
	 * @param string $current_contract_id Current acquisition contract identity.
	 * @return array<string,mixed>
	 */
	private function qualification_report( $current_contract_id ) {
		$state     = null;
		$qualified = null;

		if ( $this->qualification instanceof Acquisition_Qualification ) {
			$state     = $this->qualification->state();
			$qualified = $this->qualification->is_qualified();
		}

		$stored_contract_id = is_array( $state ) && ! empty( $state['contract_id'] ) ? (string) $state['contract_id'] : null;

		return array(
			'current_contract_id'                 => $current_contract_id,
			'current_contract_qualified'          => $qualified,
			'qualification_state_available'       => is_array( $state ),
			'qualification_state_status'          => is_array( $state ) && isset( $state['status'] ) ? (string) $state['status'] : null,
			'qualification_state_contract_id'     => $stored_contract_id,
			'qualification_state_qualified_at'    => is_array( $state ) && ! empty( $state['qualified_at'] ) ? (string) $state['qualified_at'] : null,
			'qualification_state_matches_current' => null !== $stored_contract_id && $current_contract_id === $stored_contract_id,
		);
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
	 * Project one attempt while preserving truthful legacy/stale provenance.
	 *
	 * @param mixed  $attempt             Stored attempt.
	 * @param string $current_contract_id Current acquisition contract identity.
	 * @return array<string,mixed>|null
	 */
	private function attempt_report( $attempt, $current_contract_id ) {
		if ( ! is_array( $attempt ) ) {
			return null;
		}

		$stored_trigger   = isset( $attempt['trigger'] ) ? (string) $attempt['trigger'] : '';
		$trigger          = in_array( $stored_trigger, array( 'manual', 'cron' ), true ) ? $stored_trigger : 'unknown';
		$run_id           = isset( $attempt['run_id'] ) && '' !== (string) $attempt['run_id'] ? (string) $attempt['run_id'] : null;
		$explicit         = 'unknown' !== $trigger && null !== $run_id;
		$action           = isset( $attempt['action'] ) ? (string) $attempt['action'] : '';
		$contract_id      = isset( $attempt['acquisition_contract_id'] ) && '' !== (string) $attempt['acquisition_contract_id'] ? (string) $attempt['acquisition_contract_id'] : null;
		$provenance       = $this->contract_provenance( $contract_id, $current_contract_id );
		$current_contract = 'current_contract' === $provenance;

		return array(
			'schema_version'              => isset( $attempt['schema_version'] ) ? (int) $attempt['schema_version'] : null,
			'trigger'                     => $trigger,
			'run_id'                      => $run_id,
			'attribution'                 => $explicit ? 'explicit' : 'legacy_or_unknown',
			'acquisition_contract_id'     => $contract_id,
			'contract_provenance'         => $provenance,
			'belongs_to_current_contract' => $current_contract,
			'attempted_at'                => isset( $attempt['attempted_at'] ) ? (string) $attempt['attempted_at'] : null,
			'candidate_status'            => isset( $attempt['candidate_status'] ) ? (string) $attempt['candidate_status'] : 'unknown',
			'http_code'                   => isset( $attempt['http_code'] ) && is_numeric( $attempt['http_code'] ) ? (int) $attempt['http_code'] : null,
			'action'                      => $action,
			'reason'                      => isset( $attempt['reason'] ) ? (string) $attempt['reason'] : '',
			'preserved_last_known_good'   => 'preserved_previous' === $action,
		);
	}

	/**
	 * Project one persisted run summary through an explicit whitelist.
	 *
	 * @param mixed  $summary             Stored summary.
	 * @param string $expected_trigger    Expected explicit origin.
	 * @param string $current_contract_id Current acquisition contract identity.
	 * @return array<string,mixed>|null
	 */
	private function run_summary_report( $summary, $expected_trigger, $current_contract_id ) {
		if (
			! is_array( $summary ) ||
			! isset( $summary['trigger'], $summary['run_id'] ) ||
			$expected_trigger !== (string) $summary['trigger'] ||
			'' === (string) $summary['run_id']
		) {
			return null;
		}

		$lists            = isset( $summary['lists'] ) && is_array( $summary['lists'] ) ? $summary['lists'] : array();
		$contract_id      = isset( $summary['acquisition_contract_id'] ) && '' !== (string) $summary['acquisition_contract_id'] ? (string) $summary['acquisition_contract_id'] : null;
		$provenance       = $this->contract_provenance( $contract_id, $current_contract_id );
		$current_contract = 'current_contract' === $provenance;

		return array(
			'schema_version'              => isset( $summary['schema_version'] ) ? (int) $summary['schema_version'] : null,
			'trigger'                     => $expected_trigger,
			'run_id'                      => (string) $summary['run_id'],
			'acquisition_contract_id'     => $contract_id,
			'contract_provenance'         => $provenance,
			'belongs_to_current_contract' => $current_contract,
			'started_at'                  => isset( $summary['started_at'] ) ? (string) $summary['started_at'] : null,
			'completed_at'                => isset( $summary['completed_at'] ) ? (string) $summary['completed_at'] : null,
			'overall_status'              => isset( $summary['overall_status'] ) ? (string) $summary['overall_status'] : 'unknown',
			'lists'                       => array(
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
	 * This legacy-compatible correlation view intentionally remains contract-agnostic
	 * so historical support evidence remains readable. Current-contract claims use the
	 * separate contract-aware correlation check below.
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
	 * Detect current-contract attempts without a matching current-contract summary.
	 *
	 * Legacy/stale attempts remain visible in the report but cannot participate in
	 * current-contract correlation proof.
	 *
	 * @param array<string,mixed>|null $latest_attempt Latest list attempt projection.
	 * @param array<string,mixed>|null $weekly_attempt Weekly list attempt projection.
	 * @param string                   $trigger        Explicit origin being checked.
	 * @param array<string,mixed>|null $summary        Persisted run summary projection.
	 * @return bool
	 */
	private function current_contract_attempts_without_matching_summary( $latest_attempt, $weekly_attempt, $trigger, $summary ) {
		$summary_run_id = is_array( $summary ) && ! empty( $summary['belongs_to_current_contract'] ) && ! empty( $summary['run_id'] )
			? (string) $summary['run_id']
			: null;

		foreach ( array( $latest_attempt, $weekly_attempt ) as $attempt ) {
			if (
				! is_array( $attempt ) ||
				empty( $attempt['belongs_to_current_contract'] ) ||
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
	 * Classify persisted evidence against the exact current acquisition contract.
	 *
	 * @param string|null $stored_contract_id  Stored provenance identity.
	 * @param string      $current_contract_id Current acquisition contract identity.
	 * @return string
	 */
	private function contract_provenance( $stored_contract_id, $current_contract_id ) {
		if ( null === $stored_contract_id || '' === (string) $stored_contract_id ) {
			return 'legacy_unknown_contract';
		}

		if ( $current_contract_id === (string) $stored_contract_id ) {
			return 'current_contract';
		}

		return 'stale_contract';
	}

	/**
	 * Derive a factual state without treating schedule registration as execution proof.
	 *
	 * @param bool $scheduled     Whether the owned event is registered.
	 * @param bool $cron_observed Whether a current-contract Cron-origin summary exists.
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
