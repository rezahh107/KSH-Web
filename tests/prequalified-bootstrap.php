<?php
/**
 * Prequalified WordPress option primitives for the pre-existing deterministic suite.
 *
 * The qualification-specific suite runs separately without this bootstrap so fresh,
 * stale, failed, and successful admission states are falsified directly.
 */

declare(strict_types=1);

$GLOBALS['ksh_prequalified_options'] = array(
	'ksh_kanoon_articles_acquisition_qualification' => array(
		'schema_version' => 1,
		'status'         => 'qualified',
		'contract_id'    => 'kanoon-homepage-semantic-lists-v1',
		'qualified_at'   => '2026-09-30T12:00:00+00:00',
		'latest'         => array(
			'count'      => 2,
			'http_code'  => 200,
			'source_url' => 'https://www.kanoon.ir/',
		),
		'weekly_popular' => array(
			'count'      => 2,
			'http_code'  => 200,
			'source_url' => 'https://www.kanoon.ir/',
		),
	),
);

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['ksh_prequalified_options'] )
			? $GLOBALS['ksh_prequalified_options'][ $name ]
			: $default;
	}
}

if ( ! function_exists( 'add_option' ) ) {
	function add_option( $name, $value, $deprecated = '', $autoload = null ) {
		if ( array_key_exists( $name, $GLOBALS['ksh_prequalified_options'] ) ) {
			return false;
		}

		$GLOBALS['ksh_prequalified_options'][ $name ] = $value;
		return true;
	}
}

if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['ksh_prequalified_options'][ $name ] = $value;
		return true;
	}
}
