<?php
/**
 * RGAA Tests - Liens (criteria 6.1, 6.2).
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score\Tests;

use RGAA\Score\Test_Base;
use RGAA\Score\Test_Helpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Links
 */
class Links extends Test_Base {

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'links_text'      => [ __CLASS__, 'test_links_text' ],
			'links_have_label' => [ __CLASS__, 'test_links_have_label' ],
		];
	}

	/**
	 * Test 6.1.1: Links must have meaningful text.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_links_text( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$links = $dom->getElementsByTagName( 'a' );
		$generic_pattern = '/^(cliquez\s+ici|en\s+savoir\s+plus|lire\s+la\s+suite|voir\s+plus|ici|lien)$/iu';

		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );
			if ( empty( $href ) || '#' === $href ) {
				continue;
			}
			$text = trim( $link->textContent ?? '' );
			$aria_label = Test_Helpers::get_attr( $link, 'aria-label' );
			$title = Test_Helpers::get_attr( $link, 'title' );

			$has_meaningful_text = $text || $aria_label || $title;
			$generic_text = preg_match( $generic_pattern, $text );

			if ( ! $has_meaningful_text ) {
				$issues[] = self::build_issue(
					$test_id,
					__( 'Link without explicit text', 'rgaa-page-score' ),
					__( 'Each link must have an explicit label (link text, aria-label or title).', 'rgaa-page-score' ),
					Test_Helpers::get_element_html( $link )
				);
			} elseif ( $generic_text ) {
				$issues[] = self::build_issue(
					$test_id,
					__( 'Generic link label', 'rgaa-page-score' ),
					__( 'Avoid generic labels like "Click here" or "Learn more". Prefer descriptive text.', 'rgaa-page-score' ),
					Test_Helpers::get_element_html( $link )
				);
			}
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}

	/**
	 * Test 6.2.1: Each link must have a label.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_links_have_label( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$links = $dom->getElementsByTagName( 'a' );

		foreach ( $links as $link ) {
			$href = $link->getAttribute( 'href' );
			if ( empty( $href ) || '#' === $href ) {
				continue;
			}
			$text = trim( $link->textContent ?? '' );
			$aria_label = Test_Helpers::get_attr( $link, 'aria-label' );
			$title = Test_Helpers::get_attr( $link, 'title' );

			$has_img_with_alt = false;
			foreach ( $link->getElementsByTagName( 'img' ) as $img ) {
				if ( Test_Helpers::get_attr( $img, 'alt' ) ) {
					$has_img_with_alt = true;
					break;
				}
			}

			if ( ! $text && ! $aria_label && ! $title && ! $has_img_with_alt ) {
				$issues[] = self::build_issue( $test_id, '', '', Test_Helpers::get_element_html( $link ) );
			}
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}
}
