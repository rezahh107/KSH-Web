<?php
/**
 * Acquisition qualification admission and still-active operational regressions.
 *
 * This suite intentionally runs without the prequalified bootstrap used by the
 * pre-existing test runner so fresh/stale qualification behavior is exercised.
 */

declare(strict_types=1);

use KSH\KanoonArticles\Acquisition_Qualification;
use KSH\KanoonArticles\Admin_Page;
use KSH\KanoonArticles\Article_Parser;
use KSH\KanoonArticles\Diagnostic_Report;
use KSH\KanoonArticles\Plugin;
use KSH\KanoonArticles\Preview_Service;
use KSH\KanoonArticles\Refresh_Service;
use KSH\KanoonArticles\Scheduler;
use KSH\KanoonArticles\Snapshot_Store;
use KSH\KanoonArticles\Source_Config;

$GLOBALS['ksh_q_actions']          = array();
$GLOBALS['ksh_q_current_user_can'] = true;
$GLOBALS['ksh_q_nonce_action']     = null;
$GLOBALS['wp_version']             = '7.1.2';

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback ) {
		$GLOBALS['ksh_q_actions'][ $hook ][] = $callback;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text ) { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability = '' ) { return ! empty( $GLOBALS['ksh_q_current_user_can'] ); }
}
if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message ) { throw new RuntimeException( (string) $message ); }
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) { return (string) $value; }
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $value ) { return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) ); }
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) { return $value; }
}
if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( $action = -1 ) {
		$GLOBALS['ksh_q_nonce_action'] = $action;
		return 1;
	}
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field() { echo '<input type="hidden" name="_wpnonce" value="test">'; }
}
if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text ) { echo '<button type="submit">' . esc_html( $text ) . '</button>'; }
}
if ( ! function_exists( 'add_management_page' ) ) {
	function add_management_page() { return 'tools_page_ksh'; }
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) { return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' ); }
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, $flags = 0 ) { return json_encode( $value, $flags ); }
}
if ( ! function_exists( 'wp_timezone_string' ) ) {
	function wp_timezone_string() { return 'Asia/Tehran'; }
}

require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-source-config.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-article-parser.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-preview-service.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-acquisition-qualification.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-snapshot-store.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-refresh-service.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-scheduler.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-diagnostic-report.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-admin-page.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php';

if ( ! class_exists( 'DOMDocument' ) ) {
	fwrite( STDERR, "QUALIFICATION_TEST_ENVIRONMENT_UNAVAILABLE: ext-dom is required.\n" );
	exit( 2 );
}

$assertions = 0;

function q_assert_same( $expected, $actual, string $message ): void {
	global $assertions;
	++$assertions;
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function q_assert_true( $actual, string $message ): void {
	q_assert_same( true, (bool) $actual, $message );
}

function q_homepage( int $latest_count = 2, int $weekly_count = 2, bool $weekly_collision = false, bool $latest_zero = false ): string {
	$weekly_target = $weekly_collision ? 'shared' : 'weekly';
	$monthly_target = $weekly_collision ? 'shared' : 'monthly';
	$html = '<html><body><a href="#latest">تازه ها</a><a href="#' . $weekly_target . '">پربازدید هفته</a><a href="#' . $monthly_target . '">پربازدید ماه</a>';
	$html .= '<div id="latest">';
	if ( $latest_zero ) {
		$html .= '<a href="/Article/Days">آرشیو</a><a href="https://example.com/Article/1">غریبه</a>';
	} else {
		for ( $i = 1; $i <= $latest_count; ++$i ) {
			$html .= '<a href="/Article/' . ( 910000 + $i ) . '">تازه ' . $i . '</a>';
		}
	}
	$html .= '</div>';
	$html .= '<div id="' . $weekly_target . '">';
	for ( $i = 1; $i <= $weekly_count; ++$i ) {
		$html .= '<a href="/Article/' . ( 920000 + $i ) . '">هفته ' . $i . '</a>';
	}
	$html .= '</div>';
	if ( ! $weekly_collision ) {
		$html .= '<div id="monthly"><a href="/Article/930001">ماه</a></div>';
	}
	$html .= '</body></html>';
	return $html;
}

function q_snapshot( string $source, int $id ): array {
	return array(
		'schema_version' => Snapshot_Store::SCHEMA_VERSION,
		'source'         => $source,
		'source_url'     => Source_Config::HOMEPAGE_URL,
		'items'          => array(
			array(
				'title'        => 'محلی ' . $id,
				'url'          => 'https://www.kanoon.ir/Article/' . $id,
				'date_context' => '',
			),
		),
		'count'          => 1,
		'updated_at'     => '2026-09-29T12:00:00+00:00',
		'date_context'   => '',
	);
}

$parser        = new Article_Parser();
$fetch_mode    = 'valid';
$fetch_in_mode = 0;
$fetch_count   = 0;
$fetch         = static function ( string $url ) use ( &$fetch_mode, &$fetch_in_mode, &$fetch_count ): array {
	++$fetch_count;
	++$fetch_in_mode;

	if ( 'latest_fail' === $fetch_mode && 1 === $fetch_in_mode ) {
		return array( 'ok' => false, 'http_code' => 503, 'body' => '', 'reason' => 'http_error' );
	}
	if ( 'ambiguous' === $fetch_mode ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => q_homepage( 2, 2, true, false ), 'reason' => '' );
	}
	if ( 'zero' === $fetch_mode && 1 === $fetch_in_mode ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => q_homepage( 0, 2, false, true ), 'reason' => '' );
	}

	return array( 'ok' => true, 'http_code' => 200, 'body' => q_homepage( 2, 2 ), 'reason' => '' );
};
$preview = new Preview_Service( $fetch, $parser );

