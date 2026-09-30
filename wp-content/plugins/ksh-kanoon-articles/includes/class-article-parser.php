<?php
/**
 * Pure extraction, normalization, and validation boundary.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
 * Parses the two bounded Kanoon homepage list shapes without WordPress state.
 */
final class Article_Parser {

	/**
	 * Parse Latest by binding the semantic homepage tab label to its fragment target.
	 *
	 * @param string $html Source HTML.
	 * @return array<string,mixed>
	 */
	public function parse_latest( $html ) {
		$document = $this->load_document( $html );
		if ( is_array( $document ) ) {
			return $this->result( 'latest', 'failure', array(), '', $document['reason'] );
		}

		$xpath         = new DOMXPath( $document );
		$latest_links  = $this->find_exact_text_anchors( $xpath, array( 'تازه ها', 'تازه‌ها' ) );
		$weekly_links  = $this->find_exact_text_anchors( $xpath, array( 'پربازدید هفته' ) );
		$monthly_links = $this->find_exact_text_anchors( $xpath, array( 'پربازدید ماه' ) );

		if ( 1 !== count( $latest_links ) ) {
			return $this->result( 'latest', 'ambiguous', array(), '', 'latest_tab_label_not_unique' );
		}

		$latest_id = $this->fragment_id( $latest_links[0] );
		if ( '' === $latest_id ) {
			return $this->result( 'latest', 'ambiguous', array(), '', 'latest_tab_target_missing' );
		}

		$latest_target = $this->target_by_id( $xpath, $latest_id );
		if ( ! $latest_target ) {
			return $this->result( 'latest', 'ambiguous', array(), '', 'latest_tab_target_missing' );
		}

		// When the sibling semantic tabs are uniquely resolvable, their target must
		// stay distinct from Latest. Their absence/ambiguity does not couple an
		// otherwise valid Latest list to Weekly/Monthly parsing success.
		foreach ( array( $weekly_links, $monthly_links ) as $sibling_links ) {
			if ( 1 !== count( $sibling_links ) ) {
				continue;
			}

			$sibling_id = $this->fragment_id( $sibling_links[0] );
			if ( '' !== $sibling_id && $latest_id === $sibling_id ) {
				return $this->result( 'latest', 'ambiguous', array(), '', 'latest_tab_target_collision' );
			}
		}

		return $this->parse_target_items( $xpath, $latest_target, 'latest', 'latest_anchor_query_failed', 'latest_zero_valid_items' );
	}

	/**
	 * Parse Weekly Popular by binding semantic tab label to its fragment target.
	 *
	 * Monthly Popular remains a required independently resolvable sibling boundary
	 * so the parser cannot silently treat the nearby monthly list as Weekly Popular.
	 *
	 * @param string $html Source HTML.
	 * @return array<string,mixed>
	 */
	public function parse_weekly_popular( $html ) {
		$document = $this->load_document( $html );
		if ( is_array( $document ) ) {
			return $this->result( 'weekly_popular', 'failure', array(), '', $document['reason'] );
		}

		$xpath         = new DOMXPath( $document );
		$weekly_links  = $this->find_exact_text_anchors( $xpath, array( 'پربازدید هفته' ) );
		$monthly_links = $this->find_exact_text_anchors( $xpath, array( 'پربازدید ماه' ) );

		if ( 1 !== count( $weekly_links ) || 1 !== count( $monthly_links ) ) {
			return $this->result( 'weekly_popular', 'ambiguous', array(), '', 'popular_tab_labels_not_unique' );
		}

		$weekly_id  = $this->fragment_id( $weekly_links[0] );
		$monthly_id = $this->fragment_id( $monthly_links[0] );

		if ( '' === $weekly_id || '' === $monthly_id ) {
			return $this->result( 'weekly_popular', 'ambiguous', array(), '', 'popular_tab_target_missing' );
		}

		if ( $weekly_id === $monthly_id ) {
			return $this->result( 'weekly_popular', 'ambiguous', array(), '', 'weekly_monthly_target_collision' );
		}

		$weekly_target  = $this->target_by_id( $xpath, $weekly_id );
		$monthly_target = $this->target_by_id( $xpath, $monthly_id );

		if ( ! $weekly_target || ! $monthly_target ) {
			return $this->result( 'weekly_popular', 'ambiguous', array(), '', 'popular_tab_target_missing' );
		}

		return $this->parse_target_items( $xpath, $weekly_target, 'weekly_popular', 'weekly_anchor_query_failed', 'weekly_zero_valid_items' );
	}

