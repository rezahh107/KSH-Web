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

	const VERSION = '0.2.1';

	/**
	 * Bootstrap plugin hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		$fetcher   = new Remote_Fetcher();
		$parser    = new Article_Parser();
		$preview   = new Preview_Service( array( $fetcher, 'fetch' ), $parser );
		$store      = new Snapshot_Store();
		$refresh    = new Refresh_Service( $preview, $store );
		$scheduler  = new Scheduler();
		$diagnostic = new Diagnostic_Report( $store, $scheduler );
		$admin      = new Admin_Page( $preview, $refresh, $store, $scheduler, $diagnostic );

		$scheduler->register( $refresh );
		add_action( 'admin_menu', array( $admin, 'register' ) );
		add_action( 'admin_post_' . Admin_Page::EXPORT_ACTION, array( $admin, 'download_diagnostic' ) );
	}

	/**
	 * Activation only ensures scheduling; it never performs remote acquisition.
	 *
	 * @return void
	 */
	public static function activate() {
		$scheduler = new Scheduler();
		$scheduler->ensure_scheduled();
	}

	/**
	 * Remove recurring events while deliberately preserving valid snapshots.
	 *
	 * @return void
	 */
	public static function deactivate() {
		$scheduler = new Scheduler();
		$scheduler->clear();
	}
}
