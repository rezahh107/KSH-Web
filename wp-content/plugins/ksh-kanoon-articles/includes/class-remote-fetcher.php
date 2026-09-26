<?php
/**
 * WordPress HTTP acquisition boundary.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Performs one bounded, read-only HTTP GET after explicit Preview action.
 */
final class Remote_Fetcher {

	/**
	 * Fetch one approved source.
	 *
	 * @param string $url Approved source URL.
	 * @return array<string,mixed>
	 */
	public function fetch( $url ) {
		$response = wp_safe_remote_get(
			$url,
			array(
				'timeout'             => 12,
				'redirection'         => 3,
				'limit_response_size' => 2 * 1024 * 1024,
				'user-agent'          => 'KSH-Kanoon-Articles/0.1 (+WordPress read-only preview)',
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'ok'        => false,
				'http_code' => null,
				'body'      => '',
				'reason'    => 'transport_error',
				'detail'    => $response->get_error_message(),
			);
		}

		$http_code = (int) wp_remote_retrieve_response_code( $response );
		$body      = (string) wp_remote_retrieve_body( $response );

		if ( $http_code < 200 || $http_code >= 300 ) {
			return array(
				'ok'        => false,
				'http_code' => $http_code,
				'body'      => '',
				'reason'    => 'http_error',
				'detail'    => '',
			);
		}

		if ( '' === trim( $body ) ) {
			return array(
				'ok'        => false,
				'http_code' => $http_code,
				'body'      => '',
				'reason'    => 'empty_body',
				'detail'    => '',
			);
		}

		return array(
			'ok'        => true,
			'http_code' => $http_code,
			'body'      => $body,
			'reason'    => '',
			'detail'    => '',
		);
	}
}
