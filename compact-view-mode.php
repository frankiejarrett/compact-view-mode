<?php
/**
 * Plugin Name: Compact View Mode
 * Description: View your post list in a more precise and compact way.
 * Version: 0.3.1
 * Author: Frankie Jarrett
 * Author URI: https://frankiejarrett.com
 * License: GPLv2+
 * Text Domain: compact-view-mode
 */

define( 'COMPACT_VIEW_MODE_VERSION', '0.3.1' );

define( 'COMPACT_VIEW_MODE_PLUGIN', plugin_basename( __FILE__ ) );

define( 'COMPACT_VIEW_MODE_DIR',  plugin_dir_path( __FILE__ ) );

define( 'COMPACT_VIEW_MODE_URL',  plugin_dir_url( __FILE__ ) );

/**
 * Checks whether or not the post type is in compact mode
 *
 * @return bool
 */
function cvm_is_compact() {

	$query_var = ! empty( $_GET['mode'] ) ? $_GET['mode'] : null;

	$user_setting = get_user_setting( 'cvm_post_list_mode' );

	return in_array( 'compact', array( $query_var, $user_setting ) );

}

/**
 *
 *
 * @return bool
 */
function cvm_is_view_mode_post_type() {

	if ( ! is_admin() ) {

		return false;

	}

	$post_types = get_post_types(
		array(
			'hierarchical' => false,
			'show_ui'      => true,
		)
	);

	global $typenow;

	return in_array( $typenow, $post_types );

}

/**
 *
 *
 * @return bool
 */
function cvm_is_edit_screen() {

	if ( ! is_admin() ) {

		return false;

	}

	global $pagenow;

	return ( 'edit.php' === $pagenow );

}

/**
 * Enqueue scripts in the admin
 *
 * @param string $hook
 */
function cvm_admin_enqueue_scripts( $hook ) {

	if ( 'edit.php' !== $hook || ! cvm_is_view_mode_post_type() || ! cvm_is_compact() ) {

		return;

	}

	$suffix = SCRIPT_DEBUG ? '' : '.min';

	wp_enqueue_script( 'cvm-jquery-regex', COMPACT_VIEW_MODE_URL . "assets/js/jquery.regex.min.js", array( 'jquery' ), COMPACT_VIEW_MODE_VERSION );

	wp_enqueue_script( 'cvm-compact', COMPACT_VIEW_MODE_URL . "assets/js/cvm-compact{$suffix}.js", array( 'jquery', 'cvm-jquery-regex', 'inline-edit-post' ), COMPACT_VIEW_MODE_VERSION );

	wp_enqueue_style( 'cvm-compact', COMPACT_VIEW_MODE_URL . "assets/css/cvm-compact{$suffix}.css", array(), COMPACT_VIEW_MODE_VERSION );

}
add_action( 'admin_enqueue_scripts', 'cvm_admin_enqueue_scripts' );

/**
 * Add the Compact View field to screen options
 *
 * @action admin_footer-edit.php
 */
function cvm_edit_screen_js() {

	if ( ! cvm_is_view_mode_post_type() ) {

		return;

	}

	?>
	<script type="text/javascript">
		jQuery( function( $ ) {
			$( '#adv-settings .view-mode legend' ).after( "<label for='compact-view-mode'><input id='compact-view-mode' type='radio' name='mode' value='compact'<?php checked( cvm_is_compact() ) ?> /> <?php _e( 'Compact View', 'compact-view-mode' ) ?></label>" );
		} );
	</script>
	<?php

}
add_action( 'admin_footer-edit.php', 'cvm_edit_screen_js' );

/**
 * Listens for the mode to change and sets/deletes the user setting
 *
 * @action send_headers
 */
function cvm_edit_screen_send_headers() {

	if ( ! cvm_is_edit_screen() || ! cvm_is_view_mode_post_type() || empty( $_GET['mode'] ) ) {

		return;

	}

	if ( 'compact' !== $_GET['mode'] ) {

		delete_user_setting( 'cvm_post_list_mode' );

		return;

	}

	set_user_setting( 'cvm_post_list_mode', 'compact' );

}
add_action( 'send_headers', 'cvm_edit_screen_send_headers' );

/**
 * Reset hidden columns on plugin deactivation
 *
 * @action deactivate_{plugin}
 */
function cvm_deactivate() {

	global $wpdb;

	// Show all columns for posts
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE $wpdb->usermeta SET meta_value = %s WHERE meta_key = 'manageedit-postcolumnshidden'",
			serialize( array() )
		)
	);

	$results = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wp_user-settings' AND meta_value LIKE '%%cvm_post_list_mode%%'", ARRAY_A );

	foreach ( $results as $result ) {

		$user_id = absint( $result['user_id'] );

		$settings = get_user_meta( $user_id, 'wp_user-settings', true );

		$settings = wp_parse_args( $settings );

		if ( empty( $settings ) ) {

			continue;

		}

		foreach ( $settings as $key => $value ) {

			if ( 'cvm_post_list_mode' === $key ) {

				unset( $settings[ $key ] );

			}

			if ( 'posts_list_mode' === $key ) {

				$settings[ $key ] = 'list';

			}

		}

		update_user_meta( $user_id, 'wp_user-settings', http_build_query( $settings ) );

	}

}
register_deactivation_hook( __FILE__, 'cvm_deactivate' );