$qualification_values = array();
$q_get = static function ( $name, $default = false ) use ( &$qualification_values ) {
	return array_key_exists( $name, $qualification_values ) ? $qualification_values[ $name ] : $default;
};
$q_add = static function ( $name, $value ) use ( &$qualification_values ) {
	if ( array_key_exists( $name, $qualification_values ) ) {
		return false;
	}
	$qualification_values[ $name ] = $value;
	return true;
};
$q_update = static function ( $name, $value ) use ( &$qualification_values ) {
	$qualification_values[ $name ] = $value;
	return true;
};
$qualification = new Acquisition_Qualification(
	$preview,
	$q_get,
	$q_add,
	$q_update,
	static function (): string { return '2026-09-30T13:00:00+00:00'; }
);

q_assert_same( 'kanoon-homepage-semantic-lists-v1', $qualification->current_contract_id(), 'acquisition contract identity is explicit and independent from plugin version' );
q_assert_same( false, $qualification->is_qualified(), 'fresh installation is unqualified' );

/* Seed real-looking LKG state before proving that blocked refresh cannot mutate it. */
$state = array(
	Snapshot_Store::OPTION_LATEST_SNAPSHOT => q_snapshot( 'latest', 800001 ),
	Snapshot_Store::OPTION_WEEKLY_SNAPSHOT => q_snapshot( 'weekly_popular', 800002 ),
);
$writes = 0;
$s_get = static function ( $name, $default = false ) use ( &$state ) {
	return array_key_exists( $name, $state ) ? $state[ $name ] : $default;
};
$s_add = static function ( $name, $value ) use ( &$state, &$writes ) {
	++$writes;
	if ( array_key_exists( $name, $state ) ) {
		return false;
	}
	$state[ $name ] = $value;
	return true;
};
$s_update = static function ( $name, $value ) use ( &$state, &$writes ) {
	++$writes;
	$state[ $name ] = $value;
	return true;
};
$store = new Snapshot_Store( $s_get, $s_add, $s_update );
$refresh = new Refresh_Service(
	$preview,
	$store,
	static function (): string { return '2026-09-30T13:01:00+00:00'; },
	static function (): string { return 'qualification-regression-run'; },
	$qualification
);

$before_state = $state;
$before_fetch = $fetch_count;
$blocked_manual = $refresh->run( 'manual' );
q_assert_same( 'blocked', $blocked_manual['overall_status'], 'fresh unqualified Manual refresh is explicitly blocked' );
q_assert_same( 'acquisition_contract_unqualified', $blocked_manual['reason'], 'blocked Manual refresh reports bounded qualification reason' );
q_assert_same( $before_fetch, $fetch_count, 'blocked Manual refresh performs no acquisition' );
q_assert_same( $before_state, $state, 'blocked Manual refresh performs zero snapshot/attempt/run-summary mutation' );
q_assert_same( 0, $writes, 'blocked Manual refresh never reaches option writes' );
q_assert_same( true, $blocked_manual['latest']['local_available'], 'blocked Manual refresh truthfully preserves existing Latest LKG' );
q_assert_same( true, $blocked_manual['weekly_popular']['local_available'], 'blocked Manual refresh truthfully preserves existing Weekly LKG' );

