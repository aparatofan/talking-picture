<?php
/**
 * Registers the "Talking Picture" custom post type, which acts as the library
 * of saved Talking Pictures.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the custom post type.
 */
function tp_register_post_type() {
	$labels = array(
		'name'               => __( 'Talking Pictures', 'talking-picture' ),
		'singular_name'      => __( 'Talking Picture', 'talking-picture' ),
		'menu_name'          => __( 'Talking Pictures', 'talking-picture' ),
		'add_new'            => __( 'Add New', 'talking-picture' ),
		'add_new_item'       => __( 'Add New Talking Picture', 'talking-picture' ),
		'edit_item'          => __( 'Edit Talking Picture', 'talking-picture' ),
		'new_item'           => __( 'New Talking Picture', 'talking-picture' ),
		'view_item'          => __( 'View Talking Picture', 'talking-picture' ),
		'search_items'       => __( 'Search Talking Pictures', 'talking-picture' ),
		'not_found'          => __( 'No Talking Pictures found', 'talking-picture' ),
		'not_found_in_trash' => __( 'No Talking Pictures found in Trash', 'talking-picture' ),
		'all_items'          => __( 'All Talking Pictures', 'talking-picture' ),
	);

	register_post_type(
		TP_POST_TYPE,
		array(
			'labels'          => $labels,
			// Managed entirely inside wp-admin and embedded via shortcode, so
			// no public single-view route is needed.
			'public'          => false,
			'show_ui'         => true,
			// Nest under the TBT hub menu when it is active; fall back to a
			// top-level menu of its own when the hub is deactivated.
			'show_in_menu'    => defined( 'TBT_HUB_SLUG' ) ? TBT_HUB_SLUG : true,
			'show_in_rest'    => false, // Use the classic editor so our canvas metabox is reliable.
			'menu_icon'       => 'dashicons-microphone',
			'menu_position'   => 25,
			'capability_type' => 'post',
			'hierarchical'    => false,
			// Only the title (the topic name) is a native field; everything
			// else lives in the custom editor metabox.
			'supports'        => array( 'title' ),
		)
	);
}
add_action( 'init', 'tp_register_post_type' );

/**
 * Add a thumbnail + shortcode column to the library list table so the admin
 * list doubles as an at-a-glance record of covered topics.
 *
 * @param array $columns Existing columns.
 * @return array
 */
function tp_admin_columns( $columns ) {
	$new = array();
	foreach ( $columns as $key => $label ) {
		if ( 'title' === $key ) {
			$new['tp_thumb'] = __( 'Preview', 'talking-picture' );
		}
		$new[ $key ] = $label;
		if ( 'title' === $key ) {
			$new['tp_shortcode'] = __( 'Shortcode', 'talking-picture' );
		}
	}
	return $new;
}
add_filter( 'manage_' . TP_POST_TYPE . '_posts_columns', 'tp_admin_columns' );

/**
 * Render the custom column contents.
 *
 * @param string $column  Column key.
 * @param int    $post_id Post ID.
 */
function tp_admin_column_content( $column, $post_id ) {
	if ( 'tp_thumb' === $column ) {
		$image_id = (int) get_post_meta( $post_id, '_tp_image_id', true );
		if ( $image_id ) {
			echo wp_get_attachment_image( $image_id, array( 60, 60 ), false, array( 'style' => 'width:60px;height:auto;border-radius:4px;' ) );
		} else {
			echo '&mdash;';
		}
	} elseif ( 'tp_shortcode' === $column ) {
		printf(
			'<code style="user-select:all;">[talking_picture id="%d"]</code>',
			(int) $post_id
		);
	}
}
add_action( 'manage_' . TP_POST_TYPE . '_posts_custom_column', 'tp_admin_column_content', 10, 2 );
