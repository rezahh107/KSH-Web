<?php
/**
 * Deterministic parser/orchestration/persistence/presentation test runner.
 *
 * Fixtures and WordPress primitive stubs exercise repository logic only.
 * They do not prove future WP-Cron execution or current production networking.
 */

declare(strict_types=1);

use KSH\KanoonArticles\Admin_Page;
use KSH\KanoonArticles\Article_Parser;
use KSH\KanoonArticles\Diagnostic_Report;
use KSH\KanoonArticles\Frontend_Renderer;
use KSH\KanoonArticles\Plugin;
use KSH\KanoonArticles\Preview_Service;
use KSH\KanoonArticles\Refresh_Service;
use KSH\KanoonArticles\Scheduler;
use KSH\KanoonArticles\Shortcode;
use KSH\KanoonArticles\Snapshot_Store;
use KSH\KanoonArticles\Source_Config;

$GLOBALS['ksh_test_actions']          = array();
$GLOBALS['ksh_test_shortcodes']       = array();
$GLOBALS['ksh_test_styles']           = array();
$GLOBALS['ksh_test_esc_url_calls']    = 0;
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
if ( ! function_exists( 'add_shortcode' ) ) {
	function add_shortcode( $tag, $callback ) {
		$GLOBALS['ksh_test_shortcodes'][ $tag ] = $callback;
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
	function esc_html__( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' ); }
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $text ) {
		++$GLOBALS['ksh_test_esc_url_calls'];
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}
if ( ! function_exists( 'plugin_dir_url' ) ) {
	function plugin_dir_url( $file ) { return 'https://example.test/wp-content/plugins/ksh-kanoon-articles/'; }
}
if ( ! function_exists( 'wp_enqueue_style' ) ) {
	function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
		$GLOBALS['ksh_test_styles'][ $handle ] = array(
			'src'   => (string) $src,
			'deps'  => $deps,
			'ver'   => $ver,
			'media' => $media,
		);
	}
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
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-frontend-renderer.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-shortcode.php';
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

function homepage_html( int $latest_count = 2, int $weekly_count = 2, int $offset = 0 ): string {
	$html = '<html><body><a href="#latest">تازه ها</a><a href="#weekly">پربازدید هفته</a><a href="#monthly">پربازدید ماه</a>';
	$html .= '<div id="latest">';
	for ( $i = 1; $i <= $latest_count; ++$i ) {
		$html .= '<a href="/Article/' . ( 950000 + $offset + $i ) . '">تازه ' . $i . '</a>';
	}
	$html .= '<a href="/Article/Days">آرشیو تازه ها</a></div><div id="weekly">';
	for ( $i = 1; $i <= $weekly_count; ++$i ) {
		$html .= '<a href="/Article/' . ( 960000 + $offset + $i ) . '">هفته ' . $i . '</a>';
	}
	$html .= '</div><div id="monthly"><a href="/Article/' . ( 990000 + $offset ) . '">ماه</a></div></body></html>';
	return $html;
}

$parser = new Article_Parser();

/* Source + semantic parser contract. */
assert_same( 'https://www.kanoon.ir/', Source_Config::url( 'latest' ), 'Latest source is the Kanoon homepage' );
assert_same( 'https://www.kanoon.ir/', Source_Config::url( 'weekly_popular' ), 'Weekly source remains the Kanoon homepage' );
assert_same( '', Source_Config::url( 'unknown' ), 'unknown source has no remote URL' );

$latest = $parser->parse_latest( fixture( 'home-valid.html' ) );
assert_same( 'success', $latest['status'], 'semantic homepage Latest succeeds' );
assert_same( 2, $latest['count'], 'Latest keeps only valid articles inside its semantic target' );
assert_same(
	array( 'https://www.kanoon.ir/Article/500001', 'https://www.kanoon.ir/Article/500002' ),
	array_column( $latest['items'], 'url' ),
	'Latest excludes outside-panel, archive, foreign, malformed, and duplicate candidates while preserving order'
);
assert_same( '', $latest['date_context'], 'homepage Latest does not invent a summary date context' );
assert_same( '', $latest['items'][0]['date_context'], 'homepage Latest does not invent per-item date context' );

$many_latest = $parser->parse_latest( homepage_html( 24, 2 ) );
assert_same( 'success', $many_latest['status'], 'Latest semantic parser accepts a list longer than public display cap' );
assert_same( 24, $many_latest['count'], 'Latest acquisition is not truncated to the 15-item presentation limit' );
assert_same( 'https://www.kanoon.ir/Article/950024', $many_latest['items'][23]['url'], 'Latest keeps complete source ordering beyond item 15' );

$zwnj = $parser->parse_latest(
	'<a href="#latest">تازه‌ها</a><div id="latest"><a href="/Article/1">گفت‌وگوی قلم‌چی دقایقی قبل 1 بازدید</a></div>'
);
assert_same( 'success', $zwnj['status'], 'Latest accepts the ZWNJ semantic-label spelling' );
assert_same( 'گفت‌وگوی قلم‌چی', $zwnj['items'][0]['title'], 'Latest preserves Persian joining while stripping bounded time/view suffix' );

$duplicate_latest_label = $parser->parse_latest(
	'<a href="#a">تازه ها</a><a href="#b">تازه‌ها</a><div id="a"><a href="/Article/1">الف</a></div><div id="b"><a href="/Article/2">ب</a></div>'
);
assert_same( 'ambiguous', $duplicate_latest_label['status'], 'duplicate Latest semantic labels fail closed' );
assert_same( 'latest_tab_label_not_unique', $duplicate_latest_label['reason'], 'duplicate Latest label has bounded diagnostic reason' );

$missing_latest_target = $parser->parse_latest( '<a href="#missing">تازه ها</a>' );
assert_same( 'ambiguous', $missing_latest_target['status'], 'missing Latest target fails closed' );
assert_same( 'latest_tab_target_missing', $missing_latest_target['reason'], 'missing Latest target has bounded diagnostic reason' );

$colliding_latest_target = $parser->parse_latest(
	'<a href="#shared">تازه ها</a><a href="#shared">پربازدید هفته</a><a href="#monthly">پربازدید ماه</a>' .
	'<div id="shared"><a href="/Article/1">مشترک</a></div><div id="monthly"></div>'
);
assert_same( 'ambiguous', $colliding_latest_target['status'], 'Latest target collision with a sibling semantic tab fails closed' );
assert_same( 'latest_tab_target_collision', $colliding_latest_target['reason'], 'Latest target collision has bounded diagnostic reason' );

$old_days_shape = $parser->parse_latest( fixture( 'latest-valid.html' ) );
assert_same( 'ambiguous', $old_days_shape['status'], 'old /Article/Days-shaped HTML is not a fallback semantic source' );
assert_same( 'latest_tab_label_not_unique', $old_days_shape['reason'], 'old Latest source fails because semantic homepage tab is absent' );

$latest_zero = $parser->parse_latest(
	'<a href="#latest">تازه ها</a><div id="latest"><a href="/Article/Days">آرشیو</a><a href="https://example.com/Article/1">غریبه</a></div>'
);
assert_same( 'failure', $latest_zero['status'], 'semantic Latest with zero valid article links fails' );
assert_same( 'latest_zero_valid_items', $latest_zero['reason'], 'zero-item semantic Latest has bounded reason' );

$weekly = $parser->parse_weekly_popular( fixture( 'home-valid.html' ) );
assert_same( 'success', $weekly['status'], 'Weekly Popular remains valid' );
assert_same( 2, $weekly['count'], 'Weekly rejects foreign candidate and keeps valid items' );
assert_same( 'https://www.kanoon.ir/Article/467565', $weekly['items'][0]['url'], 'Weekly source ordering remains intact' );
assert_same( 'https://www.kanoon.ir/Article/467566', $weekly['items'][1]['url'], 'Weekly canonicalizes Kanoon host and trailing slash' );

$weekly_ambiguous = $parser->parse_weekly_popular( fixture( 'home-ambiguous.html' ) );
assert_same( 'ambiguous', $weekly_ambiguous['status'], 'Weekly/Monthly target collision remains ambiguous' );
assert_same( 'weekly_monthly_target_collision', $weekly_ambiguous['reason'], 'Weekly collision reason remains bounded' );
assert_same( 'success', $parser->parse_latest( fixture( 'home-ambiguous.html' ) )['status'], 'Latest can succeed independently when Weekly is ambiguous' );

$weekly_bad = $parser->parse_weekly_popular( fixture( 'home-malformed-items.html' ) );
assert_same( 'failure', $weekly_bad['status'], 'Weekly with only malformed candidates fails' );
assert_same( 'weekly_zero_valid_items', $weekly_bad['reason'], 'malformed Weekly reports zero valid items' );

/* Preview keeps same-URL fetches independent rather than introducing shared-fetch state. */
$fetch_calls = 0;
$latest_fail_weekly_ok = static function ( string $url ) use ( &$fetch_calls ): array {
	++$fetch_calls;
	assert_same( 'https://www.kanoon.ir/', $url, 'each Preview acquisition uses homepage URL' );
	if ( 1 === $fetch_calls ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => '<a href="#a">تازه ها</a><a href="#b">تازه‌ها</a><div id="a"></div><div id="b"></div>', 'reason' => '' );
	}
	return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'home-valid.html' ), 'reason' => '' );
};
$preview = new Preview_Service( $latest_fail_weekly_ok, $parser );
$run     = $preview->run();
assert_same( 2, $fetch_calls, 'Preview performs two independently attributable homepage fetches' );
assert_same( 'ambiguous', $run['latest']['status'], 'Latest semantic failure remains independent' );
assert_same( 'success', $run['weekly_popular']['status'], 'Weekly can succeed when Latest semantic parsing fails' );
assert_same( 'partial', $run['overall_status'], 'mixed Preview outcomes are partial' );

