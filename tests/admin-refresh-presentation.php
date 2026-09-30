<?php
/**
 * Deterministic Admin_Page Manual Refresh presentation regressions.
 *
 * Exercises the actual protected render boundary for blocked qualification state
 * and for a genuine executed refresh whose attempt metadata persistence fails.
 */

declare(strict_types=1);

use KSH\KanoonArticles\Acquisition_Qualification;
use KSH\KanoonArticles\Admin_Page;
use KSH\KanoonArticles\Article_Parser;
use KSH\KanoonArticles\Preview_Service;
use KSH\KanoonArticles\Refresh_Service;
use KSH\KanoonArticles\Scheduler;
use KSH\KanoonArticles\Snapshot_Store;
use KSH\KanoonArticles\Source_Config;

$GLOBALS['ksh_admin_ui_current_user_can'] = true;
$GLOBALS['ksh_admin_ui_nonce_action']     = null;

if ( ! function_exists( '__' ) ) {
	function __( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'esc_html__' ) ) {
	function esc_html__( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_html' ) ) {
	function esc_html( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}
if ( ! function_exists( 'esc_url' ) ) {
	function esc_url( $text ) {
		return htmlspecialchars( (string) $text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8' );
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability = '' ) {
		return ! empty( $GLOBALS['ksh_admin_ui_current_user_can'] );
	}
}
if ( ! function_exists( 'wp_die' ) ) {
	function wp_die( $message ) {
		throw new RuntimeException( (string) $message );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $value ) {
		return (string) $value;
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $value ) {
		return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $value ) );
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $value ) {
		return $value;
	}
}
if ( ! function_exists( 'check_admin_referer' ) ) {
	function check_admin_referer( $action = -1 ) {
		$GLOBALS['ksh_admin_ui_nonce_action'] = $action;
		return 1;
	}
}
if ( ! function_exists( 'wp_nonce_field' ) ) {
	function wp_nonce_field() {
		echo '<input type="hidden" name="_wpnonce" value="test">';
	}
}
if ( ! function_exists( 'submit_button' ) ) {
	function submit_button( $text ) {
		echo '<button type="submit">' . esc_html( $text ) . '</button>';
	}
}
if ( ! function_exists( 'add_management_page' ) ) {
	function add_management_page() {
		return 'tools_page_ksh';
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path = '' ) {
		return 'https://example.test/wp-admin/' . ltrim( (string) $path, '/' );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $value, $flags = 0 ) {
		return json_encode( $value, $flags );
	}
}
if ( ! function_exists( 'wp_timezone_string' ) ) {
	function wp_timezone_string() {
		return 'Asia/Tehran';
	}
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

if ( ! class_exists( 'DOMDocument' ) ) {
	fwrite( STDERR, "ADMIN_REFRESH_PRESENTATION_ENVIRONMENT_UNAVAILABLE: ext-dom is required.\n" );
	exit( 2 );
}

$assertions = 0;

function ksh_admin_ui_assert_same( $expected, $actual, string $message ): void {
	global $assertions;
	++$assertions;
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function ksh_admin_ui_assert_true( $actual, string $message ): void {
	ksh_admin_ui_assert_same( true, (bool) $actual, $message );
}

function ksh_admin_ui_snapshot( string $source, int $id ): array {
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
		'updated_at'     => '2026-09-30T12:00:00+00:00',
		'date_context'   => '',
	);
}

function ksh_admin_ui_homepage(): string {
	return '<html><body>' .
		'<a href="#latest">تازه ها</a><a href="#weekly">پربازدید هفته</a><a href="#monthly">پربازدید ماه</a>' .
		'<div id="latest"><a href="/Article/991001">تازه یک</a></div>' .
		'<div id="weekly"><a href="/Article/992001">هفته یک</a></div>' .
		'<div id="monthly"><a href="/Article/993001">ماه یک</a></div>' .
		'</body></html>';
}

function ksh_admin_ui_scheduler( Acquisition_Qualification $qualification ): Scheduler {
	return new Scheduler(
		static function () {
			return false;
		},
		static function () {
			return true;
		},
		static function () {
			return 0;
		},
		static function () {
			return 1000;
		},
		static function () {
			return false;
		},
		$qualification
	);
}

/* Unqualified Manual Refresh must render a truthful no-execution state. */
$blocked_fetch_count = 0;
$blocked_fetch       = static function ( string $url ) use ( &$blocked_fetch_count ): array {
	++$blocked_fetch_count;
	return array(
		'ok'        => true,
		'http_code' => 200,
		'body'      => ksh_admin_ui_homepage(),
		'reason'    => '',
	);
};
$blocked_preview = new Preview_Service( $blocked_fetch, new Article_Parser() );
$unqualified     = new Acquisition_Qualification(
	$blocked_preview,
	static function ( $name, $default = false ) {
		return $default;
	},
	static function () {
		return false;
	},
	static function () {
		return false;
	}
);
$blocked_state   = array(
	Snapshot_Store::OPTION_LATEST_SNAPSHOT => ksh_admin_ui_snapshot( 'latest', 880001 ),
	Snapshot_Store::OPTION_WEEKLY_SNAPSHOT => ksh_admin_ui_snapshot( 'weekly_popular', 880002 ),
	Snapshot_Store::OPTION_LAST_MANUAL_RUN => array( 'sentinel' => 'manual-before' ),
	Snapshot_Store::OPTION_LAST_CRON_RUN   => array( 'sentinel' => 'cron-before' ),
);
$blocked_writes  = 0;
$blocked_get     = static function ( $name, $default = false ) use ( &$blocked_state ) {
	return array_key_exists( $name, $blocked_state ) ? $blocked_state[ $name ] : $default;
};
$blocked_add = static function ( $name, $value ) use ( &$blocked_state, &$blocked_writes ) {
	++$blocked_writes;
	$blocked_state[ $name ] = $value;
	return true;
};
$blocked_update = static function ( $name, $value ) use ( &$blocked_state, &$blocked_writes ) {
	++$blocked_writes;
	$blocked_state[ $name ] = $value;
	return true;
};
$blocked_store  = new Snapshot_Store( $blocked_get, $blocked_add, $blocked_update );
$blocked_refresh = new Refresh_Service(
	$blocked_preview,
	$blocked_store,
	static function (): string {
		return '2026-09-30T18:00:00+00:00';
	},
	static function (): string {
		return 'blocked-admin-run';
	},
	$unqualified
);
$blocked_admin = new Admin_Page(
	$blocked_preview,
	$blocked_refresh,
	$blocked_store,
	ksh_admin_ui_scheduler( $unqualified )
);

$_SERVER['REQUEST_METHOD']             = 'POST';
$_POST['ksh_action']                   = 'refresh';
$GLOBALS['ksh_admin_ui_nonce_action'] = null;
$blocked_before_state                  = $blocked_state;
$blocked_before_fetch                  = $blocked_fetch_count;
ob_start();
$blocked_admin->render();
$blocked_html = ob_get_clean();

ksh_admin_ui_assert_same( Admin_Page::REFRESH_NONCE, $GLOBALS['ksh_admin_ui_nonce_action'], 'blocked Manual Refresh still uses the protected Admin_Page action' );
ksh_admin_ui_assert_same( $blocked_before_fetch, $blocked_fetch_count, 'blocked Admin_Page Manual Refresh performs zero remote acquisition' );
ksh_admin_ui_assert_same( 0, $blocked_writes, 'blocked Admin_Page Manual Refresh performs zero snapshot/attempt/run-summary writes' );
ksh_admin_ui_assert_same( $blocked_before_state, $blocked_state, 'blocked Admin_Page Manual Refresh preserves all existing local state' );
ksh_admin_ui_assert_true( false !== strpos( $blocked_html, 'Refresh عمداً اجرا نشد' ), 'blocked overall state is presented explicitly rather than as generic failure' );
ksh_admin_ui_assert_true( false !== strpos( $blocked_html, 'قرارداد دریافت فعلی هنوز تأیید نشده است' ), 'blocked output explains the unqualified acquisition contract' );
ksh_admin_ui_assert_true( false !== strpos( $blocked_html, 'پیش از تماس با کانون و هر نوشتن عملیاتی متوقف شد' ), 'blocked output states that no remote contact or operational write occurred' );
ksh_admin_ui_assert_true( false !== strpos( $blocked_html, 'Tools → تأیید دریافت مقاله‌های کانون' ), 'blocked output directs Owner to the existing qualification action' );
ksh_admin_ui_assert_true( false !== strpos( $blocked_html, 'qualification_required یعنی Refresh پیش از شروع acquisition متوقف شد' ), 'per-list blocked attempt state is distinguished from persistence failure' );
ksh_admin_ui_assert_same( false, false !== strpos( $blocked_html, 'ناموفق؛ Snapshot action بالا معتبر است اما این اجرای عملیاتی به‌طور کامل ثبت نشد.' ), 'blocked attempt_recorded=false is not described as attempt-write failure' );
ksh_admin_ui_assert_same( false, false !== strpos( $blocked_html, 'Refresh دستی یک درخواست واقعی به منابع کانون انجام می‌دهد' ), 'static Admin copy no longer claims every Manual Refresh executes a remote request' );

/* Positive control: a qualified executed refresh with real attempt-write failure keeps the existing warning. */
$qualified_fetch_count = 0;
$qualified_fetch       = static function ( string $url ) use ( &$qualified_fetch_count ): array {
	++$qualified_fetch_count;
	return array(
		'ok'        => true,
		'http_code' => 200,
		'body'      => ksh_admin_ui_homepage(),
		'reason'    => '',
	);
};
$qualified_preview = new Preview_Service( $qualified_fetch, new Article_Parser() );
$qualified_values  = array(
	Acquisition_Qualification::OPTION_STATE => array(
		'schema_version' => Acquisition_Qualification::SCHEMA_VERSION,
		'status'         => 'qualified',
		'contract_id'    => Source_Config::ACQUISITION_CONTRACT_ID,
		'qualified_at'   => '2026-09-30T17:59:00+00:00',
	),
);
$qualified_get = static function ( $name, $default = false ) use ( &$qualified_values ) {
	return array_key_exists( $name, $qualified_values ) ? $qualified_values[ $name ] : $default;
};
$qualified = new Acquisition_Qualification(
	$qualified_preview,
	$qualified_get,
	static function () {
		return false;
	},
	static function () {
		return false;
	}
);
$degraded_state          = array();
$degraded_attempt_writes = 0;
$degraded_get            = static function ( $name, $default = false ) use ( &$degraded_state ) {
	return array_key_exists( $name, $degraded_state ) ? $degraded_state[ $name ] : $default;
};
$degraded_add = static function ( $name, $value ) use ( &$degraded_state, &$degraded_attempt_writes ) {
	if ( Snapshot_Store::OPTION_LATEST_ATTEMPT === $name || Snapshot_Store::OPTION_WEEKLY_ATTEMPT === $name ) {
		++$degraded_attempt_writes;
		return false;
	}
	$degraded_state[ $name ] = $value;
	return true;
};
$degraded_update = static function ( $name, $value ) use ( &$degraded_state, &$degraded_attempt_writes ) {
	if ( Snapshot_Store::OPTION_LATEST_ATTEMPT === $name || Snapshot_Store::OPTION_WEEKLY_ATTEMPT === $name ) {
		++$degraded_attempt_writes;
		return false;
	}
	$degraded_state[ $name ] = $value;
	return true;
};
$degraded_store = new Snapshot_Store( $degraded_get, $degraded_add, $degraded_update );
$degraded_refresh = new Refresh_Service(
	$qualified_preview,
	$degraded_store,
	static function (): string {
		return '2026-09-30T18:01:00+00:00';
	},
	static function (): string {
		return 'degraded-admin-run';
	},
	$qualified
);
$degraded_admin = new Admin_Page(
	$qualified_preview,
	$degraded_refresh,
	$degraded_store,
	ksh_admin_ui_scheduler( $qualified )
);

$_SERVER['REQUEST_METHOD']             = 'POST';
$_POST['ksh_action']                   = 'refresh';
$GLOBALS['ksh_admin_ui_nonce_action'] = null;
ob_start();
$degraded_admin->render();
$degraded_html = ob_get_clean();

ksh_admin_ui_assert_same( 2, $qualified_fetch_count, 'qualified Manual Refresh still performs independent two-list acquisition' );
ksh_admin_ui_assert_same( 2, $degraded_attempt_writes, 'positive control reaches genuine per-list attempt persistence writes' );
ksh_admin_ui_assert_true( false !== strpos( $degraded_html, 'Refresh ناقص عملیاتی' ), 'genuine attempt persistence failure remains degraded rather than blocked' );
ksh_admin_ui_assert_true( false !== strpos( $degraded_html, 'ناموفق؛ Snapshot action بالا معتبر است اما این اجرای عملیاتی به‌طور کامل ثبت نشد.' ), 'genuine attempt-write failure preserves the existing operational warning' );
ksh_admin_ui_assert_true( false !== strpos( $degraded_html, 'attempt_write_failed' ), 'genuine attempt-write failure keeps its bounded reason' );
ksh_admin_ui_assert_same( false, false !== strpos( $degraded_html, 'Refresh عمداً اجرا نشد' ), 'qualified executed refresh is not mislabeled as blocked' );
ksh_admin_ui_assert_true( isset( $degraded_state[ Snapshot_Store::OPTION_LATEST_SNAPSHOT ] ), 'qualified degraded refresh still persists valid Latest snapshot' );
ksh_admin_ui_assert_true( isset( $degraded_state[ Snapshot_Store::OPTION_WEEKLY_SNAPSHOT ] ), 'qualified degraded refresh still persists valid Weekly snapshot' );
ksh_admin_ui_assert_true( isset( $degraded_state[ Snapshot_Store::OPTION_LAST_MANUAL_RUN ] ), 'qualified degraded refresh still persists bounded Manual run summary' );

fwrite( STDOUT, 'ADMIN_REFRESH_PRESENTATION_PASS assertions=' . $assertions . "\n" );
