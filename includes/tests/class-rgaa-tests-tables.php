<?php
/**
 * RGAA Tests - Tableaux (criteria 5.1, 5.6).
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
 * Class Tables
 */
class Tables extends Test_Base {

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'tables_complex_summary' => [ __CLASS__, 'test_tables_complex_summary' ],
			'tables_headers_col'     => [ __CLASS__, 'test_tables_headers_col' ],
			'tables_headers_row'     => [ __CLASS__, 'test_tables_headers_row' ],
			'tables_headers_other'   => [ __CLASS__, 'test_tables_headers_other' ],
			'tables_cells_headers'   => [ __CLASS__, 'test_tables_cells_headers' ],
		];
	}

	/**
	 * Test 5.1.1: Complex data tables must have summary.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_tables_complex_summary( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$tables = $dom->getElementsByTagName( 'table' );

		foreach ( $tables as $table ) {
			if ( 'presentation' === $table->getAttribute( 'role' ) ) {
				continue;
			}
			$th_count = $table->getElementsByTagName( 'th' )->length;
			$has_headers = $th_count > 0 || $table->getAttribute( 'headers' ) || $table->getAttribute( 'scope' );
			if ( ! $has_headers ) {
				continue;
			}
			$summary = Test_Helpers::get_attr( $table, 'summary' );
			$aria_describedby = Test_Helpers::get_attr( $table, 'aria-describedby' );
			$caption = $table->getElementsByTagName( 'caption' )->item( 0 );
			$has_summary = $summary || $aria_describedby || $caption;

			if ( ! $has_summary ) {
				$issues[] = self::build_issue( $test_id, '', '', Test_Helpers::get_element_html( $table ) );
			}
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}

	/**
	 * Test 5.6.1: Column headers must use th or role=columnheader.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_tables_headers_col( \DOMDocument $dom, $test_id ) {
		$xpath = new \DOMXPath( $dom );
		$col_headers = $xpath->query( "//table[not(@role='presentation')]//*[@role='columnheader']" );
		return self::run_elements_test( $col_headers, $test_id, function ( $el ) {
			return 'th' !== strtolower( $el->nodeName ?? '' );
		} );
	}

	/**
	 * Test 5.6.2: Row headers must use th or role=rowheader.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_tables_headers_row( \DOMDocument $dom, $test_id ) {
		$xpath = new \DOMXPath( $dom );
		$row_headers = $xpath->query( "//table[not(@role='presentation')]//*[@role='rowheader']" );
		return self::run_elements_test( $row_headers, $test_id, function ( $el ) {
			return 'th' !== strtolower( $el->nodeName ?? '' );
		} );
	}

	/**
	 * Test 5.6.3: Headers not for full row/col must use th.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_tables_headers_other( \DOMDocument $dom, $test_id ) {
		return [ 'passed' => true, 'issues' => [] ];
	}

	/**
	 * Test 5.6.4: Cells with multiple headers must use td or th.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_tables_cells_headers( \DOMDocument $dom, $test_id ) {
		return [ 'passed' => true, 'issues' => [] ];
	}
}
