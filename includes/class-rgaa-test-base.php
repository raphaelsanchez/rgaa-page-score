<?php
/**
 * RGAA Test Base - Base class for RGAA test implementations.
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Test_Base
 */
abstract class Test_Base {

	/**
	 * Build issue using RGAA data from criteres.json.
	 *
	 * @param string $test_id  Test ID.
	 * @param string $title    Short title override (optional).
	 * @param string $message  Issue message (optional).
	 * @param string $html     HTML excerpt (optional).
	 * @return array
	 */
	protected static function build_issue( $test_id, $title = '', $message = '', $html = '' ) {
		$criterion = Data::get_criterion_for_test( $test_id );
		return [
			'criterion' => $criterion['number'],
			'test_id'   => $test_id,
			'title'     => $title ?: $criterion['title'],
			'message'   => $message ?: $criterion['test_description'],
			'html'      => $html,
			'url'       => $criterion['url'],
		];
	}

	/**
	 * Run test on a collection of elements, collecting failures.
	 *
	 * @param DOMNodeList $elements  Elements to test.
	 * @param string      $test_id   Test ID.
	 * @param callable    $is_fail   Callback( DOMElement $el ): bool - true if element fails.
	 * @param callable    $get_html  Optional. Callback( DOMElement $el ): string for HTML excerpt.
	 * @return array{passed: bool, issues: array}
	 */
	protected static function run_elements_test( $elements, $test_id, $is_fail, $get_html = null ) {
		$issues = [];
		$get_html = $get_html ?? [ Test_Helpers::class, 'get_element_html' ];

		foreach ( $elements as $el ) {
			if ( ! $el instanceof \DOMElement ) {
				continue;
			}
			if ( call_user_func( $is_fail, $el ) ) {
				$issues[] = self::build_issue( $test_id, '', '', call_user_func( $get_html, $el ) );
			}
		}

		return [
			'passed' => empty( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * Return test handlers: handler_name => callable.
	 *
	 * @return array<string, callable>
	 */
	abstract public static function get_handlers();
}