/* Existing scheduled event from the previous contract cannot bypass admission. */
$scheduled_at = 1730000000;
$schedule_writes = 0;
$clears = 0;
$scheduler = new Scheduler(
	static function () use ( &$scheduled_at ) { return $scheduled_at; },
	static function ( $timestamp, $recurrence, $hook ) use ( &$scheduled_at, &$schedule_writes ) {
		++$schedule_writes;
		$scheduled_at = $timestamp;
		return true;
	},
	static function ( $hook ) use ( &$scheduled_at, &$clears ) {
		++$clears;
		$scheduled_at = false;
		return 1;
	},
	static function () { return 1000; },
	static function () use ( &$scheduled_at ) { return false !== $scheduled_at ? 'daily' : false; },
	$qualification
);
$scheduler->register( $refresh );
$before_fetch = $fetch_count;
$before_state = $state;
$blocked_cron = $scheduler->run_cron();
q_assert_same( 'blocked', $blocked_cron['overall_status'], 'pre-existing Cron callback is blocked before current qualification' );
q_assert_same( $before_fetch, $fetch_count, 'blocked stale Cron event performs no acquisition' );
q_assert_same( $before_state, $state, 'blocked stale Cron event performs no local mutations' );
q_assert_same( false, $scheduler->ensure_scheduled(), 'unqualified current contract is not effectively schedulable' );
q_assert_same( false, $scheduled_at, 'existing previous-version scheduled event is cleared while current contract is unqualified' );
q_assert_same( 1, $clears, 'stale scheduled event is cleared exactly once' );
q_assert_same( 0, $schedule_writes, 'unqualified ensure path never creates a new schedule' );

/* Ordinary Preview remains read-only while unqualified. */
$fetch_mode    = 'valid';
$fetch_in_mode = 0;
$before_state  = $state;
$before_q      = $qualification_values;
$preview_result = $preview->run();
q_assert_same( 'success', $preview_result['overall_status'], 'ordinary Preview remains usable before qualification' );
q_assert_same( $before_state, $state, 'ordinary Preview does not mutate article local state' );
q_assert_same( $before_q, $qualification_values, 'ordinary Preview does not silently qualify the contract' );
q_assert_same( false, $qualification->is_qualified(), 'successful ordinary Preview alone does not admit writes' );

/* Failed/ambiguous/zero-item owner qualification cannot enable writes. */
$fetch_mode    = 'ambiguous';
$fetch_in_mode = 0;
$ambiguous_qualification = $qualification->qualify_current();
q_assert_same( 'rejected', $ambiguous_qualification['status'], 'ambiguous qualification is rejected' );
q_assert_same( false, $qualification->is_qualified(), 'ambiguous qualification cannot enable current contract' );
q_assert_same( array(), $qualification_values, 'ambiguous qualification persists no false success state' );

$fetch_mode    = 'zero';
$fetch_in_mode = 0;
$zero_qualification = $qualification->qualify_current();
q_assert_same( 'rejected', $zero_qualification['status'], 'zero-item qualification is rejected' );
q_assert_same( false, $qualification->is_qualified(), 'zero-item qualification cannot enable current contract' );
q_assert_same( array(), $qualification_values, 'zero-item qualification persists no success state' );

/* Exact current-contract qualification enables Manual/Cron and scheduling. */
$fetch_mode    = 'valid';
$fetch_in_mode = 0;
$qualified = $qualification->qualify_current();
q_assert_same( 'qualified', $qualified['status'], 'successful exact Preview checks qualify the current contract' );
q_assert_same( true, $qualified['state_recorded'], 'successful qualification persists bounded state' );
q_assert_same( true, $qualification->is_qualified(), 'persisted exact-contract state admits writes' );
q_assert_same( Source_Config::ACQUISITION_CONTRACT_ID, $qualification_values[ Acquisition_Qualification::OPTION_STATE ]['contract_id'], 'qualification state is bound to exact acquisition contract identity' );
q_assert_same( 2, $qualification_values[ Acquisition_Qualification::OPTION_STATE ]['latest']['count'], 'qualification stores bounded Latest evidence' );
q_assert_same( 2, $qualification_values[ Acquisition_Qualification::OPTION_STATE ]['weekly_popular']['count'], 'qualification stores bounded Weekly evidence' );

