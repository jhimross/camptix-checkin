<?php
/**
 * Standalone Badge Endpoint
 *
 * Registers a rewrite rule so the badge page is served at:
 *   /print?name=…&company=…&social=…&website=…&meal_preference=…&ticket_type=…
 *
 * The badge data travels as query parameters (the same fields the printer
 * URL uses), so no attendee lookup is needed. This completely bypasses
 * wp-admin — no sidebar, no admin bar, no WordPress chrome. The page outputs
 * a self-contained HTML document suitable for window.print().
 *
 * Access is protected: must be logged in with edit_posts capability.
 */
defined( 'ABSPATH' ) || exit;

class CTCI_Badge_Endpoint {

	const QUERY_VAR = 'ctci_print';

	public function __construct() {
		add_action( 'init',              [ $this, 'add_rewrite_rule' ] );
		add_filter( 'query_vars',        [ $this, 'add_query_var'   ] );
		add_action( 'template_redirect', [ $this, 'maybe_render'    ] );
		// Also keep the admin-page route for backwards compat (won't show admin chrome if we exit early).
		add_action( 'admin_init',        [ $this, 'maybe_render_admin' ] );
	}

	public function add_rewrite_rule(): void {
		add_rewrite_rule(
			'^print/?$',
			'index.php?' . self::QUERY_VAR . '=1',
			'top'
		);
	}

	public function add_query_var( array $vars ): array {
		$vars[] = self::QUERY_VAR;
		return $vars;
	}

	/** Frontend route: /print?name=…&company=…&… */
	public function maybe_render(): void {
		if ( ! get_query_var( self::QUERY_VAR ) ) {
			return;
		}

		$data = [
			'name'              => sanitize_text_field( $_GET['name']              ?? '' ),
			'company'           => sanitize_text_field( $_GET['company']           ?? '' ),
			'wordpress_username'=> sanitize_text_field( $_GET['wordpress_username'] ?? '' ),
			'social'            => sanitize_text_field( $_GET['social']            ?? '' ),
			'website'           => sanitize_text_field( $_GET['website']           ?? '' ),
			'meal_preference'   => sanitize_text_field( $_GET['meal_preference']   ?? '' ),
			'ticket_type'       => sanitize_text_field( $_GET['ticket_type']       ?? '' ),
		];

		if ( ! is_user_logged_in() ) {
			auth_redirect();
			exit;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to view badges.', 'camptix-checkin' ), 403 );
		}

		$this->render_badge_page( $data );
		exit;
	}

	/**
	 * Admin route: wp-admin/admin.php?page=camptix-checkin-badge&attendee_id=N
	 * Intercept early, before WordPress renders the admin shell.
	 */
	public function maybe_render_admin(): void {
		$page        = sanitize_text_field( $_GET['page']        ?? '' );
		$attendee_id = absint( $_GET['attendee_id'] ?? 0 );

		if ( $page !== 'camptix-checkin-badge' || ! $attendee_id ) {
			return;
		}

		if ( ! current_user_can( 'edit_posts' ) ) {
			wp_die( esc_html__( 'You do not have permission to view badges.', 'camptix-checkin' ) );
		}

		$this->render_badge_page( CTCI_Badge_Print::get_badge_data( $attendee_id ) );
		exit; // Prevent WordPress from rendering anything else.
	}

	/** Render the fully standalone badge HTML and exit. */
	private function render_badge_page( array $data ): void {
		// Flush any output buffers and send headers first, so nothing that
		// happens while computing the badge data can corrupt the response.
		while ( ob_get_level() ) {
			ob_end_clean();
		}

		header( 'Content-Type: text/html; charset=UTF-8' );
		header( 'X-Robots-Tag: noindex' );

		$badge_name  = trim( $data['badge_name'] ?? ( $data['name'] ?? '' ) );
		$company     = trim( $data['company'] ?? '' );
		$ticket_type = trim( $data['ticket']  ?? ( $data['ticket_type'] ?? '' ) );
		$meal_pref   = trim( $data['meal_preference'] ?? '' );
		$blog_name   = get_bloginfo( 'name' );

		// "Regular" ticket displays as the generic "Attendee" label on the badge.
		$ticket_label = ( $ticket_type && strtolower( $ticket_type ) === 'regular' )
			? __( 'Attendee', 'camptix-checkin' )
			: $ticket_type;

		// Social/web fields for the badge.
		$wp_username    = trim( $data['wordpress_username'] ?? '' );
		$twitter        = CTCI_Attendee_CPT::clean_social( trim( $data['social'] ?? '' ) );
		$website_raw    = trim( $data['website'] ?? '' );
		// Strip protocol for display.
		$website_display = $website_raw
			? preg_replace( '#^https?://(www\.)?#i', '', rtrim( $website_raw, '/' ) )
			: '';

		// Printer URL configured in Settings, and whether this visit should
		// auto-send to the printer (falling back to the print dialog).
		$printer_url = trim( (string) get_option( 'ctci_printer_url', '' ) );
		$autoprint   = ! empty( $_GET['autoprint'] );

		// Output the standalone page (no QR — QR is for email check-in only).
		include CTCI_PLUGIN_DIR . 'templates/badge.php';
	}
}

new CTCI_Badge_Endpoint();
