<?php
/**
 * Deterministic parser/orchestration/persistence test runner.
 *
 * These fixtures and WordPress primitive stubs exercise repository logic only.
 * They do not prove future WP-Cron execution or current production networking.
 */

declare(strict_types=1);

use KSH\KanoonArticles\Admin_Page;
use KSH\KanoonArticles\Article_Parser;
use KSH\KanoonArticles\Diagnostic_Report;
use KSH\KanoonArticles\Plugin;
use KSH\KanoonArticles\Preview_Service;
use KSH\KanoonArticles\Refresh_Service;
use KSH\KanoonArticles\Scheduler;
use KSH\KanoonArticles\Snapshot_Store;

$GLOBALS['ksh_test_actions']          = array();
$GLOBALS['ksh_test_cron']             = array();
$GLOBALS['ksh_test_current_user_can'] = true;
$GLOBALS['ksh_test_capability']       = null;
$GLOBALS['ksh_test_nonce_action']     = null;
$GLOBALS['wp_version']                = '7.1.2';

if ( ! function_exists( 'add_action' ) ) {
	function add_action( $hook, $callback ) {
		$GLOBALS['ksh_test_actions'][ $hook ][] = $callback;
	}
}
if ( ! function_exists( 'wp_next_scheduled' ) ) {
	function wp_next_scheduled( $hook ) {
		return isset( $GLOBALS['ksh_test_cron'][ $hook ]['timestamp'] ) ? $GLOBALS['ksh_test_cron'][ $hook ]['timestamp'] : false;
	}
}
if ( ! function_exists( 'wp_schedule_event' ) ) {
	function wp_schedule_event( $timestamp, $recurrence, $hook ) {
		$GLOBALS['ksh_test_cron'][ $hook ] = array(
			'timestamp'  => (int) $timestamp,
			'recurrence' => (string) $recurrence,
		);
		return true;
	}
}
if ( ! function_exists( 'wp_get_schedule' ) ) {
	function wp_get_schedule( $hook ) {
		return isset( $GLOBALS['ksh_test_cron'][ $hook ]['recurrence'] ) ? $GLOBALS['ksh_test_cron'][ $hook ]['recurrence'] : false;
	}
}
if ( ! function_exists( 'wp_clear_scheduled_hook' ) ) {
	function wp_clear_scheduled_hook( $hook ) {
		unset( $GLOBALS['ksh_test_cron'][ $hook ] );
		return 1;
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text ) { return $text; }
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text ) { return $text; }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return (string) $text; }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return (string) $text; }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $text ) { return (string) $text; }
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability = '' ) {
		$GLOBALS['ksh_test_capability'] = $capability;
		return ! empty( $GLOBALS['ksh_test_current_user_can'] );
	}
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
		$GLOBALS['ksh_test_nonce_action'] = $action;
		return 1;
	}
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field() { echo '<input type="hidden" name="_wpnonce" value="test">'; }
}
if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text ) { echo '<button type="submit">' . $text . '</button>'; }
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
if ( ! function_exists( 'nocache_headers' ) ) {
	function nocache_headers() { return null; }
}
if ( ! function_exists( 'wp_timezone_string' ) ) {
	function wp_timezone_string() { return 'Asia/Tehran'; }
}

require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-source-config.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-article-parser.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-preview-service.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-snapshot-store.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-refresh-service.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-scheduler.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-diagnostic-report.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-admin-page.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-remote-fetcher.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php';

if ( ! class_exists( 'DOMDocument' ) ) {
	fwrite( STDERR, "TEST_ENVIRONMENT_UNAVAILABLE: ext-dom is required for parser tests.\n" );
	exit( 2 );
}

$assertions = 0;

function fixture( string $name ): string {
	$contents = file_get_contents( __DIR__ . '/fixtures/' . $name );
	if ( false === $contents ) {
		throw new RuntimeException( 'Unable to read fixture: ' . $name );
	}
	return $contents;
}

