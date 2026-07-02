<?php
/**
 * Admin editor: the canvas metabox and its assets.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the editor metabox on the Talking Picture edit screen.
 */
function tp_add_meta_boxes() {
	add_meta_box(
		'tp_editor',
		__( 'Talking Picture Editor', 'talking-picture' ),
		'tp_render_editor_metabox',
		TP_POST_TYPE,
		'normal',
		'high'
	);
}
add_action( 'add_meta_boxes', 'tp_add_meta_boxes' );

/**
 * Enqueue the editor script/styles + the Media Library picker on our edit
 * screens only.
 *
 * @param string $hook Current admin page.
 */
function tp_admin_enqueue( $hook ) {
	if ( 'post.php' !== $hook && 'post-new.php' !== $hook ) {
		return;
	}
	$screen = get_current_screen();
	if ( ! $screen || TP_POST_TYPE !== $screen->post_type ) {
		return;
	}

	wp_enqueue_media(); // Provides wp.media for the Media Library picker.

	wp_enqueue_style(
		'tp-editor',
		TP_PLUGIN_URL . 'assets/css/editor.css',
		array(),
		TP_VERSION
	);

	wp_enqueue_script(
		'tp-editor',
		TP_PLUGIN_URL . 'assets/js/editor.js',
		array( 'jquery' ),
		TP_VERSION,
		true
	);
}
add_action( 'admin_enqueue_scripts', 'tp_admin_enqueue' );

/**
 * Render the canvas editor metabox.
 *
 * @param WP_Post $post Current post.
 */
function tp_render_editor_metabox( $post ) {
	$data = tp_get_data( $post->ID );

	wp_nonce_field( 'tp_save_editor', 'tp_editor_nonce' );

	// The editor JS reads its initial state from these data-* attributes.
	$nodes_json   = wp_json_encode( $data['nodes'] );
	$opacity_attr = esc_attr( $data['opacity'] );
	$img_id_attr  = esc_attr( $data['image_id'] );
	$img_url_attr = esc_url( $data['image_url'] );
	$mic          = tp_mic_svg();
	?>
	<div
		id="tp-editor"
		class="tp-editor"
		data-image-id="<?php echo $img_id_attr; ?>"
		data-image-url="<?php echo $img_url_attr; ?>"
		data-opacity="<?php echo $opacity_attr; ?>"
		data-nodes="<?php echo esc_attr( $nodes_json ); ?>"
	>
		<!-- Hidden fields submitted with the post. -->
		<input type="hidden" id="tp_image_id" name="tp_image_id" value="<?php echo $img_id_attr; ?>" />
		<input type="hidden" id="tp_opacity" name="tp_opacity" value="<?php echo $opacity_attr; ?>" />
		<input type="hidden" id="tp_nodes" name="tp_nodes" value="<?php echo esc_attr( $nodes_json ); ?>" />

		<div class="tp-toolbar">
			<button type="button" class="button button-primary" id="tpPickImage">
				<span class="dashicons dashicons-format-image"></span>
				<?php esc_html_e( 'Select / Change Background', 'talking-picture' ); ?>
			</button>

			<label class="tp-opacity">
				<?php esc_html_e( 'Background opacity', 'talking-picture' ); ?>
				<input type="range" id="tpOpacity" min="0" max="100" value="<?php echo esc_attr( round( $data['opacity'] * 100 ) ); ?>" />
				<span id="tpOpacityVal"><?php echo esc_html( round( $data['opacity'] * 100 ) ); ?>%</span>
			</label>

			<span class="tp-count">
				<?php esc_html_e( 'Nodes:', 'talking-picture' ); ?>
				<strong id="tpNodeCount">0</strong>
			</span>
		</div>

		<p class="tp-help description">
			<?php esc_html_e( 'Click the image to drop a microphone node. Drag to reposition. Click a node to edit its tooltip text or delete it. Nodes connect automatically in placement order. Remember to Update the post to save.', 'talking-picture' ); ?>
		</p>

		<div id="tpEmpty" class="tp-empty">
			<span class="dashicons dashicons-cloud-upload"></span>
			<p><?php esc_html_e( 'Select a background image to get started.', 'talking-picture' ); ?></p>
		</div>

		<div id="tpCanvasContainer" class="tp-canvas-container" hidden>
			<div id="tpCanvasWrap" class="tp-canvas-wrap">
				<img id="tpBg" class="tp-bg" alt="" />
				<svg id="tpLines" class="tp-lines" xmlns="http://www.w3.org/2000/svg"></svg>
			</div>
		</div>

		<!-- Node editing popover -->
		<div id="tpPopover" class="tp-popover" hidden>
			<label class="tp-popover-label"><?php esc_html_e( 'Tooltip text', 'talking-picture' ); ?></label>
			<textarea id="tpPopoverText" rows="3" placeholder="<?php esc_attr_e( 'Type the tooltip / question shown for this node…', 'talking-picture' ); ?>"></textarea>
			<div class="tp-popover-actions">
				<button type="button" class="button-link-delete" id="tpDeleteNode">
					<span class="dashicons dashicons-trash"></span> <?php esc_html_e( 'Delete', 'talking-picture' ); ?>
				</button>
				<button type="button" class="button button-primary" id="tpSaveNode">
					<?php esc_html_e( 'Done', 'talking-picture' ); ?>
				</button>
			</div>
		</div>

		<!-- Template markup used by the JS to build nodes. -->
		<script type="text/html" id="tpMicSvg"><?php echo $mic; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?></script>
	</div>
	<?php
}
