<?php
/**
 * GABC Core Class v1.0.0
 *
 * @package GridxflexAnnouncementBarswithCTA
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class GABC_Core {

	private $loader;

	/**
	 * Cache group for all GABC queries.
	 */
	const CACHE_GROUP = 'gabc';

	public function __construct() {
		$this->loader = new GABC_Loader();
		$this->define_hooks();
	}

	private function define_hooks() {
		$this->loader->add_action( 'wp_enqueue_scripts', $this, 'enqueue_public_assets' );
		$this->loader->add_action( 'wp_body_open', $this, 'render_nonsticky_top_bars' );
		$this->loader->add_action( 'wp_footer', $this, 'render_sticky_and_bottom_bars' );
		$this->loader->add_action( 'admin_menu', $this, 'register_admin_page' );

		// Analytics: click tracking (public AJAX — logged-in and non-logged-in users).
		$this->loader->add_action( 'wp_ajax_gabc_track_click',        $this, 'track_click_ajax' );
		$this->loader->add_action( 'wp_ajax_nopriv_gabc_track_click', $this, 'track_click_ajax' );

		$admin = new GABC_Admin();
		$this->loader->add_action( 'init', $admin, 'register_ajax_handlers' );
		$this->loader->add_action( 'admin_enqueue_scripts', $admin, 'enqueue_admin_assets' );
	}

	public function run() {
		$this->loader->run();
	}

	public function enqueue_public_assets() {
		wp_enqueue_style(
			'gabc-public',
			GABC_PLUGIN_URL . 'assets/css/public.css',
			array(),
			GABC_VERSION
		);

		wp_enqueue_script(
			'gabc-public',
			GABC_PLUGIN_URL . 'assets/js/public.js',
			array(),
			GABC_VERSION,
			true
		);

		wp_localize_script(
			'gabc-public',
			'gabcData',
			array(
				'nonce'   => wp_create_nonce( 'gabc_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			)
		);
	}

	public function render_nonsticky_top_bars() {
		$notices = $this->get_active_notices();
		if ( empty( $notices ) ) {
			return;
		}
		foreach ( $notices as $notice ) {
			$sticky   = isset( $notice->sticky ) ? (bool) $notice->sticky : true;
			$position = sanitize_text_field( $notice->position );
			if ( 'top' !== $position || $sticky ) {
				continue;
			}
			if ( ! $this->should_display( $notice ) ) {
				continue;
			}
			$this->render_notice_bar( $notice );
		}
	}

	public function render_sticky_and_bottom_bars() {
		$notices = $this->get_active_notices();
		if ( empty( $notices ) ) {
			return;
		}
		foreach ( $notices as $notice ) {
			$sticky   = isset( $notice->sticky ) ? (bool) $notice->sticky : true;
			$position = sanitize_text_field( $notice->position );

			if ( 'top' === $position && ! $sticky ) {
				continue;
			}
			if ( ! $this->should_display( $notice ) ) {
				continue;
			}
			$this->render_notice_bar( $notice );
		}
	}

	private function get_active_notices() {
		global $wpdb;

		$current_time = current_time( 'mysql' );
		$cache_key    = 'gabc_active_notices';
		$notices      = wp_cache_get( $cache_key, self::CACHE_GROUP );

		if ( false === $notices ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$notices = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT * FROM {$wpdb->prefix}gabc_notices
					WHERE enabled = 1
					AND (start_date IS NULL OR start_date <= %s)
					AND (end_date IS NULL OR end_date >= %s)
					ORDER BY priority DESC, id ASC",
					$current_time,
					$current_time
				)
			);
			wp_cache_set( $cache_key, $notices, self::CACHE_GROUP, 300 );
		}

		return $notices ? $notices : array();
	}

	private function render_notice_bar( $notice ) {
		$bg_color     = gabc_sanitize_color( $notice->bg_color );
		$text_color   = sanitize_hex_color( $notice->text_color );
		$button_color = gabc_sanitize_color( $notice->button_color );
		$padding      = absint( $notice->padding );
		$font_size    = absint( $notice->font_size );
		$position     = sanitize_text_field( $notice->position );
		$sticky       = isset( $notice->sticky ) ? (bool) $notice->sticky : true;

		// Trigger settings — default to "show immediately" if columns don't exist yet.
		$trigger_delay       = isset( $notice->trigger_delay )       ? absint( $notice->trigger_delay )       : 0;
		$trigger_scroll      = isset( $notice->trigger_scroll )      ? absint( $notice->trigger_scroll )      : 0;
		$trigger_exit_intent = isset( $notice->trigger_exit_intent ) ? (int) $notice->trigger_exit_intent     : 0;

		// A bar is "triggered" if any non-default trigger is set.
		$has_trigger = $trigger_delay > 0 || $trigger_scroll > 0 || $trigger_exit_intent;
		$animation     = isset( $notice->animation ) ? sanitize_text_field( $notice->animation ) : 'none';
		$animation_duration  = isset( $notice->animation_duration ) ? absint( $notice->animation_duration ) : 400;
		$has_animation = 'none' !== $animation;

		$styles = "
		--gabc-bg-color: {$bg_color};
		--gabc-text-color: {$text_color};
		--gabc-button-color: {$button_color};
		--gabc-padding: {$padding}px;
		--gabc-font-size: {$font_size}px;
		--gabc-anim-duration: {$animation_duration}ms;
		";

		if ( strpos( $bg_color, 'linear-gradient' ) !== false ) {
			$styles .= "background: {$bg_color};";
		}

		// Record a view for this notice (server-side, once per render).
		$this->record_analytics_event( absint( $notice->id ), 'view' );

			$extra_class = '';
		if ( $has_trigger ) {
			$extra_class = ' gabc-trigger-hidden';
		} elseif ( $has_animation ) {
			$extra_class = ' gabc-anim-ready';
		}
		?>
		<div class="gabc-notice-bar<?php echo esc_attr( $extra_class ); ?>"
			data-sticky="<?php echo $sticky ? 'true' : 'false'; ?>"
			data-position="<?php echo esc_attr( $position ); ?>"
			data-notice-id="<?php echo esc_attr( $notice->id ); ?>"
			data-dismissible="<?php echo $notice->dismissible ? 'true' : 'false'; ?>"
			data-trigger-delay="<?php echo esc_attr( $trigger_delay ); ?>"
			data-trigger-scroll="<?php echo esc_attr( $trigger_scroll ); ?>"
			data-trigger-exit-intent="<?php echo $trigger_exit_intent ? 'true' : 'false'; ?>"
			data-animation="<?php echo esc_attr( $animation ); ?>"
			data-animation-duration="<?php echo esc_attr( $animation_duration ); ?>"
			style="<?php echo esc_attr( $styles ); ?>">
			<div class="gabc-notice-bar__inner">
				<div class="gabc-notice-bar__content">
					<p class="gabc-notice-bar__text">
						<?php echo wp_kses_post( $notice->text ); ?>
					</p>
				</div>
				<?php $this->render_button( $notice ); ?>
				<?php $this->render_close_button( $notice ); ?>
			</div>
		</div>
		<?php
	}

	private function render_button( $notice ) {
		$button_text = sanitize_text_field( $notice->button_text );
		$button_url  = esc_url( $notice->button_url );
		$new_tab     = (bool) $notice->button_new_tab;

		if ( empty( $button_text ) || empty( $button_url ) ) {
			return;
		}

		$target = $new_tab ? 'target="_blank" rel="noopener noreferrer"' : '';

		?>
		<a href="<?php echo esc_url( $button_url ); ?>" class="gabc-notice-bar__button" <?php echo wp_kses_post( $target ); ?>>
			<?php echo esc_html( $button_text ); ?>
		</a>
		<?php
	}

	private function render_close_button( $notice ) {
		if ( ! $notice->dismissible ) {
			return;
		}
		?>
		<button class="gabc-notice-bar__close" aria-label="<?php esc_attr_e( 'Close notice', 'gridxflex-announcement-bars' ); ?>">
			<svg class="gabc-notice-bar__close-icon" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
				<path d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12 19 6.41z"/>
			</svg>
		</button>
		<?php
	}

	private function should_display( $notice ) {
		if ( ! empty( $notice->user_roles ) ) {
			$allowed_roles = maybe_unserialize( $notice->user_roles );
			if ( is_array( $allowed_roles ) && ! empty( $allowed_roles ) ) {
				$current_user = wp_get_current_user();
				if ( empty( array_intersect( $allowed_roles, (array) $current_user->roles ) ) ) {
					return false;
				}
			}
		}

		if ( $notice->hide_logged_in && is_user_logged_in() ) {
			return false;
		}

		$location = $notice->show_location;

		if ( 'homepage' === $location && ! is_front_page() ) {
			return false;
		}

		if ( 'specific_pages' === $location ) {
			$pages = maybe_unserialize( $notice->selected_pages );
			if ( is_array( $pages ) && ! empty( $pages ) && ! is_page( $pages ) ) {
				return false;
			}
		}

		if ( 'categories' === $location ) {
			$categories = maybe_unserialize( $notice->selected_categories );
			if ( is_array( $categories ) && ! empty( $categories ) && ! has_category( $categories ) ) {
				return false;
			}
		}

		if ( 'tags' === $location ) {
			$tags = maybe_unserialize( $notice->selected_tags );
			if ( is_array( $tags ) && ! empty( $tags ) && ! has_tag( $tags ) ) {
				return false;
			}
		}

		if ( 'post_types' === $location ) {
			$post_types = maybe_unserialize( $notice->selected_post_types );
			if ( is_array( $post_types ) && ! empty( $post_types ) && ! is_singular( $post_types ) ) {
				return false;
			}
		}

		return true;
	}

	// ─── Analytics ──────────────────────────────────────────────────────────

	/**
	 * Upsert a daily analytics event count.
	 * Uses INSERT ... ON DUPLICATE KEY UPDATE for atomic, race-condition-safe increments.
	 *
	 * @param int    $notice_id Notice ID.
	 * @param string $event_type 'view' or 'click'.
	 */
	private function record_analytics_event( $notice_id, $event_type ) {
		global $wpdb;

		$table = $wpdb->prefix . 'gabc_analytics';
		$today = current_time( 'Y-m-d' );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO `{$wpdb->prefix}gabc_analytics` (notice_id, event_type, event_date, count)
				 VALUES (%d, %s, %s, 1)
				 ON DUPLICATE KEY UPDATE count = count + 1",
				$notice_id,
				$event_type,
				$today
			)
		);
	}

	/**
	 * AJAX handler for client-side CTA click tracking.
	 * Accessible to both logged-in and non-logged-in users.
	 */
	public function track_click_ajax() {
		check_ajax_referer( 'gabc_nonce', 'nonce' );

		$notice_id = isset( $_POST['notice_id'] ) ? absint( $_POST['notice_id'] ) : 0;

		if ( ! $notice_id ) {
			wp_send_json_error( array( 'message' => 'Invalid notice ID' ) );
		}

		$this->record_analytics_event( $notice_id, 'click' );
		wp_send_json_success();
	}

	/**
	 * Ensure the analytics table exists (called defensively on admin pages).
	 */
	public static function maybe_create_analytics_table() {
		global $wpdb;

		$table = $wpdb->prefix . 'gabc_analytics';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		if ( $exists ) {
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE {$table} (
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

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}

	public function register_admin_page() {
		$admin = new GABC_Admin();
		$admin->register_admin_menu();
	}
}