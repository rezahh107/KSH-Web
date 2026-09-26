<?php
/**
 * Plugin Name: KSH Kanoon Articles
 * Description: Read-only qualification foundation for Kanoon article-list metadata integration.
 * Version: 0.1.0
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
require_once __DIR__ . '/includes/class-admin-page.php';
require_once __DIR__ . '/includes/class-plugin.php';

Plugin::boot();
