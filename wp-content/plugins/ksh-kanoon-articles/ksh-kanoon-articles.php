<?php
/**
 * Plugin Name: KSH Kanoon Articles
 * Description: Qualified Kanoon article metadata acquisition with independent local last-known-good refresh.
 * Version: 0.4.0
 * Text Domain: ksh-kanoon-articles
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-source-config.php';
require_once __DIR__ . '/includes/class-remote-fetcher.php';
require_once __DIR__ . '/includes/class-article-parser.php';
require_once __DIR__ . '/includes/class-preview-service.php';
require_once __DIR__ . '/includes/class-acquisition-qualification.php';
require_once __DIR__ . '/includes/class-snapshot-store.php';
require_once __DIR__ . '/includes/class-refresh-service.php';
require_once __DIR__ . '/includes/class-scheduler.php';
require_once __DIR__ . '/includes/class-diagnostic-report.php';
require_once __DIR__ . '/includes/class-admin-page.php';
require_once __DIR__ . '/includes/class-qualification-admin-page.php';
require_once __DIR__ . '/includes/class-frontend-renderer.php';
require_once __DIR__ . '/includes/class-shortcode.php';
require_once __DIR__ . '/includes/class-plugin.php';

register_activation_hook( __FILE__, array( Plugin::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Plugin::class, 'deactivate' ) );

Plugin::boot();