function assert_same( $expected, $actual, string $message ): void {
	global $assertions;
	++$assertions;
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function assert_true( $actual, string $message ): void {
	assert_same( true, (bool) $actual, $message );
}

$parser = new Article_Parser();

$latest = $parser->parse_latest( fixture( 'latest-valid.html' ) );
assert_same( 'success', $latest['status'], 'valid Latest succeeds' );
assert_same( 2, $latest['count'], 'Latest rejects invalid/foreign candidates' );
assert_same( 'گفت و گو محمد رهگشای با رامتین بهرامی قهرمان پیشرفت', $latest['items'][0]['title'], 'Latest strips bounded time/view suffix' );
assert_same( 'https://www.kanoon.ir/Article/474577', $latest['items'][0]['url'], 'Latest canonicalizes relative article URL' );
assert_same( 'https://www.kanoon.ir/Article/474578', $latest['items'][1]['url'], 'Latest preserves source ordering' );
assert_same( 'شنبه 4 مهر 1405', $latest['items'][0]['date_context'], 'Latest propagates reliable day context' );

$multi_day = $parser->parse_latest( fixture( 'latest-multi-day.html' ) );
assert_same( 'success', $multi_day['status'], 'multi-day Latest succeeds' );
assert_same( 'شنبه 4 مهر 1405', $multi_day['date_context'], 'Latest summary date context remains the initial boundary' );
assert_same( 5, $multi_day['count'], 'later date headings do not terminate Latest traversal' );
assert_same(
	array(
		'https://www.kanoon.ir/Article/910001',
		'https://www.kanoon.ir/Article/910002',
		'https://www.kanoon.ir/Article/910003',
		'https://www.kanoon.ir/Article/910004',
		'https://www.kanoon.ir/Article/910005',
	),
	array_column( $multi_day['items'], 'url' ),
	'Latest preserves source order across date transitions'
);
assert_same( 'شنبه 4 مهر 1405', $multi_day['items'][0]['date_context'], 'first-day article receives first date context' );
assert_same( 'شنبه 4 مهر 1405', $multi_day['items'][1]['date_context'], 'non-date heading does not change current date context' );
assert_same( 'یکشنبه 5 مهر 1405', $multi_day['items'][2]['date_context'], 'second-day article receives second date context' );
assert_same( 'یکشنبه 5 مهر 1405', $multi_day['items'][3]['date_context'], 'second date context remains active until next valid date heading' );
assert_same( 'دوشنبه 6 مهر 1405', $multi_day['items'][4]['date_context'], 'subsequent valid date headings rebind context repeatedly' );

$limit_html = '<html><body><h3>شنبه 4 مهر 1405</h3>';
for ( $i = 1; $i <= 10; ++$i ) {
	$limit_html .= '<a href="/Article/' . ( 930000 + $i ) . '">روز اول ' . $i . '</a>';
}
$limit_html .= '<h3>یکشنبه 5 مهر 1405</h3>';
for ( $i = 11; $i <= 25; ++$i ) {
	$limit_html .= '<a href="/Article/' . ( 930000 + $i ) . '">روز دوم ' . $i . '</a>';
}
$limit_html .= '</body></html>';
$limited     = $parser->parse_latest( $limit_html );
assert_same( 20, $limited['count'], 'Latest 20-item limit remains effective across date transitions' );
assert_same( 'https://www.kanoon.ir/Article/930020', $limited['items'][19]['url'], 'Latest limit preserves the first 20 source-ordered articles' );
assert_same( 'یکشنبه 5 مهر 1405', $limited['items'][19]['date_context'], 'Latest limit keeps the nearest preceding valid date context' );

$joining = $parser->parse_latest(
	'<html><body><h3>شنبه 4 مهر 1405</h3>' .
	'<a href="/Article/474579">گفت‌وگوی قلم‌چی دقایقی قبل 1 بازدید</a></body></html>'
);
assert_same( 'گفت‌وگوی قلم‌چی', $joining['items'][0]['title'], 'Latest preserves Persian ZWNJ in normalized titles' );

$weekly = $parser->parse_weekly_popular( fixture( 'home-valid.html' ) );
assert_same( 'success', $weekly['status'], 'valid Weekly Popular succeeds' );
assert_same( 2, $weekly['count'], 'Weekly rejects foreign candidate and keeps valid items' );
assert_same( 'https://www.kanoon.ir/Article/467565', $weekly['items'][0]['url'], 'Weekly first item preserved' );
assert_same( 'https://www.kanoon.ir/Article/467566', $weekly['items'][1]['url'], 'Weekly canonicalizes host/trailing slash' );

$ambiguous = $parser->parse_weekly_popular( fixture( 'home-ambiguous.html' ) );
assert_same( 'ambiguous', $ambiguous['status'], 'Weekly/Monthly target collision is ambiguous' );
assert_same( 'weekly_monthly_target_collision', $ambiguous['reason'], 'ambiguity reason is bounded' );

$empty_source = $parser->parse_latest( '' );
assert_same( 'failure', $empty_source['status'], 'empty Latest source fails' );
assert_same( 'empty_html', $empty_source['reason'], 'empty Latest source reports reason' );

$latest_empty = $parser->parse_latest( fixture( 'latest-empty.html' ) );
assert_same( 'failure', $latest_empty['status'], 'zero-item Latest is not valid empty success' );
assert_same( 'latest_zero_valid_items', $latest_empty['reason'], 'zero-item Latest reports reason' );

$latest_missing_boundary = $parser->parse_latest( '<html><body><a href="/Article/1">بدون مرز روز</a></body></html>' );
assert_same( 'failure', $latest_missing_boundary['status'], 'Latest missing semantic date boundary fails' );
assert_same( 'latest_date_boundary_missing', $latest_missing_boundary['reason'], 'missing Latest boundary reports reason' );

$weekly_bad = $parser->parse_weekly_popular( fixture( 'home-malformed-items.html' ) );
assert_same( 'failure', $weekly_bad['status'], 'Weekly with only malformed candidates fails' );
assert_same( 'weekly_zero_valid_items', $weekly_bad['reason'], 'malformed Weekly reports zero valid items' );

$fetch = static function ( string $url ): array {
	if ( false !== strpos( $url, '/Article/Days' ) ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'latest-valid.html' ), 'reason' => '' );
	}
	return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'home-ambiguous.html' ), 'reason' => '' );
};

$preview = new Preview_Service( $fetch, $parser );
$run     = $preview->run();
assert_same( 'success', $run['latest']['status'], 'Latest remains independently successful' );
assert_same( 'ambiguous', $run['weekly_popular']['status'], 'Weekly remains independently ambiguous' );
assert_same( 'partial', $run['overall_status'], 'one success plus one ambiguous is never full success' );

$fetch_with_weekly_failure = static function ( string $url ): array {
	if ( false !== strpos( $url, '/Article/Days' ) ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'latest-valid.html' ), 'reason' => '' );
	}
	return array( 'ok' => false, 'http_code' => 503, 'body' => '', 'reason' => 'http_error' );
};

$partial_failure = ( new Preview_Service( $fetch_with_weekly_failure, $parser ) )->run();
assert_same( 'success', $partial_failure['latest']['status'], 'Latest success survives independent Weekly fetch failure' );
assert_same( 'failure', $partial_failure['weekly_popular']['status'], 'Weekly fetch failure is reported independently' );
assert_same( 'partial', $partial_failure['overall_status'], 'independent list failure never becomes full success' );

