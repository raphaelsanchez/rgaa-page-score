<?php
/**
 * RGAA Tests - Formulaires (criteria 11.1, 11.5).
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
 * Class Forms
 */
class Forms extends Test_Base {

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'form_labels'   => [ __CLASS__, 'test_form_labels' ],
			'form_fieldset' => [ __CLASS__, 'test_form_fieldset' ],
		];
	}

	/**
	 * Test 11.1.1: Form inputs must have labels.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_form_labels( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$inputs = $dom->getElementsByTagName( 'input' );
		$skip_types = [ 'hidden', 'submit', 'button', 'image', 'reset' ];

		foreach ( $inputs as $input ) {
			$type = $input->getAttribute( 'type' );
			if ( in_array( $type, $skip_types, true ) ) {
				continue;
			}
			$has_label = Test_Helpers::get_attr( $input, 'aria-label' )
				|| Test_Helpers::get_attr( $input, 'aria-labelledby' )
				|| Test_Helpers::get_attr( $input, 'title' );

			if ( $input->getAttribute( 'id' ) ) {
				foreach ( $dom->getElementsByTagName( 'label' ) as $label ) {
					if ( $label->getAttribute( 'for' ) === $input->getAttribute( 'id' ) ) {
						$has_label = true;
						break;
					}
				}
			}

			if ( ! $has_label ) {
				$has_label = Test_Helpers::has_ancestor_with( $input, [ 'label' ], [] );
			}

			if ( ! $has_label ) {
				$issues[] = self::build_issue( $test_id, '', '', Test_Helpers::get_element_html( $input ) );
			}
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}

	/**
	 * Test 11.5.1: Radio groups must be in fieldset or role=group.
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_form_fieldset( \DOMDocument $dom, $test_id ) {
		$issues = [];
		$radio_names = [];

		foreach ( $dom->getElementsByTagName( 'input' ) as $input ) {
			if ( 'radio' !== $input->getAttribute( 'type' ) ) {
				continue;
			}
			$name = $input->getAttribute( 'name' );
			if ( ! $name ) {
				continue;
			}
			$radio_names[ $name ] = $radio_names[ $name ] ?? [];
			$radio_names[ $name ][] = $input;
		}

		foreach ( $radio_names as $inputs ) {
			if ( count( $inputs ) < 2 ) {
				continue;
			}
			$first = $inputs[0];
			if ( ! Test_Helpers::has_ancestor_with( $first, [ 'fieldset' ], [ 'group', 'radiogroup' ] ) ) {
				$issues[] = self::build_issue( $test_id, '', '', Test_Helpers::get_element_html( $first ) );
			}
		}

		return [ 'passed' => empty( $issues ), 'issues' => $issues ];
	}
}
