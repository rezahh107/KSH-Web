<?php
/**
 * Public local-snapshot renderer.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

/**
 * Renders the public Kanoon article module from validated local snapshots only.
 */
final class Frontend_Renderer {

	const DISPLAY_LIMIT = 15;

	/**
	 * Local snapshot store.
	 *
	 * @var Snapshot_Store
	 */
	private $store;

	/**
	 * Build the renderer.
	 *
	 * @param Snapshot_Store $store Local snapshot store.
	 */
	public function __construct( Snapshot_Store $store ) {
		$this->store = $store;
	}

	/**
	 * Render the complete public module, or fail softly when no valid list exists.
	 *
	 * The persisted snapshots remain complete; the display cap is presentation-only.
	 *
	 * @return string
	 */
	public function render() {
		$latest = array_slice( $this->snapshot_items( 'latest' ), 0, self::DISPLAY_LIMIT );
		$weekly = array_slice( $this->snapshot_items( 'weekly_popular' ), 0, self::DISPLAY_LIMIT );
		$lists  = array();

		if ( ! empty( $latest ) ) {
			$lists[] = array(
				'source' => 'latest',
				'label'  => __( 'تازه‌ها', 'ksh-kanoon-articles' ),
				'items'  => $latest,
			);
		}

		if ( ! empty( $weekly ) ) {
			$lists[] = array(
				'source' => 'weekly_popular',
				'label'  => __( 'پربازدید هفته', 'ksh-kanoon-articles' ),
				'items'  => $weekly,
			);
		}

		if ( empty( $lists ) ) {
			return '';
		}

		$grid_class = 'ksh-kanoon-articles__grid';
		if ( 1 === count( $lists ) ) {
			$grid_class .= ' ksh-kanoon-articles__grid--single';
		}

		ob_start();
		?>
		<section class="ksh-kanoon-articles" dir="rtl" lang="fa">
			<div class="ksh-kanoon-articles__inner">
				<h2 class="ksh-kanoon-articles__title"><?php echo esc_html__( 'تازه‌های کانون', 'ksh-kanoon-articles' ); ?></h2>
				<div class="<?php echo esc_attr( $grid_class ); ?>">
					<?php foreach ( $lists as $list ) : ?>
						<section class="ksh-kanoon-articles__panel">
							<h3 class="ksh-kanoon-articles__panel-title"><?php echo esc_html( $list['label'] ); ?></h3>
							<ul class="ksh-kanoon-articles__list">
								<?php foreach ( $list['items'] as $item ) : ?>
									<li class="ksh-kanoon-articles__item">
										<a class="ksh-kanoon-articles__link" href="<?php echo esc_url( $item['url'] ); ?>"><?php echo esc_html( $item['title'] ); ?></a>
									</li>
								<?php endforeach; ?>
							</ul>
						</section>
					<?php endforeach; ?>
				</div>
			</div>
		</section>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * Read and defensively verify one persisted snapshot before public output.
	 *
	 * Stored snapshots are produced by the strict parser/persistence path. This
	 * second lightweight check fails closed if local state is malformed or was
	 * externally altered, without attempting any remote recovery.
	 *
	 * @param string $source List identity.
	 * @return array<int,array<string,string>>
	 */
	private function snapshot_items( $source ) {
		$snapshot = $this->store->get_snapshot( $source );

		if (
			! is_array( $snapshot ) ||
			Snapshot_Store::SCHEMA_VERSION !== ( isset( $snapshot['schema_version'] ) ? (int) $snapshot['schema_version'] : 0 ) ||
			( isset( $snapshot['source'] ) ? (string) $snapshot['source'] : '' ) !== $source ||
			! isset( $snapshot['items'], $snapshot['count'] ) ||
			! is_array( $snapshot['items'] ) ||
			count( $snapshot['items'] ) !== (int) $snapshot['count'] ||
			0 === count( $snapshot['items'] )
		) {
			return array();
		}

		$items = array();
		foreach ( $snapshot['items'] as $item ) {
			if (
				! is_array( $item ) ||
				! array_key_exists( 'title', $item ) ||
				! array_key_exists( 'url', $item ) ||
				! array_key_exists( 'date_context', $item ) ||
				! is_string( $item['title'] ) ||
				! is_string( $item['url'] ) ||
				! is_string( $item['date_context'] )
			) {
				return array();
			}

			$title = trim( $item['title'] );
			$url   = trim( $item['url'] );

			if ( '' === $title || ! $this->is_canonical_article_url( $url ) ) {
				return array();
			}

			$items[] = array(
				'title'        => $title,
				'url'          => $url,
				'date_context' => trim( $item['date_context'] ),
			);
		}

		return $items;
	}

	/**
	 * Accept only the canonical URL shape emitted by the parser.
	 *
	 * @param string $url Stored URL.
	 * @return bool
	 */
	private function is_canonical_article_url( $url ) {
		return 1 === preg_match( '#^https://www\.kanoon\.ir/Article/[0-9]+$#D', $url );
	}
}
