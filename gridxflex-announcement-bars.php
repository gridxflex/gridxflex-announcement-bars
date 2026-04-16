<?php
/**
 * Plugin Name: Gridxflex Announcement Bars with CTA
 * Plugin URI: https://wordpress.org/plugins/gridxflex-announcement-bars/
 * Description: Lightweight, fully customizable announcement bar with display options and cookie-based dismissibility.
 * Version: 1.1.0
 * Author: Grid X Flex
 * Author URI: https://github.com/gridxflex
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gridxflex-announcement-bars
 * Domain Path: /languages
 * Requires at least: 5.0
 * Requires PHP: 7.2
 *
 * @package GridxflexAnnouncementBarswithCTA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GABC_VERSION', '1.1.0' );
define( 'GABC_DB_VERSION', '1.1.0' );
define( 'GABC_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'GABC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'GABC_PLUGIN_FILE', __FILE__ );
define( 'GABC_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

require_once GABC_PLUGIN_DIR . 'includes/class-gabc-loader.php';
require_once GABC_PLUGIN_DIR . 'includes/class-gabc-core.php';
require_once GABC_PLUGIN_DIR . 'includes/class-gabc-admin.php';

function gabc_init() {
	try {
		if ( ! class_exists( 'GABC_Core' ) ) {
			return;
		}

		$plugin = new GABC_Core();
		$plugin->run();
	} catch ( Exception $e ) {
		// Silently fail in production.
	}
}
add_action( 'plugins_loaded', 'gabc_init', 10 );

function gabc_activate() {
	global $wpdb;
	$table_name = $wpdb->prefix . 'gabc_notices';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		title varchar(255) NOT NULL,
		text text NOT NULL,
		button_text varchar(100) DEFAULT NULL,
		button_url varchar(500) DEFAULT NULL,
		button_new_tab tinyint(1) DEFAULT 0,
		bg_color varchar(100) DEFAULT '#f0f0f0',
		text_color varchar(20) DEFAULT '#333333',
		button_color varchar(100) DEFAULT '#007bff',
		padding int(11) DEFAULT 15,
		font_size int(11) DEFAULT 16,
		position varchar(20) DEFAULT 'top',
		sticky tinyint(1) DEFAULT 1,
		show_location varchar(50) DEFAULT 'all',
		selected_pages text DEFAULT NULL,
		selected_categories text DEFAULT NULL,
		selected_tags text DEFAULT NULL,
		selected_post_types text DEFAULT NULL,
		user_roles text DEFAULT NULL,
		hide_logged_in tinyint(1) DEFAULT 0,
		dismissible tinyint(1) DEFAULT 1,
		start_date datetime DEFAULT NULL,
		end_date datetime DEFAULT NULL,
		priority int(11) DEFAULT 0,
		enabled tinyint(1) DEFAULT 1,
		trigger_delay int(11) DEFAULT 0,
		trigger_scroll int(11) DEFAULT 0,
		trigger_exit_intent tinyint(1) DEFAULT 0,
		animation varchar(30) DEFAULT 'none',
		animation_duration int(11) DEFAULT 400,
		text_align varchar(10) DEFAULT 'left',
		font_weight varchar(10) DEFAULT 'normal',
		button_text_color varchar(20) DEFAULT '#ffffff',
		button_border_radius int(11) DEFAULT 4,
		button_padding_x int(11) DEFAULT 20,
		button_padding_y int(11) DEFAULT 8,
		mobile_font_size int(11) DEFAULT 0,
		mobile_padding int(11) DEFAULT 0,
		mobile_layout varchar(20) DEFAULT 'auto',
		created_at datetime DEFAULT CURRENT_TIMESTAMP,
		updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
		PRIMARY KEY  (id),
		KEY enabled (enabled),
		KEY priority (priority),
		KEY start_date (start_date),
		KEY end_date (end_date)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );

	$old_settings = get_option( 'gabc_settings' );
	if ( $old_settings ) {
		$cache_key = 'gabc_notices_count';
		$count = wp_cache_get( $cache_key, 'gabc' );
		if ( false === $count ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$count = $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}gabc_notices" );
			wp_cache_set( $cache_key, $count, 'gabc' );
		}
		
		if ( 0 === intval( $count ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert(
				$wpdb->prefix . 'gabc_notices',
				array(
					'title'              => 'Default Notice',
					'text'               => $old_settings['text'] ?? 'Welcome to our site!',
					'button_text'        => $old_settings['button_text'] ?? '',
					'button_url'         => $old_settings['button_url'] ?? '',
					'button_new_tab'     => $old_settings['button_new_tab'] ?? 0,
					'bg_color'           => $old_settings['bg_color'] ?? '#f0f0f0',
					'text_color'         => $old_settings['text_color'] ?? '#333333',
					'button_color'       => $old_settings['button_color'] ?? '#007bff',
					'padding'            => $old_settings['padding'] ?? 15,
					'font_size'          => $old_settings['font_size'] ?? 16,
					'position'           => $old_settings['position'] ?? 'top',
					'sticky'             => 1,
					'show_location'      => $old_settings['show_location'] ?? 'all',
					'selected_post_types'=> isset( $old_settings['selected_post_types'] ) ? maybe_serialize( $old_settings['selected_post_types'] ) : null,
					'hide_logged_in'     => $old_settings['hide_logged_in'] ?? 0,
					'dismissible'        => $old_settings['dismissible'] ?? 1,
					'enabled'            => $old_settings['enabled'] ?? 1,
					'priority'           => 0,
				)
			);
		}
	}

	// Analytics table.
	$analytics_table = $wpdb->prefix . 'gabc_analytics';
	$sql_analytics = "CREATE TABLE $analytics_table (
		id bigint(20) NOT NULL AUTO_INCREMENT,
		notice_id bigint(20) NOT NULL,
		event_type varchar(20) NOT NULL,
		event_date date NOT NULL,
		count bigint(20) NOT NULL DEFAULT 1,
		PRIMARY KEY  (id),
		UNIQUE KEY notice_event_date (notice_id, event_type, event_date),
		KEY notice_id (notice_id),
		KEY event_date (event_date)
	) $charset_collate;";

	dbDelta( $sql_analytics );

	update_option( 'gabc_db_version', GABC_DB_VERSION );
	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'gabc_activate' );

function gabc_sanitize_color( $value ) {
	$value = trim( $value );

	if ( preg_match( '/^#([0-9a-fA-F]{3}|[0-9a-fA-F]{6})$/', $value ) ) {
		return sanitize_hex_color( $value );
	}

	if ( preg_match(
		'/^linear-gradient\(\s*\d{1,3}deg\s*,\s*#[0-9a-fA-F]{3,8}\s*,\s*#[0-9a-fA-F]{3,8}\s*\)$/',
		$value
	) ) {
		return $value;
	}

	return '#f0f0f0';
}

function gabc_get_default_settings() {
	return array(
		'enabled'              => 1,
		'position'             => 'top',
		'sticky'               => 1,
		'text'                 => 'Welcome to our site! Check out our latest updates.',
		'button_text'          => 'Learn More',
		'button_url'           => '',
		'button_new_tab'       => 0,
		'bg_color'             => '#f0f0f0',
		'text_color'           => '#333333',
		'button_color'         => '#007bff',
		'padding'              => '15',
		'font_size'            => '16',
		'show_location'        => 'all',
		'hide_logged_in'       => 0,
		'dismissible'          => 1,
		'selected_post_types'  => array(),
		'text_align'           => 'left',
		'font_weight'          => 'normal',
		'button_text_color'    => '#ffffff',
		'button_border_radius' => 4,
		'button_padding_x'     => 20,
		'button_padding_y'     => 8,
		'mobile_font_size'     => 0,
		'mobile_padding'       => 0,
		'mobile_layout'        => 'auto',
	);
}

function gabc_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'gabc_deactivate' );