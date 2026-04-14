<?php
/**
 * GABC Admin Class v1.0.0
 *
 * @package GridxflexAnnouncementBarswithCTA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GABC_Admin {

	/**
	 * Cache group for all GABC queries.
	 */
	const CACHE_GROUP = 'gabc';

	/**
	 * Constructor – hooks only AJAX and assets.
	 * Admin menu is registered externally via register_admin_menu().
	 */
	public function __construct() {
		// AJAX handlers
		add_action( 'wp_ajax_gabc_save_notice',      array( $this, 'save_notice_ajax' ) );
		add_action( 'wp_ajax_gabc_delete_notice',    array( $this, 'delete_notice_ajax' ) );
		add_action( 'wp_ajax_gabc_toggle_notice',    array( $this, 'toggle_notice_ajax' ) );
		add_action( 'wp_ajax_gabc_duplicate_notice', array( $this, 'duplicate_notice_ajax' ) );
		add_action( 'wp_ajax_gabc_reset_stats',      array( $this, 'reset_stats_ajax' ) );

		// Admin assets
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * Legacy method to prevent fatal errors if called externally.
	 */
	public function register_ajax_handlers() {
		// Already registered in constructor.
	}

	public function register_admin_menu() {
		add_menu_page(
			__( 'Announcement Bars', 'gridxflex-announcement-bars' ),
			__( 'Announcement Bars', 'gridxflex-announcement-bars' ),
			'manage_options',
			'gabc-notices',
			array( $this, 'render_notices_list' ),
			'dashicons-megaphone',
			100
		);

		add_submenu_page(
			'gabc-notices',
			__( 'All Notices', 'gridxflex-announcement-bars' ),
			__( 'All Notices', 'gridxflex-announcement-bars' ),
			'manage_options',
			'gabc-notices'
		);

		add_submenu_page(
			'gabc-notices',
			__( 'Add New Notice', 'gridxflex-announcement-bars' ),
			__( 'Add New', 'gridxflex-announcement-bars' ),
			'manage_options',
			'gabc-add-notice',
			array( $this, 'render_add_edit_notice' )
		);

		add_submenu_page(
			'gabc-notices',
			__( 'Analytics', 'gridxflex-announcement-bars' ),
			__( 'Analytics', 'gridxflex-announcement-bars' ),
			'manage_options',
			'gabc-analytics',
			array( $this, 'render_analytics_page' )
		);
	}

	public function enqueue_admin_assets( $hook ) {
		if ( false === strpos( $hook, 'gabc-' ) ) {
			return;
		}

		// Ensure table exists every time admin page loads.
		$this->maybe_create_table();

		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		wp_enqueue_style(
			'gabc-admin',
			GABC_PLUGIN_URL . 'assets/css/admin.css',
			array( 'wp-color-picker' ),
			GABC_VERSION
		);

		wp_enqueue_script(
			'gabc-admin',
			GABC_PLUGIN_URL . 'assets/js/admin.js',
			array( 'jquery', 'wp-color-picker' ),
			GABC_VERSION,
			true
		);

		wp_localize_script(
			'gabc-admin',
			'gabcAdmin',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'gabc_admin' ),
			)
		);
	}

	/**
	 * Create/update the notices table */
	private function maybe_create_table() {
		global $wpdb;

		$db_version = get_option( 'gabc_db_version', '0' );
		$notices_ok  = false;
		$analytics_ok = false;

		if ( version_compare( $db_version, '1.0.0', '>=' ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$notices_ok = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'gabc_notices' ) );
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$analytics_ok = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . 'gabc_analytics' ) );
			if ( $notices_ok && $analytics_ok ) {
				return;
			}
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$wpdb->prefix}gabc_notices (
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

		// Analytics table.
		$analytics_sql = "CREATE TABLE {$wpdb->prefix}gabc_analytics (
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
		dbDelta( $analytics_sql );

		update_option( 'gabc_db_version', '1.0.0' );
	}

	/**
	 * Build the URL for editing a notice (includes edit-specific nonce).
	 */
	private function get_edit_url( $notice_id ) {
		return wp_nonce_url(
			admin_url( 'admin.php?page=gabc-add-notice&id=' . absint( $notice_id ) ),
			'gabc_edit_' . absint( $notice_id ),
			'gabc_nonce'
		);
	}

	/**
	 * Render the author / plugin info sidebar box.
	 * Uses a locally bundled SVG avatar — no remote image requests.
	 */
	private function render_author_box() {
		?>
		<div class="gabc-author-box">
			<div class="gabc-author-header">
				<span class="gabc-author-avatar dashicons dashicons-admin-users" aria-hidden="true"></span>
				<div class="gabc-author-info">
					<h3><?php esc_html_e( 'Grid X Flex', 'gridxflex-announcement-bars' ); ?></h3>
					<p><?php esc_html_e( 'Wordpress Developer', 'gridxflex-announcement-bars' ); ?></p>
				</div>
			</div>
			<div class="gabc-author-links">
				<a href="https://profiles.wordpress.org/gridxflex/" target="_blank" rel="noopener noreferrer" class="gabc-author-link">
					<span class="dashicons dashicons-admin-users"></span>
					<?php esc_html_e( 'Profile', 'gridxflex-announcement-bars' ); ?>
				</a>
				<a href="https://wordpress.org/plugins/search/GridXFlex/" target="_blank" rel="noopener noreferrer" class="gabc-author-link">
					<span class="dashicons dashicons-admin-plugins"></span>
					<?php esc_html_e( 'Plugins', 'gridxflex-announcement-bars' ); ?>
				</a>
				<a href="https://wordpress.org/themes/search/GridXFlex/" target="_blank" rel="noopener noreferrer" class="gabc-author-link">
					<span class="dashicons dashicons-admin-appearance"></span>
					<?php esc_html_e( 'Themes', 'gridxflex-announcement-bars' ); ?>
				</a>
				<a href="https://wordpress.org/plugins/search/GridXFlex-Blocks/" target="_blank" rel="noopener noreferrer" class="gabc-author-link">
					<span class="dashicons dashicons-block-default"></span>
					<?php esc_html_e( 'Blocks', 'gridxflex-announcement-bars' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	public function render_notices_list() {
		global $wpdb;

		$cache_key = 'gabc_all_notices';
		$notices   = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $notices ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$notices = $wpdb->get_results(
				"SELECT * FROM {$wpdb->prefix}gabc_notices ORDER BY priority DESC, id ASC"
			);
			wp_cache_set( $cache_key, $notices, self::CACHE_GROUP, 300 );
		}

		// Fetch all-time analytics totals per notice in a single query.
		$stats_map = array();
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$stats_rows = $wpdb->get_results(
			"SELECT notice_id, event_type, SUM(count) AS total
			 FROM {$wpdb->prefix}gabc_analytics
			 GROUP BY notice_id, event_type"
		);
		foreach ( (array) $stats_rows as $row ) {
			$stats_map[ $row->notice_id ][ $row->event_type ] = (int) $row->total;
		}

		?>
		<div class="wrap gabc-admin">
			<h1 class="wp-heading-inline pb-1"><?php esc_html_e( 'Announcement Bars', 'gridxflex-announcement-bars' ); ?></h1>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=gabc-add-notice' ) ); ?>" class="page-title-action">
				<?php esc_html_e( 'Add New', 'gridxflex-announcement-bars' ); ?>
			</a>
			<hr class="wp-header-end">

			<?php if ( isset( $_GET['gabc_saved'] ) && '1' === $_GET['gabc_saved'] && isset( $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ), 'gabc_saved_notice' ) ) : ?>
				<div class="notice notice-success is-dismissible">
					<p><?php esc_html_e( 'Notice saved successfully!', 'gridxflex-announcement-bars' ); ?></p>
				</div>
			<?php endif; ?>

			<div class="gabc-admin-layout">
				<div class="gabc-admin-main">
					<div class="gabc-table-wrap">
					<table class="wp-list-table widefat fixed striped gabc-notices-table">
						<thead>
							<tr>
								<th style="width: 40px;"><?php esc_html_e( 'ID', 'gridxflex-announcement-bars' ); ?></th>
								<th><?php esc_html_e( 'Title', 'gridxflex-announcement-bars' ); ?></th>
								<th><?php esc_html_e( 'Position', 'gridxflex-announcement-bars' ); ?></th>
								<th class="gabc-col-priority"><?php esc_html_e( 'Priority', 'gridxflex-announcement-bars' ); ?></th>
								<th class="gabc-col-schedule"><?php esc_html_e( 'Schedule', 'gridxflex-announcement-bars' ); ?></th>
								<th class="gabc-col-stat" title="<?php esc_attr_e( 'Total times the announcement bar was displayed', 'gridxflex-announcement-bars' ); ?>">
									<?php esc_html_e( 'Views', 'gridxflex-announcement-bars' ); ?>
								</th>
								<th class="gabc-col-stat" title="<?php esc_attr_e( 'Total CTA button clicks', 'gridxflex-announcement-bars' ); ?>">
									<?php esc_html_e( 'Clicks', 'gridxflex-announcement-bars' ); ?>
								</th>
								<th><?php esc_html_e( 'Status', 'gridxflex-announcement-bars' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'gridxflex-announcement-bars' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php if ( empty( $notices ) ) : ?>
								<tr>
									<td colspan="10" style="text-align: center; padding: 40px;">
										<?php esc_html_e( 'No announcement bars found.', 'gridxflex-announcement-bars' ); ?>
										<a href="<?php echo esc_url( admin_url( 'admin.php?page=gabc-add-notice' ) ); ?>">
											<?php esc_html_e( 'Create your first announcement bar', 'gridxflex-announcement-bars' ); ?>
										</a>
									</td>
								</tr>
							<?php else : ?>
								<?php foreach ( $notices as $notice ) :
									$nid    = (int) $notice->id;
									$views  = isset( $stats_map[ $nid ]['view'] )  ? $stats_map[ $nid ]['view']  : 0;
									$clicks = isset( $stats_map[ $nid ]['click'] ) ? $stats_map[ $nid ]['click'] : 0;
									$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 1 ) : 0;
								?>
									<tr data-notice-id="<?php echo esc_attr( $notice->id ); ?>">
										<td data-label="<?php esc_attr_e( 'ID', 'gridxflex-announcement-bars' ); ?>"><?php echo esc_html( $notice->id ); ?></td>
										<td data-label="<?php esc_attr_e( 'Title', 'gridxflex-announcement-bars' ); ?>">
											<strong>
												<a href="<?php echo esc_url( $this->get_edit_url( $notice->id ) ); ?>">
													<?php echo esc_html( $notice->title ); ?>
												</a>
											</strong>
										</td>
										<td data-label="<?php esc_attr_e( 'Position', 'gridxflex-announcement-bars' ); ?>"><?php echo esc_html( ucfirst( $notice->position ) ); ?></td>
										<td data-label="<?php esc_attr_e( 'Priority', 'gridxflex-announcement-bars' ); ?>" class="gabc-col-priority"><?php echo esc_html( $notice->priority ); ?></td>
										<td data-label="<?php esc_attr_e( 'Schedule', 'gridxflex-announcement-bars' ); ?>" class="gabc-col-schedule">
											<?php
											if ( $notice->start_date || $notice->end_date ) {
												echo esc_html( $notice->start_date ? date_i18n( 'M j, Y', strtotime( $notice->start_date ) ) : '—' );
												echo ' → ';
												echo esc_html( $notice->end_date ? date_i18n( 'M j, Y', strtotime( $notice->end_date ) ) : '∞' );
											} else {
												esc_html_e( 'Always', 'gridxflex-announcement-bars' );
											}
											?>
										</td>
										<td data-label="<?php esc_attr_e( 'Views', 'gridxflex-announcement-bars' ); ?>" class="gabc-col-stat">
											<span class="gabc-stat-badge gabc-stat-views"><?php echo esc_html( number_format_i18n( $views ) ); ?></span>
										</td>
										<td data-label="<?php esc_attr_e( 'Clicks', 'gridxflex-announcement-bars' ); ?>" class="gabc-col-stat">
											<span class="gabc-stat-badge gabc-stat-clicks"><?php echo esc_html( number_format_i18n( $clicks ) ); ?></span>
										</td>
										<td data-label="<?php esc_attr_e( 'Status', 'gridxflex-announcement-bars' ); ?>">
											<label class="gabc-toggle">
												<input type="checkbox" class="gabc-toggle-status"
													data-id="<?php echo esc_attr( $notice->id ); ?>"
													<?php checked( $notice->enabled, 1 ); ?>>
												<span class="gabc-toggle-slider"></span>
											</label>
										</td>
										<td class="gabc-actions-cell">
											<a href="<?php echo esc_url( $this->get_edit_url( $notice->id ) ); ?>"
												class="gabc-action-btn gabc-edit"
												title="<?php esc_attr_e( 'Edit', 'gridxflex-announcement-bars' ); ?>">
												<span class="dashicons dashicons-edit"></span>
											</a>
											<button type="button"
												class="gabc-action-btn gabc-duplicate"
												data-id="<?php echo esc_attr( $notice->id ); ?>"
												title="<?php esc_attr_e( 'Duplicate', 'gridxflex-announcement-bars' ); ?>">
												<span class="dashicons dashicons-admin-page"></span>
											</button>
											<button type="button"
												class="gabc-action-btn gabc-reset-stats"
												data-id="<?php echo esc_attr( $notice->id ); ?>"
												title="<?php esc_attr_e( 'Reset Analytics', 'gridxflex-announcement-bars' ); ?>">
												<span class="dashicons dashicons-image-rotate"></span>
											</button>
											<button type="button"
												class="gabc-action-btn gabc-delete"
												data-id="<?php echo esc_attr( $notice->id ); ?>"
												title="<?php esc_attr_e( 'Delete', 'gridxflex-announcement-bars' ); ?>">
												<span class="dashicons dashicons-trash"></span>
											</button>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
					</div><!-- .gabc-table-wrap -->
				</div><!-- .gabc-admin-main -->

				<div class="gabc-admin-sidebar">
					<?php $this->render_author_box(); ?>
				</div>
			</div><!-- .gabc-admin-layout -->
		</div>
		<?php
	}

	public function render_add_edit_notice() {
		global $wpdb;

		$notice_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
		$notice = null;

		if ( $notice_id ) {
			// Verify edit nonce.
			if ( ! isset( $_GET['gabc_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['gabc_nonce'] ) ), 'gabc_edit_' . $notice_id ) ) {
				wp_die( esc_html__( 'Invalid security token', 'gridxflex-announcement-bars' ) );
			}

			$cache_key = 'gabc_notice_' . $notice_id;
			$notice    = wp_cache_get( $cache_key, self::CACHE_GROUP );

			if ( false === $notice ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$notice = $wpdb->get_row(
					$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gabc_notices WHERE id = %d", $notice_id )
				);
				if ( $notice ) {
					wp_cache_set( $cache_key, $notice, self::CACHE_GROUP, 300 );
				}
			}

			if ( ! $notice ) {
				wp_die( esc_html__( 'Notice not found', 'gridxflex-announcement-bars' ) );
			}
		}

		// Get values or defaults.
		$title               = $notice ? $notice->title               : '';
		$text                = $notice ? $notice->text                : '';
		$button_text         = $notice ? $notice->button_text         : '';
		$button_url          = $notice ? $notice->button_url          : '';
		$button_new_tab      = $notice ? $notice->button_new_tab      : 0;
		$bg_color            = $notice ? $notice->bg_color            : '#f0f0f0';
		$text_color          = $notice ? $notice->text_color          : '#333333';
		$button_color        = $notice ? $notice->button_color        : '#007bff';
		$padding             = $notice ? $notice->padding             : 15;
		$font_size           = $notice ? $notice->font_size           : 16;
		$position            = $notice ? $notice->position            : 'top';
		$sticky              = $notice ? (int) $notice->sticky        : 1;
		$show_location       = $notice ? $notice->show_location       : 'all';
		$selected_pages      = $notice ? maybe_unserialize( $notice->selected_pages )      : array();
		$selected_categories = $notice ? maybe_unserialize( $notice->selected_categories ) : array();
		$selected_tags       = $notice ? maybe_unserialize( $notice->selected_tags )       : array();
		$selected_post_types = $notice ? maybe_unserialize( $notice->selected_post_types ) : array();
		$user_roles          = $notice ? maybe_unserialize( $notice->user_roles )          : array();
		$hide_logged_in      = $notice ? $notice->hide_logged_in      : 0;
		$dismissible         = $notice ? $notice->dismissible         : 1;
		$start_date          = $notice ? $notice->start_date          : '';
		$end_date            = $notice ? $notice->end_date            : '';
		$priority            = $notice ? $notice->priority            : 0;
		$enabled             = $notice ? $notice->enabled             : 1;
		$trigger_delay       = $notice ? (int) ( $notice->trigger_delay       ?? 0 ) : 0;
		$trigger_scroll      = $notice ? (int) ( $notice->trigger_scroll      ?? 0 ) : 0;
		$trigger_exit_intent = $notice ? (int) ( $notice->trigger_exit_intent ?? 0 ) : 0;
		$animation           = $notice ? (string) ( $notice->animation        ?? 'none' ) : 'none';
		$animation_duration  = $notice ? (int) ( $notice->animation_duration ?? 400 ) : 400;

		$pages      = get_pages();
		$categories = get_categories( array( 'hide_empty' => false ) );
		$tags       = get_tags( array( 'hide_empty' => false ) );
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$wp_roles   = wp_roles()->roles;

		$h1_title = $notice_id
			? ( $title ? sprintf(
				/* translators: %s: notice title */
				__( 'Edit Announcement Bar &mdash; %s', 'gridxflex-announcement-bars' ),
				esc_html( $title )
			) : esc_html__( 'Edit Announcement Bar', 'gridxflex-announcement-bars' ) )
			: esc_html__( 'Add New Announcement Bar', 'gridxflex-announcement-bars' );

		$tab_title = $notice_id
			? ( $title
				? sprintf( 'Edit Announcement Bar %1$s %2$s %3$s %4$s', html_entity_decode( '&mdash;', ENT_QUOTES, 'UTF-8' ), $title, html_entity_decode( '&#x2039;', ENT_QUOTES, 'UTF-8' ), get_bloginfo( 'name' ) )
				: sprintf( 'Edit Announcement Bar %1$s %2$s', html_entity_decode( '&#x2039;', ENT_QUOTES, 'UTF-8' ), get_bloginfo( 'name' ) ) )
			: sprintf( 'Add New Announcement Bar %1$s %2$s', html_entity_decode( '&#x2039;', ENT_QUOTES, 'UTF-8' ), get_bloginfo( 'name' ) );

		// Set the browser tab title via an enqueued inline script (no bare <script> tags allowed).
		wp_add_inline_script(
			'gabc-admin',
			'document.title = ' . wp_json_encode( $tab_title ) . ';'
		);
		?>
		<div class="wrap gabc-admin">
			<h1><?php echo wp_kses_post( $h1_title ); ?></h1>

			<div class="gabc-admin-layout">
				<div class="gabc-admin-main">
					<form id="gabc-notice-form" method="post">
						<input type="hidden" name="notice_id" value="<?php echo esc_attr( $notice_id ); ?>" />

						<div class="gabc-form-container">

							<!-- Basic Settings -->
							<div class="gabc-form-section">
								<h2><?php esc_html_e( 'Basic Settings', 'gridxflex-announcement-bars' ); ?></h2>
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gabc_enabled"><?php esc_html_e( 'Enable Notice', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="checkbox" name="enabled" id="gabc_enabled" value="1" <?php checked( $enabled, 1 ); ?> />
												<span class="description"><?php esc_html_e( 'Show this announcement bar on the frontend', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_title"><?php esc_html_e( 'Title', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="text" name="title" id="gabc_title" value="<?php echo esc_attr( $title ); ?>" required />
												<span class="description"><?php esc_html_e( 'Internal title for identification (not shown to visitors)', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_text"><?php esc_html_e( 'Notice Text', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<textarea name="text" id="gabc_text" rows="3" required><?php echo esc_textarea( $text ); ?></textarea>
												<span class="description"><?php esc_html_e( 'The message displayed in the announcement bar. HTML allowed.', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_position"><?php esc_html_e( 'Position', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<select name="position" id="gabc_position">
													<option value="top" <?php selected( $position, 'top' ); ?>><?php esc_html_e( 'Top', 'gridxflex-announcement-bars' ); ?></option>
													<option value="bottom" <?php selected( $position, 'bottom' ); ?>><?php esc_html_e( 'Bottom', 'gridxflex-announcement-bars' ); ?></option>
												</select>
												<span class="description"><?php esc_html_e( 'Where to display the announcement bar', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_sticky"><?php esc_html_e( 'Sticky Position', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="checkbox" name="sticky" id="gabc_sticky" value="1" <?php checked( $sticky, 1 ); ?> />
												<span class="description"><?php esc_html_e( 'Fixed position (stays visible while scrolling)', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_priority"><?php esc_html_e( 'Priority', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="number" name="priority" id="gabc_priority" value="<?php echo esc_attr( $priority ); ?>" min="0" step="1" />
												<span class="description"><?php esc_html_e( 'Higher priority notices display first (if multiple notices)', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Button Settings -->
							<div class="gabc-form-section">
								<h2><?php esc_html_e( 'Button Settings', 'gridxflex-announcement-bars' ); ?></h2>
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gabc_button_text"><?php esc_html_e( 'Button Text', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="text" name="button_text" id="gabc_button_text" value="<?php echo esc_attr( $button_text ); ?>" />
												<span class="description"><?php esc_html_e( 'Leave empty to hide the button', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_button_url"><?php esc_html_e( 'Button URL', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="url" name="button_url" id="gabc_button_url" value="<?php echo esc_attr( $button_url ); ?>" />
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_button_new_tab"><?php esc_html_e( 'Open in New Tab', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="checkbox" name="button_new_tab" id="gabc_button_new_tab" value="1" <?php checked( $button_new_tab, 1 ); ?> />
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Design Settings -->
							<div class="gabc-form-section">
								<h2><?php esc_html_e( 'Design Settings', 'gridxflex-announcement-bars' ); ?></h2>
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gabc_animation"><?php esc_html_e( 'Entrance Animation', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<select name="animation" id="gabc_animation">
													<option value="none"   <?php selected( $animation, 'none' ); ?>><?php esc_html_e( 'Default (no animation)', 'gridxflex-announcement-bars' ); ?></option>
													<option value="slide"  <?php selected( $animation, 'slide' ); ?>><?php esc_html_e( 'Slide in', 'gridxflex-announcement-bars' ); ?></option>
													<option value="fade"   <?php selected( $animation, 'fade' ); ?>><?php esc_html_e( 'Fade in', 'gridxflex-announcement-bars' ); ?></option>
													<option value="reveal" <?php selected( $animation, 'reveal' ); ?>><?php esc_html_e( 'Smooth reveal', 'gridxflex-announcement-bars' ); ?></option>
													<option value="pop"    <?php selected( $animation, 'pop' ); ?>><?php esc_html_e( 'Pop in', 'gridxflex-announcement-bars' ); ?></option>
													<option value="bounce" <?php selected( $animation, 'bounce' ); ?>><?php esc_html_e( 'Bounce in', 'gridxflex-announcement-bars' ); ?></option>
													<option value="flip"   <?php selected( $animation, 'flip' ); ?>><?php esc_html_e( 'Flip in', 'gridxflex-announcement-bars' ); ?></option>
												</select>
												<span class="description"><?php esc_html_e( 'Animation played when the announcement bar first appears.', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
    <th scope="row">
        <label for="gabc_animation_duration"><?php esc_html_e( 'Animation Duration', 'gridxflex-announcement-bars' ); ?></label>
    </th>
    <td>
        <span class="gabc-input-unit-wrap">
            <input type="number" name="animation_duration" id="gabc_animation_duration" value="<?php echo esc_attr( $animation_duration ); ?>" min="100" max="2000" step="50" />
            <span class="gabc-unit">ms</span>
        </span>
        <span class="description"><?php esc_html_e( 'How long the entrance animation takes. Default: 400ms.', 'gridxflex-announcement-bars' ); ?></span>
    </td>
</tr>
										<tr>
											<th scope="row">
												<label for="bg_color"><?php esc_html_e( 'Background Color', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="text" name="bg_color" id="bg_color" class="gabc-color-picker" value="<?php echo esc_attr( $bg_color ); ?>" />
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_text_color"><?php esc_html_e( 'Text Color', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="text" name="text_color" id="gabc_text_color" class="gabc-color-picker" value="<?php echo esc_attr( $text_color ); ?>" />
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="button_color"><?php esc_html_e( 'Button Color', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="text" name="button_color" id="button_color" class="gabc-color-picker" value="<?php echo esc_attr( $button_color ); ?>" />
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_padding"><?php esc_html_e( 'Padding', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<span class="gabc-input-unit-wrap">
    <input type="number" name="padding" id="gabc_padding" value="<?php echo esc_attr( $padding ); ?>" min="0" step="1" />
    <span class="gabc-unit">px</span>
</span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_font_size"><?php esc_html_e( 'Font Size', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<span class="gabc-input-unit-wrap">
    <input type="number" name="font_size" id="gabc_font_size" value="<?php echo esc_attr( $font_size ); ?>" min="10" max="30" step="1" />
    <span class="gabc-unit">px</span>
</span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Visibility Settings -->
							<div class="gabc-form-section">
								<h2><?php esc_html_e( 'Visibility Settings', 'gridxflex-announcement-bars' ); ?></h2>
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gabc_show_location"><?php esc_html_e( 'Display Location', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<select name="show_location" id="gabc_show_location">
													<option value="all" <?php selected( $show_location, 'all' ); ?>><?php esc_html_e( 'Show on Entire Site', 'gridxflex-announcement-bars' ); ?></option>
													<option value="homepage" <?php selected( $show_location, 'homepage' ); ?>><?php esc_html_e( 'Homepage Only', 'gridxflex-announcement-bars' ); ?></option>
													<option value="specific_pages" <?php selected( $show_location, 'specific_pages' ); ?>><?php esc_html_e( 'Specific Pages', 'gridxflex-announcement-bars' ); ?></option>
													<option value="categories" <?php selected( $show_location, 'categories' ); ?>><?php esc_html_e( 'Specific Categories', 'gridxflex-announcement-bars' ); ?></option>
													<option value="tags" <?php selected( $show_location, 'tags' ); ?>><?php esc_html_e( 'Specific Tags', 'gridxflex-announcement-bars' ); ?></option>
													<option value="post_types" <?php selected( $show_location, 'post_types' ); ?>><?php esc_html_e( 'Specific Post Types', 'gridxflex-announcement-bars' ); ?></option>
												</select>
											</td>
										</tr>
										<tr id="gabc_pages_row" style="display: none;">
											<th scope="row">
												<label><?php esc_html_e( 'Select Pages', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td class="gabc-checkbox-list">
												<?php foreach ( $pages as $page ) : ?>
													<label>
														<input type="checkbox" name="selected_pages[]" value="<?php echo esc_attr( $page->ID ); ?>" <?php checked( in_array( $page->ID, (array) $selected_pages, true ) ); ?> />
														<?php echo esc_html( $page->post_title ); ?>
													</label>
												<?php endforeach; ?>
											</td>
										</tr>
										<tr id="gabc_categories_row" style="display: none;">
											<th scope="row">
												<label><?php esc_html_e( 'Select Categories', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td class="gabc-checkbox-list">
												<?php foreach ( $categories as $category ) : ?>
													<label>
														<input type="checkbox" name="selected_categories[]" value="<?php echo esc_attr( $category->term_id ); ?>" <?php checked( in_array( $category->term_id, (array) $selected_categories, true ) ); ?> />
														<?php echo esc_html( $category->name ); ?>
													</label>
												<?php endforeach; ?>
											</td>
										</tr>
										<tr id="gabc_tags_row" style="display: none;">
											<th scope="row">
												<label><?php esc_html_e( 'Select Tags', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td class="gabc-checkbox-list">
												<?php foreach ( $tags as $tag ) : ?>
													<label>
														<input type="checkbox" name="selected_tags[]" value="<?php echo esc_attr( $tag->term_id ); ?>" <?php checked( in_array( $tag->term_id, (array) $selected_tags, true ) ); ?> />
														<?php echo esc_html( $tag->name ); ?>
													</label>
												<?php endforeach; ?>
											</td>
										</tr>
										<tr id="gabc_post_types_row" style="display: none;">
											<th scope="row">
												<label><?php esc_html_e( 'Select Post Types', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td class="gabc-checkbox-list">
												<?php foreach ( $post_types as $pt_slug => $pt_obj ) : ?>
													<label>
														<input type="checkbox" name="selected_post_types[]" value="<?php echo esc_attr( $pt_slug ); ?>" <?php checked( in_array( $pt_slug, (array) $selected_post_types, true ) ); ?> />
														<?php echo esc_html( $pt_obj->labels->name ); ?>
													</label>
												<?php endforeach; ?>
											</td>
										</tr>
										<tr id="gabc_user_roles_row">
											<th scope="row">
												<label><?php esc_html_e( 'User Roles', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td class="gabc-checkbox-list">
												<?php foreach ( $wp_roles as $role_slug => $role_info ) : ?>
													<label>
														<input type="checkbox" name="user_roles[]" value="<?php echo esc_attr( $role_slug ); ?>" <?php checked( in_array( $role_slug, (array) $user_roles, true ) ); ?> />
														<?php echo esc_html( $role_info['name'] ); ?>
													</label>
												<?php endforeach; ?>
												<span class="description"><?php esc_html_e( 'Show notice only to selected user roles. Leave unchecked to show to all users.', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_hide_logged_in"><?php esc_html_e( 'Hide from Logged-in Users', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="checkbox" name="hide_logged_in" id="gabc_hide_logged_in" value="1" <?php checked( $hide_logged_in, 1 ); ?> />
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_dismissible"><?php esc_html_e( 'Dismissible', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="checkbox" name="dismissible" id="gabc_dismissible" value="1" <?php checked( $dismissible, 1 ); ?> />
												<span class="description"><?php esc_html_e( 'Allow users to close the notice (30-day cookie)', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Schedule Settings -->
							<div class="gabc-form-section">
								<h2><?php esc_html_e( 'Schedule Settings', 'gridxflex-announcement-bars' ); ?></h2>
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gabc_start_date"><?php esc_html_e( 'Start Date', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="datetime-local" name="start_date" id="gabc_start_date" value="<?php echo esc_attr( $start_date ? str_replace( ' ', 'T', $start_date ) : '' ); ?>" />
												<span class="description"><?php esc_html_e( 'Leave empty to show immediately', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_end_date"><?php esc_html_e( 'End Date', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="datetime-local" name="end_date" id="gabc_end_date" value="<?php echo esc_attr( $end_date ? str_replace( ' ', 'T', $end_date ) : '' ); ?>" />
												<span class="description"><?php esc_html_e( 'Leave empty to show indefinitely', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Trigger Settings -->
							<div class="gabc-form-section">
								<h2><?php esc_html_e( 'Trigger Settings', 'gridxflex-announcement-bars' ); ?></h2>
								<table class="form-table" role="presentation">
									<tbody>
										<tr>
											<th scope="row">
												<label for="gabc_trigger_delay"><?php esc_html_e( 'Show After (seconds)', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<span class="gabc-input-unit-wrap">
    <input type="number" name="trigger_delay" id="gabc_trigger_delay" value="<?php echo esc_attr( $trigger_delay ); ?>" min="0" step="1" max="300" />
    <span class="gabc-unit">s</span>
</span>
												<span class="description"><?php esc_html_e( 'Delay before the announcement bar appears. Set to 0 to show immediately.', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_trigger_scroll"><?php esc_html_e( 'Show on Scroll (%)', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<span class="gabc-input-unit-wrap">
    <input type="number" name="trigger_scroll" id="gabc_trigger_scroll" value="<?php echo esc_attr( $trigger_scroll ); ?>" min="0" step="1" max="100" />
    <span class="gabc-unit">%</span>
</span>
												<span class="description"><?php esc_html_e( 'Show notice when user scrolls this % down the page. Set to 0 to disable.', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
										<tr>
											<th scope="row">
												<label for="gabc_trigger_exit_intent"><?php esc_html_e( 'Show on Exit Intent', 'gridxflex-announcement-bars' ); ?></label>
											</th>
											<td>
												<input type="checkbox" name="trigger_exit_intent" id="gabc_trigger_exit_intent" value="1" <?php checked( $trigger_exit_intent, 1 ); ?> />
												<span class="description"><?php esc_html_e( 'Show notice when user moves cursor toward closing the tab (desktop only).', 'gridxflex-announcement-bars' ); ?></span>
											</td>
										</tr>
									</tbody>
								</table>
							</div>

							<!-- Form Actions -->
							<div class="gabc-form-actions">
								<button type="submit" class="button-primary">
									<?php esc_html_e( 'Save Notice', 'gridxflex-announcement-bars' ); ?>
								</button>
								<a href="<?php echo esc_url( admin_url( 'admin.php?page=gabc-notices' ) ); ?>" class="button">
									<?php esc_html_e( 'Cancel', 'gridxflex-announcement-bars' ); ?>
								</a>
							</div>
						</div>
					</form>
				</div><!-- .gabc-admin-main -->

				<div class="gabc-admin-sidebar">
					<?php $this->render_author_box(); ?>
				</div>
			</div><!-- .gabc-admin-layout -->
		</div>
		<?php
	}

	public function save_notice_ajax() {
		check_ajax_referer( 'gabc_admin', '_wpnonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'gridxflex-announcement-bars' ) ) );
		}

		global $wpdb;

		// Ensure table exists before inserting.
		$this->maybe_create_table();

		$notice_id = isset( $_POST['notice_id'] ) ? absint( $_POST['notice_id'] ) : 0;

		$data = array(
			'title'               => isset( $_POST['title'] )          ? sanitize_text_field( wp_unslash( $_POST['title'] ) )          : '',
			'text'                => isset( $_POST['text'] )           ? wp_kses_post( wp_unslash( $_POST['text'] ) )                  : '',
			'button_text'         => isset( $_POST['button_text'] )    ? sanitize_text_field( wp_unslash( $_POST['button_text'] ) )    : '',
			'button_url'          => isset( $_POST['button_url'] )     ? esc_url_raw( wp_unslash( $_POST['button_url'] ) )             : '',
			'button_new_tab'      => isset( $_POST['button_new_tab'] ) ? 1 : 0,
			'bg_color'            => isset( $_POST['bg_color'] )       ? gabc_sanitize_color( sanitize_text_field( wp_unslash( $_POST['bg_color'] ) ) )       : '#f0f0f0',
			'text_color'          => isset( $_POST['text_color'] )     ? sanitize_hex_color( wp_unslash( $_POST['text_color'] ) )      : '#333333',
			'button_color'        => isset( $_POST['button_color'] )   ? gabc_sanitize_color( sanitize_text_field( wp_unslash( $_POST['button_color'] ) ) )   : '#007bff',
			'padding'             => isset( $_POST['padding'] )        ? absint( $_POST['padding'] )                                  : 15,
			'font_size'           => isset( $_POST['font_size'] )      ? absint( $_POST['font_size'] )                                : 16,
			'position'            => isset( $_POST['position'] )       ? sanitize_text_field( wp_unslash( $_POST['position'] ) )      : 'top',
			'sticky'              => isset( $_POST['sticky'] )         ? 1 : 0,
			'show_location'       => isset( $_POST['show_location'] )  ? sanitize_text_field( wp_unslash( $_POST['show_location'] ) ) : 'all',
			'selected_pages'      => isset( $_POST['selected_pages'] )      ? maybe_serialize( array_map( 'absint', wp_unslash( $_POST['selected_pages'] ) ) )                             : null,
			'selected_categories' => isset( $_POST['selected_categories'] ) ? maybe_serialize( array_map( 'absint', wp_unslash( $_POST['selected_categories'] ) ) )                       : null,
			'selected_tags'       => isset( $_POST['selected_tags'] )       ? maybe_serialize( array_map( 'absint', wp_unslash( $_POST['selected_tags'] ) ) )                             : null,
			'selected_post_types' => isset( $_POST['selected_post_types'] ) ? maybe_serialize( array_map( 'sanitize_text_field', wp_unslash( $_POST['selected_post_types'] ) ) )         : null,
			'user_roles'          => isset( $_POST['user_roles'] )          ? maybe_serialize( array_map( 'sanitize_text_field', wp_unslash( $_POST['user_roles'] ) ) )                   : null,
			'hide_logged_in'      => isset( $_POST['hide_logged_in'] ) ? 1 : 0,
			'dismissible'         => isset( $_POST['dismissible'] )    ? 1 : 0,
			'start_date'          => ( isset( $_POST['start_date'] ) && ! empty( $_POST['start_date'] ) ) ? str_replace( 'T', ' ', sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) ) : null,
			'end_date'            => ( isset( $_POST['end_date'] )   && ! empty( $_POST['end_date'] ) )   ? str_replace( 'T', ' ', sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) )   : null,
			'priority'            => isset( $_POST['priority'] ) ? absint( $_POST['priority'] ) : 0,
			'enabled'             => isset( $_POST['enabled'] )  ? 1 : 0,
			'trigger_delay'       => isset( $_POST['trigger_delay'] )       ? absint( $_POST['trigger_delay'] )       : 0,
			'trigger_scroll'      => isset( $_POST['trigger_scroll'] )      ? absint( $_POST['trigger_scroll'] )      : 0,
			'trigger_exit_intent' => isset( $_POST['trigger_exit_intent'] ) ? 1                                       : 0,
			'animation'           => isset( $_POST['animation'] )           ? sanitize_text_field( wp_unslash( $_POST['animation'] ) )                 : 'none',
			'animation_duration'  => isset( $_POST['animation_duration'] ) ? absint( $_POST['animation_duration'] ) : 400,
		);

		// Validate required fields.
		if ( empty( $data['title'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Title is required.', 'gridxflex-announcement-bars' ) ) );
		}
		if ( empty( $data['text'] ) ) {
			wp_send_json_error( array( 'message' => __( 'Notice text is required.', 'gridxflex-announcement-bars' ) ) );
		}

		$formats = array(
			'%s', '%s', '%s', '%s', '%d',  // title, text, button_text, button_url, button_new_tab
			'%s', '%s', '%s', '%d', '%d',  // bg_color, text_color, button_color, padding, font_size
			'%s', '%d', '%s', '%s', '%s',  // position, sticky, show_location, selected_pages, selected_categories
			'%s', '%s', '%s', '%d', '%d',  // selected_tags, selected_post_types, user_roles, hide_logged_in, dismissible
			'%s', '%s', '%d', '%d',        // start_date, end_date, priority, enabled
			'%d', '%d', '%d',              // trigger_delay, trigger_scroll, trigger_exit_intent
			'%s', '%d',   // animation, animation_duration
		);

		if ( $notice_id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->update(
				"{$wpdb->prefix}gabc_notices",
				$data,
				array( 'id' => $notice_id ),
				$formats,
				array( '%d' )
			);
		} else {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$result = $wpdb->insert(
				"{$wpdb->prefix}gabc_notices",
				$data,
				$formats
			);
			$notice_id = $wpdb->insert_id;
		}

		if ( false === $result ) {
			wp_send_json_error( array(
				'message' => sprintf(
					/* translators: %s: database error message */
					__( 'Database error: %s', 'gridxflex-announcement-bars' ),
					$wpdb->last_error
				),
			) );
		}

		// Bust cache.
		wp_cache_delete( 'gabc_all_notices', self::CACHE_GROUP );
		wp_cache_delete( 'gabc_notice_' . $notice_id, self::CACHE_GROUP );

		// If editing an existing notice: no redirect (stay on edit page).
		// If adding a new notice: redirect to the all-notices list.
		$was_new = ! ( isset( $_POST['notice_id'] ) && absint( $_POST['notice_id'] ) > 0 );

		$response = array(
			'message' => __( 'Notice saved successfully!', 'gridxflex-announcement-bars' ),
		);

		if ( $was_new ) {
			$response['redirect'] = wp_nonce_url(
    admin_url( 'admin.php?page=gabc-notices&gabc_saved=1' ),
    'gabc_saved_notice'
);
		}

		wp_send_json_success( $response );
	}

	public function delete_notice_ajax() {
		check_ajax_referer( 'gabc_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'gridxflex-announcement-bars' ) ) );
		}

		global $wpdb;
		$notice_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $notice_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'gridxflex-announcement-bars' ) ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			"{$wpdb->prefix}gabc_notices",
			array( 'id' => $notice_id ),
			array( '%d' )
		);

		// Delete associated analytics.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->delete(
			"{$wpdb->prefix}gabc_analytics",
			array( 'notice_id' => $notice_id ),
			array( '%d' )
		);

		wp_cache_delete( 'gabc_all_notices', self::CACHE_GROUP );
		wp_cache_delete( 'gabc_notice_' . $notice_id, self::CACHE_GROUP );

		wp_send_json_success( array( 'message' => __( 'Notice deleted successfully!', 'gridxflex-announcement-bars' ) ) );
	}

	public function toggle_notice_ajax() {
		check_ajax_referer( 'gabc_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'gridxflex-announcement-bars' ) ) );
		}

		global $wpdb;
		$notice_id = isset( $_POST['id'] )      ? absint( $_POST['id'] )      : 0;
		$enabled   = isset( $_POST['enabled'] ) ? absint( $_POST['enabled'] ) : 0;

		if ( ! $notice_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'gridxflex-announcement-bars' ) ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->update(
			"{$wpdb->prefix}gabc_notices",
			array( 'enabled' => $enabled ),
			array( 'id'      => $notice_id ),
			array( '%d' ),
			array( '%d' )
		);

		wp_cache_delete( 'gabc_all_notices', self::CACHE_GROUP );
		wp_cache_delete( 'gabc_notice_' . $notice_id, self::CACHE_GROUP );

		wp_send_json_success( array( 'message' => __( 'Notice status updated!', 'gridxflex-announcement-bars' ) ) );
	}

	public function duplicate_notice_ajax() {
		check_ajax_referer( 'gabc_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'gridxflex-announcement-bars' ) ) );
		}

		global $wpdb;
		$notice_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $notice_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'gridxflex-announcement-bars' ) ) );
		}

		$cache_key = 'gabc_notice_' . $notice_id;
		$notice = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $notice ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$notice = $wpdb->get_row(
				$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}gabc_notices WHERE id = %d", $notice_id ),
				ARRAY_A
			);
			if ( $notice ) {
				wp_cache_set( $cache_key, $notice, self::CACHE_GROUP, 300 );
			}
		} else {
			$notice = (array) $notice;
		}

		if ( ! $notice ) {
			wp_send_json_error( array( 'message' => __( 'Notice not found', 'gridxflex-announcement-bars' ) ) );
		}

		unset( $notice['id'], $notice['created_at'], $notice['updated_at'] );
		$notice['title']   .= ' (Copy)';
		$notice['enabled']  = 0;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$wpdb->insert( "{$wpdb->prefix}gabc_notices", $notice );

		wp_cache_delete( 'gabc_all_notices', self::CACHE_GROUP );

		wp_send_json_success( array( 'message' => __( 'Notice duplicated successfully!', 'gridxflex-announcement-bars' ) ) );
	}

	public function reset_stats_ajax() {
		check_ajax_referer( 'gabc_admin', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized', 'gridxflex-announcement-bars' ) ) );
		}

		global $wpdb;
		$notice_id = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0;

		if ( ! $notice_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid notice ID', 'gridxflex-announcement-bars' ) ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			"{$wpdb->prefix}gabc_analytics",
			array( 'notice_id' => $notice_id ),
			array( '%d' )
		);

		wp_send_json_success( array( 'message' => __( 'Analytics reset successfully!', 'gridxflex-announcement-bars' ) ) );
	}

	/**
	 * Render the Analytics admin page.
	 * Charts are shown on demand: click "View Chart" on any row, or use Compare mode
	 * to select multiple notices and overlay them on a single chart.
	 */
	public function render_analytics_page() {
		global $wpdb;

		// Fetch all notices for the name lookup.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$notices = $wpdb->get_results(
			"SELECT id, title FROM {$wpdb->prefix}gabc_notices ORDER BY priority DESC, id ASC"
		);
		$notice_map = array();
		foreach ( (array) $notices as $n ) {
			$notice_map[ (int) $n->id ] = $n->title;
		}

		// All-time totals.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$totals = $wpdb->get_results(
			"SELECT notice_id, event_type, SUM(count) AS total
			 FROM {$wpdb->prefix}gabc_analytics
			 GROUP BY notice_id, event_type
			 ORDER BY notice_id ASC"
		);

		$stats = array();
		foreach ( (array) $totals as $row ) {
			$nid = (int) $row->notice_id;
			if ( ! isset( $stats[ $nid ] ) ) {
				$stats[ $nid ] = array( 'view' => 0, 'click' => 0 );
			}
			$stats[ $nid ][ $row->event_type ] = (int) $row->total;
		}

		// Last 30 days daily data — keyed by notice_id.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$daily_rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT notice_id, event_type, event_date, SUM(count) AS total
				 FROM {$wpdb->prefix}gabc_analytics
				 WHERE event_date >= %s
				 GROUP BY notice_id, event_type, event_date
				 ORDER BY event_date ASC",
				gmdate( 'Y-m-d', strtotime( '-29 days' ) )
			)
		);

		// Build per-notice chart data arrays for JS: notice_id => { labels, views, clicks }.
		$chart_data = array();
		$date_cache = array();
		// Pre-build the 30-day label array once.
		for ( $i = 29; $i >= 0; $i-- ) {
			$date_cache[] = gmdate( 'Y-m-d', strtotime( "-{$i} days" ) );
		}

		foreach ( array_keys( $notice_map ) as $nid ) {
			$daily = array();
			foreach ( (array) $daily_rows as $row ) {
				if ( (int) $row->notice_id !== $nid ) {
					continue;
				}
				if ( ! isset( $daily[ $row->event_date ] ) ) {
					$daily[ $row->event_date ] = array( 'view' => 0, 'click' => 0 );
				}
				$daily[ $row->event_date ][ $row->event_type ] = (int) $row->total;
			}

			$labels      = array();
			$view_data   = array();
			$click_data  = array();
			foreach ( $date_cache as $d ) {
				$labels[]    = gmdate( 'M j', strtotime( $d ) );
				$view_data[] = isset( $daily[ $d ]['view'] )  ? $daily[ $d ]['view']  : 0;
				$click_data[] = isset( $daily[ $d ]['click'] ) ? $daily[ $d ]['click'] : 0;
			}

			$chart_data[ $nid ] = array(
				'title'  => $notice_map[ $nid ],
				'labels' => $labels,
				'views'  => $view_data,
				'clicks' => $click_data,
			);
		}

		?>
		<div class="wrap gabc-admin">
			<h1 class="mb-0"><?php esc_html_e( 'Analytics', 'gridxflex-announcement-bars' ); ?></h1>
			<p class="description pb-1"><?php esc_html_e( 'All-time view and click totals for each announcement bar. Click "View Chart" to see the 30-day trend, or use Compare mode to overlay multiple bars.', 'gridxflex-announcement-bars' ); ?></p>
			<hr class="wp-header-end">

			<div class="gabc-admin-layout">
				<div class="gabc-admin-main">
					<?php if ( empty( $notice_map ) ) : ?>
						<p><?php esc_html_e( 'No announcement bars found. Create one first.', 'gridxflex-announcement-bars' ); ?></p>
					<?php else : ?>

					<!-- Compare mode toolbar -->
					<div class="gabc-analytics-toolbar">
						<button type="button" id="gabc-compare-toggle" class="button">
							<?php esc_html_e( 'Compare Mode', 'gridxflex-announcement-bars' ); ?>
						</button>
						<button type="button" id="gabc-compare-run" class="button button-primary" style="display:none;">
							<?php esc_html_e( 'Show Comparison Chart', 'gridxflex-announcement-bars' ); ?>
						</button>
						<span id="gabc-compare-hint" class="description" style="display:none;">
							<?php esc_html_e( 'Select 2 or more bars from the table below, then click "Show Comparison Chart".', 'gridxflex-announcement-bars' ); ?>
						</span>
					</div>

					<!-- Summary Table -->
					<table class="wp-list-table widefat fixed striped gabc-notices-table" id="gabc-analytics-table">
						<thead>
							<tr>
								<th class="gabc-col-compare" style="display:none; width:36px;"></th>
								<th><?php esc_html_e( 'Announcement Bar', 'gridxflex-announcement-bars' ); ?></th>
								<th class="gabc-col-stat"><?php esc_html_e( 'Total Views', 'gridxflex-announcement-bars' ); ?></th>
								<th class="gabc-col-stat"><?php esc_html_e( 'Total Clicks', 'gridxflex-announcement-bars' ); ?></th>
								<th class="gabc-col-stat"><?php esc_html_e( 'CTR', 'gridxflex-announcement-bars' ); ?></th>
								<th class="gabc-col-stat" style="width:160px;"><?php esc_html_e( 'Actions', 'gridxflex-announcement-bars' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $notice_map as $nid => $title ) :
								$views  = isset( $stats[ $nid ]['view'] )  ? $stats[ $nid ]['view']  : 0;
								$clicks = isset( $stats[ $nid ]['click'] ) ? $stats[ $nid ]['click'] : 0;
								$ctr    = $views > 0 ? round( ( $clicks / $views ) * 100, 1 ) : 0;
							?>
							<tr data-notice-id="<?php echo esc_attr( $nid ); ?>">
								<!-- Compare checkbox column — hidden until compare mode active -->
								<td class="gabc-col-compare" style="display:none; text-align:center;">
									<input type="checkbox" class="gabc-compare-check" value="<?php echo esc_attr( $nid ); ?>" aria-label="<?php esc_attr_e( 'Select for comparison', 'gridxflex-announcement-bars' ); ?>" />
								</td>
								<td>
									<strong><?php echo esc_html( $title ); ?></strong>
									<span class="gabc-notice-id-badge">#<?php echo esc_html( $nid ); ?></span>
								</td>
								<td class="gabc-col-stat">
									<span class="gabc-stat-badge gabc-stat-views"><?php echo esc_html( number_format_i18n( $views ) ); ?></span>
								</td>
								<td class="gabc-col-stat">
									<span class="gabc-stat-badge gabc-stat-clicks"><?php echo esc_html( number_format_i18n( $clicks ) ); ?></span>
								</td>
								<td class="gabc-col-stat">
									<span class="gabc-stat-badge gabc-stat-ctr"><?php echo esc_html( $ctr ); ?>%</span>
								</td>
								<td class="gabc-col-stat">
									<button type="button"
										class="button button-small gabc-view-chart"
										data-id="<?php echo esc_attr( $nid ); ?>"
										aria-expanded="false">
										<?php esc_html_e( 'View Chart', 'gridxflex-announcement-bars' ); ?>
									</button>
									<button type="button"
										class="button button-small gabc-reset-stats"
										data-id="<?php echo esc_attr( $nid ); ?>">
										<?php esc_html_e( 'Reset', 'gridxflex-announcement-bars' ); ?>
									</button>
								</td>
							</tr>
							<?php endforeach; ?>
						</tbody>
					</table>

					<!-- On-demand chart panel (single or compare) -->
					<div id="gabc-chart-panel" style="display:none; margin-top:20px;">
						<div class="gabc-chart-wrap">
							<div class="gabc-chart-panel-header">
								<h3 id="gabc-chart-panel-title" style="margin:0;"></h3>
								<button type="button" id="gabc-chart-close" class="button button-small">
									<?php esc_html_e( '✕ Close', 'gridxflex-announcement-bars' ); ?>
								</button>
							</div>
							<div id="gabc-chart-canvas-wrap"></div>
						</div>
					</div>

					<?php endif; // notice_map ?>
				</div><!-- .gabc-admin-main -->

				<div class="gabc-admin-sidebar">
					<?php $this->render_author_box(); ?>
				</div>
			</div><!-- .gabc-admin-layout -->
		</div>

		<!-- Embed chart data as a JSON object — accessed by the inline script below. -->
		<script type="application/json" id="gabc-chart-data">
			<?php echo wp_json_encode( $chart_data ); ?>
		</script>
		<?php
		// Enqueue the chart renderer via the proper WP API — no bare <script> tags.
		$this->enqueue_chart_renderer();
	}

	/**
	 * Registers the SVG chart renderer as an inline script attached to gabc-admin.
	 * Charts render on demand (View Chart button) or in compare mode.
	 * Called from render_analytics_page() so it only runs on the analytics screen.
	 */
	private function enqueue_chart_renderer() {
		$js = <<<'JS'
(function() {
	// ── Palette for compare mode (views / clicks per notice, cycling) ────────
	var SERIES_COLORS = [
		{ views: '#4f8cff', clicks: '#f5a623' },
		{ views: '#22c55e', clicks: '#ef4444' },
		{ views: '#a855f7', clicks: '#f97316' },
		{ views: '#06b6d4', clicks: '#ec4899' },
	];

	// ── Load pre-rendered chart data from the JSON script tag ────────────────
	var chartDataEl = document.getElementById('gabc-chart-data');
	if (!chartDataEl) return;
	var allData = {};
	try { allData = JSON.parse(chartDataEl.textContent || '{}'); } catch(e) { return; }

	// ── DOM refs ──────────────────────────────────────────────────────────────
	var panel       = document.getElementById('gabc-chart-panel');
	var panelTitle  = document.getElementById('gabc-chart-panel-title');
	var canvasWrap  = document.getElementById('gabc-chart-canvas-wrap');
	var closeBtn    = document.getElementById('gabc-chart-close');
	var cmpToggle   = document.getElementById('gabc-compare-toggle');
	var cmpRun      = document.getElementById('gabc-compare-run');
	var cmpHint     = document.getElementById('gabc-compare-hint');
	if (!panel || !canvasWrap) return;

	var compareMode = false;

	// ── Core SVG chart renderer ───────────────────────────────────────────────
	// series = [ { label, views, clicks, color: {views, clicks} }, … ]
	// labels = shared 30-day x-axis labels
	function renderChart(labels, series) {
		var wrap = canvasWrap;
		var W    = wrap.offsetWidth || 700;
		var H    = 180;
		var padL = 44, padR = 16, padT = 28, padB = 36;
		var chartW = W - padL - padR;
		var chartH = H - padT - padB;

		// Max across all series
		var allVals = [];
		series.forEach(function(s) {
			allVals = allVals.concat(s.views, s.clicks);
		});
		var maxVal = Math.max(1, Math.max.apply(null, allVals));

		function xPos(i) { return padL + (i / Math.max(labels.length - 1, 1)) * chartW; }
		function yPos(v) { return padT + chartH - (v / maxVal) * chartH; }

		function polyline(data, color, dash) {
			var pts = data.map(function(v, i) { return xPos(i) + ',' + yPos(v); }).join(' ');
			var style = dash ? ' stroke-dasharray="5,3"' : '';
			return '<polyline points="' + pts + '" fill="none" stroke="' + color +
				'" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"' + style + '/>';
		}
		function dots(data, color) {
			return data.map(function(v, i) {
				return '<circle cx="' + xPos(i) + '" cy="' + yPos(v) +
					'" r="3" fill="' + color + '" stroke="#fff" stroke-width="1"/>';
			}).join('');
		}

		var xLabels = labels.map(function(lbl, i) {
			if (i % 5 !== 0 && i !== labels.length - 1) return '';
			return '<text x="' + xPos(i) + '" y="' + (H - 8) +
				'" text-anchor="middle" font-size="10" fill="#888">' + lbl + '</text>';
		}).join('');

		var yTicks = [0, 0.25, 0.5, 0.75, 1].map(function(f) {
			var y   = padT + chartH * (1 - f);
			var val = Math.round(maxVal * f);
			return '<line x1="' + padL + '" y1="' + y + '" x2="' + (W - padR) + '" y2="' + y +
				'" stroke="#eee" stroke-width="1"/>' +
				'<text x="' + (padL - 4) + '" y="' + (y + 4) +
				'" text-anchor="end" font-size="10" fill="#aaa">' + val + '</text>';
		}).join('');

		// Legend — each series gets a Views swatch and a Clicks swatch
		var legendItems = [];
		var lx = padL;
		series.forEach(function(s, si) {
			var prefix = series.length > 1 ? (s.label + ' ') : '';
			legendItems.push(
				'<rect x="' + lx + '" y="4" width="10" height="10" fill="' + s.color.views + '" rx="2"/>' +
				'<text x="' + (lx + 13) + '" y="13" font-size="10" fill="#555">' + prefix + 'Views</text>'
			);
			lx += prefix.length * 6 + 70;
			legendItems.push(
				'<rect x="' + lx + '" y="4" width="10" height="10" fill="' + s.color.clicks +
				'" rx="2" opacity="0.8"/>' +
				'<line x1="' + (lx + 1) + '" y1="9" x2="' + (lx + 9) + '" y2="9" stroke="#fff" stroke-width="1.5" stroke-dasharray="2,1"/>' +
				'<text x="' + (lx + 13) + '" y="13" font-size="10" fill="#555">' + prefix + 'Clicks</text>'
			);
			lx += prefix.length * 6 + 70;
		});

		var lines = '';
		series.forEach(function(s) {
			lines += polyline(s.views,  s.color.views,  false);
			lines += dots(s.views,  s.color.views);
			lines += polyline(s.clicks, s.color.clicks, true);
			lines += dots(s.clicks, s.color.clicks);
		});

		var svg = '<svg xmlns="http://www.w3.org/2000/svg" width="100%" height="' + H +
			'" viewBox="0 0 ' + W + ' ' + H + '">' +
			yTicks + lines + xLabels + legendItems.join('') + '</svg>';

		var div = document.createElement('div');
		div.className = 'gabc-chart-svg-wrap';
		div.innerHTML = svg;
		canvasWrap.innerHTML = '';
		canvasWrap.appendChild(div);
	}

	// ── Show single chart ─────────────────────────────────────────────────────
	function showSingleChart(nid) {
		var d = allData[nid];
		if (!d) return;

		panelTitle.textContent = d.title + ' \u2014 Last 30 Days';
		renderChart(d.labels, [{
			label:  d.title,
			views:  d.views,
			clicks: d.clicks,
			color:  SERIES_COLORS[0],
		}]);

		panel.style.display = '';
		panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });

		// Mark active row
		document.querySelectorAll('#gabc-analytics-table tr').forEach(function(tr) {
			tr.classList.toggle('gabc-row-active', tr.dataset.noticeId == nid);
		});
	}

	// ── Show compare chart ────────────────────────────────────────────────────
	function showCompareChart(nids) {
		if (nids.length < 2) {
			alert('Please select at least 2 announcement bars to compare.');
			return;
		}
		var labels = null;
		var series = nids.map(function(nid, i) {
			var d = allData[nid];
			if (!d) return null;
			if (!labels) labels = d.labels;
			return {
				label:  d.title,
				views:  d.views,
				clicks: d.clicks,
				color:  SERIES_COLORS[i % SERIES_COLORS.length],
			};
		}).filter(Boolean);

		if (!labels || !series.length) return;

		panelTitle.textContent = 'Comparison \u2014 Last 30 Days';
		renderChart(labels, series);
		panel.style.display = '';
		panel.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
	}

	// ── View Chart button ─────────────────────────────────────────────────────
	document.addEventListener('click', function(e) {
		var btn = e.target.closest('.gabc-view-chart');
		if (!btn) return;
		var nid  = btn.dataset.id;
		var isOpen = btn.getAttribute('aria-expanded') === 'true';

		// Reset all other buttons
		document.querySelectorAll('.gabc-view-chart').forEach(function(b) {
			b.setAttribute('aria-expanded', 'false');
			b.textContent = 'View Chart';
		});
		document.querySelectorAll('#gabc-analytics-table tr').forEach(function(tr) {
			tr.classList.remove('gabc-row-active');
		});

		if (isOpen) {
			panel.style.display = 'none';
		} else {
			btn.setAttribute('aria-expanded', 'true');
			btn.textContent = 'Hide Chart';
			showSingleChart(nid);
		}
	});

	// ── Close panel button ────────────────────────────────────────────────────
	if (closeBtn) {
		closeBtn.addEventListener('click', function() {
			panel.style.display = 'none';
			document.querySelectorAll('.gabc-view-chart').forEach(function(b) {
				b.setAttribute('aria-expanded', 'false');
				b.textContent = 'View Chart';
			});
			document.querySelectorAll('#gabc-analytics-table tr').forEach(function(tr) {
				tr.classList.remove('gabc-row-active');
			});
		});
	}

	// ── Compare mode toggle ───────────────────────────────────────────────────
	if (cmpToggle) {
		cmpToggle.addEventListener('click', function() {
			compareMode = !compareMode;
			cmpToggle.textContent = compareMode ? 'Exit Compare Mode' : 'Compare Mode';
			cmpToggle.classList.toggle('button-primary', compareMode);
			cmpToggle.classList.toggle('button', !compareMode);

			// Show / hide compare column and controls
			document.querySelectorAll('.gabc-col-compare').forEach(function(el) {
				el.style.display = compareMode ? '' : 'none';
			});
			cmpRun.style.display  = compareMode ? '' : 'none';
			cmpHint.style.display = compareMode ? '' : 'none';

			// Hide View Chart buttons in compare mode to avoid confusion
			document.querySelectorAll('.gabc-view-chart').forEach(function(b) {
				b.style.display = compareMode ? 'none' : '';
			});

			if (!compareMode) {
				// Uncheck all checkboxes
				document.querySelectorAll('.gabc-compare-check').forEach(function(cb) {
					cb.checked = false;
				});
				panel.style.display = 'none';
			}
		});
	}

	// ── Compare run button ────────────────────────────────────────────────────
	if (cmpRun) {
		cmpRun.addEventListener('click', function() {
			var checked = document.querySelectorAll('.gabc-compare-check:checked');
			var nids = Array.prototype.map.call(checked, function(cb) { return cb.value; });
			showCompareChart(nids);
		});
	}
})();
JS;
		wp_add_inline_script( 'gabc-admin', $js );
	}
}