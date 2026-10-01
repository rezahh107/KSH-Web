<?php
/**
 * Still-active diagnostic attempt/run-summary correlation regressions.
 *
 * @package KSH_Web_Tests
 */

declare(strict_types=1);

use KSH\KanoonArticles\Acquisition_Qualification;
use KSH\KanoonArticles\Diagnostic_Report;
use KSH\KanoonArticles\Scheduler;
use KSH\KanoonArticles\Snapshot_Store;
use KSH\KanoonArticles\Source_Config;

require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-source-config.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-acquisition-qualification.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-snapshot-store.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-scheduler.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-diagnostic-report.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-plugin.php';

$assertions = 0;

function c_assert_same( $expected, $actual, string $message ): void {
	global $assertions;
	++$assertions;
	if ( $expected !== $actual ) {
		fwrite( STDERR, "FAIL: {$message}\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) . "\n" );
		exit( 1 );
	}
}

$qualification_state = array(
	Acquisition_Qualification::OPTION_STATE => array(
		'schema_version' => Acquisition_Qualification::SCHEMA_VERSION,
		'status'         => 'qualified',
		'contract_id'    => Source_Config::ACQUISITION_CONTRACT_ID,
		'qualified_at'   => '2026-09-30T15:00:00+00:00',
	),
);
$qualification = new Acquisition_Qualification(
	null,
	static function ( $name, $default = false ) use ( &$qualification_state ) {
		return array_key_exists( $name, $qualification_state ) ? $qualification_state[ $name ] : $default;
	},
	static function () { throw new RuntimeException( 'correlation test must not write qualification state' ); },
	static function () { throw new RuntimeException( 'correlation test must not write qualification state' ); }
);

$scheduler = new Scheduler(
	static function () { return 1730000000; },
	static function () { throw new RuntimeException( 'diagnostic correlation read must not schedule' ); },
	static function () { throw new RuntimeException( 'diagnostic correlation read must not clear schedule' ); },
	static function () { return 1000; },
	static function () { return 'daily'; },
	$qualification
);

$attempt = static function ( $source, $trigger, $run_id ) {
	return array(
		'schema_version'   => Snapshot_Store::ATTEMPT_SCHEMA_VERSION,
		'source'           => $source,
		'trigger'          => $trigger,
		'run_id'           => $run_id,
		'attempted_at'     => '2026-09-30T15:01:00+00:00',
		'candidate_status' => 'success',
		'http_code'        => 200,
		'reason'           => '',
		'action'           => 'updated',
	);
};
$summary = static function ( $trigger, $run_id ) {
	return array(
		'schema_version' => Snapshot_Store::RUN_SUMMARY_SCHEMA_VERSION,
		'trigger'        => $trigger,
		'run_id'         => $run_id,
		'started_at'     => '2026-09-30T15:00:00+00:00',
		'completed_at'   => '2026-09-30T15:01:00+00:00',
		'overall_status' => 'success',
		'lists'          => array(),
	);
};
$build_report = static function ( array $values ) use ( $scheduler ): array {
	$store = new Snapshot_Store(
		static function ( $name, $default = false ) use ( $values ) {
			return array_key_exists( $name, $values ) ? $values[ $name ] : $default;
		},
		static function () { throw new RuntimeException( 'correlation diagnostic must remain read-only' ); },
		static function () { throw new RuntimeException( 'correlation diagnostic must remain read-only' ); }
	);

	return ( new Diagnostic_Report(
		$store,
		$scheduler,
		static function (): string { return '2026-09-30T15:02:00+00:00'; },
		static function (): array { return array( 'wordpress_version' => '7.1.2', 'php_version' => PHP_VERSION ); }
	) )->build();
};

$matching_cron = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $attempt( 'weekly_popular', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => $summary( 'cron', 'cron-current' ),
	)
);
c_assert_same( false, $matching_cron['assessment']['cron_attempts_without_matching_summary'], 'matching Cron attempts and summary are complete' );
c_assert_same( false, $matching_cron['assessment']['observability_incomplete'], 'matching Cron state is not falsely incomplete' );

$latest_newer_cron = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'cron', 'cron-newer' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $attempt( 'weekly_popular', 'cron', 'cron-older' ),
	)
);
c_assert_same( true, $latest_newer_cron['assessment']['cron_attempts_without_matching_summary'], 'newer Latest Cron attempt without summary exposes correlation gap' );
c_assert_same( true, $latest_newer_cron['assessment']['observability_incomplete'], 'asymmetric Latest Cron state is incomplete' );