q_assert_same( true, $scheduler->ensure_scheduled(), 'qualified contract becomes schedulable' );
q_assert_true( false !== $scheduled_at, 'qualified ensure path creates a schedule' );
q_assert_same( 1, $schedule_writes, 'qualified contract schedules exactly once after stale event was cleared' );

$fetch_mode    = 'valid';
$fetch_in_mode = 0;
$before_fetch  = $fetch_count;
$manual1       = $refresh->run( 'manual' );
q_assert_same( 'success', $manual1['overall_status'], 'qualified Manual refresh executes writable canonical path' );
q_assert_same( $before_fetch + 2, $fetch_count, 'qualified Manual refresh executes both independent acquisitions' );
q_assert_same( 'manual', $store->get_attempt( 'latest' )['trigger'], 'qualified Manual Latest attempt records explicit origin' );
q_assert_same( $manual1['run_id'], $store->get_run_summary( 'manual' )['run_id'], 'qualified Manual summary correlates to run id' );
$manual_summary_1 = $store->get_run_summary( 'manual' );

$fetch_in_mode = 0;
$cron1 = $scheduler->run_cron();
q_assert_same( 'success', $cron1['overall_status'], 'qualified Cron callback executes writable canonical path' );
q_assert_same( 'cron', $store->get_attempt( 'weekly_popular' )['trigger'], 'qualified Cron Weekly attempt records explicit origin' );
q_assert_same( $cron1['run_id'], $store->get_run_summary( 'cron' )['run_id'], 'qualified Cron summary correlates to run id' );
q_assert_same( $manual_summary_1, $store->get_run_summary( 'manual' ), 'Cron execution does not overwrite latest Manual summary' );
$cron_summary_1 = $store->get_run_summary( 'cron' );

$fetch_in_mode = 0;
$manual2 = $refresh->run( 'manual' );
q_assert_same( $cron_summary_1, $store->get_run_summary( 'cron' ), 'later Manual execution does not overwrite latest Cron summary' );
q_assert_same( $manual2['run_id'], $store->get_run_summary( 'manual' )['run_id'], 'latest Manual summary advances independently' );

/* Independent LKG behavior remains intact after admission. */
$fetch_mode    = 'latest_fail';
$fetch_in_mode = 0;
$latest_before_failure = $store->get_snapshot( 'latest' );
$weekly_before_failure = $store->get_snapshot( 'weekly_popular' );
$partial = $refresh->run( 'manual' );
q_assert_same( 'partial', $partial['overall_status'], 'qualified refresh preserves independent Latest/Weekly outcomes' );
q_assert_same( 'preserved_previous', $partial['latest']['action'], 'failed Latest preserves prior LKG' );
q_assert_same( $latest_before_failure, $store->get_snapshot( 'latest' ), 'failed Latest does not replace LKG' );
q_assert_same( 'updated', $partial['weekly_popular']['action'], 'healthy Weekly can advance independently' );
q_assert_true( $weekly_before_failure !== $store->get_snapshot( 'weekly_popular' ), 'healthy Weekly snapshot advances independently' );

/* Stale/historical qualification never authorizes the current contract. */
$qualification_values[ Acquisition_Qualification::OPTION_STATE ]['contract_id'] = 'article-days-v0.3.x';
q_assert_same( false, $qualification->is_qualified(), 'historical v0.3.x-style contract identity cannot authorize v0.4.0' );
$fetch_in_mode = 0;
$before_fetch = $fetch_count;
$stale_block = $refresh->run( 'cron' );
q_assert_same( 'blocked', $stale_block['overall_status'], 'changing stored contract identity immediately makes prior qualification stale' );
q_assert_same( $before_fetch, $fetch_count, 'stale qualification blocks Cron before acquisition' );

/* Restore current qualification for the still-active diagnostic regression cases. */
$qualification_values[ Acquisition_Qualification::OPTION_STATE ]['contract_id'] = Source_Config::ACQUISITION_CONTRACT_ID;
q_assert_same( true, $qualification->is_qualified(), 'current qualification restored for operational regressions' );

