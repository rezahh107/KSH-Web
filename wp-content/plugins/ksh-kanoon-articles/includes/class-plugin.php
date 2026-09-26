<?php
/**
 * Plugin composition root.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Wires owned components to WordPress lifecycle.
 */
final class Plugin {

	/**
	 * Bootstrap plugin hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		$fetcher = new Remote_Fetcher();
		$parser  = new Article_Parser();
		$preview = new Preview_Service( array( $fetcher, 'fetch' ), $parser );
		$admin   = new Admin_Page( $preview );

		add_action( 'admin_menu', array( $admin, 'register' ) );
	}
}
