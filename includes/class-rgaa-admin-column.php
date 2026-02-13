<?php
/**
 * Admin column for RGAA score in posts/pages list.
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Admin_Column
 */
class Admin_Column {

	/**
	 * Post types to show the column.
	 *
	 * @var array
	 */
	protected static $post_types = [ 'post', 'page' ];

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		foreach ( self::$post_types as $post_type ) {
			add_filter( "manage_{$post_type}_posts_columns", [ __CLASS__, 'add_column' ] );
			add_action( "manage_{$post_type}_posts_custom_column", [ __CLASS__, 'render_column' ], 10, 2 );
		}
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_styles' ] );
	}

	/**
	 * Enqueue column styles.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_styles( $hook ) {
		if ( 'edit.php' !== $hook ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, self::$post_types, true ) ) {
			return;
		}
		wp_enqueue_style(
			'rgaa-page-score-admin',
			RGAA_PAGE_SCORE_PLUGIN_URL . 'assets/css/admin.css',
			[],
			RGAA_PAGE_SCORE_VERSION
		);
	}

	/**
	 * Add RGAA column to post list.
	 *
	 * @param array $columns Existing columns.
	 * @return array Modified columns.
	 */
	public static function add_column( $columns ) {
		$columns['rgaa_page_score'] = 'RGAA';
		return $columns;
	}

	/**
	 * Render column content.
	 *
	 * @param string $column  Column name.
	 * @param int    $post_id Post ID.
	 */
	public static function render_column( $column, $post_id ) {
		if ( 'rgaa_page_score' !== $column ) {
			return;
		}

		$score = Post_Meta::get_score( $post_id );

		if ( -1 === $score ) {
			echo '<span class="rgaa-page-score-indicator rgaa-page-score-na" title="' . esc_attr__( 'Not analyzed', 'rgaa-page-score' ) . '" aria-label="' . esc_attr__( 'Not analyzed', 'rgaa-page-score' ) . '">—</span>';
			return;
		}

		$class = self::get_score_class( $score );
		$label = self::get_score_label( $score );

		printf(
			'<span class="rgaa-page-score-indicator rgaa-page-score-%s" title="%s" aria-label="%s">%d%%</span>',
			esc_attr( $class ),
			esc_attr( $label ),
			esc_attr( $label ),
			(int) $score
		);
	}

	/**
	 * Get CSS class for score.
	 *
	 * @param int $score Score 0-100.
	 * @return string
	 */
	protected static function get_score_class( $score ) {
		if ( $score >= 100 ) {
			return 'good';
		}
		if ( $score >= 50 ) {
			return 'medium';
		}
		return 'bad';
	}

	/**
	 * Get human-readable label for score.
	 *
	 * @param int $score Score 0-100.
	 * @return string
	 */
	protected static function get_score_label( $score ) {
		if ( $score >= 100 ) {
			return __( 'Compliant', 'rgaa-page-score' );
		}
		if ( $score >= 50 ) {
			return __( 'Minor issues', 'rgaa-page-score' );
		}
		return __( 'Major issues', 'rgaa-page-score' );
	}
}
