<?php
/**
 * RGAA Test Helpers - Common utilities for accessibility checks.
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Test_Helpers
 */
class Test_Helpers {

	/**
	 * Check if element has alternative text (alt, aria-label, aria-labelledby, title).
	 *
	 * @param DOMElement $element DOM element.
	 * @param bool       $include_title Include title attribute.
	 * @return bool
	 */
	public static function has_alternative_text( $element, $include_title = false ) {
		if ( ! $element instanceof \DOMElement ) {
			return false;
		}
		$alt = trim( $element->getAttribute( 'alt' ) ?? '' );
		$aria_label = trim( $element->getAttribute( 'aria-label' ) ?? '' );
		$aria_labelledby = trim( $element->getAttribute( 'aria-labelledby' ) ?? '' );
		$title = trim( $element->getAttribute( 'title' ) ?? '' );

		$has = ! empty( $alt ) || ! empty( $aria_label ) || ! empty( $aria_labelledby );
		if ( $include_title ) {
			$has = $has || ! empty( $title );
		}
		return $has;
	}

	/**
	 * Check if element is decorative (role=presentation|none, aria-hidden=true).
	 *
	 * @param DOMElement $element DOM element.
	 * @return bool
	 */
	public static function is_decorative( $element ) {
		if ( ! $element instanceof \DOMElement ) {
			return false;
		}
		$role = $element->getAttribute( 'role' );
		$aria_hidden = $element->getAttribute( 'aria-hidden' );
		return in_array( $role, [ 'presentation', 'none' ], true ) || 'true' === $aria_hidden;
	}

	/**
	 * Check if element has an ancestor with given tag or role.
	 *
	 * @param DOMNode $element   Starting element.
	 * @param array   $tags     Tag names to match (e.g. ['fieldset']).
	 * @param array   $roles    Role values to match (e.g. ['group', 'radiogroup']).
	 * @return bool
	 */
	public static function has_ancestor_with( $element, $tags = [], $roles = [] ) {
		$parent = $element->parentNode;
		while ( $parent ) {
			$tag = strtolower( $parent->nodeName ?? '' );
			$role = ( $parent instanceof \DOMElement ) ? strtolower( $parent->getAttribute( 'role' ) ?? '' ) : '';
			if ( in_array( $tag, $tags, true ) || in_array( $role, $roles, true ) ) {
				return true;
			}
			$parent = $parent->parentNode ?? null;
		}
		return false;
	}

	/**
	 * Get trimmed attribute value.
	 *
	 * @param DOMElement $element DOM element.
	 * @param string     $attr    Attribute name.
	 * @return string
	 */
	public static function get_attr( $element, $attr ) {
		if ( ! $element instanceof \DOMElement ) {
			return '';
		}
		return trim( $element->getAttribute( $attr ) ?? '' );
	}

	/**
	 * Get truncated HTML of element.
	 *
	 * @param DOMElement $element DOM element.
	 * @param int        $max_len Max length.
	 * @return string
	 */
	public static function get_element_html( $element, $max_len = 100 ) {
		if ( ! $element instanceof \DOMElement ) {
			return '';
		}
		$html = $element->ownerDocument->saveHTML( $element );
		$html = preg_replace( '/\s+/', ' ', $html );
		if ( strlen( $html ) > $max_len ) {
			$html = substr( $html, 0, $max_len ) . '…';
		}
		return $html;
	}
}
