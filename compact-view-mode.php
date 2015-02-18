<?php
/**
 * Plugin Name: Compact View Mode
 * Description: View your post list in a more precise and compact way.
 * Version: 0.1.0
 * Author: Frankie Jarrett
 * Author URI: http://frankiejarrett.com
 * License: GPLv2+
 * Text Domain: compact-view-mode
 */

/**
 * Checks whether or not the post type is in compact mode
 *
 * @return bool
 */
function cvm_is_compact() {
	if (
		( ! empty( $_REQUEST['mode'] ) && 'compact' === $_REQUEST['mode'] )
		||
		'compact' === get_user_setting( "cvm_post_list_mode" )
	) {
		return true;
	}

	return false;
}

/**
 * Modify the DOM on edit screens
 *
 * @action admin_footer-edit.php
 *
 * @return void
 */
function cvm_edit_screen_js() {
	$compact_url = add_query_arg( array( 'mode' => 'compact' ) );
	?>
	<script>
	jQuery( document ).ready( function( $ ) {
		$( '.tablenav .view-switch' ).prepend( '<a href="<?php echo esc_url( $compact_url ) ?>" class="dashicons dashicons-editor-justify" id="view-switch-compact"><span class="screen-reader-text"><?php esc_html_e( 'Compact View', 'compact-view-mode' ) ?></span></a>' );
	});
	</script>
	<?php
	if ( ! cvm_is_compact() ) {
		return;
	}
	?>
	<style type="text/css">
	td.post-title strong,
	.row-actions {
		float: left;
	}
	.row-actions {
		margin-left: 15px;
	}
	</style>
	<script>
	jQuery( document ).ready( function( $ ) {
		$( '.tablenav .view-switch a' ).removeClass( 'current' );
		$( '#view-switch-compact' ).addClass( 'current' );

		$.each( $( '#the-list tr' ), function() {
			minimizeCellData( $( this ) );
		});

		function minimizeCellData( $row ) {
			$row.find( '.column-date br' ).remove();

			$row.find( '.column-date' ).contents().filter( function() {
				return this.nodeType === Node.TEXT_NODE;
			}).remove();

			var $tagCell = $row.find( '.column-tags' ).addClass( 'cvm-original' ),
			      $clone = $tagCell.clone().removeClass( 'cvm-original' ).addClass( 'cvm-new' );

			$tagCell.after( $clone ).show();
			$tagCell.hide();

			var $tags = $clone.children(),
			    count = $tags.length > 0 ? '<a href="#" class="cvm-tag-count">' + $tags.length + '</a>' : '&mdash;';

			$clone.html( count );
		}

		$( document ).on( 'click', '.cvm-tag-count', function() {
			var $row = $( this ).parent().parent();

			$row.find( '.cvm-new' ).hide();
			$row.find( '.cvm-original' ).show();

			return false;
		});
	});
	</script>
	<?php
}
add_action( 'admin_footer-edit.php', 'cvm_edit_screen_js' );

/**
 * Listens for the mode to change and sets/deletes the user setting
 *
 * @action send_headers
 *
 * @return void
 */
function cvm_edit_screen_send_headers() {
	if ( ! is_admin() ) {
		return;
	}

	global $pagenow, $typenow;

	if ( 'edit.php' !== $pagenow || 'post' !== $typenow || empty( $_REQUEST['mode'] ) ) {
		return;
	}

	$user_id  = get_current_user_id();
	$meta_key = "manageedit-postcolumnshidden";
	$columns  = cvm_get_post_columns();
	$allowed  = cvm_get_allowed_default_columns();

	/**
	 * Array of columns that are allowed to be viewed by default when switching to compact mode
	 *
	 * @return array
	 */
	$allowed = apply_filters( 'cvm_allowed_default_columns', $allowed );

	$hide = array_values( array_filter( array_diff( $columns, $allowed ) ) );

	if ( 'compact' === $_REQUEST['mode'] ) {
		set_user_setting( "cvm_post_list_mode", 'compact' );
		update_user_meta( $user_id, $meta_key, $hide );
	} else {
		delete_user_setting( "cvm_post_list_mode" );
		update_user_meta( $user_id, $meta_key, array() );
	}
}
add_action( 'send_headers', 'cvm_edit_screen_send_headers' );

/**
 * Return an array of all post columns
 *
 * @return array
 */
function cvm_get_post_columns() {
	$table   = new WP_Posts_List_Table;
	$columns = $table->get_columns();

	return array_values( array_filter( array_flip( $columns ) ) );
}

/**
 * Return an array of allowed column slugs
 *
 * @return array
 */
function cvm_get_allowed_default_columns() {
	$allowed = array( 'cb', 'title', 'author', 'categories', 'tags', 'comments', 'date', 'wpseo-score' );

	/**
	 * Columns that are allowed to be viewed by default when switching to compact mode
	 *
	 * @return array
	 */
	return apply_filters( 'cvm_allowed_default_columns', $allowed );
}

/**
 * Reset hidden columns on plugin deactivation
 *
 * @action deactivate_{plugin}
 *
 * @return void
 */
function cvm_deactivate() {
	global $wpdb;

	// Show all columns for posts
	$wpdb->query(
		$wpdb->prepare(
			"UPDATE $wpdb->usermeta SET meta_value = %s WHERE meta_key = 'manageedit-postcolumnshidden'",
			maybe_serialize( array() )
		)
	);

	$results = $wpdb->get_results( "SELECT user_id FROM $wpdb->usermeta WHERE meta_key = 'wp_user-settings' AND meta_value LIKE '%%cvm_post_list_mode%%'", ARRAY_A );

	foreach ( $results as $result ) {
		$user_id  = absint( $result['user_id'] );
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