/* Persistence + refresh contract. */
$option_values   = array();
$option_autoload = array();
$get_option      = static function ( $name, $default = false ) use ( &$option_values ) {
	return array_key_exists( $name, $option_values ) ? $option_values[ $name ] : $default;
};
$add_option      = static function ( $name, $value, $deprecated = '', $autoload = null ) use ( &$option_values, &$option_autoload ) {
	if ( array_key_exists( $name, $option_values ) ) {
		return false;
	}
	$option_values[ $name ]   = $value;
	$option_autoload[ $name ] = $autoload;
	return true;
};
$update_option   = static function ( $name, $value, $autoload = null ) use ( &$option_values, &$option_autoload ) {
	$option_values[ $name ]   = $value;
	$option_autoload[ $name ] = $autoload;
	return true;
};
$store           = new Snapshot_Store( $get_option, $add_option, $update_option );
$fetch_mode      = 'initial';
$fetch_count     = 0;
$refresh_fetch   = static function ( string $url ) use ( &$fetch_mode, &$fetch_count ): array {
	++$fetch_count;
	$is_latest = false !== strpos( $url, '/Article/Days' );

	if ( 'latest_fail' === $fetch_mode && $is_latest ) {
		return array( 'ok' => false, 'http_code' => 503, 'body' => '', 'reason' => 'http_error' );
	}
	if ( 'weekly_ambiguous' === $fetch_mode && ! $is_latest ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'home-ambiguous.html' ), 'reason' => '' );
	}

	if ( $is_latest ) {
		if ( 'initial' === $fetch_mode ) {
			$body = fixture( 'latest-valid.html' );
		} else {
			$body = '<html><body><h3>سه شنبه 7 مهر 1405</h3>' .
				'<a href="/Article/950001">تازه جدید اول</a><a href="/Article/950002">تازه جدید دوم</a></body></html>';
		}
		return array( 'ok' => true, 'http_code' => 200, 'body' => $body, 'reason' => '' );
	}

	$body = 'initial' === $fetch_mode ? fixture( 'home-valid.html' ) :
		'<html><body><a href="#weekly">پربازدید هفته</a><a href="#monthly">پربازدید ماه</a>' .
		'<div id="weekly"><a href="/Article/960001">هفته جدید اول</a><a href="/Article/960002">هفته جدید دوم</a></div>' .
		'<div id="monthly"><a href="/Article/960099">ماهانه</a></div></body></html>';
	return array( 'ok' => true, 'http_code' => 200, 'body' => $body, 'reason' => '' );
};
$clock_tick      = 0;
$clock           = static function () use ( &$clock_tick ): string {
	++$clock_tick;
	return sprintf( '2026-09-27T00:00:%02d+00:00', $clock_tick );
};
$refresh_preview = new Preview_Service( $refresh_fetch, $parser );
$refresh         = new Refresh_Service( $refresh_preview, $store, $clock );

$first_refresh = $refresh->run();
assert_same( 'success', $first_refresh['overall_status'], 'first valid refresh updates both lists' );
assert_true( $first_refresh['latest']['attempt_recorded'], 'normal Latest attempt metadata is recorded' );
assert_true( $first_refresh['weekly_popular']['attempt_recorded'], 'normal Weekly attempt metadata is recorded' );
assert_same( '', $first_refresh['latest']['attempt_reason'], 'normal attempt persistence has no diagnostic failure reason' );
assert_true( null !== $store->get_snapshot( 'latest' ), 'first successful Latest creates a snapshot' );
assert_true( null !== $store->get_snapshot( 'weekly_popular' ), 'first successful Weekly creates a snapshot' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LATEST_SNAPSHOT ], 'Latest snapshot is non-autoloaded' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_WEEKLY_SNAPSHOT ], 'Weekly snapshot is non-autoloaded' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LATEST_ATTEMPT ], 'Latest attempt is non-autoloaded' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_WEEKLY_ATTEMPT ], 'Weekly attempt is non-autoloaded' );

$initial_latest = $store->get_snapshot( 'latest' );
$initial_weekly = $store->get_snapshot( 'weekly_popular' );
assert_same(
	array( 'schema_version', 'source', 'source_url', 'items', 'count', 'updated_at', 'date_context' ),
	array_keys( $initial_latest ),
	'snapshot persists only bounded normalized metadata'
);
assert_same( array( 'title', 'url', 'date_context' ), array_keys( $initial_latest['items'][0] ), 'persisted items omit request/raw diagnostic data' );
assert_same(
	array( 'https://www.kanoon.ir/Article/474577', 'https://www.kanoon.ir/Article/474578' ),
	array_column( $initial_latest['items'], 'url' ),
	'persisted Latest ordering remains source ordering'
);

$fetch_mode = 'replace';
$replaced   = $refresh->run();
assert_same( 'success', $replaced['overall_status'], 'valid refresh replaces both prior snapshots' );
assert_same( 'https://www.kanoon.ir/Article/950001', $store->get_snapshot( 'latest' )['items'][0]['url'], 'valid Latest refresh replaces prior Latest snapshot' );
assert_same( 'https://www.kanoon.ir/Article/960001', $store->get_snapshot( 'weekly_popular' )['items'][0]['url'], 'valid Weekly refresh replaces prior Weekly snapshot' );

/* Attempt metadata failure is operationally degraded without rolling back valid snapshots. */
$attempt_fail_values = array(
	Snapshot_Store::OPTION_LATEST_ATTEMPT => array(
		'schema_version'   => 1,
		'source'           => 'latest',
		'attempted_at'     => '2026-09-26T23:00:00+00:00',
		'candidate_status' => 'success',
		'http_code'        => 200,
		'reason'           => '',
		'action'           => 'updated',
	),
);
$attempt_fail_get = static function ( $name, $default = false ) use ( &$attempt_fail_values ) {
	return array_key_exists( $name, $attempt_fail_values ) ? $attempt_fail_values[ $name ] : $default;
};
$attempt_fail_add = static function ( $name, $value ) use ( &$attempt_fail_values ) {
	if ( Snapshot_Store::OPTION_LATEST_ATTEMPT === $name ) {
		return false;
	}
	$attempt_fail_values[ $name ] = $value;
	return true;
};
$attempt_fail_update = static function ( $name, $value ) use ( &$attempt_fail_values ) {
	if ( Snapshot_Store::OPTION_LATEST_ATTEMPT === $name ) {
		return false;
	}
	$attempt_fail_values[ $name ] = $value;
	return true;
};
$attempt_fail_store = new Snapshot_Store( $attempt_fail_get, $attempt_fail_add, $attempt_fail_update );
$fetch_mode         = 'replace';
$attempt_fail_run   = new Refresh_Service(
	$refresh_preview,
	$attempt_fail_store,
	static function (): string { return '2026-09-27T02:00:00+00:00'; }
);
$attempt_fail_result = $attempt_fail_run->run();
assert_same( 'degraded', $attempt_fail_result['overall_status'], 'one failed attempt-status write prevents full refresh success' );
assert_same( 'updated', $attempt_fail_result['latest']['action'], 'Latest snapshot action remains updated when only attempt metadata fails' );
assert_same( 'updated', $attempt_fail_result['weekly_popular']['action'], 'Weekly snapshot still updates independently' );
assert_same( false, $attempt_fail_result['latest']['attempt_recorded'], 'Latest exposes failed attempt persistence' );
assert_same( 'attempt_write_failed', $attempt_fail_result['latest']['attempt_reason'], 'Latest exposes bounded attempt-write failure reason' );
assert_same( true, $attempt_fail_result['weekly_popular']['attempt_recorded'], 'Weekly attempt persistence remains independently successful' );
assert_same( 'https://www.kanoon.ir/Article/950001', $attempt_fail_store->get_snapshot( 'latest' )['items'][0]['url'], 'valid Latest snapshot is not rolled back after attempt write failure' );
assert_same( 'https://www.kanoon.ir/Article/960001', $attempt_fail_store->get_snapshot( 'weekly_popular' )['items'][0]['url'], 'both valid snapshots remain updated in degraded operation' );
assert_same( '2026-09-26T23:00:00+00:00', $attempt_fail_store->get_attempt( 'latest' )['attempted_at'], 'failed attempt write preserves the previous latest recorded attempt' );

