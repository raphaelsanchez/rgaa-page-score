<?php
/**
 * RGAA data loader - loads criteria, methodologies and tests registry from JSON.
 *
 * Data source: https://github.com/DISIC/accessibilite.numerique.gouv.fr
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Data
 */
class Data {

	const RGAA_BASE_URL = 'https://accessibilite.numerique.gouv.fr/methode/criteres-et-tests/';
	const DATA_DIR      = 'data';

	/**
	 * Cached criteria data.
	 *
	 * @var array|null
	 */
	protected static $criteria = null;

	/**
	 * Cached methodologies.
	 *
	 * @var array|null
	 */
	protected static $methodologies = null;

	/**
	 * Cached tests registry.
	 *
	 * @var array|null
	 */
	protected static $registry = null;

	/**
	 * Get the path to a data file.
	 *
	 * @param string $filename File name.
	 * @return string
	 */
	protected static function get_data_path( $filename ) {
		return RGAA_PAGE_SCORE_PLUGIN_DIR . self::DATA_DIR . '/' . $filename;
	}

	/**
	 * Load and parse a JSON file.
	 *
	 * @param string $filename File name.
	 * @return array|null
	 */
	protected static function load_json( $filename ) {
		$path = self::get_data_path( $filename );
		if ( ! file_exists( $path ) ) {
			return null;
		}
		$json = file_get_contents( $path );
		$data = json_decode( $json, true );
		return is_array( $data ) ? $data : null;
	}

	/**
	 * Get criteria data (from criteres.json).
	 *
	 * @return array
	 */
	public static function get_criteria() {
		if ( null === self::$criteria ) {
			$data = self::load_json( 'criteres.json' );
			self::$criteria = $data ? self::index_criteria( $data ) : [];
		}
		return self::$criteria;
	}

	/**
	 * Index criteria by topic.criterion format for easy lookup.
	 *
	 * @param array $data Raw criteres.json data.
	 * @return array
	 */
	protected static function index_criteria( $data ) {
		$indexed = [];
		$topics = $data['topics'] ?? [];

		foreach ( $topics as $topic ) {
			$topic_num = $topic['number'] ?? 0;
			$topic_name = $topic['topic'] ?? '';

			foreach ( $topic['criteria'] ?? [] as $item ) {
				$criterium = $item['criterium'] ?? [];
				$crit_num = $criterium['number'] ?? 0;
				$key = $topic_num . '.' . $crit_num;
				$indexed[ $key ] = [
					'topic'   => $topic_name,
					'number'  => $key,
					'title'   => $criterium['title'] ?? '',
					'tests'   => $criterium['tests'] ?? [],
				];
			}
		}

		return $indexed;
	}

	/**
	 * Get methodologies (from methodologies.json).
	 *
	 * @return array
	 */
	public static function get_methodologies() {
		if ( null === self::$methodologies ) {
			$data = self::load_json( 'methodologies.json' );
			self::$methodologies = is_array( $data ) ? $data : [];
		}
		return self::$methodologies;
	}

	/**
	 * Get tests registry (from tests-registry.json).
	 *
	 * @return array
	 */
	public static function get_registry() {
		if ( null === self::$registry ) {
			$data = self::load_json( 'tests-registry.json' );
			self::$registry = $data['automatable_tests'] ?? [];
		}
		return self::$registry;
	}

	/**
	 * Get criterion info for a test ID (e.g. "1.1.1").
	 *
	 * @param string $test_id Test ID (topic.criterion.test).
	 * @return array{number: string, title: string, url: string, methodology: string, test_description: string}
	 */
	public static function get_criterion_for_test( $test_id ) {
		$parts = explode( '.', $test_id );
		$topic_num = $parts[0] ?? '';
		$crit_num = $parts[1] ?? '';
		$test_num = $parts[2] ?? '';
		$criterion_key = $topic_num . '.' . $crit_num;

		$criteria = self::get_criteria();
		$methodologies = self::get_methodologies();
		$registry = self::get_registry();

		$criterion = $criteria[ $criterion_key ] ?? null;
		$title = $criterion['title'] ?? '';
		$methodology = $methodologies[ $test_id ] ?? '';

		// Test description from criteres.json (first line of test).
		$test_description = '';
		if ( $criterion && $test_num && isset( $criterion['tests'][ $test_num ] ) ) {
			$test_lines = $criterion['tests'][ $test_num ];
			$first_line = is_array( $test_lines ) ? ( $test_lines[0] ?? '' ) : $test_lines;
			$test_description = preg_replace( '/\[([^\]]+)\]\([^)]+\)/', '$1', $first_line );
		}

		// Fallback for custom tests (e.g. 9.1.0) not in criteres.json.
		if ( empty( $title ) && isset( $registry[ $test_id ]['description'] ) ) {
			$title = $registry[ $test_id ]['description'];
			$test_description = $registry[ $test_id ]['description'];
		}

		// Strip markdown links from title for display.
		$title = preg_replace( '/\[([^\]]+)\]\([^)]+\)/', '$1', $title );

		return [
			'number'           => $criterion_key ?: $test_id,
			'title'            => $title,
			'url'              => self::RGAA_BASE_URL . '#' . ( $criterion_key ?: $test_id ),
			'methodology'      => $methodology,
			'test_description' => $test_description,
		];
	}

	/**
	 * Get all test IDs from criteres.json (source of truth for RGAA structure).
	 *
	 * @return array Test IDs in order (e.g. ['1.1.1', '1.1.2', '1.2.1', ...])
	 */
	public static function get_all_tests_from_criteria() {
		$data = self::load_json( 'criteres.json' );
		if ( ! $data ) {
			return [];
		}

		$test_ids = [];
		$topics   = $data['topics'] ?? [];

		foreach ( $topics as $topic ) {
			$topic_num = $topic['number'] ?? 0;

			foreach ( $topic['criteria'] ?? [] as $item ) {
				$criterium = $item['criterium'] ?? [];
				$crit_num  = $criterium['number'] ?? 0;
				$tests     = $criterium['tests'] ?? [];

				foreach ( array_keys( $tests ) as $test_num ) {
					$test_ids[] = $topic_num . '.' . $crit_num . '.' . $test_num;
				}
			}
		}

		return $test_ids;
	}

	/**
	 * Get automatable tests: intersection of criteria tests + registry handlers.
	 * Order follows the official RGAA structure (criteres.json).
	 *
	 * @return array [test_id => config, ...]
	 */
	public static function get_automatable_tests_ordered() {
		$all_tests   = self::get_all_tests_from_criteria();
		$registry    = self::get_registry();

		// Custom tests not in criteria (e.g. 9.1.0 for content structure).
		$custom_tests = array_diff( array_keys( $registry ), $all_tests );

		$ordered = [];
		foreach ( $all_tests as $test_id ) {
			if ( isset( $registry[ $test_id ] ) ) {
				$ordered[ $test_id ] = $registry[ $test_id ];
			}
		}
		foreach ( $custom_tests as $test_id ) {
			$ordered[ $test_id ] = $registry[ $test_id ];
		}

		return $ordered;
	}

	/**
	 * Get the list of automatable test IDs in order.
	 *
	 * @return array
	 */
	public static function get_automatable_tests() {
		return array_keys( self::get_automatable_tests_ordered() );
	}
}
