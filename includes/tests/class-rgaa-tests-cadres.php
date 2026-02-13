<?php
/**
 * RGAA Tests - Cadres / Frames (criterion 2.1).
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
 * Class Cadres
 */
class Cadres extends Test_Base {

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'iframe_title' => [ __CLASS__, 'test_iframe_title' ],
		];
	}

	/**
	 * Test 2.1.1: Iframe/frame must have title attribute.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_iframe_title( \DOMDocument $dom, $test_id ) {
		$issues = [];
		foreach ( [ 'iframe', 'frame' ] as $tag ) {
			$frames = $dom->getElementsByTagName( $tag );
			foreach ( $frames as $frame ) {
				if ( empty( Test_Helpers::get_attr( $frame, 'title' ) ) ) {
					$issues[] = self::build_issue( $test_id, '', '', Test_Helpers::get_element_html( $frame ) );
				}
			}
		}
		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}
}
