<?php
/**
 * RGAA Tests - Navigation (criterion 12.7).
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
 * Class Navigation
 */
class Navigation extends Test_Base {

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'skip_link' => [ __CLASS__, 'test_skip_link' ],
		];
	}

	/**
	 * Test 12.7.1: Skip link to main content must be present.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_skip_link( \DOMDocument $dom, $test_id ) {
		$main = $dom->getElementsByTagName( 'main' )->item( 0 );
		if ( ! $main ) {
			return [ 'passed' => true, 'issues' => [] ];
		}

		$main_id = Test_Helpers::get_attr( $main, 'id' );
		if ( ! $main_id ) {
			return [ 'passed' => true, 'issues' => [] ];
		}

		$has_skip_link = false;
		foreach ( $dom->getElementsByTagName( 'a' ) as $link ) {
			$href = $link->getAttribute( 'href' );
			if ( $href && ( '#' . $main_id === $href || preg_match( '/#' . preg_quote( $main_id, '/' ) . '$/', $href ) ) ) {
				$has_skip_link = true;
				break;
			}
		}

		if ( $has_skip_link ) {
			return [ 'passed' => true, 'issues' => [] ];
		}

		return [
			'passed' => false,
			'issues' => [ self::build_issue( $test_id, '', '', '' ) ],
		];
	}
}
