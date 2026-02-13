<?php
/**
 * RGAA Contrast Helper - WCAG contrast ratio calculation.
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Contrast_Helper
 */
class Contrast_Helper {

	/**
	 * WCAG AA minimum ratio for normal text (smaller than 24px).
	 */
	const RATIO_NORMAL = 4.5;

	/**
	 * WCAG AA minimum ratio for large text (>= 24px or bold >= 18.5px).
	 */
	const RATIO_LARGE = 3.0;

	/**
	 * Get contrast ratio between two colors (WCAG 2.1 formula).
	 *
	 * @param string $color1 Hex color (e.g. #fff or #ffffff).
	 * @param string $color2 Hex color.
	 * @return float Contrast ratio between 1 and 21.
	 */
	public static function get_contrast_ratio( $color1, $color2 ) {
		$l1 = self::get_relative_luminance( $color1 );
		$l2 = self::get_relative_luminance( $color2 );
		$lighter = max( $l1, $l2 );
		$darker = min( $l1, $l2 );
		return ( $lighter + 0.05 ) / ( $darker + 0.05 );
	}

	/**
	 * Get relative luminance of a color (WCAG 2.1).
	 *
	 * @param string $hex Hex color (#rgb or #rrggbb).
	 * @return float Luminance between 0 and 1.
	 */
	public static function get_relative_luminance( $hex ) {
		$rgb = self::hex_to_rgb( $hex );
		if ( ! $rgb ) {
			return 0.5;
		}

		$r = $rgb['r'] / 255;
		$g = $rgb['g'] / 255;
		$b = $rgb['b'] / 255;

		$r = $r <= 0.03928 ? $r / 12.92 : pow( ( $r + 0.055 ) / 1.055, 2.4 );
		$g = $g <= 0.03928 ? $g / 12.92 : pow( ( $g + 0.055 ) / 1.055, 2.4 );
		$b = $b <= 0.03928 ? $b / 12.92 : pow( ( $b + 0.055 ) / 1.055, 2.4 );

		return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
	}

	/**
	 * Convert hex to RGB.
	 *
	 * @param string $hex Hex color.
	 * @return array{r: int, g: int, b: int}|null
	 */
	public static function hex_to_rgb( $hex ) {
		$hex = ltrim( $hex, '#' );
		if ( strlen( $hex ) === 3 ) {
			$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
		}
		if ( strlen( $hex ) !== 6 || ! ctype_xdigit( $hex ) ) {
			return null;
		}
		return [
			'r' => (int) hexdec( substr( $hex, 0, 2 ) ),
			'g' => (int) hexdec( substr( $hex, 2, 2 ) ),
			'b' => (int) hexdec( substr( $hex, 4, 2 ) ),
		];
	}

	/**
	 * Check if contrast meets WCAG AA.
	 *
	 * @param string $foreground Hex foreground color.
	 * @param string $background Hex background color.
	 * @param bool   $is_large   True if large text (>= 24px or bold >= 18.5px).
	 * @return bool
	 */
	public static function meets_wcag_aa( $foreground, $background, $is_large = false ) {
		$ratio = self::get_contrast_ratio( $foreground, $background );
		$min_ratio = $is_large ? self::RATIO_LARGE : self::RATIO_NORMAL;
		return $ratio >= $min_ratio;
	}

	/**
	 * Build color palette from theme.json (slug => hex).
	 *
	 * @return array<string, string>
	 */
	public static function get_color_palette() {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return [];
		}
		$palette_by_origin = wp_get_global_settings( [ 'color', 'palette' ] );
		if ( ! is_array( $palette_by_origin ) ) {
			return [];
		}

		$palette = [];
		foreach ( [ 'custom', 'theme', 'default' ] as $origin ) {
			$items = $palette_by_origin[ $origin ] ?? [];
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				$slug = $item['slug'] ?? '';
				$color = $item['color'] ?? '';
				if ( $slug && $color ) {
					$palette[ $slug ] = self::normalize_hex( $color );
				}
			}
		}

		return $palette;
	}

	/**
	 * Build font size map from theme.json (slug => size in px).
	 *
	 * @return array<string, float>
	 */
	public static function get_font_size_map() {
		if ( ! function_exists( 'wp_get_global_settings' ) ) {
			return [];
		}
		$sizes_by_origin = wp_get_global_settings( [ 'typography', 'fontSizes' ] );
		if ( ! is_array( $sizes_by_origin ) ) {
			return [];
		}

		$map = [];
		foreach ( [ 'custom', 'theme', 'default' ] as $origin ) {
			$items = $sizes_by_origin[ $origin ] ?? [];
			if ( ! is_array( $items ) ) {
				continue;
			}
			foreach ( $items as $item ) {
				$slug = $item['slug'] ?? '';
				$size = $item['size'] ?? '';
				if ( $slug && $size ) {
					$map[ $slug ] = self::parse_size_to_px( $size );
				}
			}
		}

		return $map;
	}

	/**
	 * Parse CSS size to approximate pixels.
	 *
	 * @param string $size CSS size (e.g. "1.25rem", "20px").
	 * @return float
	 */
	public static function parse_size_to_px( $size ) {
		if ( preg_match( '/^([\d.]+)\s*px$/i', $size, $m ) ) {
			return (float) $m[1];
		}
		if ( preg_match( '/^([\d.]+)\s*rem$/i', $size, $m ) ) {
			return (float) $m[1] * 16;
		}
		if ( preg_match( '/^([\d.]+)\s*em$/i', $size, $m ) ) {
			return (float) $m[1] * 16;
		}
		return 16;
	}

	/**
	 * Normalize hex color.
	 *
	 * @param string $color Color (hex, rgb, etc.).
	 * @return string Hex color or empty.
	 */
	public static function normalize_hex( $color ) {
		$color = trim( strtolower( $color ) );
		if ( preg_match( '/^#([a-f0-9]{3}|[a-f0-9]{6})$/i', $color ) ) {
			return $color;
		}
		if ( preg_match( '/^([a-f0-9]{3}|[a-f0-9]{6})$/i', $color ) ) {
			return '#' . $color;
		}
		return $color;
	}

	/**
	 * Resolve color slug to hex.
	 *
	 * @param string $value Slug or hex.
	 * @param array  $palette Palette from get_color_palette().
	 * @return string|null Hex or null if not found.
	 */
	public static function resolve_color( $value, $palette ) {
		if ( empty( $value ) ) {
			return null;
		}
		$value = trim( $value );
		if ( isset( $palette[ $value ] ) ) {
			return $palette[ $value ];
		}
		if ( preg_match( '/^#?[a-f0-9]{3,8}$/i', $value ) ) {
			return self::normalize_hex( $value );
		}
		// Support rgb/rgba format.
		if ( preg_match( '/rgba?\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)/', $value, $m ) ) {
			return sprintf( '#%02x%02x%02x', (int) $m[1], (int) $m[2], (int) $m[3] );
		}
		// Support var(--wp--preset--color--slug).
		if ( preg_match( '/var\s*\(\s*--wp--preset--color--([a-z0-9-]+)\s*\)/i', $value, $m ) ) {
			$slug = $m[1];
			return isset( $palette[ $slug ] ) ? $palette[ $slug ] : null;
		}
		return null;
	}
}
