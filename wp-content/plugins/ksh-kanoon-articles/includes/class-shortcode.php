<?php
/**
 * Theme/builder-agnostic shortcode adapter.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Exposes the frontend renderer through a standard WordPress shortcode.
 */
final class Shortcode {

	const TAG          = 'ksh_kanoon_articles';
	const STYLE_HANDLE = 'ksh-kanoon-articles-frontend';

	/**
	 * Public renderer.
	 *
	 * @var Frontend_Renderer
	 */
	private $renderer;

	/**
	 * Build the shortcode adapter.
	 *
	 * @param Frontend_Renderer $renderer Public local-snapshot renderer.
	 */
	public function __construct( Frontend_Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * Register placement and presentation hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_shortcode( self::TAG, array( $this, 'render' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * Delegate public HTML generation to the reusable renderer.
	 *
	 * @return string
	 */
	public function render() {
		return $this->renderer->render();
	}

	/**
	 * Load one tiny fully scoped stylesheet on public pages.
	 *
	 * This intentionally avoids brittle shortcode/page-builder detection. The
	 * stylesheet is harmless when the module is absent and remains independent
	 * from acquisition or persistence behavior.
	 *
	 * @return void
	 */
	public function enqueue_styles() {
		$plugin_file = dirname( __DIR__ ) . '/ksh-kanoon-articles.php';

		wp_enqueue_style(
			self::STYLE_HANDLE,
			plugin_dir_url( $plugin_file ) . 'assets/css/frontend.css',
			array(),
			Plugin::VERSION
		);
	}
}
