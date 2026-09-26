<?php
/**
 * Deterministic parser/orchestration test runner.
 *
 * These fixtures exercise the admitted parser contract only. They do not claim
 * the production host can reach kanoon.ir or that the live DOM still matches.
 */

declare(strict_types=1);

use KSH\KanoonArticles\Article_Parser;
use KSH\KanoonArticles\Preview_Service;

require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-source-config.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-article-parser.php';
require_once __DIR__ . '/../wp-content/plugins/ksh-kanoon-articles/includes/class-preview-service.php';

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
		return array(
			'ok'        => true,
			'http_code' => 200,
			'body'      => fixture( 'latest-valid.html' ),
			'reason'    => '',
		);
	}
	return array(
		'ok'        => true,
		'http_code' => 200,
		'body'      => fixture( 'home-ambiguous.html' ),
		'reason'    => '',
	);
};

$preview = new Preview_Service( $fetch, $parser );
$run     = $preview->run();
assert_same( 'success', $run['latest']['status'], 'Latest remains independently successful' );
assert_same( 'ambiguous', $run['weekly_popular']['status'], 'Weekly remains independently ambiguous' );
assert_same( 'partial', $run['overall_status'], 'one success plus one ambiguous is never full success' );

$fetch_with_weekly_failure = static function ( string $url ): array {
	if ( false !== strpos( $url, '/Article/Days' ) ) {
		return array(
			'ok'        => true,
			'http_code' => 200,
			'body'      => fixture( 'latest-valid.html' ),
			'reason'    => '',
		);
	}
	return array(
		'ok'        => false,
		'http_code' => 503,
		'body'      => '',
		'reason'    => 'http_error',
	);
};

$partial_failure = ( new Preview_Service( $fetch_with_weekly_failure, $parser ) )->run();
assert_same( 'success', $partial_failure['latest']['status'], 'Latest success survives independent Weekly fetch failure' );
assert_same( 'failure', $partial_failure['weekly_popular']['status'], 'Weekly fetch failure is reported independently' );
assert_same( 'partial', $partial_failure['overall_status'], 'independent list failure never becomes full success' );

echo 'TEST_PASS assertions=' . $assertions . PHP_EOL;
