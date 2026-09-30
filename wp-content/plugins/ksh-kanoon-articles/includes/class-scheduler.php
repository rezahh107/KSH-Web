<?php
/**
 * WordPress-native approximately-daily scheduling.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Owns one recurring hook and a cheap upgrade-safe schedule existence check.
 */
final class Scheduler {

	const HOOK = 'ksh_kanoon_articles_daily_refresh';

	/** @var callable */
	private $next_scheduled;

	/** @var callable */
	private $schedule_event;

	/** @var callable */
	private $clear_scheduled_hook;

	/** @var callable */
	private $now;

	/** @var callable */
	private $get_schedule;

	/** @var Refresh_Service|null */
	private $refresh;

	/** @var Acquisition_Qualification */
	private $qualification;

	/**
	 * Build scheduler with injectable WordPress primitives for deterministic tests.
	 *
	 * @param callable|null                  $next_scheduled       Next-event lookup.
	 * @param callable|null                  $schedule_event       Recurring-event scheduler.
	 * @param callable|null                  $clear_scheduled_hook Event remover.
	 * @param callable|null                  $now                  Current Unix timestamp producer.
	 * @param callable|null                  $get_schedule         Recurrence lookup.
	 * @param Acquisition_Qualification|null $qualification        Current-contract admission state.
	 */
	public function __construct( $next_scheduled = null, $schedule_event = null, $clear_scheduled_hook = null, $now = null, $get_schedule = null, Acquisition_Qualification $qualification = null ) {
		$this->next_scheduled       = $next_scheduled ? $next_scheduled : 'wp_next_scheduled';
		$this->schedule_event       = $schedule_event ? $schedule_event : 'wp_schedule_event';
		$this->clear_scheduled_hook = $clear_scheduled_hook ? $clear_scheduled_hook : 'wp_clear_scheduled_hook';
		$this->now                  = $now ? $now : 'time';
		$this->get_schedule         = $get_schedule ? $get_schedule : 'wp_get_schedule';
		$this->refresh              = null;
		$this->qualification        = $qualification ? $qualification : new Acquisition_Qualification();
	}

	/**
	 * Wire the same canonical refresh semantics to Cron and self-heal schedule on init.
	 *
	 * @param Refresh_Service $refresh Canonical refresh service.
	 * @return void
	 */
	public function register( Refresh_Service $refresh ) {
		$this->refresh = $refresh;
		add_action( self::HOOK, array( $this, 'run_cron' ) );
		add_action( 'init', array( $this, 'ensure_scheduled' ) );
	}

	/**
	 * Execute Cron only when the exact current acquisition contract is qualified.
	 *
	 * This guard closes the stale-event upgrade case independently from the same
	 * fail-closed guard inside Refresh_Service.
	 *
	 * @return array<string,mixed>|null
	 */
	public function run_cron() {
		if ( ! $this->qualification->is_qualified() ) {
			return array(
				'overall_status' => 'blocked',
				'trigger'        => 'cron',
				'reason'         => 'acquisition_contract_unqualified',
				'contract_id'    => $this->qualification->current_contract_id(),
			);
		}

		if ( ! $this->refresh instanceof Refresh_Service ) {
			return null;
		}

		return $this->refresh->run( 'cron' );
	}

	/**
	 * Ensure one named daily event exists only for a qualified current contract.
	 *
	 * A stale event left by a previous plugin/acquisition contract is cleared while
	 * unqualified. This method performs no remote acquisition.
	 *
	 * @return bool True when a qualified event already exists or scheduling succeeds.
	 */
	public function ensure_scheduled() {
		$next = $this->next_scheduled;

		if ( ! $this->qualification->is_qualified() ) {
			if ( false !== $next( self::HOOK ) ) {
				$clear = $this->clear_scheduled_hook;
				$clear( self::HOOK );
			}

			return false;
		}

		if ( false !== $next( self::HOOK ) ) {
			return true;
		}

		$now      = $this->now;
		$schedule = $this->schedule_event;

		return false !== $schedule( (int) $now() + 3600, 'daily', self::HOOK );
	}

	/**
	 * Return the next registered event timestamp, when present.
	 *
	 * @return int|false
	 */
	public function next_run() {
		$next = $this->next_scheduled;

		return $next( self::HOOK );
	}

	/**
	 * Return the registered recurrence name without changing schedule state.
	 *
	 * @return string|null
	 */
	public function recurrence() {
		$get_schedule = $this->get_schedule;
		$schedule     = $get_schedule( self::HOOK );

		return is_string( $schedule ) && '' !== $schedule ? $schedule : null;
	}

	/**
	 * Remove this plugin's recurring hook without deleting snapshots.
	 *
	 * @return void
	 */
	public function clear() {
		$clear = $this->clear_scheduled_hook;
		$clear( self::HOOK );
	}
}