$fetch_calls = 0;
$latest_ok_weekly_fail = static function ( string $url ) use ( &$fetch_calls ): array {
	++$fetch_calls;
	if ( 1 === $fetch_calls ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'home-valid.html' ), 'reason' => '' );
	}
	return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'home-ambiguous.html' ), 'reason' => '' );
};
$run = ( new Preview_Service( $latest_ok_weekly_fail, $parser ) )->run();
assert_same( 'success', $run['latest']['status'], 'Latest can succeed when Weekly parsing fails' );
assert_same( 'ambiguous', $run['weekly_popular']['status'], 'Weekly ambiguity remains independently reportable' );
assert_same( 'partial', $run['overall_status'], 'reverse mixed outcome is partial' );

/* Persistence + refresh preserve full snapshots and last-known-good semantics. */
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
$store       = new Snapshot_Store( $get_option, $add_option, $update_option );
$fetch_mode  = 'initial';
$fetch_count = 0;
$refresh_fetch = static function ( string $url ) use ( &$fetch_mode, &$fetch_count ): array {
	++$fetch_count;
	$is_latest_call = 1 === ( $fetch_count % 2 );

	if ( 'latest_fail' === $fetch_mode && $is_latest_call ) {
		return array( 'ok' => false, 'http_code' => 503, 'body' => '', 'reason' => 'http_error' );
	}
	if ( 'weekly_ambiguous' === $fetch_mode && ! $is_latest_call ) {
		return array( 'ok' => true, 'http_code' => 200, 'body' => fixture( 'home-ambiguous.html' ), 'reason' => '' );
	}

	$body = 'initial' === $fetch_mode ? homepage_html( 21, 17 ) : homepage_html( 22, 18, 1000 );
	return array( 'ok' => true, 'http_code' => 200, 'body' => $body, 'reason' => '' );
};
$clock_tick = 0;
$clock      = static function () use ( &$clock_tick ): string {
	++$clock_tick;
	return sprintf( '2026-09-30T12:00:%02d+00:00', $clock_tick );
};
$refresh_preview = new Preview_Service( $refresh_fetch, $parser );
$refresh         = new Refresh_Service( $refresh_preview, $store, $clock );