$attempt_fail_scheduler = new Scheduler(
	static function () { return false; },
	static function () { return true; },
	static function () { return 1; },
	static function () { return 1000; }
);
$attempt_fail_admin = new Admin_Page( $refresh_preview, $attempt_fail_run, $attempt_fail_store, $attempt_fail_scheduler );
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['ksh_action']       = 'refresh';
ob_start();
$attempt_fail_admin->render();
$degraded_html = ob_get_clean();
assert_true( false !== strpos( $degraded_html, 'Refresh ناقص عملیاتی' ), 'manual refresh renders degraded operational warning' );
assert_true( false !== strpos( $degraded_html, 'تازه‌ها — ثبت وضعیت تلاش:' ), 'manual refresh identifies the list whose attempt record failed' );
assert_true( false !== strpos( $degraded_html, 'attempt_write_failed' ), 'manual refresh exposes bounded attempt-write failure reason' );
assert_same( false, false !== strpos( $degraded_html, 'Refresh کامل:' ), 'degraded manual refresh cannot render full-success wording' );
assert_true( false !== strpos( $degraded_html, 'آخرین تلاش ثبت‌شده' ), 'persistent status truthfully labels latest recorded attempt semantics' );
unset( $_POST['ksh_action'] );
$_SERVER['REQUEST_METHOD'] = 'GET';

/* Snapshot-write failure remains distinct from attempt-write failure. */
$snapshot_fail_values = array();
$snapshot_fail_get    = static function ( $name, $default = false ) use ( &$snapshot_fail_values ) {
	return array_key_exists( $name, $snapshot_fail_values ) ? $snapshot_fail_values[ $name ] : $default;
};
$snapshot_fail_add    = static function ( $name, $value ) use ( &$snapshot_fail_values ) {
	if ( Snapshot_Store::OPTION_LATEST_SNAPSHOT === $name ) {
		return false;
	}
	$snapshot_fail_values[ $name ] = $value;
	return true;
};
$snapshot_fail_update = static function ( $name, $value ) use ( &$snapshot_fail_values ) {
	if ( Snapshot_Store::OPTION_LATEST_SNAPSHOT === $name ) {
		return false;
	}
	$snapshot_fail_values[ $name ] = $value;
	return true;
};
$snapshot_fail_store  = new Snapshot_Store( $snapshot_fail_get, $snapshot_fail_add, $snapshot_fail_update );
$fetch_mode           = 'replace';
$snapshot_fail_run    = new Refresh_Service(
	$refresh_preview,
	$snapshot_fail_store,
	static function (): string { return '2026-09-27T03:00:00+00:00'; }
);
$snapshot_fail_result = $snapshot_fail_run->run();
assert_same( 'partial', $snapshot_fail_result['overall_status'], 'snapshot write failure keeps existing partial semantics when attempts record successfully' );
assert_same( 'no_valid_snapshot_available', $snapshot_fail_result['latest']['action'], 'failed Latest snapshot write does not redefine action as updated' );
assert_same( 'snapshot_write_failed', $snapshot_fail_result['latest']['reason'], 'snapshot write failure retains its distinct reason' );
assert_same( true, $snapshot_fail_result['latest']['attempt_recorded'], 'snapshot write failure can still record attempt metadata successfully' );
assert_same( '', $snapshot_fail_result['latest']['attempt_reason'], 'snapshot write failure is distinct from attempt write failure' );
assert_same( 'updated', $snapshot_fail_result['weekly_popular']['action'], 'Weekly snapshot remains independently updatable during Latest snapshot write failure' );

$latest_before_failure = $store->get_snapshot( 'latest' );
$fetch_mode            = 'latest_fail';
$partial_latest_fail   = $refresh->run();
assert_same( 'partial', $partial_latest_fail['overall_status'], 'one healthy list may advance while failed Latest preserves old data' );
assert_same( 'preserved_previous', $partial_latest_fail['latest']['action'], 'failed Latest preserves previous snapshot' );
assert_same( $latest_before_failure, $store->get_snapshot( 'latest' ), 'failed Latest cannot erase or replace previous Latest snapshot' );
assert_same( 'failure', $store->get_attempt( 'latest' )['candidate_status'], 'failed attempt status is recorded independently' );
assert_same( 'http_error', $store->get_attempt( 'latest' )['reason'], 'bounded failure reason is recorded without changing snapshot' );

$weekly_before_ambiguous = $store->get_snapshot( 'weekly_popular' );
$fetch_mode              = 'weekly_ambiguous';
$partial_weekly_ambig    = $refresh->run();
assert_same( 'partial', $partial_weekly_ambig['overall_status'], 'healthy Latest advances while ambiguous Weekly preserves old data' );
assert_same( 'preserved_previous', $partial_weekly_ambig['weekly_popular']['action'], 'ambiguous Weekly preserves previous snapshot' );
assert_same( $weekly_before_ambiguous, $store->get_snapshot( 'weekly_popular' ), 'ambiguous Weekly cannot erase or replace prior snapshot' );
assert_same( 'ambiguous', $store->get_attempt( 'weekly_popular' )['candidate_status'], 'ambiguous Weekly attempt remains diagnosable' );

