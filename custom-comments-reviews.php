<?php
/**
 * Plugin Name:       Custom Comments & Reviews
 * Plugin URI:        https://example.com/plugins/custom-comments-reviews/
 * Description:       Enhances WordPress comments and WooCommerce reviews with a professional look and feel, and Elementor integration.
 * Version:           1.0.0
 * Author:            Jules AI Assistant
 * Author URI:        https://example.com/
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       custom-comments-reviews
 * Domain Path:       /languages
 * Elementor tested up to: 3.x.x
 * Elementor Pro tested up to: 3.x.x
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

define( 'CCR_VERSION', '1.0.0' );
define( 'CCR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CCR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Create basic folders
if ( ! file_exists( CCR_PLUGIN_DIR . 'includes' ) ) {
    mkdir( CCR_PLUGIN_DIR . 'includes', 0755, true );
}

if ( ! file_exists( CCR_PLUGIN_DIR . 'templates' ) ) {
    mkdir( CCR_PLUGIN_DIR . 'templates', 0755, true );
}

if ( ! file_exists( CCR_PLUGIN_DIR . 'assets/css' ) ) {
    mkdir( CCR_PLUGIN_DIR . 'assets/css', 0755, true );
}

if ( ! file_exists( CCR_PLUGIN_DIR . 'assets/js' ) ) {
    mkdir( CCR_PLUGIN_DIR . 'assets/js', 0755, true );
}

if ( ! file_exists( CCR_PLUGIN_DIR . 'elementor-widgets' ) ) {
    mkdir( CCR_PLUGIN_DIR . 'elementor-widgets', 0755, true );
}

if ( ! file_exists( CCR_PLUGIN_DIR . 'languages' ) ) {
    mkdir( CCR_PLUGIN_DIR . 'languages', 0755, true );
}

// Placeholder for activation/deactivation hooks
register_activation_hook( __FILE__, 'ccr_activate_plugin' );
function ccr_activate_plugin() {
    // Actions to run on plugin activation
    // e.g., set default options, flush rewrite rules if CPTs are involved
}

register_deactivation_hook( __FILE__, 'ccr_deactivate_plugin' );
function ccr_deactivate_plugin() {
    // Actions to run on plugin deactivation
    // e.g., clean up options or data
}

// Main plugin class will be loaded here later
// require_once CCR_PLUGIN_DIR . 'includes/class-custom-comments-reviews.php';

// function ccr_init() {
//     Custom_Comments_Reviews::instance();
// }
// add_action( 'plugins_loaded', 'ccr_init' );

// Include core files
require_once CCR_PLUGIN_DIR . 'includes/comment-template-functions.php';
require_once CCR_PLUGIN_DIR . 'includes/like-dislike-functions.php';


/**
 * Enqueue plugin scripts and styles.
 */
function ccr_enqueue_assets() {
    // Enqueue Styles
    wp_enqueue_style(
        'ccr-styles',
        CCR_PLUGIN_URL . 'assets/css/ccr-styles.css',
        array(),
        CCR_VERSION
    );

    // Enqueue Scripts
    wp_enqueue_script(
        'ccr-front-js',
        CCR_PLUGIN_URL . 'assets/js/ccr-front.js',
        array('jquery'), // Dependency
        CCR_VERSION,
        true // Load in footer
    );

    // Localize script for AJAX
    wp_localize_script(
        'ccr-front-js',
        'ccr_ajax_object',
        array(
            'ajax_url' => admin_url( 'admin-ajax.php' ),
            'nonce'    => wp_create_nonce( 'ccr_like_dislike_nonce' ),
            // Add other translations or data if needed
            'text_processing' => __('Processing...', 'custom-comments-reviews'),
        )
    );
}
add_action( 'wp_enqueue_scripts', 'ccr_enqueue_assets' );


/**
 * Load plugin textdomain.
 */
function ccr_load_textdomain() {
    load_plugin_textdomain( 'custom-comments-reviews', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}
add_action( 'init', 'ccr_load_textdomain' );

// Placeholder for a helper function to check if Elementor is active
function ccr_is_elementor_active() {
    return did_action( 'elementor/loaded' );
}

// Add a message to the plugins page if Elementor is not active (optional, but good practice)
function ccr_admin_notice_missing_main_plugin() {
    if ( ! ccr_is_elementor_active() ) {
        $message = sprintf(
            /* translators: 1: Plugin name 2: Elementor */
            esc_html__( '"%1$s" requires "%2$s" to be installed and activated. Please install and activate Elementor.', 'custom-comments-reviews' ),
            '<strong>' . esc_html__( 'Custom Comments & Reviews', 'custom-comments-reviews' ) . '</strong>',
            '<strong>' . esc_html__( 'Elementor', 'custom-comments-reviews' ) . '</strong>'
        );
        printf( '<div class="notice notice-warning is-dismissible"><p>%1$s</p></div>', $message );
    }
}
// add_action( 'admin_notices', 'ccr_admin_notice_missing_main_plugin' ); // We can enable this later when Elementor parts are built

// Basic security check
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

?>
