<?php
/**
 * RGAA Tests - Structuration de l'information (criteria 9.1).
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
 * Class Structure
 */
class Structure extends Test_Base {

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'heading_hierarchy' => [ __CLASS__, 'test_heading_hierarchy' ],
			'content_structure' => [ __CLASS__, 'test_basic_structure' ],
		];
	}

	/**
	 * Test 9.1.1: Heading hierarchy (h1-h6).
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_heading_hierarchy( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$last_level = 0;
		$xpath = new \DOMXPath( $dom );
		$headings = $xpath->query( '//*[self::h1 or self::h2 or self::h3 or self::h4 or self::h5 or self::h6]' );

		foreach ( $headings as $heading ) {
			$tag = strtolower( $heading->nodeName );
			$level = (int) substr( $tag, 1 );

			if ( $level > $last_level + 1 ) {
				$issues[] = self::build_issue(
					$test_id,
					__( 'Incorrect heading hierarchy', 'rgaa-page-score' ),
					sprintf(
						/* translators: %1$d: previous level, %2$d: current level */
						__( 'Do not skip levels: after h%1$d, use h%2$d (not h%2$d directly).', 'rgaa-page-score' ),
						$last_level,
						$level
					),
					Test_Helpers::get_element_html( $heading )
				);
			}
			$last_level = $level;
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}

	/**
	 * Test 9.1.0: Basic structure (has at least one heading for long content).
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_basic_structure( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$has_heading = false;
		for ( $i = 1; $i <= 6; $i++ ) {
			if ( $dom->getElementsByTagName( 'h' . $i )->length > 0 ) {
				$has_heading = true;
				break;
			}
		}

		$body = $dom->getElementsByTagName( 'body' )->item( 0 ) ?? $dom->documentElement;
		if ( $body && strlen( trim( $body->textContent ?? '' ) ) > 200 && ! $has_heading ) {
			$issues[] = self::build_issue( $test_id, '', '', '' );
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}
}
