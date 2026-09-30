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

	const VERSION = '0.4.0';

	/**
	 * Bootstrap plugin hooks.
	 *
	 * @return void
	 */
	public static function boot() {
		$fetcher       = new Remote_Fetcher();
		$parser        = new Article_Parser();
		$preview       = new Preview_Service( array( $fetcher, 'fetch' ), $parser );
		$qualification = new Acquisition_Qualification( $preview );
		$store         = new Snapshot_Store();
		$refresh       = new Refresh_Service( $preview, $store, null, null, $qualification );
		$scheduler     = new Scheduler( null, null, null, null, null, $qualification );
		$diagnostic    = new Diagnostic_Report( $store, $scheduler );
		$admin         = new Admin_Page( $preview, $refresh, $store, $scheduler, $diagnostic );
		$qualify_admin = new Qualification_Admin_Page( $qualification, $scheduler );
		$renderer      = new Frontend_Renderer( $store );
		$shortcode     = new Shortcode( $renderer );

		$scheduler->register( $refresh );
		$shortcode->register();
		add_action( 'admin_menu', array( $admin, 'register' ) );
		add_action( 'admin_menu', array( $qualify_admin, 'register' ) );
		add_action( 'admin_post_' . Admin_Page::EXPORT_ACTION, array( $admin, 'download_diagnostic' ) );
	}

	/**
	 * Activation only ensures scheduling when the current acquisition contract is qualified.
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
