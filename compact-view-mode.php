<?php
/**
 * Plugin Name: Compact View Mode
 * Description: Adds a Compact View mode to the screen options when viewing posts.
 * Version: 0.4.2
 * Author: Frankie Jarrett
 * Author URI: https://frankiejarrett.com
 * Text Domain: compact-view-mode
 * License: GPL-2.0
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 *
 * Copyright © 2016 Frankie Jarrett. All Rights Reserved.
 */

define( 'COMPACT_VIEW_MODE_VERSION', '0.4.2' );
define( 'COMPACT_VIEW_MODE_PLUGIN', plugin_basename( __FILE__ ) );
define( 'COMPACT_VIEW_MODE_DIR', plugin_dir_path( __FILE__ ) );
define( 'COMPACT_VIEW_MODE_URL', plugin_dir_url( __FILE__ ) );

final class Compact_View_Mode {

	/**
	 * Plugin instance.
	 *
	 * @var Compact_View_Mode
	 */
	private static $instance;

	/**
	 * Class constructor.
	 */
	public function __construct() {

		register_deactivation_hook( __FILE__, array( __CLASS__, 'deactivate' ) );

		if ( ! is_admin() ) {

			return false;

		}

		add_action( 'plugins_loaded',        array( $this, 'i18n' ) );
		add_action( 'send_headers',          array( $this, 'send_headers' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_footer-edit.php', array( $this, 'edit_screen_footer' ) );

	}

	/**
	 * Return the plugin instance.
	 *
	 * @return Compact_View_Mode
	 */
	public static function instance() {

		if ( ! self::$instance ) {

			self::$instance = new self();

		}

		return self::$instance;

	}

	/**
	 * Does the current user have view mode set to compact?
	 *
	 * @return bool
	 */
	public function is_compact() {

		$query_var    = ! empty( $_GET['mode'] ) ? $_GET['mode'] : null;
		$user_setting = get_user_setting( 'cvm_post_list_mode' );

		return in_array( 'compact', array( $query_var, $user_setting ) );

	}

	/**
	 * Is the current screen the edit screen?
	 *
	 * @return bool
	 */
	public function is_edit_screen() {

		global $pagenow;

		return ( 'edit.php' === $pagenow );

	}

	/**
	 * Does the current post type support view modes?
	 *
	 * @return bool
	 */
	public function is_view_mode_post_type() {

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
	 * Load languages.
	 *
	 * @action plugins_loaded
	 */
	public function i18n() {

		load_plugin_textdomain( 'compact-view-mode' );

	}

	/**
	 * Listens for the mode to change and sets the user setting.
	 *
	 * @action send_headers
	 */
	public function send_headers() {

		if ( ! $this->is_edit_screen() || ! $this->is_view_mode_post_type() || empty( $_GET['mode'] ) ) {

			return;

		}

		if ( 'compact' !== $_GET['mode'] ) {

			delete_user_setting( 'cvm_post_list_mode' );

			return;

		}

		set_user_setting( 'cvm_post_list_mode', 'compact' );

	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @action admin_enqueue_scripts
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {

		if ( 'edit.php' !== $hook || ! $this->is_compact() || ! $this->is_view_mode_post_type() ) {

			return;

		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'cvm-jquery-regex', COMPACT_VIEW_MODE_URL . 'js/jquery.regex.min.js', array( 'jquery' ), COMPACT_VIEW_MODE_VERSION );
		wp_enqueue_script( 'cvm-compact', COMPACT_VIEW_MODE_URL . "js/cvm-compact{$suffix}.js", array( 'jquery', 'cvm-jquery-regex', 'inline-edit-post' ), COMPACT_VIEW_MODE_VERSION );

		wp_enqueue_style( 'cvm-compact', COMPACT_VIEW_MODE_URL . "css/cvm-compact{$suffix}.css", array(), COMPACT_VIEW_MODE_VERSION );

	}

	/**
	 * Add a Compact View field to the screen options.
	 *
	 * @action admin_footer-edit.php
	 */
	public function edit_screen_footer() {

		if ( ! $this->is_view_mode_post_type() ) {

			return;

		}

		?>
		<script type="text/javascript">
			jQuery( function( $ ) {
				$( '#adv-settings .view-mode legend' ).after( "<label for='compact-view-mode'><input id='compact-view-mode' type='radio' name='mode' value='compact'<?php checked( $this->is_compact() ) ?> /> <?php _e( 'Compact View', 'compact-view-mode' ) ?></label>" );
			} );
		</script>
		<?php

	}

	/**
	 * Reset user settings when the plugin is deactivated.
	 *
	 * @action deactivate_{plugin}
	 */
	public static function deactivate() {

		/**
		 * Remove user setting on deactivation.
		 *
		 * @since 0.4.0
		 *
		 * @var bool
		 */
		if ( ! (bool) apply_filters( 'cvm_reset_on_deactivate', true ) ) {

			return;

		}

		global $wpdb;

		$results = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta} WHERE meta_key = 'wp_user-settings' AND meta_value LIKE '%%cvm_post_list_mode%%';" );

		foreach ( $results as $user_id ) {

			$user_id  = absint( $user_id );
			$settings = wp_parse_args( get_user_meta( $user_id, 'wp_user-settings', true ) );

			unset( $settings['cvm_post_list_mode'] );

			$settings['posts_list_mode'] = 'list';

			update_user_meta( $user_id, 'wp_user-settings', http_build_query( $settings ) );

		}

	}

}

Compact_View_Mode::instance();
