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

	/**
	 * Build scheduler with injectable WordPress primitives for deterministic tests.
	 *
	 * @param callable|null $next_scheduled       Next-event lookup.
	 * @param callable|null $schedule_event       Recurring-event scheduler.
	 * @param callable|null $clear_scheduled_hook Event remover.
	 * @param callable|null $now                  Current Unix timestamp producer.
	 */
	public function __construct( $next_scheduled = null, $schedule_event = null, $clear_scheduled_hook = null, $now = null ) {
		$this->next_scheduled       = $next_scheduled ? $next_scheduled : 'wp_next_scheduled';
		$this->schedule_event       = $schedule_event ? $schedule_event : 'wp_schedule_event';
		$this->clear_scheduled_hook = $clear_scheduled_hook ? $clear_scheduled_hook : 'wp_clear_scheduled_hook';
		$this->now                  = $now ? $now : 'time';
	}

	/**
	 * Wire the same canonical refresh method to cron and self-heal schedule on init.
	 *
	 * @param Refresh_Service $refresh Canonical refresh service.
	 * @return void
	 */
	public function register( Refresh_Service $refresh ) {
		add_action( self::HOOK, array( $refresh, 'run' ) );
		add_action( 'init', array( $this, 'ensure_scheduled' ) );
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
	 * Remove this plugin's recurring hook without deleting snapshots.
	 *
	 * @return void
	 */
	public function clear() {
		$clear = $this->clear_scheduled_hook;
		$clear( self::HOOK );
	}
}