/* No previous snapshot + invalid candidates never fabricate local data. */
$empty_values = array();
$empty_get    = static function ( $name, $default = false ) use ( &$empty_values ) {
	return array_key_exists( $name, $empty_values ) ? $empty_values[ $name ] : $default;
};
$empty_add    = static function ( $name, $value ) use ( &$empty_values ) {
	$empty_values[ $name ] = $value;
	return true;
};
$empty_update = static function ( $name, $value ) use ( &$empty_values ) {
	$empty_values[ $name ] = $value;
	return true;
};
$empty_store  = new Snapshot_Store( $empty_get, $empty_add, $empty_update );
$bad_fetch    = static function ( string $url ): array {
	if ( false !== strpos( $url, '/Article/Days' ) ) {
		return array( 'ok' => false, 'http_code' => 500, 'body' => '', 'reason' => 'http_error' );
	}
	return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'home-ambiguous.html' ), 'reason' => '' );
};
$empty_refresh = new Refresh_Service( new Preview_Service( $bad_fetch, $parser ), $empty_store, static function (): string { return '2026-09-27T01:00:00+00:00'; } );
$empty_result  = $empty_refresh->run();
assert_same( 'no_valid_snapshot_available', $empty_result['latest']['action'], 'failed first Latest does not fabricate local data' );
assert_same( 'no_valid_snapshot_available', $empty_result['weekly_popular']['action'], 'ambiguous first Weekly does not fabricate local data' );
assert_same( null, $empty_store->get_snapshot( 'latest' ), 'no invalid Latest snapshot is written' );
assert_same( null, $empty_store->get_snapshot( 'weekly_popular' ), 'no invalid Weekly snapshot is written' );

/* Manual and cron wiring share one canonical Refresh_Service while preserving explicit origin. */
$GLOBALS['ksh_test_actions'] = array();
$schedule_next = false;
$scheduled_at  = null;
$cleared       = false;
$scheduler     = new Scheduler(
	static function () use ( &$schedule_next ) { return $schedule_next; },
	static function ( $timestamp, $recurrence, $hook ) use ( &$schedule_next, &$scheduled_at ) {
		$scheduled_at  = array( $timestamp, $recurrence, $hook );
		$schedule_next = $timestamp;
		return true;
	},
	static function () use ( &$cleared ) { $cleared = true; return 1; },
	static function () { return 1000; },
	static function () use ( &$schedule_next ) { return false !== $schedule_next ? 'daily' : false; }
);
$scheduler->register( $refresh );
assert_true( isset( $GLOBALS['ksh_test_actions'][ Scheduler::HOOK ][0] ), 'scheduler registers one owned cron callback' );
$cron_callback = $GLOBALS['ksh_test_actions'][ Scheduler::HOOK ][0];
assert_true( is_array( $cron_callback ) && $cron_callback[0] === $scheduler && 'run_cron' === $cron_callback[1], 'scheduled callback owns explicit cron attribution before calling canonical refresh' );

$fetch_mode = 'replace';
$admin      = new Admin_Page( $refresh_preview, $refresh, $store, $scheduler );
$before     = $fetch_count;
$manual_run = $admin->run_manual_refresh();
assert_same( $before + 2, $fetch_count, 'manual refresh invokes the canonical two-list refresh path once' );
assert_same( 'manual', $manual_run['trigger'], 'manual refresh records explicit manual origin' );
assert_true( $manual_run['run_summary_recorded'], 'manual refresh persists its bounded run summary' );
assert_same( $manual_run['run_id'], $manual_run['latest']['run_id'], 'manual Latest outcome carries the same run id' );
assert_same( $manual_run['run_id'], $manual_run['weekly_popular']['run_id'], 'manual Weekly outcome carries the same run id' );
assert_same( 'manual', $store->get_attempt( 'latest' )['trigger'], 'manual Latest attempt records origin' );
assert_same( $manual_run['run_id'], $store->get_attempt( 'latest' )['run_id'], 'manual Latest attempt correlates to run summary' );
assert_same( $manual_run['run_id'], $store->get_run_summary( 'manual' )['run_id'], 'manual run summary correlates to both list outcomes' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LAST_MANUAL_RUN ], 'manual run summary is non-autoloaded' );
$manual_summary_before_cron = $store->get_run_summary( 'manual' );

$before   = $fetch_count;
$cron_run = call_user_func( $cron_callback );
assert_same( $before + 2, $fetch_count, 'scheduled callback invokes the same canonical two-list refresh path once' );
assert_same( 'cron', $cron_run['trigger'], 'scheduled callback records explicit cron origin' );
assert_true( $cron_run['run_summary_recorded'], 'cron callback persists its bounded run summary' );
assert_same( $cron_run['run_id'], $cron_run['latest']['run_id'], 'cron Latest outcome carries the same run id' );
assert_same( $cron_run['run_id'], $cron_run['weekly_popular']['run_id'], 'cron Weekly outcome carries the same run id' );
assert_same( 'cron', $store->get_attempt( 'weekly_popular' )['trigger'], 'cron Weekly attempt records origin' );
assert_same( $cron_run['run_id'], $store->get_run_summary( 'cron' )['run_id'], 'cron run summary correlates to list outcomes' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LAST_CRON_RUN ], 'cron run summary is non-autoloaded' );
assert_same( $manual_summary_before_cron, $store->get_run_summary( 'manual' ), 'cron refresh does not erase latest manual summary' );
$cron_summary_before_manual = $store->get_run_summary( 'cron' );

$before      = $fetch_count;
$manual_run2 = $admin->run_manual_refresh();
assert_same( $before + 2, $fetch_count, 'later manual refresh still reuses the canonical acquisition path' );
assert_same( $cron_summary_before_manual, $store->get_run_summary( 'cron' ), 'later manual refresh does not erase latest cron summary' );
$manual_summary_before_cron2 = $store->get_run_summary( 'manual' );

$before    = $fetch_count;
$cron_run2 = call_user_func( $cron_callback );
assert_same( $before + 2, $fetch_count, 'later cron refresh still reuses the canonical acquisition path' );
assert_same( $manual_summary_before_cron2, $store->get_run_summary( 'manual' ), 'later cron refresh does not erase latest manual summary' );
assert_same( $cron_run2['run_id'], $store->get_run_summary( 'cron' )['run_id'], 'latest cron summary advances independently' );

