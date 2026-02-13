<?php
/**
 * Plugin Name: RGAA Page Score
 * Plugin URI: https://github.com/raphaelsanchez/rgaa-page-score
 * Description: Display a RGAA accessibility score in the pages list and a meta box in the editor with improvement suggestions.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Author: Raphael Sanchez
 * Author URI: https://raphaelsanchez.design
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: rgaa-page-score
 *
 * @package RGAA_Page_Score
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'RGAA_PAGE_SCORE_VERSION', '1.0.0' );
define( 'RGAA_PAGE_SCORE_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RGAA_PAGE_SCORE_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize the plugin.
 */
add_action( 'plugins_loaded', 'rgaa_page_score_init' );

/**
 * Load plugin components.
 */
function rgaa_page_score_init() {
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-data.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-test-helpers.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-test-base.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-contrast-helper.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-images.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-cadres.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-contrast.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-tables.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-links.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-forms.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-structure.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/tests/class-rgaa-tests-navigation.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-scanner.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-post-meta.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-admin-column.php';
	require_once RGAA_PAGE_SCORE_PLUGIN_DIR . 'includes/class-rgaa-metabox.php';

	\RGAA\Score\Post_Meta::init();
	\RGAA\Score\Admin_Column::init();
	\RGAA\Score\Metabox::init();
}