$first_refresh = $refresh->run();
assert_same( 'success', $first_refresh['overall_status'], 'first valid refresh updates both homepage lists' );
assert_same( 21, $store->get_snapshot( 'latest' )['count'], 'Latest snapshot preserves more than 15 valid items' );
assert_same( 17, $store->get_snapshot( 'weekly_popular' )['count'], 'Weekly snapshot preserves more than 15 valid items' );
assert_same( '', $store->get_snapshot( 'latest' )['date_context'], 'Latest snapshot keeps schema-compatible empty date_context' );
assert_same( '', $store->get_snapshot( 'latest' )['items'][0]['date_context'], 'Latest item keeps schema-compatible empty date_context' );
assert_same( Source_Config::LATEST_URL, $store->get_snapshot( 'latest' )['source_url'], 'Latest snapshot records homepage source URL' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LATEST_SNAPSHOT ], 'Latest snapshot stays non-autoloaded' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_WEEKLY_SNAPSHOT ], 'Weekly snapshot stays non-autoloaded' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LATEST_ATTEMPT ], 'Latest attempt stays non-autoloaded' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_WEEKLY_ATTEMPT ], 'Weekly attempt stays non-autoloaded' );
assert_same( 'https://www.kanoon.ir/Article/950001', $store->get_snapshot( 'latest' )['items'][0]['url'], 'snapshot ordering starts with first semantic Latest article' );
assert_same( 'https://www.kanoon.ir/Article/950021', $store->get_snapshot( 'latest' )['items'][20]['url'], 'snapshot ordering remains intact beyond renderer limit' );

$fetch_mode = 'replace';
$replaced   = $refresh->run();
assert_same( 'success', $replaced['overall_status'], 'later valid refresh replaces both snapshots' );
assert_same( 'https://www.kanoon.ir/Article/951001', $store->get_snapshot( 'latest' )['items'][0]['url'], 'valid Latest candidate replaces previous Latest snapshot' );
assert_same( 'https://www.kanoon.ir/Article/961001', $store->get_snapshot( 'weekly_popular' )['items'][0]['url'], 'valid Weekly candidate replaces previous Weekly snapshot' );

$latest_before_failure = $store->get_snapshot( 'latest' );
$fetch_mode            = 'latest_fail';
$latest_failed         = $refresh->run();
assert_same( 'partial', $latest_failed['overall_status'], 'failed Latest does not block healthy Weekly refresh' );
assert_same( 'preserved_previous', $latest_failed['latest']['action'], 'failed Latest refresh preserves prior valid snapshot' );
assert_same( $latest_before_failure, $store->get_snapshot( 'latest' ), 'failed Latest refresh cannot replace or erase LKG data' );
assert_same( 'failure', $store->get_attempt( 'latest' )['candidate_status'], 'failed Latest remains diagnosable' );
assert_same( 'http_error', $store->get_attempt( 'latest' )['reason'], 'Latest failure reason is preserved' );

$weekly_before_failure = $store->get_snapshot( 'weekly_popular' );
$fetch_mode            = 'weekly_ambiguous';
$weekly_failed         = $refresh->run();
assert_same( 'partial', $weekly_failed['overall_status'], 'ambiguous Weekly does not block healthy Latest refresh' );
assert_same( 'preserved_previous', $weekly_failed['weekly_popular']['action'], 'ambiguous Weekly preserves prior valid snapshot' );
assert_same( $weekly_before_failure, $store->get_snapshot( 'weekly_popular' ), 'ambiguous Weekly cannot replace or erase LKG data' );
assert_same( 'ambiguous', $store->get_attempt( 'weekly_popular' )['candidate_status'], 'Weekly ambiguity remains diagnosable' );

