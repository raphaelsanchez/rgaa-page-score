<?php
/**
 * RGAA Score meta box in editor.
 *
 * @package RGAA_Page_Score
 */

namespace RGAA\Score;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Metabox
 */
class Metabox {

	/**
	 * Initialize hooks.
	 */
	public static function init() {
		add_action( 'add_meta_boxes', [ __CLASS__, 'register_metabox' ] );
		add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
	}

	/**
	 * Register the meta box.
	 */
	public static function register_metabox() {
		$post_types = [ 'post', 'page' ];
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'rgaa_page_score_metabox',
				__( 'RGAA Page Score', 'rgaa-page-score' ),
				[ __CLASS__, 'render_metabox' ],
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Enqueue admin assets.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public static function enqueue_assets( $hook ) {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->post_type, [ 'post', 'page' ], true ) ) {
			return;
		}

		// Metabox styles and JS on edit screens.
		if ( in_array( $hook, [ 'post.php', 'post-new.php' ], true ) ) {
			wp_enqueue_style(
				'rgaa-page-score-admin',
				RGAA_PAGE_SCORE_PLUGIN_URL . 'assets/css/admin.css',
				[],
				RGAA_PAGE_SCORE_VERSION
			);
			wp_add_inline_script( 'jquery', self::get_admin_js() );
		}
	}

	/**
	 * Render meta box content.
	 *
	 * @param WP_Post $post Post object.
	 */
	public static function render_metabox( $post ) {
		$score  = Post_Meta::get_score( $post->ID );
		$issues = Post_Meta::get_issues( $post->ID );

		wp_nonce_field( 'rgaa_page_score_analyze', 'rgaa_page_score_nonce' );

		?>
		<div id="rgaa-page-score-metabox" class="rgaa-metabox">
			<div class="rgaa-page-score-display">
				<?php if ( -1 === $score ) : ?>
					<div class="rgaa-page-score-row">
						<div class="rgaa-page-score-na-block">
							<p class="rgaa-page-score-value rgaa-page-score-na">
								<?php esc_html_e( 'Not analyzed', 'rgaa-page-score' ); ?>
							</p>
							<p class="rgaa-page-score-description">
								<?php esc_html_e( 'Save the post or click "Analyze" to get the score.', 'rgaa-page-score' ); ?>
							</p>
						</div>
						<div class="rgaa-actions">
							<button type="button" class="button button-primary rgaa-reanalyze" data-post-id="<?php echo (int) $post->ID; ?>">
								<?php esc_html_e( 'Analyze', 'rgaa-page-score' ); ?>
							</button>
						</div>
					</div>
				<?php else : ?>
					<div class="rgaa-page-score-row">
						<div class="rgaa-page-score-cards">
							<div class="rgaa-card rgaa-page-score-value rgaa-page-score-<?php echo esc_attr( self::get_score_class( $score ) ); ?>">
								<span class="rgaa-card-label"><?php esc_html_e( 'Score', 'rgaa-page-score' ); ?></span>
								<span class="rgaa-card-number"><?php echo (int) $score; ?>%</span>
								<span class="rgaa-card-sublabel"><?php echo esc_html( self::get_score_label( $score ) ); ?></span>
							</div>
							<div class="rgaa-card rgaa-page-score-count">
								<span class="rgaa-card-label"><?php esc_html_e( 'Errors', 'rgaa-page-score' ); ?></span>
								<span class="rgaa-card-number"><?php echo (int) count( $issues ); ?></span>
								<span class="rgaa-card-sublabel"><?php echo esc_html( sprintf( _n( 'issue detected', 'issues detected', count( $issues ), 'rgaa-page-score' ) ) ); ?></span>
							</div>
						</div>
						<div class="rgaa-actions">
							<button type="button" class="button button-secondary rgaa-reanalyze" data-post-id="<?php echo (int) $post->ID; ?>">
								<?php esc_html_e( 'Re-analyze', 'rgaa-page-score' ); ?>
							</button>
						</div>
					</div>
				<?php endif; ?>
			</div>

			<?php if ( ! empty( $issues ) ) : ?>
				<div class="rgaa-issues-list">
					<h4><?php esc_html_e( 'Improvement suggestions', 'rgaa-page-score' ); ?></h4>
					<ul>
						<?php foreach ( $issues as $issue ) : ?>
							<li class="rgaa-issue">
								<strong>
									<?php
									printf(
										/* translators: %s: RGAA criterion */
										esc_html__( 'Criterion %s', 'rgaa-page-score' ),
										esc_html( $issue['criterion'] ?? '' )
									);
									?>
									: <?php echo esc_html( $issue['title'] ?? '' ); ?>
								</strong>
								<p><?php echo esc_html( $issue['message'] ?? '' ); ?></p>
								<?php if ( ! empty( $issue['html'] ) ) : ?>
									<code class="rgaa-issue-html"><?php echo esc_html( $issue['html'] ); ?></code>
								<?php endif; ?>
								<?php if ( ! empty( $issue['url'] ) ) : ?>
									<p>
										<a href="<?php echo esc_url( $issue['url'] ); ?>" target="_blank" rel="noopener noreferrer">
											<?php esc_html_e( 'View RGAA methodology', 'rgaa-page-score' ); ?>
										</a>
									</p>
								<?php endif; ?>
							</li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php elseif ( $score >= 0 && $score < 100 ) : ?>
				<p class="rgaa-no-issues">
					<?php esc_html_e( 'No issues detected automatically. Some criteria require manual verification.', 'rgaa-page-score' ); ?>
				</p>
			<?php elseif ( 100 === $score ) : ?>
				<p class="rgaa-perfect">
					<?php esc_html_e( 'No issues detected. Automated tests are compliant.', 'rgaa-page-score' ); ?>
				</p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Get score CSS class.
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
	 * Get score label.
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

	/**
	 * Get admin JavaScript.
	 *
	 * @return string
	 */
	protected static function get_admin_js() {
		return '
			jQuery(function($) {
				$(document).on("click", ".rgaa-reanalyze", function() {
					var $btn = $(this);
					var postId = $btn.data("post-id");
					var nonce = $("#rgaa_page_score_nonce").val();
					if (!postId || !nonce) return;
					$btn.prop("disabled", true).text("' . esc_js( __( 'Analyzing…', 'rgaa-page-score' ) ) . '");
					$.post(ajaxurl, {
						action: "rgaa_page_score_analyze",
						post_id: postId,
						rgaa_page_score_nonce: nonce
					}).done(function(response) {
						if (response.success) {
							location.reload();
						} else {
							$btn.prop("disabled", false).text("' . esc_js( __( 'Re-analyze', 'rgaa-page-score' ) ) . '");
							alert(response.data && response.data.message ? response.data.message : "Error");
						}
					}).fail(function() {
						$btn.prop("disabled", false).text("' . esc_js( __( 'Re-analyze', 'rgaa-page-score' ) ) . '");
						alert("Error during analysis.");
					});
				});
			});
		';
	}
}
