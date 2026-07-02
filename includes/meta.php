<?php
/**
 * Meta storage and sanitisation for a Talking Picture.
 *
 * Stored meta keys:
 *   _tp_image_id  (int)    Media Library attachment ID for the background.
 *   _tp_opacity   (float)  Background opacity, 0..1.
 *   _tp_nodes     (string) JSON array of { xPct, yPct, text }.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read a Talking Picture's data as a normalised array, ready for rendering.
 *
 * @param int $post_id Post ID.
 * @return array{image_id:int,image_url:string,opacity:float,nodes:array}
 */
function tp_get_data( $post_id ) {
	$image_id = (int) get_post_meta( $post_id, '_tp_image_id', true );
	$image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'full' ) : '';

	$opacity = get_post_meta( $post_id, '_tp_opacity', true );
	$opacity = ( '' === $opacity ) ? 0.7 : (float) $opacity;

	$nodes_raw = get_post_meta( $post_id, '_tp_nodes', true );
	$nodes     = array();
	if ( is_string( $nodes_raw ) && '' !== $nodes_raw ) {
		$decoded = json_decode( $nodes_raw, true );
		if ( is_array( $decoded ) ) {
			$nodes = $decoded;
		}
	}

	return array(
		'image_id'  => $image_id,
		'image_url' => $image_url ? $image_url : '',
		'opacity'   => $opacity,
		'nodes'     => $nodes,
	);
}

/**
 * Clamp a numeric value to the 0..100 percentage range.
 *
 * @param mixed $v Value.
 * @return float
 */
function tp_clamp_pct( $v ) {
	$v = (float) $v;
	if ( $v < 0 ) {
		return 0.0;
	}
	if ( $v > 100 ) {
		return 100.0;
	}
	return $v;
}

/**
 * Persist the editor metabox when a Talking Picture is saved.
 *
 * @param int $post_id Post ID.
 */
function tp_save_meta( $post_id ) {
	// Guard clauses: nonce, autosave, capability, correct post type.
	if ( ! isset( $_POST['tp_editor_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['tp_editor_nonce'] ) ), 'tp_save_editor' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( get_post_type( $post_id ) !== TP_POST_TYPE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	// Background image attachment ID.
	$image_id = isset( $_POST['tp_image_id'] ) ? (int) $_POST['tp_image_id'] : 0;
	if ( $image_id > 0 ) {
		update_post_meta( $post_id, '_tp_image_id', $image_id );
	} else {
		delete_post_meta( $post_id, '_tp_image_id' );
	}

	// Opacity, stored 0..1.
	$opacity = isset( $_POST['tp_opacity'] ) ? (float) $_POST['tp_opacity'] : 0.7;
	if ( $opacity < 0 ) {
		$opacity = 0.0;
	}
	if ( $opacity > 1 ) {
		$opacity = 1.0;
	}
	update_post_meta( $post_id, '_tp_opacity', $opacity );

	// Nodes: decode, validate each entry, re-encode.
	$nodes_clean = array();
	if ( isset( $_POST['tp_nodes'] ) ) {
		// The field carries a JSON string; unslash before decoding.
		$raw     = wp_unslash( $_POST['tp_nodes'] );
		$decoded = json_decode( $raw, true );
		if ( is_array( $decoded ) ) {
			foreach ( $decoded as $node ) {
				if ( ! is_array( $node ) ) {
					continue;
				}
				$nodes_clean[] = array(
					'xPct' => tp_clamp_pct( isset( $node['xPct'] ) ? $node['xPct'] : 0 ),
					'yPct' => tp_clamp_pct( isset( $node['yPct'] ) ? $node['yPct'] : 0 ),
					'text' => isset( $node['text'] ) ? sanitize_textarea_field( $node['text'] ) : '',
				);
			}
		}
	}
	update_post_meta( $post_id, '_tp_nodes', wp_json_encode( $nodes_clean ) );
}
add_action( 'save_post_' . TP_POST_TYPE, 'tp_save_meta' );