/* Persistence-write failures remain distinct and cannot corrupt healthy list state. */
$attempt_fail_values = array();
$attempt_fail_get    = static function ( $name, $default = false ) use ( &$attempt_fail_values ) {
	return array_key_exists( $name, $attempt_fail_values ) ? $attempt_fail_values[ $name ] : $default;
};
$attempt_fail_add = static function ( $name, $value, $deprecated = '', $autoload = null ) use ( &$attempt_fail_values ) {
	if ( Snapshot_Store::OPTION_LATEST_ATTEMPT === $name ) {
		return false;
	}
	$attempt_fail_values[ $name ] = $value;
	return true;
};
$attempt_fail_update = static function ( $name, $value, $autoload = null ) use ( &$attempt_fail_values ) {
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
	static function (): string { return '2026-09-30T12:30:00+00:00'; }
);
$attempt_fail_result = $attempt_fail_run->run();
assert_same( 'degraded', $attempt_fail_result['overall_status'], 'attempt metadata write failure prevents false full-success status' );
assert_same( 'updated', $attempt_fail_result['latest']['action'], 'Latest snapshot may update even when its attempt metadata write fails' );
assert_same( false, $attempt_fail_result['latest']['attempt_recorded'], 'Latest exposes attempt metadata write failure' );
assert_same( 'attempt_write_failed', $attempt_fail_result['latest']['attempt_reason'], 'attempt metadata write failure keeps bounded reason' );
assert_same( true, $attempt_fail_result['weekly_popular']['attempt_recorded'], 'Weekly attempt metadata remains independently writable' );
assert_true( null !== $attempt_fail_store->get_snapshot( 'latest' ), 'attempt metadata failure does not roll back a valid Latest snapshot' );
assert_true( null !== $attempt_fail_store->get_snapshot( 'weekly_popular' ), 'attempt metadata failure does not roll back a valid Weekly snapshot' );

$snapshot_fail_values = array();
$snapshot_fail_get    = static function ( $name, $default = false ) use ( &$snapshot_fail_values ) {
	return array_key_exists( $name, $snapshot_fail_values ) ? $snapshot_fail_values[ $name ] : $default;
};
$snapshot_fail_add = static function ( $name, $value, $deprecated = '', $autoload = null ) use ( &$snapshot_fail_values ) {
	if ( Snapshot_Store::OPTION_LATEST_SNAPSHOT === $name ) {
		return false;
	}
	$snapshot_fail_values[ $name ] = $value;
	return true;
};
$snapshot_fail_update = static function ( $name, $value, $autoload = null ) use ( &$snapshot_fail_values ) {
	if ( Snapshot_Store::OPTION_LATEST_SNAPSHOT === $name ) {
		return false;
	}
	$snapshot_fail_values[ $name ] = $value;
	return true;
};
$snapshot_fail_store  = new Snapshot_Store( $snapshot_fail_get, $snapshot_fail_add, $snapshot_fail_update );
$snapshot_fail_run    = new Refresh_Service(
	$refresh_preview,
	$snapshot_fail_store,
	static function (): string { return '2026-09-30T12:31:00+00:00'; }
);
$snapshot_fail_result = $snapshot_fail_run->run();
assert_same( 'partial', $snapshot_fail_result['overall_status'], 'Latest snapshot write failure leaves independently healthy Weekly outcome partial' );
assert_same( 'no_valid_snapshot_available', $snapshot_fail_result['latest']['action'], 'failed first Latest snapshot write cannot claim updated state' );
assert_same( 'snapshot_write_failed', $snapshot_fail_result['latest']['reason'], 'snapshot write failure keeps bounded reason' );
assert_same( true, $snapshot_fail_result['latest']['attempt_recorded'], 'snapshot write failure can still be recorded diagnostically' );
assert_same( 'updated', $snapshot_fail_result['weekly_popular']['action'], 'Weekly snapshot remains independently updatable when Latest write fails' );

$summary_fail_values = array();
$summary_fail_get    = static function ( $name, $default = false ) use ( &$summary_fail_values ) {
	return array_key_exists( $name, $summary_fail_values ) ? $summary_fail_values[ $name ] : $default;
};
$summary_fail_add = static function ( $name, $value, $deprecated = '', $autoload = null ) use ( &$summary_fail_values ) {
	if ( Snapshot_Store::OPTION_LAST_CRON_RUN === $name ) {
		return false;
	}
	$summary_fail_values[ $name ] = $value;
	return true;
};
$summary_fail_update = static function ( $name, $value, $autoload = null ) use ( &$summary_fail_values ) {
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
	static function (): string { return '2026-09-30T12:32:00+00:00'; },
	static function (): string { return 'cron-run-summary-write-failure'; }
);
$summary_fail_result = $summary_fail_run->run( 'cron' );
assert_same( false, $summary_fail_result['run_summary_recorded'], 'run-summary write failure is exposed' );
assert_same( 'run_summary_write_failed', $summary_fail_result['run_summary_reason'], 'run-summary write failure keeps bounded reason' );
assert_true( null !== $summary_fail_store->get_snapshot( 'latest' ), 'run-summary failure does not roll back Latest snapshot' );
assert_true( null !== $summary_fail_store->get_snapshot( 'weekly_popular' ), 'run-summary failure does not roll back Weekly snapshot' );
assert_same( null, $summary_fail_store->get_run_summary( 'cron' ), 'failed Cron summary persistence cannot fabricate Cron proof' );

