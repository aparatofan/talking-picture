<?php
/**
 * Front-end rendering via the [talking_picture id="123"] shortcode.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the render assets (enqueued on demand when the shortcode runs).
 */
function tp_register_public_assets() {
	wp_register_style(
		'tp-render',
		TP_PLUGIN_URL . 'assets/css/render.css',
		array(),
		TP_VERSION
	);
	wp_register_script(
		'tp-render',
		TP_PLUGIN_URL . 'assets/js/render.js',
		array(),
		TP_VERSION,
		true
	);
}
add_action( 'wp_enqueue_scripts', 'tp_register_public_assets' );

/**
 * [talking_picture id="123"] shortcode handler.
 *
 * @param array $atts Shortcode attributes.
 * @return string HTML.
 */
function tp_shortcode( $atts ) {
	$atts = shortcode_atts(
		array( 'id' => 0 ),
		$atts,
		'talking_picture'
	);

	$id = (int) $atts['id'];
	if ( $id <= 0 || get_post_type( $id ) !== TP_POST_TYPE ) {
		return '';
	}

	$data = tp_get_data( $id );
	if ( empty( $data['image_url'] ) ) {
		return '';
	}

	// Enqueue the front-end assets only when a shortcode is actually present.
	wp_enqueue_style( 'tp-render' );
	wp_enqueue_script( 'tp-render' );

	// Payload consumed by render.js.
	$payload = array(
		'image'   => $data['image_url'],
		'opacity' => $data['opacity'],
		'nodes'   => array_map(
			function ( $n ) {
				return array(
					'xPct' => isset( $n['xPct'] ) ? (float) $n['xPct'] : 0,
					'yPct' => isset( $n['yPct'] ) ? (float) $n['yPct'] : 0,
					'text' => isset( $n['text'] ) ? (string) $n['text'] : '',
				);
			},
			$data['nodes']
		),
	);

	// JSON_HEX_TAG keeps a stray "</script>" inside tooltip text from breaking
	// out of the data block.
	$json = wp_json_encode( $payload, JSON_HEX_TAG | JSON_HEX_AMP );
	$mic  = tp_mic_svg();

	ob_start();
	?>
	<div class="tp-render" data-tp>
		<div class="tp-wrap">
			<img class="tp-img" alt="<?php echo esc_attr( get_the_title( $id ) ); ?>" />
			<svg class="tp-svg" xmlns="http://www.w3.org/2000/svg"></svg>
			<button type="button" class="tp-fs-btn" title="<?php esc_attr_e( 'Full screen', 'talking-picture' ); ?>" aria-label="<?php esc_attr_e( 'Full screen', 'talking-picture' ); ?>">
				<svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path fill="currentColor" d="M4 4h6V2H2v8h2V4zm16 0v6h2V2h-8v2h6zM4 20v-6H2v8h8v-2H4zm16 0h-6v2h8v-8h-2v6z"/></svg>
			</button>
		</div>
		<script type="application/json" class="tp-data"><?php echo $json; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode with JSON_HEX_TAG. ?></script>
		<script type="text/html" class="tp-mic-tpl"><?php echo $mic; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- trusted inline SVG. ?></script>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'talking_picture', 'tp_shortcode' );
