<?php
/**
 * Canonical refresh admission bypass regression.
 *
 * @package KSH_Web_Tests
 */

declare(strict_types=1);

use KSH\KanoonArticles\Acquisition_Qualification;
use KSH\KanoonArticles\Article_Parser;
use KSH\KanoonArticles\Preview_Service;
use KSH\KanoonArticles\Refresh_Service;
use KSH\KanoonArticles\Snapshot_Store;
use KSH\KanoonArticles\Source_Config;

require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-source-config.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-article-parser.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-preview-service.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-acquisition-qualification.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-snapshot-store.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-refresh-service.php';

$assertions = 0;

function b_assert_same( $expected, $actual, string $message ): void {
	global $assertions;
	++$assertions;
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

$fetch_count = 0;
$fetch       = static function ( string $url ) use ( &$fetch_count ): array {
	++$fetch_count;
	return array(
		'ok'        => true,
		'http_code' => 200,
		'body'      => '<a href="#latest">تازه ها</a><a href="#weekly">پربازدید هفته</a><a href="#monthly">پربازدید ماه</a><div id="latest"><a href="/Article/910001">تازه</a></div><div id="weekly"><a href="/Article/920001">هفته</a></div><div id="monthly"><a href="/Article/930001">ماه</a></div>',
		'reason'    => '',
	);
};
$preview = new Preview_Service( $fetch, new Article_Parser() );

$qualification_values = array();
$qualification = new Acquisition_Qualification(
	$preview,
	static function ( $name, $default = false ) use ( &$qualification_values ) {
		return array_key_exists( $name, $qualification_values ) ? $qualification_values[ $name ] : $default;
	},
	static function ( $name, $value ) use ( &$qualification_values ) {
		$qualification_values[ $name ] = $value;
		return true;
	},
	static function ( $name, $value ) use ( &$qualification_values ) {
		$qualification_values[ $name ] = $value;
		return true;
	}
);

$local_values = array(
	Snapshot_Store::OPTION_LATEST_SNAPSHOT => array(
		'schema_version' => Snapshot_Store::SCHEMA_VERSION,
		'source'         => 'latest',
		'source_url'     => Source_Config::HOMEPAGE_URL,
		'items'          => array(
			array(
				'title'        => 'LKG Latest',
				'url'          => 'https://www.kanoon.ir/Article/800001',
				'date_context' => '',
			),
		),
		'count'          => 1,
		'updated_at'     => '2026-09-29T12:00:00+00:00',
		'date_context'   => '',
	),
	Snapshot_Store::OPTION_WEEKLY_SNAPSHOT => array(
		'schema_version' => Snapshot_Store::SCHEMA_VERSION,
		'source'         => 'weekly_popular',
		'source_url'     => Source_Config::HOMEPAGE_URL,
		'items'          => array(
			array(
				'title'        => 'LKG Weekly',
				'url'          => 'https://www.kanoon.ir/Article/800002',
				'date_context' => '',
			),
		),
		'count'          => 1,
		'updated_at'     => '2026-09-29T12:00:00+00:00',
		'date_context'   => '',
	),
);
$write_count = 0;
$store       = new Snapshot_Store(
	static function ( $name, $default = false ) use ( &$local_values ) {
		return array_key_exists( $name, $local_values ) ? $local_values[ $name ] : $default;
	},
	static function ( $name, $value ) use ( &$local_values, &$write_count ) {
		++$write_count;
		$local_values[ $name ] = $value;
		return true;
	},
	static function ( $name, $value ) use ( &$local_values, &$write_count ) {
		++$write_count;
		$local_values[ $name ] = $value;
		return true;
	}
);
$refresh = new Refresh_Service(
	$preview,
	$store,
	static function (): string { return '2026-09-30T16:00:00+00:00'; },
	static function (): string { return 'should-never-be-created'; },
	$qualification
);

$initial_local = $local_values;

$default_result = $refresh->run();
b_assert_same( 'blocked', $default_result['overall_status'], 'default legacy/unknown trigger is blocked while current contract is unqualified' );
b_assert_same( 'unknown', $default_result['trigger'], 'default trigger remains truthfully normalized as unknown' );
b_assert_same( 'acquisition_contract_unqualified', $default_result['reason'], 'default blocked call reports qualification reason' );
b_assert_same( 0, $fetch_count, 'default blocked call cannot reach remote acquisition' );
b_assert_same( 0, $write_count, 'default blocked call cannot reach snapshot/attempt/run-summary writes' );
b_assert_same( $initial_local, $local_values, 'default blocked call preserves all existing LKG state' );
b_assert_same( false, $default_result['run_summary_recorded'], 'default blocked call cannot fabricate a run summary' );

$alternate_result = $refresh->run( 'future_internal' );
b_assert_same( 'blocked', $alternate_result['overall_status'], 'alternate non-production trigger is also blocked while unqualified' );
b_assert_same( 'unknown', $alternate_result['trigger'], 'alternate trigger normalizes to legacy unknown without bypass authority' );
b_assert_same( 0, $fetch_count, 'alternate trigger cannot bypass acquisition admission' );
b_assert_same( 0, $write_count, 'alternate trigger cannot bypass writable admission' );
b_assert_same( $initial_local, $local_values, 'alternate trigger cannot alter LKG state' );

$qualification_values[ Acquisition_Qualification::OPTION_STATE ] = array(
	'schema_version' => Acquisition_Qualification::SCHEMA_VERSION,
	'status'         => 'qualified',
	'contract_id'    => Source_Config::ACQUISITION_CONTRACT_ID,
	'qualified_at'   => '2026-09-30T16:01:00+00:00',
);
b_assert_same( true, $qualification->is_qualified(), 'exact current contract state admits canonical refresh' );

$qualified_unknown = $refresh->run();
b_assert_same( 'success', $qualified_unknown['overall_status'], 'legacy unknown attribution remains executable only after exact-contract admission' );
b_assert_same( 2, $fetch_count, 'qualified canonical refresh performs both list acquisitions' );
b_assert_same( false, $qualified_unknown['run_summary_recorded'], 'legacy unknown attribution remains non-persisted as a run summary' );
b_assert_same( 'unknown_trigger_not_persisted', $qualified_unknown['run_summary_reason'], 'legacy unknown summary semantics remain explicit after admission' );

echo 'REFRESH_ADMISSION_BYPASS_PASS assertions=' . $assertions . PHP_EOL;