/* Schedule registration is not execution proof; missing schedule is distinct. */
$scheduled_only_store_values = array();
$scheduled_only_store = new Snapshot_Store(
	static function ( $name, $default = false ) use ( &$scheduled_only_store_values ) {
		return array_key_exists( $name, $scheduled_only_store_values ) ? $scheduled_only_store_values[ $name ] : $default;
	},
	static function () { throw new RuntimeException( 'diagnostic read must not write' ); },
	static function () { throw new RuntimeException( 'diagnostic read must not write' ); }
);
$scheduled_only_scheduler = new Scheduler(
	static function () { return 1730000000; },
	static function () { throw new RuntimeException( 'diagnostic read must not schedule' ); },
	static function () { throw new RuntimeException( 'diagnostic read must not clear schedule' ); },
	static function () { return 1000; },
	static function () { return 'daily'; },
	$qualification
);
$scheduled_report = ( new Diagnostic_Report(
	$scheduled_only_store,
	$scheduled_only_scheduler,
	static function (): string { return '2026-09-30T14:00:00+00:00'; },
	static function (): array { return array( 'wordpress_version' => '7.1.2', 'php_version' => PHP_VERSION ); }
) )->build();
q_assert_same( true, $scheduled_report['scheduler']['event_registered'], 'registered schedule is reported as registered' );
q_assert_same( false, $scheduled_report['scheduler']['cron_execution_observed'], 'registered schedule alone is not observed Cron execution' );
q_assert_same( 'SCHEDULED_NOT_YET_OBSERVED', $scheduled_report['assessment']['diagnostic_state'], 'scheduled-only diagnostic state remains explicit' );

$missing_scheduler = new Scheduler(
	static function () { return false; },
	static function () { throw new RuntimeException( 'diagnostic read must not schedule' ); },
	static function () { throw new RuntimeException( 'diagnostic read must not clear schedule' ); },
	static function () { return 1000; },
	static function () { return false; },
	$qualification
);
$missing_report = ( new Diagnostic_Report(
	$scheduled_only_store,
	$missing_scheduler,
	static function (): string { return '2026-09-30T14:01:00+00:00'; },
	static function (): array { return array( 'wordpress_version' => '7.1.2', 'php_version' => PHP_VERSION ); }
) )->build();
q_assert_same( false, $missing_report['scheduler']['event_registered'], 'missing schedule remains distinguishable from registered schedule' );
q_assert_same( false, $missing_report['scheduler']['cron_execution_observed'], 'missing schedule does not fabricate Cron execution' );
q_assert_same( 'SCHEDULE_MISSING', $missing_report['assessment']['diagnostic_state'], 'missing schedule has deterministic diagnostic state' );

/* Run-summary persistence failure cannot become Cron proof. */
$summary_fail_values = array();
$summary_fail_get = static function ( $name, $default = false ) use ( &$summary_fail_values ) {
	return array_key_exists( $name, $summary_fail_values ) ? $summary_fail_values[ $name ] : $default;
};
$summary_fail_add = static function ( $name, $value ) use ( &$summary_fail_values ) {
	if ( Snapshot_Store::OPTION_LAST_CRON_RUN === $name ) {
		return false;
	}
	$summary_fail_values[ $name ] = $value;
	return true;
};
$summary_fail_update = static function ( $name, $value ) use ( &$summary_fail_values ) {
	if ( Snapshot_Store::OPTION_LAST_CRON_RUN === $name ) {
		return false;
	}
	$summary_fail_values[ $name ] = $value;
	return true;
};
$summary_fail_store = new Snapshot_Store( $summary_fail_get, $summary_fail_add, $summary_fail_update );
$fetch_mode         = 'valid';
$fetch_in_mode      = 0;
$summary_fail_refresh = new Refresh_Service(
	$preview,
	$summary_fail_store,
	static function (): string { return '2026-09-30T14:02:00+00:00'; },
	static function (): string { return 'cron-summary-failure'; },
	$qualification
);
$summary_fail_result = $summary_fail_refresh->run( 'cron' );
q_assert_same( false, $summary_fail_result['run_summary_recorded'], 'Cron run-summary persistence failure is explicit' );
q_assert_same( 'run_summary_write_failed', $summary_fail_result['run_summary_reason'], 'run-summary failure has bounded reason' );
q_assert_same( null, $summary_fail_store->get_run_summary( 'cron' ), 'failed run-summary write leaves no false persisted Cron proof' );