	/**
	 * Extract canonical article anchors from one already-resolved semantic target.
	 *
	 * @param DOMXPath   $xpath        XPath instance.
	 * @param DOMElement $target       Semantic target container.
	 * @param string     $source       List identity.
	 * @param string     $query_reason Failure reason for XPath query failure.
	 * @param string     $zero_reason  Failure reason for zero valid articles.
	 * @return array<string,mixed>
	 */
	private function parse_target_items( DOMXPath $xpath, DOMElement $target, $source, $query_reason, $zero_reason ) {
		$anchors = $xpath->query( './/a[@href]', $target );
		$items   = array();
		$seen    = array();

		if ( false === $anchors ) {
			return $this->result( $source, 'failure', array(), '', $query_reason );
		}

		foreach ( $anchors as $anchor ) {
			if ( ! $anchor instanceof DOMElement ) {
				continue;
			}

			$item = $this->normalize_article_anchor( $anchor, $source, '' );
			if ( ! $item || isset( $seen[ $item['url'] ] ) ) {
				continue;
			}

			$seen[ $item['url'] ] = true;
			$items[]              = $item;
		}

		if ( 0 === count( $items ) ) {
			return $this->result( $source, 'failure', array(), '', $zero_reason );
		}

		return $this->result( $source, 'success', $items, '', '' );
	}