/* Manual/Cron orchestration and diagnostic reads preserve established boundaries. */
$GLOBALS['ksh_test_actions'] = array();
$schedule_next = false;
$scheduled_at  = null;
$scheduler     = new Scheduler(
	static function () use ( &$schedule_next ) { return $schedule_next; },
	static function ( $timestamp, $recurrence, $hook ) use ( &$schedule_next, &$scheduled_at ) {
		$scheduled_at  = array( $timestamp, $recurrence, $hook );
		$schedule_next = $timestamp;
		return true;
	},
	static function () use ( &$schedule_next ) { $schedule_next = false; return 1; },
	static function () { return 1000; },
	static function () use ( &$schedule_next ) { return false !== $schedule_next ? 'daily' : false; }
);
$scheduler->register( $refresh );
assert_true( isset( $GLOBALS['ksh_test_actions'][ Scheduler::HOOK ][0] ), 'scheduler registers one owned Cron callback' );
$cron_callback = $GLOBALS['ksh_test_actions'][ Scheduler::HOOK ][0];

$fetch_mode = 'replace';
$admin      = new Admin_Page( $refresh_preview, $refresh, $store, $scheduler );
$before     = $fetch_count;
$manual_run = $admin->run_manual_refresh();
assert_same( $before + 2, $fetch_count, 'manual refresh retains independent two-list acquisition' );
assert_same( 'manual', $manual_run['trigger'], 'manual refresh records origin' );
assert_true( $manual_run['run_summary_recorded'], 'manual refresh persists bounded run summary' );
assert_same( $manual_run['run_id'], $store->get_run_summary( 'manual' )['run_id'], 'manual summary correlates to run id' );

$before   = $fetch_count;
$cron_run = call_user_func( $cron_callback );
assert_same( $before + 2, $fetch_count, 'Cron refresh retains independent two-list acquisition' );
assert_same( 'cron', $cron_run['trigger'], 'Cron refresh records origin' );
assert_true( $cron_run['run_summary_recorded'], 'Cron refresh persists bounded run summary' );
assert_same( $cron_run['run_id'], $store->get_run_summary( 'cron' )['run_id'], 'Cron summary correlates to run id' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LAST_MANUAL_RUN ], 'manual run summary remains non-autoloaded' );
assert_same( false, $option_autoload[ Snapshot_Store::OPTION_LAST_CRON_RUN ], 'Cron run summary remains non-autoloaded' );