$summary_fail_scheduler = new Scheduler(
	static function () { return 1730000000; },
	static function () { return true; },
	static function () { return 1; },
	static function () { return 1000; },
	static function () { return 'daily'; },
	$qualification
);
$summary_fail_report = ( new Diagnostic_Report(
	$summary_fail_store,
	$summary_fail_scheduler,
	static function (): string { return '2026-09-30T14:03:00+00:00'; },
	static function (): array { return array( 'wordpress_version' => '7.1.2', 'php_version' => PHP_VERSION ); }
) )->build();
q_assert_same( false, $summary_fail_report['scheduler']['cron_execution_observed'], 'failed run-summary persistence remains non-proof of Cron execution' );
q_assert_same( true, $summary_fail_report['assessment']['cron_attempts_without_matching_summary'], 'Cron attempts without matching run summary remain visible' );
q_assert_same( true, $summary_fail_report['assessment']['observability_incomplete'], 'run-summary persistence failure marks observability incomplete' );

/* Per-list run-id correlation fails closed for mismatched current state. */
$diagnostic_attempt = static function ( $source, $trigger, $run_id ) {
	return array(
		'schema_version'   => Snapshot_Store::ATTEMPT_SCHEMA_VERSION,
		'source'           => $source,
		'trigger'          => $trigger,
		'run_id'           => $run_id,
		'attempted_at'     => '2026-09-30T14:04:00+00:00',
		'candidate_status' => 'success',
		'http_code'        => 200,
		'reason'           => '',
		'action'           => 'updated',
	);
};
$diagnostic_summary = static function ( $trigger, $run_id ) {
	return array(
		'schema_version' => Snapshot_Store::RUN_SUMMARY_SCHEMA_VERSION,
		'trigger'        => $trigger,
		'run_id'         => $run_id,
		'started_at'     => '2026-09-30T14:03:00+00:00',
		'completed_at'   => '2026-09-30T14:04:00+00:00',
		'overall_status' => 'success',
		'lists'          => array(),
	);
};
$correlation_values = array(
	Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', 'cron-newer' ),
	Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'cron', 'cron-older' ),
	Snapshot_Store::OPTION_LAST_CRON_RUN   => $diagnostic_summary( 'cron', 'cron-older' ),
);
$correlation_store = new Snapshot_Store(
	static function ( $name, $default = false ) use ( &$correlation_values ) {
		return array_key_exists( $name, $correlation_values ) ? $correlation_values[ $name ] : $default;
	},
	static function () { throw new RuntimeException( 'correlation diagnostic must remain read-only' ); },
	static function () { throw new RuntimeException( 'correlation diagnostic must remain read-only' ); }
);
$correlation_report = ( new Diagnostic_Report(
	$correlation_store,
	$scheduled_only_scheduler,
	static function (): string { return '2026-09-30T14:05:00+00:00'; },
	static function (): array { return array( 'wordpress_version' => '7.1.2', 'php_version' => PHP_VERSION ); }
) )->build();
q_assert_same( true, $correlation_report['assessment']['cron_attempts_without_matching_summary'], 'mismatched per-list Cron run id fails correlation closed' );
q_assert_same( true, $correlation_report['assessment']['observability_incomplete'], 'mismatched run correlation marks observability incomplete' );

$correlation_values = array(
	Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', 'cron-current' ),
	Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'cron', 'cron-current' ),
	Snapshot_Store::OPTION_LAST_CRON_RUN   => $diagnostic_summary( 'cron', 'cron-current' ),
);
$matching_report = ( new Diagnostic_Report(
	$correlation_store,
	$scheduled_only_scheduler,
	static function (): string { return '2026-09-30T14:06:00+00:00'; },
	static function (): array { return array( 'wordpress_version' => '7.1.2', 'php_version' => PHP_VERSION ); }
) )->build();
q_assert_same( false, $matching_report['assessment']['cron_attempts_without_matching_summary'], 'matching per-list Cron attempts and summary restore complete correlation' );
q_assert_same( false, $matching_report['assessment']['observability_incomplete'], 'matching correlation is not falsely incomplete' );