	/**
	 * Normalize and validate one article anchor.
	 *
	 * @param DOMElement $anchor       Anchor element.
	 * @param string     $source       List identity.
	 * @param string     $date_context Reliable date/day context, when present.
	 * @return array<string,string>|null
	 */
	private function normalize_article_anchor( DOMElement $anchor, $source, $date_context ) {
		$url = $this->canonical_article_url( $anchor->getAttribute( 'href' ) );
		if ( '' === $url ) {
			return null;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
		$title = $this->clean_article_title( $anchor->textContent );
		if ( '' === $title ) {
			return null;
		}

		return array(
			'title'        => $title,
			'url'          => $url,
			'date_context' => $date_context,
			'source'       => $source,
		);
	}

	/**
	 * Canonicalize only the intended Kanoon article path family.
	 *
	 * @param string $href Raw href.
	 * @return string
	 */
	private function canonical_article_url( $href ) {
		$href = trim( html_entity_decode( $href, ENT_QUOTES | ENT_HTML5, 'UTF-8' ) );
		if ( '' === $href ) {
			return '';
		}

		if ( 0 === strpos( $href, '/' ) ) {
			$href = 'https://www.kanoon.ir' . $href;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Parser stays WordPress-independent for deterministic fixture tests.
		$parts = parse_url( $href );
		if ( ! is_array( $parts ) || empty( $parts['host'] ) || empty( $parts['path'] ) ) {
			return '';
		}

		$host = strtolower( $parts['host'] );
		if ( 'kanoon.ir' !== $host && 'www.kanoon.ir' !== $host ) {
			return '';
		}

		if ( isset( $parts['query'] ) || isset( $parts['fragment'] ) ) {
			return '';
		}

		$path = rtrim( $parts['path'], '/' );
		if ( 1 !== preg_match( '#^/Article/[0-9]+$#', $path ) ) {
			return '';
		}

		return 'https://www.kanoon.ir' . $path;
	}

	/**
	 * Remove bounded relative-time/view metadata when carried inside an article link.
	 *
	 * @param string $raw_title Anchor text.
	 * @return string
	 */
	private function clean_article_title( $raw_title ) {
		$title = $this->normalize_text( $raw_title );
		$digit = '0-9۰-۹٠-٩';

		$title = preg_replace(
			'/\s+(?:دقایقی\s+قبل|[' . $digit . ']+\s*(?:دقیقه|ساعت|روز)\s+قبل)\s+[' . $digit . ']+\s+بازدید\s*$/u',
			'',
			$title
		);

		return $this->normalize_text( is_string( $title ) ? $title : '' );
	}

	/**
	 * Find anchors matching one of the exact normalized semantic labels.
	 *
	 * @param DOMXPath          $xpath XPath instance.
	 * @param array<int,string> $texts Accepted exact labels.
	 * @return array<int,DOMElement>
	 */
	private function find_exact_text_anchors( DOMXPath $xpath, $texts ) {
		$anchors = $xpath->query( '//a[@href]' );
		$matches = array();

		if ( false === $anchors ) {
			return $matches;
		}

		foreach ( $anchors as $anchor ) {
			if ( ! $anchor instanceof DOMElement ) {
				continue;
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
			$text = $this->normalize_text( $anchor->textContent );
			if ( in_array( $text, $texts, true ) ) {
				$matches[] = $anchor;
			}
		}

		return $matches;
	}

	/**
	 * Resolve a same-document fragment ID from a tab anchor.
	 *
	 * @param DOMElement $anchor Label anchor.
	 * @return string
	 */
	private function fragment_id( DOMElement $anchor ) {
		$href = trim( $anchor->getAttribute( 'href' ) );
		if ( 0 !== strpos( $href, '#' ) || 1 === strlen( $href ) ) {
			return '';
		}

		return rawurldecode( substr( $href, 1 ) );
	}

	/**
	 * Resolve exactly one element by ID.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @param string   $id    Element ID.
	 * @return DOMElement|null
	 */
	private function target_by_id( DOMXPath $xpath, $id ) {
		$targets = $xpath->query( '//*[@id=' . $this->xpath_literal( $id ) . ']' );
		if ( false === $targets || 1 !== $targets->length ) {
			return null;
		}

		$target = $targets->item( 0 );

		return $target instanceof DOMElement ? $target : null;
	}

	/**
	 * Load HTML without leaking libxml warnings into wp-admin.
	 *
	 * @param string $html Source HTML.
	 * @return DOMDocument|array<string,string>
	 */
	private function load_document( $html ) {
		if ( ! class_exists( 'DOMDocument' ) ) {
			return array( 'reason' => 'dom_extension_unavailable' );
		}

		if ( '' === trim( $html ) ) {
			return array( 'reason' => 'empty_html' );
		}

		$previous = libxml_use_internal_errors( true );
		$document = new DOMDocument();
		$loaded   = $document->loadHTML( '<?xml encoding="UTF-8">' . $html, LIBXML_NONET | LIBXML_NOWARNING | LIBXML_NOERROR );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $loaded ) {
			return array( 'reason' => 'malformed_html' );
		}

		return $document;
	}

	/**
	 * Collapse whitespace while preserving meaningful Persian joining characters.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function normalize_text( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( "\xC2\xA0", ' ', $text );
		$text = preg_replace( '/\s+/u', ' ', $text );

		return trim( is_string( $text ) ? $text : '' );
	}

	/**
	 * Quote a literal for XPath.
	 *
	 * @param string $value Literal value.
	 * @return string
	 */
	private function xpath_literal( $value ) {
		if ( false === strpos( $value, "'" ) ) {
			return "'" . $value . "'";
		}

		if ( false === strpos( $value, '"' ) ) {
			return '"' . $value . '"';
		}

		$parts = explode( "'", $value );

		return "concat('" . implode( "', \"'\", '", $parts ) . "')";
	}

	/**
	 * Build a consistent parser result.
	 *
	 * @param string                          $source       List identity.
	 * @param string                          $status       Result state.
	 * @param array<int,array<string,string>> $items        Items.
	 * @param string                          $date_context Date context.
	 * @param string                          $reason       Diagnostic reason.
	 * @return array<string,mixed>
	 */
	private function result( $source, $status, $items, $date_context, $reason ) {
		return array(
			'source'       => $source,
			'status'       => $status,
			'items'        => $items,
			'count'        => count( $items ),
			'date_context' => $date_context,
			'reason'       => $reason,
		);
	}
}