$diagnostic_schedule_mutations = 0;
$diagnostic_scheduler = new Scheduler(
	static function () { return 1730000000; },
	static function () use ( &$diagnostic_schedule_mutations ) { ++$diagnostic_schedule_mutations; return true; },
	static function () use ( &$diagnostic_schedule_mutations ) { ++$diagnostic_schedule_mutations; return 1; },
	static function () { return 1000; },
	static function () { return 'daily'; }
);
$diagnostic = new Diagnostic_Report(
	$store,
	$diagnostic_scheduler,
	static function (): string { return '2026-09-30T13:00:00+00:00'; }
);
$state_before_report = $option_values;
$fetch_before_report = $fetch_count;
$report              = $diagnostic->build();
assert_same( $fetch_before_report, $fetch_count, 'diagnostic build performs zero remote acquisitions' );
assert_same( $state_before_report, $option_values, 'diagnostic build performs zero option mutations' );
assert_same( 0, $diagnostic_schedule_mutations, 'diagnostic build performs zero schedule mutations' );
assert_same( Plugin::VERSION, $report['plugin']['version'], 'diagnostic reports synchronized plugin version' );
assert_same( true, $report['scheduler']['event_registered'], 'diagnostic reports scheduler registration independently' );
assert_same( true, $report['scheduler']['cron_execution_observed'], 'persisted Cron summary remains observable' );
assert_same( true, $report['lists']['latest']['current_snapshot']['valid'], 'diagnostic validates current Latest snapshot locally' );
assert_same( $store->get_snapshot( 'latest' )['count'], $report['lists']['latest']['current_snapshot']['count'], 'diagnostic exposes full stored count, not public display cap' );
$json = wp_json_encode( $report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
assert_true( is_array( json_decode( $json, true ) ), 'diagnostic remains valid JSON' );
assert_same( false, false !== strpos( $json, '<html' ), 'diagnostic never exports raw remote HTML' );

/* Diagnostic export stays read-only and retains capability/nonce protection. */
$_SERVER['REQUEST_METHOD']             = 'GET';
$GLOBALS['ksh_test_current_user_can'] = true;
$GLOBALS['ksh_test_capability']       = null;
$GLOBALS['ksh_test_nonce_action']     = null;
$download_admin = new Admin_Page(
	$refresh_preview,
	$refresh,
	$store,
	$diagnostic_scheduler,
	$diagnostic,
	static function () { return null; }
);
$download_state_before    = $option_values;
$download_fetch_before    = $fetch_count;
$download_schedule_before = $diagnostic_schedule_mutations;
ob_start();
$download_admin->download_diagnostic();
$download_json = ob_get_clean();
assert_same( Admin_Page::CAPABILITY, $GLOBALS['ksh_test_capability'], 'diagnostic download requires administrator capability' );
assert_same( Admin_Page::EXPORT_NONCE, $GLOBALS['ksh_test_nonce_action'], 'diagnostic download verifies dedicated nonce' );
assert_true( is_array( json_decode( $download_json, true ) ), 'diagnostic download emits valid JSON' );
assert_same( $download_fetch_before, $fetch_count, 'diagnostic download performs zero remote acquisitions' );
assert_same( $download_state_before, $option_values, 'diagnostic download performs zero local-state mutations' );
assert_same( $download_schedule_before, $diagnostic_schedule_mutations, 'diagnostic download performs zero schedule mutations' );

$GLOBALS['ksh_test_current_user_can'] = false;
$GLOBALS['ksh_test_nonce_action']     = null;
$blocked = false;
try {
	$download_admin->download_diagnostic();
} catch ( RuntimeException $exception ) {
	$blocked = true;
}
assert_true( $blocked, 'unauthorized diagnostic download is blocked' );
assert_same( null, $GLOBALS['ksh_test_nonce_action'], 'unauthorized diagnostic download is rejected before nonce processing' );
$GLOBALS['ksh_test_current_user_can'] = true;

/* Public renderer caps presentation only and remains remote-free/read-only. */
$public_date_marker = 'KSH-DATE-CONTEXT-PRIVATE-1405-07-08';
$latest_items       = array();
$weekly_items       = array();
for ( $i = 1; $i <= 21; ++$i ) {
	$latest_items[] = array(
		'title'        => 2 === $i ? '<script>alert("x")</script>' : 'تازه ' . $i,
		'url'          => 'https://www.kanoon.ir/Article/' . ( 970000 + $i ),
		'date_context' => 1 === $i ? $public_date_marker : '',
	);
}
for ( $i = 1; $i <= 17; ++$i ) {
	$weekly_items[] = array(
		'title'        => 'محبوب ' . $i,
		'url'          => 'https://www.kanoon.ir/Article/' . ( 980000 + $i ),
		'date_context' => '',
	);
}
$frontend_values = array(
	Snapshot_Store::OPTION_LATEST_SNAPSHOT => array(
		'schema_version' => Snapshot_Store::SCHEMA_VERSION,
		'source'         => 'latest',
		'source_url'     => Source_Config::LATEST_URL,
		'items'          => $latest_items,
		'count'          => count( $latest_items ),
		'updated_at'     => '2026-09-30T13:10:00+00:00',
		'date_context'   => '',
	),
	Snapshot_Store::OPTION_WEEKLY_SNAPSHOT => array(
		'schema_version' => Snapshot_Store::SCHEMA_VERSION,
		'source'         => 'weekly_popular',
		'source_url'     => Source_Config::WEEKLY_URL,
		'items'          => $weekly_items,
		'count'          => count( $weekly_items ),
		'updated_at'     => '2026-09-30T13:10:00+00:00',
		'date_context'   => '',
	),
);
$frontend_reads  = 0;
$frontend_writes = 0;
$frontend_get    = static function ( $name, $default = false ) use ( &$frontend_values, &$frontend_reads ) {
	++$frontend_reads;
	return array_key_exists( $name, $frontend_values ) ? $frontend_values[ $name ] : $default;
};
$frontend_reject_write = static function () use ( &$frontend_writes ) {
	++$frontend_writes;
	throw new RuntimeException( 'frontend rendering must remain read-only' );
};
$frontend_store  = new Snapshot_Store( $frontend_get, $frontend_reject_write, $frontend_reject_write );
$renderer        = new Frontend_Renderer( $frontend_store );
$frontend_before = $frontend_values;
$remote_before   = $fetch_count;
$cron_before     = $GLOBALS['ksh_test_cron'];
$esc_url_before  = $GLOBALS['ksh_test_esc_url_calls'];
$frontend_html   = $renderer->render();

assert_same( $remote_before, $fetch_count, 'frontend rendering performs zero remote acquisitions' );
assert_same( $frontend_before, $frontend_values, 'frontend rendering leaves complete snapshots intact' );
assert_same( 0, $frontend_writes, 'frontend rendering performs zero option writes' );
assert_same( $cron_before, $GLOBALS['ksh_test_cron'], 'frontend rendering performs zero schedule mutations' );
assert_true( $frontend_reads >= 2, 'frontend reads only local snapshot state' );
assert_same( 21, $frontend_values[ Snapshot_Store::OPTION_LATEST_SNAPSHOT ]['count'], 'Latest stored count remains above display cap' );
assert_same( 17, $frontend_values[ Snapshot_Store::OPTION_WEEKLY_SNAPSHOT ]['count'], 'Weekly stored count remains above display cap' );

$rendered_links = array();
preg_match_all( '/<a class="ksh-kanoon-articles__link" href="([^"]+)">/', $frontend_html, $rendered_links );
$expected_links = array_merge(
	array_slice( array_column( $latest_items, 'url' ), 0, Frontend_Renderer::DISPLAY_LIMIT ),
	array_slice( array_column( $weekly_items, 'url' ), 0, Frontend_Renderer::DISPLAY_LIMIT )
);
assert_same( 30, count( $rendered_links[1] ), 'public HTML emits no more than 15 links per list' );
assert_same( $expected_links, $rendered_links[1], 'public renderer preserves source order while displaying only first 15 per list' );
assert_same( 30, $GLOBALS['ksh_test_esc_url_calls'] - $esc_url_before, 'every rendered URL passes through esc_url' );
assert_same( false, false !== strpos( $frontend_html, 'https://www.kanoon.ir/Article/970016' ), 'Latest item 16 is not publicly rendered' );
assert_same( false, false !== strpos( $frontend_html, 'https://www.kanoon.ir/Article/980016' ), 'Weekly item 16 is not publicly rendered' );
assert_true( false !== strpos( $frontend_html, '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;' ), 'article titles remain escaped' );
assert_same( false, false !== strpos( $frontend_html, '<script>alert("x")</script>' ), 'raw title HTML is never emitted' );
assert_same( false, false !== strpos( $frontend_html, $public_date_marker ), 'public HTML does not expose date_context' );
assert_same( false, false !== strpos( $frontend_html, 'ksh-kanoon-articles__meta' ), 'public HTML contains no date metadata markup' );
assert_true( false !== strpos( $frontend_html, '<section class="ksh-kanoon-articles" dir="rtl" lang="fa">' ), 'frontend preserves explicit RTL semantics' );

$frontend_diagnostic = new Diagnostic_Report(
	$frontend_store,
	$diagnostic_scheduler,
	static function (): string { return '2026-09-30T13:20:00+00:00'; }
);
$frontend_report_json = wp_json_encode( $frontend_diagnostic->build(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
assert_true( false !== strpos( $frontend_report_json, $public_date_marker ), 'diagnostic still exposes stored date_context metadata' );

$weekly_only_values = array( Snapshot_Store::OPTION_WEEKLY_SNAPSHOT => $frontend_values[ Snapshot_Store::OPTION_WEEKLY_SNAPSHOT ] );
$weekly_only_get    = static function ( $name, $default = false ) use ( &$weekly_only_values ) {
	return array_key_exists( $name, $weekly_only_values ) ? $weekly_only_values[ $name ] : $default;
};
$weekly_only_store = new Snapshot_Store( $weekly_only_get, $frontend_reject_write, $frontend_reject_write );
$weekly_only_html  = ( new Frontend_Renderer( $weekly_only_store ) )->render();
assert_true( false !== strpos( $weekly_only_html, '>پربازدید هفته</h3>' ), 'one available list still renders cleanly' );
assert_same( false, false !== strpos( $weekly_only_html, '>تازه‌ها</h3>' ), 'missing Latest is not substituted from Weekly' );
assert_true( false !== strpos( $weekly_only_html, 'ksh-kanoon-articles__grid--single' ), 'single-list soft failure layout remains valid' );

$missing_get   = static function ( $name, $default = false ) { return $default; };
$missing_store = new Snapshot_Store( $missing_get, $frontend_reject_write, $frontend_reject_write );
assert_same( '', ( new Frontend_Renderer( $missing_store ) )->render(), 'both missing lists fail softly without a public error panel' );

$malformed_values = array(
	Snapshot_Store::OPTION_LATEST_SNAPSHOT => array(
		'schema_version' => Snapshot_Store::SCHEMA_VERSION,
		'source'         => 'latest',
		'items'          => array( array( 'title' => 'خراب', 'url' => 'javascript:alert(1)', 'date_context' => '' ) ),
		'count'          => 1,
		'updated_at'     => '2026-09-30T13:10:00+00:00',
		'date_context'   => '',
	),
);
$malformed_get = static function ( $name, $default = false ) use ( &$malformed_values ) {
	return array_key_exists( $name, $malformed_values ) ? $malformed_values[ $name ] : $default;
};
$malformed_store = new Snapshot_Store( $malformed_get, $frontend_reject_write, $frontend_reject_write );
assert_same( '', ( new Frontend_Renderer( $malformed_store ) )->render(), 'malformed local state fails closed without remote fallback' );

/* Shortcode, CSS ownership, responsive RTL, and font-inheritance boundaries. */
$GLOBALS['ksh_test_shortcodes'] = array();
$GLOBALS['ksh_test_styles']     = array();
$shortcode                      = new Shortcode( $renderer );
$shortcode->register();
assert_true( isset( $GLOBALS['ksh_test_shortcodes'][ Shortcode::TAG ] ), 'existing shortcode remains registered' );
assert_same( 'ksh_kanoon_articles', Shortcode::TAG, 'shortcode syntax remains backward compatible' );
assert_true( isset( $GLOBALS['ksh_test_actions']['wp_enqueue_scripts'] ), 'frontend stylesheet keeps native enqueue lifecycle' );
$shortcode->enqueue_styles();
assert_same( Plugin::VERSION, $GLOBALS['ksh_test_styles'][ Shortcode::STYLE_HANDLE ]['ver'], 'frontend stylesheet cache identity follows plugin version' );
assert_same( $frontend_html, call_user_func( $GLOBALS['ksh_test_shortcodes'][ Shortcode::TAG ] ), 'shortcode output delegates exactly to renderer' );

$frontend_source  = file_get_contents( __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-frontend-renderer.php' );
$shortcode_source = file_get_contents( __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-shortcode.php' );
foreach ( array( 'Remote_Fetcher', 'Preview_Service', 'Refresh_Service', 'wp_remote_', 'save_snapshot', 'save_attempt', 'save_run_summary', 'ensure_scheduled' ) as $forbidden_frontend_dependency ) {
	assert_same( false, false !== strpos( $frontend_source . $shortcode_source, $forbidden_frontend_dependency ), 'frontend remains detached from remote/mutation dependency: ' . $forbidden_frontend_dependency );
}
assert_true( false !== strpos( $frontend_source, 'get_snapshot' ), 'renderer still depends on local snapshot read boundary' );
assert_true( false !== strpos( $frontend_source, 'DISPLAY_LIMIT = 15' ), 'presentation limit is explicit and local to renderer' );

$frontend_css = file_get_contents( __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/assets/css/frontend.css' );
assert_true( is_string( $frontend_css ) && '' !== $frontend_css, 'frontend CSS exists' );
assert_true( false !== strpos( $frontend_css, 'font-size: 0.875rem;' ), 'article links use approved compact desktop scale' );
assert_true( false !== strpos( $frontend_css, 'line-height: 1.6;' ), 'article typography keeps proportionate readable line-height' );
assert_true( false !== strpos( $frontend_css, 'padding-block: 0.55rem;' ), 'article rows use tighter scan-friendly spacing' );
assert_same( false, false !== stripos( $frontend_css, '@font-face' ), 'KSH CSS does not deliver fonts' );
assert_same( 0, preg_match( '/\bfont-family\s*:/i', $frontend_css ), 'KSH CSS does not take over font-family ownership' );
assert_same( 0, preg_match( '/["\']Vazir["\']/i', $frontend_css ), 'KSH CSS does not invent a Vazir family alias' );
assert_true( false !== strpos( $frontend_css, 'font: inherit;' ), 'KSH typography inherits the site-delivered family when available' );
assert_true( false !== strpos( $frontend_css, 'align-items: start;' ), 'desktop panels retain natural heights' );
assert_true( false !== strpos( $frontend_css, '@media (max-width: 48rem)' ), 'responsive breakpoint remains bounded' );
assert_true( false !== strpos( $frontend_css, 'grid-template-columns: 1fr;' ), 'mobile remains single-column' );
assert_true( false !== strpos( $frontend_css, ':focus-visible' ), 'keyboard focus remains visible' );
assert_same( 0, preg_match( '/^\s*(?:width|min-width|max-width)\s*:/m', $frontend_css ), 'frontend CSS introduces no fixed physical width declarations' );
foreach ( preg_split( '/\R/', $frontend_css ) as $css_line ) {
	$css_line = trim( $css_line );
	if ( '' === $css_line || '{' !== substr( $css_line, -1 ) || 0 === strpos( $css_line, '@' ) ) {
		continue;
	}
	assert_same( 0, strpos( $css_line, '.ksh-kanoon-articles' ), 'every concrete CSS selector stays scoped below module root' );
}

/* Schedule lifecycle remains remote-free and snapshots survive deactivation. */
$before = $fetch_count;
assert_true( $scheduler->ensure_scheduled(), 'missing schedule is registered successfully' );
assert_same( array( 4600, 'daily', Scheduler::HOOK ), $scheduled_at, 'self-healing schedule retains daily named hook' );
assert_same( $before, $fetch_count, 'schedule registration performs no remote acquisition' );

$GLOBALS['ksh_test_cron'] = array();
$before                   = $fetch_count;
Plugin::activate();
assert_true( false !== wp_next_scheduled( Scheduler::HOOK ), 'activation schedules owned daily event' );
assert_same( $before, $fetch_count, 'activation performs no remote acquisition' );

$snapshot_before_deactivate = $store->get_snapshot( 'latest' );
Plugin::deactivate();
assert_same( false, wp_next_scheduled( Scheduler::HOOK ), 'deactivation clears scheduled event' );
assert_same( $snapshot_before_deactivate, $store->get_snapshot( 'latest' ), 'deactivation preserves valid snapshots' );

$GLOBALS['ksh_test_actions']    = array();
$GLOBALS['ksh_test_shortcodes'] = array();
$before                         = $fetch_count;
Plugin::boot();
assert_true( isset( $GLOBALS['ksh_test_actions'][ 'admin_post_' . Admin_Page::EXPORT_ACTION ][0] ), 'plugin boot keeps authenticated diagnostic download handler' );
assert_true( isset( $GLOBALS['ksh_test_shortcodes'][ Shortcode::TAG ] ), 'plugin boot keeps public shortcode' );
assert_true( isset( $GLOBALS['ksh_test_actions']['wp_enqueue_scripts'][0] ), 'plugin boot keeps frontend style enqueue hook' );
assert_same( $before, $fetch_count, 'plugin boot performs no remote acquisition' );

$plugin_header = file_get_contents( __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/ksh-kanoon-articles.php' );
assert_true( false !== strpos( $plugin_header, 'Version: ' . Plugin::VERSION ), 'plugin header and runtime version remain synchronized' );

echo 'TEST_PASS assertions=' . $assertions . PHP_EOL;
