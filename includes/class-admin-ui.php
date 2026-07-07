<?php
/**
 * Admin UI — Scanner dashboard & Attendee list
 */
defined( 'ABSPATH' ) || exit;

class CTCI_Admin_UI {

	public function __construct() {
		add_action( 'admin_menu',            [ $this, 'register_menus' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_assets' ] );
				add_action( 'admin_post_ctci_save_sync_settings', [ $this, 'handle_save_sync_settings' ] );
		add_action( 'admin_post_ctci_save_attendee',      [ $this, 'handle_save_attendee'      ] );
		add_action( 'admin_post_ctci_add_attendee',       [ $this, 'handle_add_attendee'       ] );
		add_action( 'admin_post_ctci_delete_attendee',    [ $this, 'handle_delete_attendee'    ] );
		add_action( 'admin_post_ctci_reset_all',          [ $this, 'handle_reset_all'          ] );
	}

	public function register_menus(): void {
		add_menu_page(
			__( 'CampTix Check-In', 'camptix-checkin' ),
			__( 'Check-In', 'camptix-checkin' ),
			'edit_posts',
			'camptix-checkin',
			[ $this, 'render_dashboard_page' ],
			'dashicons-camera',
			56
		);

		add_submenu_page(
			'camptix-checkin',
			__( 'Dashboard', 'camptix-checkin' ),
			__( 'Dashboard', 'camptix-checkin' ),
			'edit_posts',
			'camptix-checkin',
			[ $this, 'render_dashboard_page' ]
		);

		add_submenu_page(
			'camptix-checkin',
			__( 'QR Scanner', 'camptix-checkin' ),
			__( 'QR Scanner', 'camptix-checkin' ),
			'edit_posts',
			'camptix-checkin-scanner',
			[ $this, 'render_scanner_page' ]
		);

		add_submenu_page(
			'camptix-checkin',
			__( 'Attendees', 'camptix-checkin' ),
			__( 'Attendees', 'camptix-checkin' ),
			'edit_posts',
			'camptix-checkin-attendees',
			[ $this, 'render_attendees_page' ]
		);

		add_submenu_page(
			'camptix-checkin',
			__( 'Send QR Codes', 'camptix-checkin' ),
			__( 'Send QR Codes', 'camptix-checkin' ),
			'manage_options',
			'camptix-checkin-send',
			[ $this, 'render_send_page' ]
		);

		// Hidden pages — registered so admin.php?page= works, removed from visible menu on a later hook.
		add_submenu_page( 'camptix-checkin', __( 'Print Badge', 'camptix-checkin' ),   __( 'Print Badge', 'camptix-checkin' ),   'edit_posts', 'camptix-checkin-badge', [ $this, 'render_badge_page' ] );
		add_submenu_page( 'camptix-checkin', __( 'Edit Attendee', 'camptix-checkin' ), __( 'Edit Attendee', 'camptix-checkin' ), 'edit_posts', 'camptix-checkin-edit',  [ $this, 'render_edit_page'  ] );
		add_submenu_page( 'camptix-checkin', __( 'Add Attendee', 'camptix-checkin' ),  __( 'Add Attendee', 'camptix-checkin' ),  'edit_posts', 'camptix-checkin-add',   [ $this, 'render_add_page'   ] );

		add_action( 'admin_head', [ $this, 'hide_submenu_pages' ] );

		add_submenu_page(
			'camptix-checkin',
			__( 'Sync from WordCamp', 'camptix-checkin' ),
			__( '↓ Sync WordCamp', 'camptix-checkin' ),
			'manage_options',
			'camptix-checkin-sync',
			[ $this, 'render_sync_page' ]
		);

		add_submenu_page(
			'camptix-checkin',
			__( 'Settings', 'camptix-checkin' ),
			__( 'Settings', 'camptix-checkin' ),
			'manage_options',
			'camptix-checkin-settings',
			[ $this, 'render_settings_page' ]
		);
	}

	/** Remove hidden utility pages from the visible sidebar menu. */
	public function hide_submenu_pages(): void {
		remove_submenu_page( 'camptix-checkin', 'camptix-checkin-badge' );
		remove_submenu_page( 'camptix-checkin', 'camptix-checkin-edit' );
		remove_submenu_page( 'camptix-checkin', 'camptix-checkin-add' );
	}

	public function enqueue_assets( string $hook ): void {
		$pages = [
			'toplevel_page_camptix-checkin',
			'check-in_page_camptix-checkin-scanner',
			'check-in_page_camptix-checkin-attendees',
			'check-in_page_camptix-checkin-send',
			'check-in_page_camptix-checkin-badge',
			'check-in_page_camptix-checkin-edit',
			'check-in_page_camptix-checkin-add',
			'check-in_page_camptix-checkin-sync',
			'check-in_page_camptix-checkin-settings',
		];

		if ( ! in_array( $hook, $pages, true ) ) {
			return;
		}

		// jsQR for QR decoding from camera feed.
		wp_enqueue_script(
			'jsqr',
			CTCI_PLUGIN_URL . 'assets/js/jsqr.min.js',
			[],
			'1.4.0',
			true
		);

		wp_enqueue_style(
			'ctci-admin',
			CTCI_PLUGIN_URL . 'assets/css/admin.css',
			[],
			CTCI_VERSION
		);

		wp_enqueue_script(
			'ctci-scanner',
			CTCI_PLUGIN_URL . 'assets/js/scanner.js',
			[ 'jsqr', 'wp-api' ],
			CTCI_VERSION,
			true
		);

		wp_localize_script( 'ctci-scanner', 'ctciConfig', [
			'apiBase'      => rest_url( 'camptix-checkin/v1' ),
			'nonce'        => wp_create_nonce( 'wp_rest' ),
			'adminUrl'     => admin_url( 'admin.php' ),
			'adminPostUrl' => admin_url( 'admin-post.php' ),
			'strings'   => [
				'scanning'          => __( 'Scanning…', 'camptix-checkin' ),
				'scan_success'      => __( 'Checked in!', 'camptix-checkin' ),
				'already_checked'   => __( 'Already checked in', 'camptix-checkin' ),
				'invalid_qr'        => __( 'Invalid QR code', 'camptix-checkin' ),
				'error'             => __( 'Error', 'camptix-checkin' ),
				'camera_error'      => __( 'Could not access camera. Please allow camera access.', 'camptix-checkin' ),
			],
		] );
	}

	/* ----------------------------------------------------------
	 * Page renderers
	 * -------------------------------------------------------- */

	public function render_dashboard_page(): void {
		include CTCI_PLUGIN_DIR . 'templates/dashboard.php';
	}

	public function render_scanner_page(): void {
		include CTCI_PLUGIN_DIR . 'templates/scanner.php';
	}

	public function render_add_page(): void {
		include CTCI_PLUGIN_DIR . 'templates/add-attendee.php';
	}

	public function render_attendees_page(): void {
		include CTCI_PLUGIN_DIR . 'templates/attendees.php';
	}

	public function render_send_page(): void {
		// Handle bulk-send action.
		if ( isset( $_POST['ctci_send_qr'] ) && check_admin_referer( 'ctci_send_qr_nonce' ) ) {
			$sent  = CTCI_Email::send_all_qr_codes();
			$class = $sent > 0 ? 'notice-success' : 'notice-warning';
			echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>';
			/* translators: %d = number of emails sent */
			printf( esc_html__( 'QR codes sent to %d attendees.', 'camptix-checkin' ), $sent );
			echo '</p></div>';
		}

		include CTCI_PLUGIN_DIR . 'templates/send-qr.php';
	}

	public function render_badge_page(): void {
		// Badge is rendered standalone by CTCI_Badge_Endpoint on admin_init.
		// If we reach here without an attendee_id, redirect gracefully.
		if ( empty( $_GET['attendee_id'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=camptix-checkin-attendees' ) );
			exit;
		}
		// With an attendee_id the endpoint already exited; this is a fallback only.
		include CTCI_PLUGIN_DIR . 'templates/badge.php';
	}

	public function render_edit_page(): void {
		include CTCI_PLUGIN_DIR . 'templates/edit-attendee.php';
	}

	public function handle_add_attendee(): void {
		check_admin_referer( 'ctci_add_attendee' );
		if ( ! current_user_can( 'edit_posts' ) ) wp_die( 'Permission denied.' );

		$first = sanitize_text_field( $_POST['ctci_first_name'] ?? '' );
		$email = sanitize_email( $_POST['ctci_email'] ?? '' );

		if ( ! $first || ! is_email( $email ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=camptix-checkin-add&error=' . urlencode( 'First name and a valid email are required.' ) ) );
			exit;
		}

		$last  = sanitize_text_field( $_POST['ctci_last_name']  ?? '' );
		$badge = sanitize_text_field( $_POST['ctci_badge_name'] ?? '' );
		$title = $badge ?: trim( "$first $last" );

		$post_id = wp_insert_post( [
			'post_type'   => 'ctci_attendee',
			'post_status' => 'publish',
			'post_title'  => $title,
		] );

		if ( is_wp_error( $post_id ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=camptix-checkin-add&error=' . urlencode( $post_id->get_error_message() ) ) );
			exit;
		}

		$meta = [
			'ctci_first_name'         => $first,
			'ctci_last_name'          => $last,
			'ctci_badge_name'         => $badge,
			'ctci_email'              => $email,
			'ctci_ticket_type'        => sanitize_text_field( $_POST['ctci_ticket_type']        ?? 'Regular' ),
			'ctci_company'            => sanitize_text_field( $_POST['ctci_company']            ?? '' ),
			'ctci_wordpress_username' => sanitize_text_field( $_POST['ctci_wordpress_username'] ?? '' ),
			'ctci_social'             => sanitize_text_field( $_POST['ctci_social']             ?? '' ),
			'ctci_website'            => esc_url_raw( $_POST['ctci_website']                    ?? '' ),
			'ctci_contributor_day'    => sanitize_text_field( $_POST['ctci_contributor_day']    ?? 'No' ),
			'ctci_order_status'       => 'publish',
		];
		foreach ( $meta as $k => $v ) update_post_meta( $post_id, $k, $v );

		wp_safe_redirect( admin_url( 'admin.php?page=camptix-checkin-edit&attendee_id=' . $post_id . '&saved=1' ) );
		exit;
	}

	public function handle_delete_attendee(): void {
		$post_id = absint( $_GET['attendee_id'] ?? 0 );
		check_admin_referer( 'ctci_delete_attendee_' . $post_id );
		if ( ! current_user_can( 'delete_posts' ) ) wp_die( 'Permission denied.' );

		$post = get_post( $post_id );
		if ( $post && $post->post_type === 'ctci_attendee' ) {
			wp_delete_post( $post_id, true ); // force-delete, skip trash
		}
		wp_safe_redirect( admin_url( 'admin.php?page=camptix-checkin-attendees&deleted=1' ) );
		exit;
	}

	public function handle_reset_all(): void {
		check_admin_referer( 'ctci_reset_all' );
		if ( ! current_user_can( 'manage_options' ) ) wp_die( 'Permission denied.' );

		$ids = get_posts( [
			'post_type'      => 'ctci_attendee',
			'posts_per_page' => -1,
			'post_status'    => 'any',
			'fields'         => 'ids',
		] );
		foreach ( $ids as $id ) wp_delete_post( $id, true );

		// Clear transient QR cache.
		global $wpdb;
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ctci_qr_%' OR option_name LIKE '_transient_timeout_ctci_qr_%'" );

		wp_safe_redirect( admin_url( 'admin.php?page=camptix-checkin-settings&reset=1' ) );
		exit;
	}

	public function handle_save_attendee(): void {
		$post_id = absint( $_POST['ctci_attendee_id'] ?? 0 );
		check_admin_referer( 'ctci_save_attendee_' . $post_id );

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'camptix-checkin' ) );
		}

		$post = get_post( $post_id );
		if ( ! $post || $post->post_type !== 'ctci_attendee' ) {
			wp_die( esc_html__( 'Attendee not found.', 'camptix-checkin' ) );
		}

		// Update meta fields.
		$fields = [
			'ctci_first_name'         => 'sanitize_text_field',
			'ctci_last_name'          => 'sanitize_text_field',
			'ctci_badge_name'         => 'sanitize_text_field',
			'ctci_email'              => 'sanitize_email',
			'ctci_ticket_type'        => 'sanitize_text_field',
			'ctci_company'            => 'sanitize_text_field',
			'ctci_wordpress_username' => 'sanitize_text_field',
			'ctci_social'             => 'sanitize_text_field',
			'ctci_website'            => 'esc_url_raw',
			'ctci_contributor_day'    => 'sanitize_text_field',
		];

		foreach ( $fields as $key => $sanitizer ) {
			if ( isset( $_POST[ $key ] ) ) {
				update_post_meta( $post_id, $key, $sanitizer( $_POST[ $key ] ) );
			}
		}

		// Update post title to reflect new badge/full name.
		$first = sanitize_text_field( $_POST['ctci_first_name'] ?? '' );
		$last  = sanitize_text_field( $_POST['ctci_last_name']  ?? '' );
		$badge = sanitize_text_field( $_POST['ctci_badge_name'] ?? '' );
		$title = $badge ?: trim( "$first $last" );
		if ( $title ) {
			wp_update_post( [ 'ID' => $post_id, 'post_title' => $title ] );
		}

		// Handle check-in toggle.
		$meta_key    = get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );
		$checked_in  = ! empty( $_POST['ctci_checked_in'] );
		$custom_time = sanitize_text_field( $_POST['ctci_checkin_time'] ?? '' );

		if ( $checked_in ) {
			// Use provided time or keep existing; set to now if blank.
			$existing = get_post_meta( $post_id, $meta_key, true );
			$time     = $custom_time ?: ( $existing ?: current_time( 'mysql' ) );
			update_post_meta( $post_id, $meta_key, $time );
		} else {
			// Un-check: remove the meta entirely.
			delete_post_meta( $post_id, $meta_key );
		}

		wp_safe_redirect( admin_url(
			'admin.php?page=camptix-checkin-edit&attendee_id=' . $post_id . '&saved=1'
		) );
		exit;
	}

	public function render_sync_page(): void {
		include CTCI_PLUGIN_DIR . 'templates/sync.php';
	}

	public function handle_save_sync_settings(): void {
		check_admin_referer( 'ctci_save_sync_settings_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'camptix-checkin' ) );
		}
		update_option( CTCI_WordCamp_Sync::OPT_URL,      esc_url_raw( $_POST['ctci_wc_url']      ?? '' ) );
		update_option( CTCI_WordCamp_Sync::OPT_USER,     sanitize_text_field( $_POST['ctci_wc_user']     ?? '' ) );
		// Only update password if a new value was submitted.
		if ( ! empty( $_POST['ctci_wc_app_pass'] ) ) {
			update_option( CTCI_WordCamp_Sync::OPT_PASS, sanitize_text_field( $_POST['ctci_wc_app_pass'] ) );
		}
		$old_schedule = get_option( CTCI_WordCamp_Sync::OPT_SCHEDULE, 'off' );
		$new_schedule = sanitize_text_field( $_POST['ctci_wc_schedule'] ?? 'off' );
		update_option( CTCI_WordCamp_Sync::OPT_SCHEDULE, $new_schedule );
		update_option( CTCI_WordCamp_Sync::OPT_Q_COMPANY, sanitize_text_field( $_POST['ctci_q_company'] ?? 'company' ) );
		update_option( CTCI_WordCamp_Sync::OPT_Q_SOCIAL,  sanitize_text_field( $_POST['ctci_q_social']  ?? 'social'  ) );
		update_option( CTCI_WordCamp_Sync::OPT_Q_WEBSITE, sanitize_text_field( $_POST['ctci_q_website'] ?? 'website' ) );
		if ( $old_schedule !== $new_schedule ) {
			do_action( 'ctci_schedule_changed' );
		}
		wp_safe_redirect( admin_url( 'admin.php?page=camptix-checkin-sync&saved=1' ) );
		exit;
	}

	public function render_settings_page(): void {
		if ( isset( $_POST['ctci_save_settings'] ) && check_admin_referer( 'ctci_settings_nonce' ) ) {
			update_option( 'ctci_checkin_meta_key',       sanitize_text_field( $_POST['ctci_meta_key'] ?? '' ) );
			update_option( 'ctci_email_subject',          sanitize_text_field( $_POST['ctci_email_subject'] ?? '' ) );
			update_option( 'ctci_badge_show_website',     (int) ! empty( $_POST['ctci_badge_website'] ) );
			update_option( 'ctci_badge_show_social',      (int) ! empty( $_POST['ctci_badge_social'] ) );
			update_option( 'ctci_badge_show_company',     (int) ! empty( $_POST['ctci_badge_company'] ) );
			update_option( 'ctci_social_meta_field',      sanitize_text_field( $_POST['ctci_social_meta_field'] ?? 'ctci_social' ) );
			update_option( 'ctci_website_meta_field',     sanitize_text_field( $_POST['ctci_website_meta_field'] ?? 'ctci_website' ) );
			update_option( 'ctci_company_meta_field',     sanitize_text_field( $_POST['ctci_company_meta_field'] ?? 'ctci_company' ) );
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Settings saved.', 'camptix-checkin' ) . '</p></div>';
		}

		include CTCI_PLUGIN_DIR . 'templates/settings.php';
	}
}

new CTCI_Admin_UI();
