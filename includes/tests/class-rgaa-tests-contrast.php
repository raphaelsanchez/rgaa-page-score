<?php
/**
 * RGAA Tests - Contraste des couleurs (criterion 3.2).
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score\Tests;

use RGAA\Score\Test_Base;
use RGAA\Score\Contrast_Helper;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Contrast
 */
class Contrast extends Test_Base {

	/** Default background color when block has none (inherited from body). */
	const DEFAULT_BODY_BACKGROUND = '#ffffff';

	/**
	 * Get handlers.
	 *
	 * @return array<string, callable>
	 */
	public static function get_handlers() {
		return [
			'contrast_blocks' => [ __CLASS__, 'test_contrast_blocks' ],
		];
	}

	/**
	 * Test 3.2.1: Text/background contrast in blocks (WCAG AA).
	 *
	 * @param \DOMDocument $dom    DOM document.
	 * @param string      $test_id Test ID.
	 * @param array       $context Context with 'post' for block parsing.
	 * @return array{passed: bool, issues: array}
	 */
	public static function test_contrast_blocks( \DOMDocument $dom, $test_id, $context = [] ) {
		$post = $context['post'] ?? null;
		if ( ! $post || ! function_exists( 'parse_blocks' ) ) {
			return [ 'passed' => true, 'issues' => [] ];
		}

		$blocks = parse_blocks( $post->post_content );
		$palette = Contrast_Helper::get_color_palette();
		$font_sizes = Contrast_Helper::get_font_size_map();
		$issues = [];

		self::check_blocks_contrast( $blocks, $palette, $font_sizes, $test_id, $issues );

		return [
			'passed' => empty( $issues ),
			'issues' => $issues,
		];
	}

	/**
	 * Recursively check blocks for contrast issues.
	 *
	 * @param array  $blocks     Parsed blocks.
	 * @param array  $palette    Color palette.
	 * @param array  $font_sizes Font size map.
	 * @param string $test_id   Test ID.
	 * @param array  $issues    Issues [by reference].
	 */
	protected static function check_blocks_contrast( $blocks, $palette, $font_sizes, $test_id, &$issues ) {
		foreach ( $blocks as $block ) {
			if ( ! is_array( $block ) || empty( $block['blockName'] ) ) {
				continue;
			}

			$attrs = $block['attrs'] ?? [];
			$style = $attrs['style'] ?? [];

			$text_color = $attrs['customTextColor'] ?? $attrs['textColor'] ?? null;
			$bg_color = $attrs['customBackgroundColor'] ?? $attrs['backgroundColor'] ?? null;

			if ( isset( $style['color']['text'] ) ) {
				$text_color = $style['color']['text'];
			}
			if ( isset( $style['color']['background'] ) ) {
				$bg_color = $style['color']['background'];
			}

			$text_hex = $text_color ? Contrast_Helper::resolve_color( $text_color, $palette ) : null;
			$bg_hex = $bg_color ? Contrast_Helper::resolve_color( $bg_color, $palette ) : null;

			// When text color is set but background is not, use body default.
			if ( $text_hex && ! $bg_hex ) {
				$bg_hex = self::DEFAULT_BODY_BACKGROUND;
			}
			// When background is set but text color is not, assume default black text.
			if ( $bg_hex && ! $text_hex ) {
				$text_hex = '#000000';
			}

			if ( $text_hex && $bg_hex ) {
				$font_size_px = self::get_block_font_size_px( $attrs, $font_sizes );
				$is_bold = ! empty( $style['typography']['fontWeight'] ) && in_array( (string) $style['typography']['fontWeight'], [ '700', 'bold' ], true );
				$is_large = $font_size_px >= 24 || ( $is_bold && $font_size_px >= 18.5 );

				if ( ! Contrast_Helper::meets_wcag_aa( $text_hex, $bg_hex, $is_large ) ) {
					$ratio = round( Contrast_Helper::get_contrast_ratio( $text_hex, $bg_hex ), 1 );
					$min_required = $is_large ? 3.0 : 4.5;
					$issues[] = self::build_issue(
						$test_id,
						'',
						sprintf(
							/* translators: 1: contrast ratio, 2: minimum required */
							__( 'Insufficient contrast: ratio %1$s:1 (minimum required %2$s:1 for WCAG AA).', 'rgaa-page-score' ),
							$ratio,
							$min_required
						),
						$block['blockName'] . ' (' . $text_hex . ' / ' . $bg_hex . ')'
					);
				}
			}

			if ( ! empty( $block['innerBlocks'] ) ) {
				self::check_blocks_contrast( $block['innerBlocks'], $palette, $font_sizes, $test_id, $issues );
			}
		}
	}

	/**
	 * Get font size in pixels for a block.
	 *
	 * @param array $attrs     Block attributes.
	 * @param array $font_sizes Font size map.
	 * @return float
	 */
	protected static function get_block_font_size_px( $attrs, $font_sizes ) {
		$style = $attrs['style'] ?? [];
		$font_size = $attrs['fontSize'] ?? null;
		$custom_font_size = $attrs['customFontSize'] ?? null;
		$style_font_size = $style['typography']['fontSize'] ?? null;

		if ( $custom_font_size ) {
			return Contrast_Helper::parse_size_to_px( $custom_font_size );
		}
		if ( $style_font_size ) {
			return Contrast_Helper::parse_size_to_px( $style_font_size );
		}
		if ( $font_size && isset( $font_sizes[ $font_size ] ) ) {
			return $font_sizes[ $font_size ];
		}
		return 16;
	}
}
