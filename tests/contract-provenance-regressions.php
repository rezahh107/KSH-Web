<?php
/**
 * Acquisition-contract provenance regressions for persisted operational evidence.
 *
 * @package KSH_Web_Tests
 */

declare(strict_types=1);

use KSH\KanoonArticles\Acquisition_Qualification;
use KSH\KanoonArticles\Article_Parser;
use KSH\KanoonArticles\Diagnostic_Report;
use KSH\KanoonArticles\Plugin;
use KSH\KanoonArticles\Preview_Service;
use KSH\KanoonArticles\Refresh_Service;
use KSH\KanoonArticles\Scheduler;
use KSH\KanoonArticles\Snapshot_Store;
use KSH\KanoonArticles\Source_Config;

require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-source-config.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-article-parser.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-preview-service.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-acquisition-qualification.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-snapshot-store.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-refresh-service.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-scheduler.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-diagnostic-report.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php';

if ( ! class_exists( 'DOMDocument' ) ) {
	fwrite( STDERR, "CONTRACT_PROVENANCE_ENVIRONMENT_UNAVAILABLE: ext-dom is required.\n" );
	exit( 2 );
}

$assertions = 0;

function p_assert_same( $expected, $actual, string $message ): void {
	global $assertions;
	++$assertions;
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

function p_assert_true( $actual, string $message ): void {
	p_assert_same( true, (bool) $actual, $message );
}

function p_qualification( bool $qualified ): Acquisition_Qualification {
	$state = $qualified
		? array(
			Acquisition_Qualification::OPTION_STATE => array(
				'schema_version' => Acquisition_Qualification::SCHEMA_VERSION,
				'status'         => 'qualified',
				'contract_id'    => Source_Config::ACQUISITION_CONTRACT_ID,
				'qualified_at'   => '2026-10-01T06:00:00+00:00',
			),
		)
		: array();

	return new Acquisition_Qualification(
		null,
		static function ( $name, $default = false ) use ( &$state ) {
			return array_key_exists( $name, $state ) ? $state[ $name ] : $default;
		},
		static function () {
			throw new RuntimeException( 'provenance diagnostic must not write qualification state' );
		},
		static function () {
			throw new RuntimeException( 'provenance diagnostic must not write qualification state' );
		}
	);
}

function p_summary( string $trigger, string $run_id, ?string $contract_id ): array {
	$summary = array(
		'schema_version' => null === $contract_id ? 1 : Snapshot_Store::RUN_SUMMARY_SCHEMA_VERSION,
		'trigger'        => $trigger,
		'run_id'         => $run_id,
		'started_at'     => '2026-10-01T06:01:00+00:00',
		'completed_at'   => '2026-10-01T06:02:00+00:00',
		'overall_status' => 'success',
		'lists'          => array(),
	);

	if ( null !== $contract_id ) {
		$summary['acquisition_contract_id'] = $contract_id;
	}

	return $summary;
}

function p_attempt( string $source, string $trigger, string $run_id, ?string $contract_id ): array {
	$attempt = array(
		'schema_version'   => null === $contract_id ? 2 : Snapshot_Store::ATTEMPT_SCHEMA_VERSION,
		'source'           => $source,
		'trigger'          => $trigger,
		'run_id'           => $run_id,
		'attempted_at'     => '2026-10-01T06:01:00+00:00',
		'candidate_status' => 'success',
		'http_code'        => 200,
		'reason'           => '',
		'action'           => 'updated',
	);

	if ( null !== $contract_id ) {
		$attempt['acquisition_contract_id'] = $contract_id;
	}

	return $attempt;
}

function p_report( array $values, bool $qualified ): array {
	$qualification = p_qualification( $qualified );
	$store         = new Snapshot_Store(
		static function ( $name, $default = false ) use ( $values ) {
			return array_key_exists( $name, $values ) ? $values[ $name ] : $default;
		},
		static function () {
			throw new RuntimeException( 'provenance diagnostic must remain read-only' );
		},
		static function () {
			throw new RuntimeException( 'provenance diagnostic must remain read-only' );
		}
	);
	$scheduler     = new Scheduler(
		static function () { return 1730000000; },
		static function () { throw new RuntimeException( 'provenance diagnostic must not schedule' ); },
		static function () { throw new RuntimeException( 'provenance diagnostic must not clear schedule' ); },
		static function () { return 1000; },
		static function () { return 'daily'; },
		$qualification
	);

	return ( new Diagnostic_Report(
		$store,
		$scheduler,
		static function (): string { return '2026-10-01T06:03:00+00:00'; },
		static function (): array { return array( 'wordpress_version' => '7.1.2', 'php_version' => PHP_VERSION ); },
		$qualification
	) )->build();
}

$current = Source_Config::ACQUISITION_CONTRACT_ID;
$stale   = 'kanoon-homepage-semantic-lists-v0-stale';

/* A. Legacy Cron evidence cannot become current-contract proof while unqualified. */
$legacy_unqualified = p_report(
	array( Snapshot_Store::OPTION_LAST_CRON_RUN => p_summary( 'cron', 'legacy-cron', null ) ),
	false
);
p_assert_same( $current, $legacy_unqualified['acquisition']['current_contract_id'], 'diagnostic exposes the exact current acquisition contract id' );
p_assert_same( false, $legacy_unqualified['acquisition']['current_contract_qualified'], 'fresh current contract remains explicitly unqualified' );
p_assert_same( false, $legacy_unqualified['scheduler']['cron_execution_observed'], 'legacy Cron summary cannot prove current-contract execution while unqualified' );
p_assert_same( 'legacy_unknown_contract', $legacy_unqualified['scheduler']['last_cron_run']['contract_provenance'], 'legacy Cron summary remains visible with unknown-contract provenance' );
p_assert_same( true, $legacy_unqualified['scheduler']['historical_cron_execution_evidence_available'], 'legacy Cron evidence remains visible as historical evidence' );

/* B. Qualification alone does not upgrade a legacy Cron summary. */
$legacy_qualified = p_report(
	array( Snapshot_Store::OPTION_LAST_CRON_RUN => p_summary( 'cron', 'legacy-cron', null ) ),
	true
);
p_assert_same( true, $legacy_qualified['acquisition']['current_contract_qualified'], 'current contract qualification is reported independently from execution' );
p_assert_same( false, $legacy_qualified['scheduler']['cron_execution_observed'], 'qualifying current contract does not upgrade legacy Cron evidence' );
p_assert_same( false, $legacy_qualified['scheduler']['last_cron_run']['belongs_to_current_contract'], 'legacy summary does not belong to current contract' );

/* C. Explicit stale-contract evidence cannot become current execution proof. */
$stale_report = p_report(
	array( Snapshot_Store::OPTION_LAST_CRON_RUN => p_summary( 'cron', 'stale-cron', $stale ) ),
	true
);
p_assert_same( false, $stale_report['scheduler']['cron_execution_observed'], 'stale-contract Cron summary cannot prove current execution' );
p_assert_same( 'stale_contract', $stale_report['scheduler']['last_cron_run']['contract_provenance'], 'stale Cron summary is explicitly classified' );
p_assert_same( $stale, $stale_report['scheduler']['last_cron_run']['acquisition_contract_id'], 'stale contract identity remains readable' );

/* D. Exact current-contract Cron evidence is admitted as execution proof. */
$current_cron = p_report(
	array( Snapshot_Store::OPTION_LAST_CRON_RUN => p_summary( 'cron', 'current-cron', $current ) ),
	true
);
p_assert_same( true, $current_cron['scheduler']['cron_execution_observed'], 'matching current-contract Cron summary proves current execution' );
p_assert_same( 'current_contract', $current_cron['scheduler']['last_cron_run']['contract_provenance'], 'matching Cron summary is classified as current-contract evidence' );
p_assert_same( true, $current_cron['scheduler']['last_cron_run']['belongs_to_current_contract'], 'matching Cron summary belongs to current contract' );
p_assert_same( 'CRON_EXECUTION_OBSERVED', $current_cron['assessment']['diagnostic_state'], 'diagnostic state upgrades only for current-contract Cron evidence' );

/* E. Manual and Cron provenance remain independent. */
$current_manual_stale_cron = p_report(
	array(
		Snapshot_Store::OPTION_LAST_MANUAL_RUN => p_summary( 'manual', 'current-manual', $current ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => p_summary( 'cron', 'stale-cron', $stale ),
	),
	true
);
p_assert_same( true, $current_manual_stale_cron['refresh']['manual_execution_observed'], 'current Manual evidence is observed independently' );
p_assert_same( false, $current_manual_stale_cron['refresh']['cron_execution_observed'], 'current Manual execution does not upgrade stale Cron evidence' );

$stale_manual_current_cron = p_report(
	array(
		Snapshot_Store::OPTION_LAST_MANUAL_RUN => p_summary( 'manual', 'stale-manual', $stale ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => p_summary( 'cron', 'current-cron', $current ),
	),
	true
);
p_assert_same( false, $stale_manual_current_cron['refresh']['manual_execution_observed'], 'current Cron execution does not upgrade stale Manual evidence' );
p_assert_same( true, $stale_manual_current_cron['refresh']['cron_execution_observed'], 'current Cron evidence remains independently observed' );

/* F. Cross-contract attempts cannot participate in current-contract correlation proof. */
$stale_attempts_current_summary = p_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => p_attempt( 'latest', 'cron', 'shared-run', $stale ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => p_attempt( 'weekly_popular', 'cron', 'shared-run', $stale ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => p_summary( 'cron', 'shared-run', $current ),
	),
	true
);
p_assert_same( 'stale_contract', $stale_attempts_current_summary['lists']['latest']['latest_attempt']['contract_provenance'], 'stale per-list attempt provenance remains visible' );
p_assert_same( false, $stale_attempts_current_summary['assessment']['current_contract_cron_attempts_without_matching_summary'], 'stale attempts do not create a false current-contract correlation gap' );
p_assert_same( false, $stale_attempts_current_summary['assessment']['current_contract_observability_incomplete'], 'stale attempts are outside current-contract completeness proof' );

$current_attempts_stale_summary = p_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => p_attempt( 'latest', 'cron', 'shared-run', $current ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => p_attempt( 'weekly_popular', 'cron', 'shared-run', $current ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => p_summary( 'cron', 'shared-run', $stale ),
	),
	true
);
p_assert_same( false, $current_attempts_stale_summary['scheduler']['cron_execution_observed'], 'stale summary remains non-proof even when run id matches current attempts' );
p_assert_same( true, $current_attempts_stale_summary['assessment']['current_contract_cron_attempts_without_matching_summary'], 'current attempts require a matching current-contract summary' );
p_assert_same( true, $current_attempts_stale_summary['assessment']['current_contract_observability_incomplete'], 'cross-contract summary mismatch fails current observability closed' );

/* G. Historical evidence is preserved rather than destructively removed. */
p_assert_same( 'legacy-cron', $legacy_qualified['refresh']['last_cron_run']['run_id'], 'legacy summary remains readable in refresh evidence' );
p_assert_same( 'success', $legacy_qualified['refresh']['last_cron_run']['overall_status'], 'legacy summary outcome remains readable historically' );

/* Newly persisted Refresh evidence carries exact current-contract provenance end to end. */
$persisted = array();
$fetches   = 0;
$body      = '<html><body>' .
	'<a href="#latest">تازه ها</a><a href="#weekly">پربازدید هفته</a><a href="#monthly">پربازدید ماه</a>' .
	'<div id="latest"><a href="/Article/710001">تازه</a></div>' .
	'<div id="weekly"><a href="/Article/720001">هفته</a></div>' .
	'<div id="monthly"><a href="/Article/730001">ماه</a></div>' .
	'</body></html>';
$preview = new Preview_Service(
	static function () use ( &$fetches, $body ): array {
		++$fetches;
		return array( 'ok' => true, 'http_code' => 200, 'body' => $body, 'reason' => '' );
	},
	new Article_Parser()
);
$qualified = p_qualification( true );
$store     = new Snapshot_Store(
	static function ( $name, $default = false ) use ( &$persisted ) {
		return array_key_exists( $name, $persisted ) ? $persisted[ $name ] : $default;
	},
	static function ( $name, $value ) use ( &$persisted ) {
		$persisted[ $name ] = $value;
		return true;
	},
	static function ( $name, $value ) use ( &$persisted ) {
		$persisted[ $name ] = $value;
		return true;
	}
);
$refresh = new Refresh_Service(
	$preview,
	$store,
	static function (): string { return '2026-10-01T06:10:00+00:00'; },
	static function (): string { return 'current-contract-cron-run'; },
	$qualified
);
$run = $refresh->run( 'cron' );

p_assert_same( 2, $fetches, 'qualified Cron refresh still performs independent two-list acquisition' );
p_assert_same( $current, $run['acquisition_contract_id'], 'refresh result exposes the producing acquisition contract' );
p_assert_same( $current, $persisted[ Snapshot_Store::OPTION_LAST_CRON_RUN ]['acquisition_contract_id'], 'persisted Cron summary carries current acquisition-contract provenance' );
p_assert_same( $current, $persisted[ Snapshot_Store::OPTION_LATEST_ATTEMPT ]['acquisition_contract_id'], 'persisted Latest attempt carries current acquisition-contract provenance' );
p_assert_same( $current, $persisted[ Snapshot_Store::OPTION_WEEKLY_ATTEMPT ]['acquisition_contract_id'], 'persisted Weekly attempt carries current acquisition-contract provenance' );
p_assert_same( Snapshot_Store::RUN_SUMMARY_SCHEMA_VERSION, $persisted[ Snapshot_Store::OPTION_LAST_CRON_RUN ]['schema_version'], 'new Cron summary uses provenance-aware schema version' );
p_assert_same( Snapshot_Store::ATTEMPT_SCHEMA_VERSION, $persisted[ Snapshot_Store::OPTION_LATEST_ATTEMPT ]['schema_version'], 'new attempt uses provenance-aware schema version' );

echo 'CONTRACT_PROVENANCE_PASS assertions=' . $assertions . PHP_EOL;
