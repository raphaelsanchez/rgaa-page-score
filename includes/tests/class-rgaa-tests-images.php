<?php
/**
 * RGAA Tests - Images (criteria 1.1, 1.2).
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
 * Class Images
 */
class Images extends Test_Base {

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'images_alt'             => [ __CLASS__, 'test_images_alt' ],
			'images_area_alt'        => [ __CLASS__, 'test_images_area_alt' ],
			'images_input_image_alt' => [ __CLASS__, 'test_images_input_image_alt' ],
			'images_svg_alt'         => [ __CLASS__, 'test_images_svg_alt' ],
			'decorative_images'      => [ __CLASS__, 'test_decorative_images' ],
		];
	}

	/**
	 * Test 1.1.1: Images with information must have alt text.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_images_alt( \DOMDocument $dom, $test_id ) {
		$imgs = $dom->getElementsByTagName( 'img' );
		return self::run_elements_test( $imgs, $test_id, function ( $img ) {
			if ( Test_Helpers::is_decorative( $img ) ) {
				return false;
			}
			return ! Test_Helpers::has_alternative_text( $img );
		} );
	}

	/**
	 * Test 1.1.2: Area elements in image maps must have alt text.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_images_area_alt( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$areas = $dom->getElementsByTagName( 'area' );

		foreach ( $areas as $area ) {
			if ( empty( trim( $area->getAttribute( 'href' ) ?? '' ) ) ) {
				continue;
			}
			if ( Test_Helpers::is_decorative( $area ) ) {
				continue;
			}
			if ( ! Test_Helpers::has_alternative_text( $area, true ) ) {
				$issues[] = self::build_issue( $test_id, '', '', Test_Helpers::get_element_html( $area ) );
			}
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}

	/**
	 * Test 1.1.3: Input type=image must have alt text.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_images_input_image_alt( \DOMDocument $dom, $test_id ) {
		$xpath = new \DOMXPath( $dom );
		$inputs = $xpath->query( "//input[@type='image']" );
		return self::run_elements_test( $inputs, $test_id, function ( $el ) {
			return ! Test_Helpers::has_alternative_text( $el, true );
		} );
	}

	/**
	 * Test 1.1.5: SVG with role=img must have alternative text.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_images_svg_alt( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$svgs = $dom->getElementsByTagName( 'svg' );

		foreach ( $svgs as $svg ) {
			if ( 'img' !== $svg->getAttribute( 'role' ) ) {
				continue;
			}
			$aria_label = Test_Helpers::get_attr( $svg, 'aria-label' );
			$aria_labelledby = Test_Helpers::get_attr( $svg, 'aria-labelledby' );
			$title_elem = $svg->getElementsByTagName( 'title' )->item( 0 );
			$desc_elem = $svg->getElementsByTagName( 'desc' )->item( 0 );
			$title_text = $title_elem ? trim( $title_elem->textContent ?? '' ) : '';
			$desc_text = $desc_elem ? trim( $desc_elem->textContent ?? '' ) : '';
			$svg_title = Test_Helpers::get_attr( $svg, 'title' );

			$has_alternative = $aria_label || $aria_labelledby || $title_text || $desc_text || $svg_title;

			if ( ! $has_alternative ) {
				$issues[] = self::build_issue( $test_id, '', '', Test_Helpers::get_element_html( $svg ) );
			}
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}

	/**
	 * Test 1.2.1: Decorative images must be properly ignored.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_decorative_images( \DOMDocument $dom, $test_id ) {
		$imgs = $dom->getElementsByTagName( 'img' );
		return self::run_elements_test( $imgs, $test_id, function ( $img ) {
			$alt = $img->getAttribute( 'alt' );
			if ( ! empty( trim( $alt ?? '' ) ) ) {
				return false;
			}
			return ( '' === $alt || null === $alt ) && ( $img->hasAttribute( 'aria-label' ) || $img->hasAttribute( 'title' ) );
		} );
	}
}