$weekly_newer_cron = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'cron', 'cron-older' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $attempt( 'weekly_popular', 'cron', 'cron-newer' ),
	)
);
c_assert_same( true, $weekly_newer_cron['assessment']['cron_attempts_without_matching_summary'], 'newer Weekly Cron attempt without summary exposes correlation gap' );
c_assert_same( true, $weekly_newer_cron['assessment']['observability_incomplete'], 'asymmetric Weekly Cron state is incomplete' );

$previous_cron_summary = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'cron', 'cron-newer' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $attempt( 'weekly_popular', 'cron', 'cron-previous' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => $summary( 'cron', 'cron-previous' ),
	)
);
c_assert_same( true, $previous_cron_summary['assessment']['cron_attempts_without_matching_summary'], 'previous Cron summary cannot hide newer per-list attempt' );
c_assert_same( true, $previous_cron_summary['assessment']['observability_incomplete'], 'previous summary with newer attempt remains incomplete' );

$advanced_cron_summary = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $attempt( 'weekly_popular', 'cron', 'cron-stale' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN   => $summary( 'cron', 'cron-current' ),
	)
);
c_assert_same( true, $advanced_cron_summary['assessment']['cron_attempts_without_matching_summary'], 'current Cron summary cannot hide stale per-list attempt' );
c_assert_same( true, $advanced_cron_summary['assessment']['observability_incomplete'], 'summary advancement with stale attempt remains incomplete' );

$latest_newer_manual = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'manual', 'manual-newer' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $attempt( 'weekly_popular', 'manual', 'manual-older' ),
	)
);
c_assert_same( true, $latest_newer_manual['assessment']['manual_attempts_without_matching_summary'], 'newer Latest Manual attempt without summary exposes correlation gap' );
c_assert_same( true, $latest_newer_manual['assessment']['observability_incomplete'], 'asymmetric Latest Manual state is incomplete' );

$weekly_newer_manual = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'manual', 'manual-older' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT => $attempt( 'weekly_popular', 'manual', 'manual-newer' ),
	)
);
c_assert_same( true, $weekly_newer_manual['assessment']['manual_attempts_without_matching_summary'], 'newer Weekly Manual attempt without summary exposes correlation gap' );
c_assert_same( true, $weekly_newer_manual['assessment']['observability_incomplete'], 'asymmetric Weekly Manual state is incomplete' );

$independent_triggers = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT  => $attempt( 'latest', 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_WEEKLY_ATTEMPT  => $attempt( 'weekly_popular', 'manual', 'manual-current' ),
		Snapshot_Store::OPTION_LAST_CRON_RUN    => $summary( 'cron', 'cron-current' ),
		Snapshot_Store::OPTION_LAST_MANUAL_RUN  => $summary( 'manual', 'manual-current' ),
	)
);
c_assert_same( false, $independent_triggers['assessment']['cron_attempts_without_matching_summary'], 'Manual attempt does not create false Cron gap' );
c_assert_same( false, $independent_triggers['assessment']['manual_attempts_without_matching_summary'], 'Cron attempt does not create false Manual gap' );
c_assert_same( false, $independent_triggers['assessment']['observability_incomplete'], 'independently matching Manual and Cron states remain complete' );

$missing_run_id = $build_report(
	array(
		Snapshot_Store::OPTION_LATEST_ATTEMPT => $attempt( 'latest', 'cron', null ),
	)
);
c_assert_same( 'cron', $missing_run_id['lists']['latest']['latest_attempt']['trigger'], 'explicit Cron trigger remains visible without run id' );
c_assert_same( 'legacy_or_unknown', $missing_run_id['lists']['latest']['latest_attempt']['attribution'], 'missing run id cannot fabricate explicit correlation attribution' );
c_assert_same( true, $missing_run_id['assessment']['cron_attempts_without_matching_summary'], 'explicit Cron attempt without usable run id fails closed' );
c_assert_same( true, $missing_run_id['assessment']['observability_incomplete'], 'missing run id remains incomplete observability' );

echo 'DIAGNOSTIC_CORRELATION_PASS assertions=' . $assertions . PHP_EOL;
