<?php
/**
 * RGAA HTML Scanner - Orchestrates accessibility tests from official RGAA data.
 *
 * Data source: https://github.com/DISIC/accessibilite.numerique.gouv.fr
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

use RGAA\Score\Tests\Images;
use RGAA\Score\Tests\Cadres;
use RGAA\Score\Tests\Contrast;
use RGAA\Score\Tests\Tables;
use RGAA\Score\Tests\Links;
use RGAA\Score\Tests\Forms;
use RGAA\Score\Tests\Structure;
use RGAA\Score\Tests\Navigation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Scanner
 */
class Scanner {

	/**
	 * Handler method mapping (handler_name => callable).
	 *
	 * @var array<string, callable>
	 */
	protected $handlers = [];

	/**
	 * Test class registry.
	 *
	 * @var array<string>
	 */
	protected static $test_classes = [
		Images::class,
		Cadres::class,
		Contrast::class,
		Tables::class,
		Links::class,
		Forms::class,
		Structure::class,
		Navigation::class,
	];

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->handlers = self::collect_handlers();
	}

	/**
	 * Collect all handlers from test classes.
	 *
	 * @return array<string, callable>
	 */
	protected static function collect_handlers() {
		$handlers = [];
		foreach ( self::$test_classes as $class ) {
			if ( ! class_exists( $class ) || ! method_exists( $class, 'get_handlers' ) ) {
				continue;
			}
			$handlers = array_merge( $handlers, $class::get_handlers() );
		}
		return $handlers;
	}

	/**
	 * Register a new test class (for extensibility).
	 *
	 * @param string $class_name Fully qualified class name.
	 */
	public static function register_test_class( $class_name ) {
		if ( ! in_array( $class_name, self::$test_classes, true ) ) {
			self::$test_classes[] = $class_name;
		}
	}

	/**
	 * Analyze a post's content.
	 *
	 * @param int $post_id Post ID.
	 * @return array{score: int, issues: array}
	 */
	public function analyze_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return [ 'score' => 0, 'issues' => [] ];
		}

		$html = $this->get_post_html( $post );
		return $this->analyze_html( $html, [ 'post' => $post ] );
	}

	/**
	 * Get HTML content from post.
	 *
	 * @param WP_Post $post Post object.
	 * @return string HTML content.
	 */
	protected function get_post_html( $post ) {
		$title   = '<h1>' . esc_html( $post->post_title ) . '</h1>';
		$content = apply_filters( 'the_content', $post->post_content );
		return $title . $content;
	}

	/**
	 * Analyze HTML string using criteria from criteres.json.
	 *
	 * @param string $html    HTML content.
	 * @param array  $context Optional. Context data (e.g. ['post' => WP_Post] for block parsing).
	 * @return array{score: int, issues: array}
	 */
	public function analyze_html( $html, $context = [] ) {
		$issues = [];
		$passed = 0;

		$dom = $this->load_html( $html );
		if ( ! $dom ) {
			return [ 'score' => 0, 'issues' => [] ];
		}

		$tests = Data::get_automatable_tests_ordered();
		$tests_count = count( $tests );

		if ( 0 === $tests_count ) {
			return [ 'score' => 0, 'issues' => [] ];
		}

		foreach ( $tests as $test_id => $config ) {
			$handler = $config['handler'] ?? '';
			if ( empty( $handler ) || ! isset( $this->handlers[ $handler ] ) ) {
				continue;
			}

			$result = call_user_func( $this->handlers[ $handler ], $dom, $test_id, $context );
			if ( $result['passed'] ) {
				++$passed;
			} else {
				$issues = array_merge( $issues, $result['issues'] );
			}
		}

		$score = (int) round( ( $passed / $tests_count ) * 100 );

		return [
			'score'  => min( 100, $score ),
			'issues' => $issues,
		];
	}

	/**
	 * Load HTML into DOMDocument.
	 *
	 * @param string $html HTML string.
	 * @return \DOMDocument|null DOMDocument or null on failure.
	 */
	protected function load_html( $html ) {
		$dom = new \DOMDocument();
		libxml_use_internal_errors( true );
		$wrapped = '<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>' . $html . '</body></html>';
		$dom->loadHTML( $wrapped );
		libxml_clear_errors();
		return $dom;
	}
}
