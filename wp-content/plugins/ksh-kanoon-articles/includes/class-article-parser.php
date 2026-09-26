<?php
/**
 * Pure extraction, normalization, and validation boundary.
 *
 * @package KSH_Kanoon_Articles
 */

namespace KSH\KanoonArticles;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Parses the two bounded Kanoon list shapes without WordPress state.
 */
final class Article_Parser {

	const LATEST_LIMIT = 20;

	/**
	 * Parse Latest from the approved /Article/Days document.
	 *
	 * @param string $html Source HTML.
	 * @return array<string,mixed>
	 */
	public function parse_latest( $html ) {
		$document = $this->load_document( $html );
		if ( is_array( $document ) ) {
			return $this->result( 'latest', 'failure', array(), '', $document['reason'] );
		}

		$xpath        = new DOMXPath( $document );
		$date_heading = $this->find_latest_date_heading( $xpath );
		if ( ! $date_heading ) {
			return $this->result( 'latest', 'failure', array(), '', 'latest_date_boundary_missing' );
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
		$date_context = $this->normalize_text( $date_heading->textContent );
		$items        = array();
		$seen         = array();
		$node         = $date_heading;

		$node = $this->next_node( $node );
		while ( $node ) {
			$current = $node;
			$node    = $this->next_node( $node );

			if ( $this->is_sidebar_boundary( $current ) ) {
				break;
			}

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
			if ( ! $current instanceof DOMElement || 'a' !== strtolower( $current->tagName ) ) {
				continue;
			}

			$item = $this->normalize_article_anchor( $current, 'latest', $date_context );
			if ( ! $item || isset( $seen[ $item['url'] ] ) ) {
				continue;
			}

			$seen[ $item['url'] ] = true;
			$items[]              = $item;

			if ( self::LATEST_LIMIT === count( $items ) ) {
				break;
			}
		}

		if ( 0 === count( $items ) ) {
			return $this->result( 'latest', 'failure', array(), $date_context, 'latest_zero_valid_items' );
		}

		return $this->result( 'latest', 'success', $items, $date_context, '' );
	}

	/**
	 * Parse Weekly Popular by binding semantic tab label to its fragment target.
	 *
	 * Monthly Popular is required as an independently resolvable sibling boundary so the
	 * parser cannot silently treat the nearby monthly list as Weekly Popular.
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
		$weekly_links  = $this->find_exact_text_anchors( $xpath, 'پربازدید هفته' );
		$monthly_links = $this->find_exact_text_anchors( $xpath, 'پربازدید ماه' );

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

		$anchors = $xpath->query( './/a[@href]', $weekly_target );
		$items   = array();
		$seen    = array();

		if ( false === $anchors ) {
			return $this->result( 'weekly_popular', 'failure', array(), '', 'weekly_anchor_query_failed' );
		}

		foreach ( $anchors as $anchor ) {
			if ( ! $anchor instanceof DOMElement ) {
				continue;
			}

			$item = $this->normalize_article_anchor( $anchor, 'weekly_popular', '' );
			if ( ! $item || isset( $seen[ $item['url'] ] ) ) {
				continue;
			}

			$seen[ $item['url'] ] = true;
			$items[]              = $item;
		}

		if ( 0 === count( $items ) ) {
			return $this->result( 'weekly_popular', 'failure', array(), '', 'weekly_zero_valid_items' );
		}

		return $this->result( 'weekly_popular', 'success', $items, '', '' );
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
	 * Remove bounded relative-time/view metadata currently carried inside Latest links.
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
	 * Find one Latest page date heading.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @return DOMElement|null
	 */
	private function find_latest_date_heading( DOMXPath $xpath ) {
		$headings = $xpath->query( '//h1|//h2|//h3|//h4' );
		if ( false === $headings ) {
			return null;
		}

		$weekday_pattern = '(?:شنبه|یکشنبه|دوشنبه|سه\s*شنبه|چهارشنبه|پنج\s*شنبه|جمعه)';
		$digit           = '0-9۰-۹٠-٩';
		$month_pattern   = '(?:فروردین|اردیبهشت|خرداد|تیر|مرداد|شهریور|مهر|آبان|آذر|دی|بهمن|اسفند)';
		$pattern         = '/^' . $weekday_pattern . '\s+[' . $digit . ']{1,2}\s+' . $month_pattern . '\s+[' . $digit . ']{4}$/u';

		foreach ( $headings as $heading ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
			if ( $heading instanceof DOMElement && 1 === preg_match( $pattern, $this->normalize_text( $heading->textContent ) ) ) {
				return $heading;
			}
		}

		return null;
	}

	/**
	 * Determine whether traversal reached a known sidebar list boundary.
	 *
	 * @param DOMNode $node Current node.
	 * @return bool
	 */
	private function is_sidebar_boundary( DOMNode $node ) {
		if ( ! $node instanceof DOMElement ) {
			return false;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
		$tag = strtolower( $node->tagName );
		if ( ! in_array( $tag, array( 'h1', 'h2', 'h3', 'h4' ), true ) ) {
			return false;
		}

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
		$text = $this->normalize_text( $node->textContent );

		return in_array( $text, array( 'پربازدیدهای ماه', 'پربازدید ماه', 'تازه ها', 'تازه‌ها' ), true );
	}

	/**
	 * Find anchors with exact normalized label text.
	 *
	 * @param DOMXPath $xpath XPath instance.
	 * @param string   $text  Expected label.
	 * @return array<int,DOMElement>
	 */
	private function find_exact_text_anchors( DOMXPath $xpath, $text ) {
		$anchors = $xpath->query( '//a[@href]' );
		$matches = array();

		if ( false === $anchors ) {
			return $matches;
		}

		foreach ( $anchors as $anchor ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
			if ( $anchor instanceof DOMElement && $text === $this->normalize_text( $anchor->textContent ) ) {
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
	 * Document-order traversal starting after a node.
	 *
	 * @param DOMNode $node Current node.
	 * @return DOMNode|null
	 */
	private function next_node( DOMNode $node ) {
		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
		if ( $node->firstChild ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
			return $node->firstChild;
		}

		while ( $node ) {
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
			if ( $node->nextSibling ) {
				// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
				return $node->nextSibling;
			}
			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- Native DOM property.
			$node = $node->parentNode;
		}

		return null;
	}

	/**
	 * Collapse whitespace and invisible separators without changing meaningful content.
	 *
	 * @param string $text Raw text.
	 * @return string
	 */
	private function normalize_text( $text ) {
		$text = html_entity_decode( (string) $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$text = str_replace( array( "\xC2\xA0", "\xE2\x80\x8C" ), ' ', $text );
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
	 * @param string                   $source       List identity.
	 * @param string                   $status       Result state.
	 * @param array<int,array<string,string>> $items Items.
	 * @param string                   $date_context Date context.
	 * @param string                   $reason       Diagnostic reason.
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