/* Run-summary persistence failure never rolls back valid article snapshots or fabricates cron proof. */
$summary_fail_values = array();
$summary_fail_get    = static function ( $name, $default = false ) use ( &$summary_fail_values ) {
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
$summary_fail_run   = new Refresh_Service(
	$refresh_preview,
	$summary_fail_store,
	static function (): string { return '2026-09-27T04:00:00+00:00'; },
	static function (): string { return 'cron-run-summary-write-failure'; }
);
$fetch_mode          = 'replace';
$summary_fail_result = $summary_fail_run->run( 'cron' );
assert_same( false, $summary_fail_result['run_summary_recorded'], 'cron run exposes run-summary persistence failure' );
assert_same( 'run_summary_write_failed', $summary_fail_result['run_summary_reason'], 'cron run-summary failure has bounded reason' );
assert_true( null !== $summary_fail_store->get_snapshot( 'latest' ), 'diagnostic write failure does not roll back valid Latest snapshot' );
assert_true( null !== $summary_fail_store->get_snapshot( 'weekly_popular' ), 'diagnostic write failure does not roll back valid Weekly snapshot' );
assert_same( null, $summary_fail_store->get_run_summary( 'cron' ), 'failed cron run-summary write leaves no false persisted cron summary' );

/* Diagnostic report distinguishes scheduling from observed cron execution and stays read-only. */
$diagnostic_schedule_mutations = 0;
$diagnostic_scheduler          = new Scheduler(
	static function () { return 1730000000; },
	static function () use ( &$diagnostic_schedule_mutations ) { ++$diagnostic_schedule_mutations; return true; },
	static function () use ( &$diagnostic_schedule_mutations ) { ++$diagnostic_schedule_mutations; return 1; },
	static function () { return 1000; },
	static function () { return 'daily'; }
);
$diagnostic = new Diagnostic_Report(
	$store,
	$diagnostic_scheduler,
	static function (): string { return '2026-09-27T10:11:12+00:00'; }
);
$state_before_report = $option_values;
$fetch_before_report = $fetch_count;
$report              = $diagnostic->build();
assert_same( $fetch_before_report, $fetch_count, 'diagnostic JSON state generation performs zero remote acquisitions' );
assert_same( $state_before_report, $option_values, 'diagnostic JSON state generation performs zero option mutations' );
assert_same( 0, $diagnostic_schedule_mutations, 'diagnostic JSON state generation performs zero schedule mutations' );
assert_same( Diagnostic_Report::SCHEMA, $report['report']['schema'], 'diagnostic report exposes stable schema identifier' );
assert_same( Plugin::VERSION, $report['plugin']['version'], 'diagnostic report exposes running plugin version' );
assert_same( '7.1.2', $report['runtime']['wordpress_version'], 'diagnostic report exposes safe WordPress version fact' );
assert_same( PHP_VERSION, $report['runtime']['php_version'], 'diagnostic report exposes safe PHP version fact' );
assert_same( true, $report['scheduler']['event_registered'], 'diagnostic report exposes registered schedule independently' );
assert_same( 'daily', $report['scheduler']['recurrence'], 'diagnostic report exposes owned recurrence' );
assert_same( true, $report['scheduler']['cron_execution_observed'], 'persisted cron-origin run summary proves observed cron execution in deterministic state' );
assert_same( $store->get_run_summary( 'cron' )['started_at'], $report['scheduler']['last_cron_run']['started_at'], 'report exposes latest observed cron timestamp' );
assert_same( $store->get_run_summary( 'cron' )['overall_status'], $report['assessment']['last_cron_overall_status'], 'report exposes latest observed cron result' );
assert_same( $store->get_run_summary( 'manual' )['run_id'], $report['refresh']['last_manual_run']['run_id'], 'manual and cron summaries remain separately reportable' );
assert_same( true, $report['lists']['latest']['current_snapshot']['valid'], 'report validates current Latest snapshot locally' );
assert_true( $report['lists']['latest']['current_snapshot']['count'] > 0, 'report includes current Latest count' );
assert_true( ! empty( $report['lists']['latest']['current_snapshot']['items'] ), 'report includes bounded normalized Latest items' );
assert_same( 'cron', $report['lists']['latest']['latest_attempt']['trigger'], 'report includes latest per-list explicit origin' );
assert_same( $cron_run2['run_id'], $report['lists']['latest']['latest_attempt']['run_id'], 'report correlates latest per-list attempt with latest cron run' );
assert_same( false, $report['assessment']['observability_incomplete'], 'matching latest attempts and run summary report complete observability' );
assert_same( 'CRON_EXECUTION_OBSERVED', $report['assessment']['diagnostic_state'], 'observed cron summary produces factual observed state' );

$json = wp_json_encode( $report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
assert_true( is_string( $json ) && is_array( json_decode( $json, true ) ), 'diagnostic report encodes as valid UTF-8 JSON' );
assert_true( false !== strpos( $json, 'تازه جدید اول' ), 'Persian normalized snapshot text survives JSON encoding' );
assert_same( false, false !== strpos( $json, '<html' ), 'diagnostic report never exports raw remote HTML' );
assert_same( false, false !== strpos( $json, 'raw_html' ), 'diagnostic report has no raw-html field' );
foreach ( array( 'password', 'passwd', 'secret', 'cookie', 'authorization', 'nonce', 'db_password', 'database_password', 'salt', 'username', 'email' ) as $forbidden_key ) {
	assert_same( false, false !== stripos( $json, '"' . $forbidden_key . '"' ), 'diagnostic report excludes forbidden key: ' . $forbidden_key );
}

/* Scheduled event without persisted cron-origin summary is explicitly not observed. */
$no_cron_values = array(
	Snapshot_Store::OPTION_LATEST_ATTEMPT => array(
		'schema_version'   => 1,
		'source'           => 'latest',
		'attempted_at'     => '2026-09-26T23:00:00+00:00',
		'candidate_status' => 'success',
		'http_code'        => 200,
		'reason'           => '',
		'action'           => 'updated',
	),
);
$no_cron_get = static function ( $name, $default = false ) use ( &$no_cron_values ) {
	return array_key_exists( $name, $no_cron_values ) ? $no_cron_values[ $name ] : $default;
};
$no_cron_add = static function ( $name, $value ) use ( &$no_cron_values ) {
	$no_cron_values[ $name ] = $value;
	return true;
};
$no_cron_update = static function ( $name, $value ) use ( &$no_cron_values ) {
	$no_cron_values[ $name ] = $value;
	return true;
};
$no_cron_store     = new Snapshot_Store( $no_cron_get, $no_cron_add, $no_cron_update );
$scheduled_report  = ( new Diagnostic_Report(
	$no_cron_store,
	$diagnostic_scheduler,
	static function (): string { return '2026-09-27T10:12:00+00:00'; }
) )->build();
assert_same( true, $scheduled_report['scheduler']['event_registered'], 'scheduled-only state reports schedule registration' );
assert_same( false, $scheduled_report['scheduler']['cron_execution_observed'], 'schedule registration alone never proves cron execution' );
assert_same( null, $scheduled_report['scheduler']['last_cron_run'], 'scheduled-only state has no fabricated cron run' );
assert_same( 'SCHEDULED_NOT_YET_OBSERVED', $scheduled_report['assessment']['diagnostic_state'], 'scheduled-only state remains explicitly unobserved' );
assert_same( 'unknown', $scheduled_report['lists']['latest']['latest_attempt']['trigger'], 'legacy attempt without trigger remains readable as unknown' );
assert_same( 'legacy_or_unknown', $scheduled_report['lists']['latest']['latest_attempt']['attribution'], 'legacy attempt is truthfully attributed' );
assert_same( false, $scheduled_report['assessment']['observability_incomplete'], 'legacy unattributed attempt does not fabricate an observability-write failure' );

$missing_schedule = new Scheduler(
	static function () { return false; },
	static function () { throw new RuntimeException( 'diagnostic read must not schedule' ); },
	static function () { throw new RuntimeException( 'diagnostic read must not clear schedule' ); },
	static function () { return 1000; },
	static function () { return false; }
);
$missing_report = ( new Diagnostic_Report(
	$no_cron_store,
	$missing_schedule,
	static function (): string { return '2026-09-27T10:13:00+00:00'; }
) )->build();
assert_same( false, $missing_report['scheduler']['event_registered'], 'missing schedule is distinguishable from scheduled state' );
assert_same( false, $missing_report['scheduler']['cron_execution_observed'], 'missing schedule does not imply cron execution' );
assert_same( 'SCHEDULE_MISSING', $missing_report['assessment']['diagnostic_state'], 'missing schedule has distinct deterministic state' );

$summary_fail_scheduler = new Scheduler(
	static function () { return 1730000000; },
	static function () { return true; },
	static function () { return 1; },
	static function () { return 1000; },
	static function () { return 'daily'; }
);
$summary_fail_report = ( new Diagnostic_Report(
	$summary_fail_store,
	$summary_fail_scheduler,
	static function (): string { return '2026-09-27T10:14:00+00:00'; }
) )->build();
assert_same( false, $summary_fail_report['scheduler']['cron_execution_observed'], 'failed run-summary persistence cannot become persistent cron proof' );
assert_same( true, $summary_fail_report['assessment']['cron_attempts_without_matching_summary'], 'report explicitly surfaces persisted cron attempts lacking required run summary' );
assert_same( true, $summary_fail_report['assessment']['observability_incomplete'], 'run-summary persistence failure is explicitly reported as incomplete observability' );
assert_same( 'SCHEDULED_NOT_YET_OBSERVED', $summary_fail_report['assessment']['diagnostic_state'], 'run-summary write failure stays unobserved despite registered schedule' );

/* Per-list attempt correlation fails closed for partial/interleaved Manual and Cron state. */
$diagnostic_attempt = static function ( $source, $trigger, $run_id ) {
	return array(
		'schema_version'   => Snapshot_Store::ATTEMPT_SCHEMA_VERSION,
		'source'           => $source,
		'trigger'          => $trigger,
		'run_id'           => $run_id,
		'attempted_at'     => '2026-09-27T10:20:00+00:00',
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
		'started_at'     => '2026-09-27T10:19:00+00:00',
		'completed_at'   => '2026-09-27T10:20:00+00:00',
		'overall_status' => 'success',
		'lists'          => array(),
	);
};
$build_correlation_report = static function ( array $values ) use ( $diagnostic_scheduler ): array {
	$get = static function ( $name, $default = false ) use ( $values ) {
		return array_key_exists( $name, $values ) ? $values[ $name ] : $default;
	};
	$reject_write = static function ( $name, $value ) {
		throw new RuntimeException( 'correlation diagnostic must remain read-only: ' . $name );
	};
	$correlation_store = new Snapshot_Store( $get, $reject_write, $reject_write );

	return ( new Diagnostic_Report(
		$correlation_store,
		$diagnostic_scheduler,
		static function (): string { return '2026-09-27T10:21:00+00:00'; }
	) )->build();
};

$matching_cron_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => $diagnostic_summary( 'cron', 'cron-current' ),
	)
);
assert_same( false, $matching_cron_report['assessment']['cron_attempts_without_matching_summary'], 'matching Cron per-list attempts and summary are complete' );
assert_same( false, $matching_cron_report['assessment']['observability_incomplete'], 'fully matching current Cron run has complete observability' );

