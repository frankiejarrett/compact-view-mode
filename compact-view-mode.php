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
	global $typenow;

	if (
		( ! empty( $_REQUEST['mode'] ) && 'compact' === $_REQUEST['mode'] )
		||
		'compact' === get_user_setting( "cvm_{$typenow}_list_mode" )
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

		$( '.inline-edit-row' ).on( 'remove', function() {
			var id = $( this ).prop( 'id' ).replace( 'edit-', '' );

			minimizeCellData( $( '#post-' + id ) );
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

	global $pagenow;

	if ( 'edit.php' !== $pagenow || empty( $_REQUEST['mode'] ) ) {
		return;
	}

	global $typenow;

	if ( 'compact' === $_REQUEST['mode'] ) {
		set_user_setting( "cvm_{$typenow}_list_mode", 'compact' );
	} else {
		delete_user_setting( "cvm_{$typenow}_list_mode" );
	}
}
add_action( 'send_headers', 'cvm_edit_screen_send_headers' );
