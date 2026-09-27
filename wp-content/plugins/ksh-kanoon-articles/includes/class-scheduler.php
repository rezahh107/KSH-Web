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

	/**
	 * Next-event lookup.
	 *
	 * @var callable
	 */
	private $next_scheduled;

	/**
	 * Recurring-event scheduler.
	 *
	 * @var callable
	 */
	private $schedule_event;

	/**
	 * Event remover.
	 *
	 * @var callable
	 */
	private $clear_scheduled_hook;

	/**
	 * Current Unix timestamp producer.
	 *
	 * @var callable
	 */
	private $now;

	/**
	 * Recurrence lookup.
	 *
	 * @var callable
	 */
	private $get_schedule;

	/**
	 * Canonical refresh service registered for the owned Cron callback.
	 *
	 * @var Refresh_Service|null
	 */
	private $refresh;

	/**
	 * Build scheduler with injectable WordPress primitives for deterministic tests.
	 *
	 * @param callable|null $next_scheduled       Next-event lookup.
	 * @param callable|null $schedule_event       Recurring-event scheduler.
	 * @param callable|null $clear_scheduled_hook Event remover.
	 * @param callable|null $now                  Current Unix timestamp producer.
	 * @param callable|null $get_schedule         Recurrence lookup.
	 */
	public function __construct( $next_scheduled = null, $schedule_event = null, $clear_scheduled_hook = null, $now = null, $get_schedule = null ) {
		$this->next_scheduled       = $next_scheduled ? $next_scheduled : 'wp_next_scheduled';
		$this->schedule_event       = $schedule_event ? $schedule_event : 'wp_schedule_event';
		$this->clear_scheduled_hook = $clear_scheduled_hook ? $clear_scheduled_hook : 'wp_clear_scheduled_hook';
		$this->now                  = $now ? $now : 'time';
		$this->get_schedule         = $get_schedule ? $get_schedule : 'wp_get_schedule';
		$this->refresh              = null;
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
	 * Execute the canonical refresh path with explicit Cron attribution.
	 *
	 * This method is the persistent-evidence boundary for actual scheduled
	 * callback execution. Merely having an event registered does not call it.
	 *
	 * @return array<string,mixed>|null
	 */
	public function run_cron() {
		if ( ! $this->refresh instanceof Refresh_Service ) {
			return null;
		}

		return $this->refresh->run( 'cron' );
	}

	/**
	 * Ensure exactly one named daily event exists. Never performs acquisition.
	 *
	 * @return bool True when an event already exists or scheduling succeeds.
	 */
	public function ensure_scheduled() {
		$next = $this->next_scheduled;
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