$latest_newer_cron_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', 'cron-newer' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'cron', 'cron-older' ),
	)
);
assert_same( true, $latest_newer_cron_report['assessment']['cron_attempts_without_matching_summary'], 'newer Latest Cron attempt without matching summary exposes a gap despite older Weekly attempt' );
assert_same( true, $latest_newer_cron_report['assessment']['observability_incomplete'], 'asymmetric Latest Cron state fails closed as incomplete observability' );

$weekly_newer_cron_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', 'cron-older' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'cron', 'cron-newer' ),
	)
);
assert_same( true, $weekly_newer_cron_report['assessment']['cron_attempts_without_matching_summary'], 'newer Weekly Cron attempt without matching summary exposes a gap despite older Latest attempt' );

$previous_cron_summary_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', 'cron-newer' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'cron', 'cron-previous' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => $diagnostic_summary( 'cron', 'cron-previous' ),
	)
);
assert_same( true, $previous_cron_summary_report['assessment']['cron_attempts_without_matching_summary'], 'previous Cron summary cannot hide one per-list attempt advancing to a newer run' );

$advanced_cron_summary_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'cron', 'cron-stale' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => $diagnostic_summary( 'cron', 'cron-current' ),
	)
);
assert_same( true, $advanced_cron_summary_report['assessment']['cron_attempts_without_matching_summary'], 'advanced Cron summary keeps a stale failed per-list attempt mechanically visible' );
assert_same( true, $advanced_cron_summary_report['assessment']['observability_incomplete'], 'summary advancement with one stale attempt remains incomplete' );