/* Diagnostic output remains a bounded privacy whitelist. */
$json = wp_json_encode( $matching_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
q_assert_true( is_string( $json ) && is_array( json_decode( $json, true ) ), 'diagnostic remains valid JSON' );
foreach ( array( 'password', 'passwd', 'secret', 'cookie', 'authorization', 'nonce', 'db_password', 'database_password', 'salt', 'username', 'email' ) as $forbidden_key ) {
	q_assert_same( false, false !== stripos( $json, '"' . $forbidden_key . '"' ), 'diagnostic excludes forbidden privacy key: ' . $forbidden_key );
}
q_assert_same( false, false !== strpos( $json, '<html' ), 'diagnostic never exports remote HTML' );
q_assert_same( false, false !== strpos( $json, 'raw_html' ), 'diagnostic has no raw HTML field' );

/* Degraded operational/admin rendering remains truthful when attempt persistence fails. */
$degraded_values = array();
$degraded_get = static function ( $name, $default = false ) use ( &$degraded_values ) {
	return array_key_exists( $name, $degraded_values ) ? $degraded_values[ $name ] : $default;
};
$degraded_add = static function ( $name, $value ) use ( &$degraded_values ) {
	if ( Snapshot_Store::OPTION_LATEST_ATTEMPT === $name ) {
		return false;
	}
	$degraded_values[ $name ] = $value;
	return true;
};
$degraded_update = static function ( $name, $value ) use ( &$degraded_values ) {
	if ( Snapshot_Store::OPTION_LATEST_ATTEMPT === $name ) {
		return false;
	}
	$degraded_values[ $name ] = $value;
	return true;
};
$degraded_store = new Snapshot_Store( $degraded_get, $degraded_add, $degraded_update );
$fetch_mode     = 'valid';
$fetch_in_mode  = 0;
$degraded_refresh = new Refresh_Service(
	$preview,
	$degraded_store,
	static function (): string { return '2026-09-30T14:07:00+00:00'; },
	static function (): string { return 'degraded-admin-run'; },
	$qualification
);
$degraded_scheduler = new Scheduler(
	static function () { return false; },
	static function () { return true; },
	static function () { return 1; },
	static function () { return 1000; },
	static function () { return false; },
	$qualification
);
$degraded_admin = new Admin_Page( $preview, $degraded_refresh, $degraded_store, $degraded_scheduler );
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['ksh_action']       = 'refresh';
ob_start();
$degraded_admin->render();
$degraded_html = ob_get_clean();
unset( $_POST['ksh_action'] );
$_SERVER['REQUEST_METHOD'] = 'GET';
q_assert_true( false !== strpos( $degraded_html, 'Refresh ناقص عملیاتی' ), 'admin preserves degraded operational warning' );
q_assert_true( false !== strpos( $degraded_html, 'attempt_write_failed' ), 'admin exposes bounded attempt persistence failure' );
q_assert_same( false, false !== strpos( $degraded_html, 'Refresh کامل:' ), 'degraded admin result cannot render full-success wording' );
q_assert_same( Admin_Page::REFRESH_NONCE, $GLOBALS['ksh_q_nonce_action'], 'degraded Manual refresh remains nonce-protected' );

/* Production source composition contains both admission boundaries. */
$plugin_source    = file_get_contents( __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php' );
$refresh_source   = file_get_contents( __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-refresh-service.php' );
$scheduler_source = file_get_contents( __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-scheduler.php' );
q_assert_true( is_string( $plugin_source ) && is_string( $refresh_source ) && is_string( $scheduler_source ), 'production admission sources are readable for composition regression check' );
q_assert_true( false !== strpos( $plugin_source, 'new Acquisition_Qualification( $preview )' ), 'production composition builds qualification from exact current Preview service' );
q_assert_true( false !== strpos( $plugin_source, 'new Refresh_Service( $preview, $store, null, null, $qualification )' ), 'production refresh receives the qualification boundary explicitly' );
q_assert_true( false !== strpos( $plugin_source, 'new Scheduler( null, null, null, null, null, $qualification )' ), 'production scheduler receives the same qualification boundary explicitly' );
q_assert_true( false !== strpos( $refresh_source, "array( 'manual', 'cron' )" ) && false !== strpos( $refresh_source, '! $this->qualification->is_qualified()' ), 'canonical Manual/Cron refresh path contains fail-closed admission guard' );
q_assert_true( false !== strpos( $scheduler_source, '! $this->qualification->is_qualified()' ), 'scheduler contains independent stale-event admission guard' );

q_assert_same( '0.4.0', Plugin::VERSION, 'qualification repair remains within the existing unmerged v0.4.0 release identity' );

echo 'QUALIFICATION_REGRESSION_PASS assertions=' . $assertions . PHP_EOL;
