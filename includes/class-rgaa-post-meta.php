<?php
/**
 * Post meta management for RGAA score.
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Post_Meta
 */
class Post_Meta {

	const META_KEY_SCORE = '_rgaa_score';
	const META_KEY_ISSUES = '_rgaa_issues';

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'save_post', [ __CLASS__, 'maybe_analyze_on_save' ], 20, 3 );
		add_action( 'wp_ajax_rgaa_page_score_analyze', [ __CLASS__, 'ajax_analyze' ] );
	}

	/**
	 * Analyze post on save.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post object.
	 * @param bool    $update  Whether this is an existing post being updated.
	 */
	public static function maybe_analyze_on_save( $post_id, $post, $update ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		if ( wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post_types = [ 'post', 'page' ];
		if ( ! in_array( $post->post_type, $post_types, true ) ) {
			return;
		}

		self::analyze_post( $post_id );
	}

	/**
	 * Handle AJAX analyze request.
	 */
	public static function ajax_analyze() {
		check_ajax_referer( 'rgaa_page_score_analyze', 'rgaa_page_score_nonce' );

		$post_id = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;
		if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
			wp_send_json_error( [ 'message' => __( 'Permission denied.', 'rgaa-page-score' ) ] );
		}

		$result = self::analyze_post( $post_id );
		wp_send_json_success( $result );
	}

	/**
	 * Analyze a post and store results.
	 *
	 * @param int $post_id Post ID.
	 * @return array{score: int, issues: array}
	 */
	public static function analyze_post( $post_id ) {
		$scanner = new Scanner();
		$result  = $scanner->analyze_post( $post_id );

		update_post_meta( $post_id, self::META_KEY_SCORE, $result['score'] );
		update_post_meta( $post_id, self::META_KEY_ISSUES, $result['issues'] );

		return $result;
	}

	/**
	 * Get stored score for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return int Score 0-100 or -1 if not analyzed.
	 */
	public static function get_score( $post_id ) {
		$score = get_post_meta( $post_id, self::META_KEY_SCORE, true );
		return '' === $score ? -1 : (int) $score;
	}

	/**
	 * Get stored issues for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array List of issues.
	 */
	public static function get_issues( $post_id ) {
		$issues = get_post_meta( $post_id, self::META_KEY_ISSUES, true );
		return is_array( $issues ) ? $issues : [];
	}
}
