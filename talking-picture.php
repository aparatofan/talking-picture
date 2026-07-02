<?php
/**
 * Plugin Name:       Talking Picture
 * Plugin URI:        https://github.com/aparatofan/talking-picture
 * Description:        Build interactive "Talking Pictures" — drop microphone nodes with tooltips onto an image from your Media Library, keep them all in a reusable library, and embed any one with a shortcode.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.2
 * Author:            Talking Picture
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       talking-picture
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'TP_VERSION', '1.0.0' );
define( 'TP_PLUGIN_FILE', __FILE__ );
define( 'TP_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'TP_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'TP_POST_TYPE', 'talking_picture' );

require_once TP_PLUGIN_DIR . 'includes/post-type.php';
require_once TP_PLUGIN_DIR . 'includes/meta.php';
require_once TP_PLUGIN_DIR . 'includes/admin.php';
require_once TP_PLUGIN_DIR . 'includes/shortcode.php';

/**
 * Shared inline SVG for the microphone marker, so the plugin has no
 * dependency on an external icon CDN.
 *
 * @return string
 */
function tp_mic_svg() {
	return '<svg class="tp-mic" viewBox="0 0 384 512" aria-hidden="true" focusable="false" xmlns="http://www.w3.org/2000/svg"><path fill="currentColor" d="M192 0C139 0 96 43 96 96v160c0 53 43 96 96 96s96-43 96-96V96c0-53-43-96-96-96zM64 216c0-13-11-24-24-24s-24 11-24 24v40c0 89 66 163 152 175v41h-48c-13 0-24 11-24 24s11 24 24 24h144c13 0 24-11 24-24s-11-24-24-24h-48v-41c86-12 152-86 152-175v-40c0-13-11-24-24-24s-24 11-24 24v40c0 66-54 120-120 120S64 322 64 256v-40z"/></svg>';
}

// Activation: register the post type once, then flush rewrite rules so the
// admin menu and any queries resolve cleanly.
register_activation_hook(
	__FILE__,
	function () {
		tp_register_post_type();
		flush_rewrite_rules();
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