$latest_newer_manual_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'manual', 'manual-newer' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'manual', 'manual-older' ),
	)
);
assert_same( true, $latest_newer_manual_report['assessment']['manual_attempts_without_matching_summary'], 'newer Latest Manual attempt without matching summary exposes a gap' );

$weekly_newer_manual_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'manual', 'manual-older' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $diagnostic_attempt( 'weekly_popular', 'manual', 'manual-newer' ),
	)
);
assert_same( true, $weekly_newer_manual_report['assessment']['manual_attempts_without_matching_summary'], 'newer Weekly Manual attempt without matching summary exposes a gap' );

$independent_trigger_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT  => $diagnostic_attempt( 'latest', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT  => $diagnostic_attempt( 'weekly_popular', 'manual', 'manual-current' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN    => $diagnostic_summary( 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_LAST_MANUAL_RUN  => $diagnostic_summary( 'manual', 'manual-current' ),
	)
);
assert_same( false, $independent_trigger_report['assessment']['cron_attempts_without_matching_summary'], 'Manual attempt does not create a false Cron correlation gap' );
assert_same( false, $independent_trigger_report['assessment']['manual_attempts_without_matching_summary'], 'Cron attempt does not create a false Manual correlation gap' );
assert_same( false, $independent_trigger_report['assessment']['observability_incomplete'], 'independently matching Manual and Cron state remains complete' );

$missing_run_id_report = $build_correlation_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $diagnostic_attempt( 'latest', 'cron', null ),
	)
);
assert_same( 'cron', $missing_run_id_report['lists']['latest']['latest_attempt']['trigger'], 'explicit Cron trigger remains visible when run id is missing' );
assert_same( 'legacy_or_unknown', $missing_run_id_report['lists']['latest']['latest_attempt']['attribution'], 'missing run id does not fabricate explicit correlation attribution' );
assert_same( true, $missing_run_id_report['assessment']['cron_attempts_without_matching_summary'], 'explicit Cron attempt without usable run id fails closed as an observability gap' );

/* Page render and JSON export remain remote-free; download is protected by capability + nonce. */
$_SERVER['REQUEST_METHOD'] = 'GET';
$before                    = $fetch_count;
ob_start();
$admin->render();
$admin_html = ob_get_clean();
assert_same( $before, $fetch_count, 'opening the admin page alone performs no remote acquisition' );
assert_true( false !== strpos( $admin_html, 'گزارش تشخیصی' ), 'existing Tools page contains compact diagnostic section' );
assert_true( false !== strpos( $admin_html, 'دانلود گزارش JSON' ), 'existing Tools page exposes one-click JSON download' );
assert_true( false !== strpos( $admin_html, Admin_Page::EXPORT_ACTION ), 'diagnostic form targets native admin-post action' );

$download_admin = new Admin_Page(
	$refresh_preview,
	$refresh,
	$store,
	$diagnostic_scheduler,
	$diagnostic,
	static function () { return null; }
);
$GLOBALS['ksh_test_current_user_can'] = true;
$GLOBALS['ksh_test_capability']       = null;
$GLOBALS['ksh_test_nonce_action']     = null;
$download_state_before               = $option_values;
$download_fetch_before               = $fetch_count;
$download_schedule_before            = $diagnostic_schedule_mutations;
ob_start();
$download_admin->download_diagnostic();
$download_json = ob_get_clean();
assert_same( Admin_Page::CAPABILITY, $GLOBALS['ksh_test_capability'], 'download requires administrator capability' );
assert_same( Admin_Page::EXPORT_NONCE, $GLOBALS['ksh_test_nonce_action'], 'download action verifies dedicated nonce' );
assert_true( is_array( json_decode( $download_json, true ) ), 'download handler emits valid JSON directly' );
assert_same( $download_fetch_before, $fetch_count, 'download handler performs zero remote acquisitions' );
assert_same( $download_state_before, $option_values, 'download handler performs zero snapshot/attempt/run-summary mutations' );
assert_same( $download_schedule_before, $diagnostic_schedule_mutations, 'download handler performs zero schedule mutations' );

$GLOBALS['ksh_test_current_user_can'] = false;
$GLOBALS['ksh_test_nonce_action']     = null;
$blocked = false;
try {
	$download_admin->download_diagnostic();
} catch ( RuntimeException $exception ) {
	$blocked = true;
}
assert_true( $blocked, 'unauthorized diagnostic download is blocked' );
assert_same( null, $GLOBALS['ksh_test_nonce_action'], 'unauthorized download is rejected before nonce processing' );
$GLOBALS['ksh_test_current_user_can'] = true;

/* Schedule ensure, activation, and deactivation boundaries still do not acquire remotely. */
$before = $fetch_count;
assert_true( $scheduler->ensure_scheduled(), 'missing schedule is registered successfully' );
assert_same( array( 4600, 'daily', Scheduler::HOOK ), $scheduled_at, 'self-healing schedule uses one daily named hook' );
assert_same( $before, $fetch_count, 'schedule existence check performs no remote acquisition' );
assert_same( 'daily', $scheduler->recurrence(), 'registered schedule recurrence is readable without mutation' );

$GLOBALS['ksh_test_cron'] = array();
$before                   = $fetch_count;
Plugin::activate();
assert_true( false !== wp_next_scheduled( Scheduler::HOOK ), 'activation schedules the named daily event' );
assert_same( $before, $fetch_count, 'activation scheduling performs no remote acquisition' );

$snapshot_before_deactivate = $store->get_snapshot( 'latest' );
Plugin::deactivate();
assert_same( false, wp_next_scheduled( Scheduler::HOOK ), 'deactivation removes scheduled events' );
assert_same( $snapshot_before_deactivate, $store->get_snapshot( 'latest' ), 'deactivation does not delete valid snapshots' );

$GLOBALS['ksh_test_actions'] = array();
$before                      = $fetch_count;
Plugin::boot();
assert_true( isset( $GLOBALS['ksh_test_actions'][ 'admin_post_' . Admin_Page::EXPORT_ACTION ][0] ), 'plugin boot registers authenticated native diagnostic download handler' );
assert_same( $before, $fetch_count, 'plugin boot remains remote-acquisition free' );

echo 'TEST_PASS assertions=' . $assertions . PHP_EOL;
