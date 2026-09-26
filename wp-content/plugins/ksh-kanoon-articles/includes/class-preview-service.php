<?php
/**
 * Read-only Preview orchestration.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Runs both sources independently and never persists the result.
 */
final class Preview_Service {

	/**
	 * Remote acquisition callable.
	 *
	 * @var callable
	 */
	private $fetch;

	/**
	 * Parser boundary.
	 *
	 * @var Article_Parser
	 */
	private $parser;

	/**
	 * Create the read-only Preview orchestrator.
	 *
	 * @param callable       $fetch  Callable accepting URL and returning acquisition result.
	 * @param Article_Parser $parser Parser.
	 */
	public function __construct( $fetch, Article_Parser $parser ) {
		$this->fetch  = $fetch;
		$this->parser = $parser;
	}

	/**
	 * Run both list checks independently.
	 *
	 * @return array<string,mixed>
	 */
	public function run() {
		$latest = $this->run_one( 'latest' );
		$weekly = $this->run_one( 'weekly_popular' );

		return array(
			'overall_status' => $this->overall_status( $latest['status'], $weekly['status'] ),
			'latest'         => $latest,
			'weekly_popular' => $weekly,
		);
	}

	/**
	 * Execute one independently reportable list.
	 *
	 * @param string $source List identity.
	 * @return array<string,mixed>
	 */
	private function run_one( $source ) {
		$url     = Source_Config::url( $source );
		$fetcher = $this->fetch;
		$remote  = $fetcher( $url );

		if ( empty( $remote['ok'] ) ) {
			return array(
				'source'       => $source,
				'source_url'   => $url,
				'status'       => 'failure',
				'count'        => 0,
				'items'        => array(),
				'date_context' => '',
				'reason'       => isset( $remote['reason'] ) ? (string) $remote['reason'] : 'remote_failure',
				'request'      => array(
					'ok'        => false,
					'http_code' => isset( $remote['http_code'] ) ? $remote['http_code'] : null,
				),
			);
		}

		if ( 'latest' === $source ) {
			$parsed = $this->parser->parse_latest( (string) $remote['body'] );
		} else {
			$parsed = $this->parser->parse_weekly_popular( (string) $remote['body'] );
		}

		$parsed['source_url'] = $url;
		$parsed['request']    = array(
			'ok'        => true,
			'http_code' => isset( $remote['http_code'] ) ? $remote['http_code'] : null,
		);

		return $parsed;
	}

	/**
	 * Never report full success unless both lists succeeded.
	 *
	 * @param string $latest_status Latest state.
	 * @param string $weekly_status Weekly state.
	 * @return string
	 */
	private function overall_status( $latest_status, $weekly_status ) {
		if ( 'success' === $latest_status && 'success' === $weekly_status ) {
			return 'success';
		}

		if ( 'success' === $latest_status || 'success' === $weekly_status ) {
			return 'partial';
		}

		if ( 'ambiguous' === $latest_status || 'ambiguous' === $weekly_status ) {
			return 'ambiguous';
		}

		return 'failure';
	}
}
