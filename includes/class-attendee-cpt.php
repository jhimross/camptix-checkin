<?php
/**
 * Local Attendee CPT — meta keys match the real CampTix CSV export columns
 * from WordCamp Philippines 2026.
 *
 * Stored meta keys:
 *   ctci_remote_id          – Attendee ID from CampTix
 *   ctci_first_name         – First Name
 *   ctci_last_name          – Last Name
 *   ctci_email              – E-mail Address
 *   ctci_ticket_type        – Ticket Type (Regular, Student, Professional, etc.)
 *   ctci_order_status       – Status (Publish, etc.)
 *   ctci_badge_name         – Name on Badge
 *   ctci_company            – Company Name
 *   ctci_wordpress_username – WordPress.org Username
 *   ctci_social             – Twitter/X handle
 *   ctci_website            – Website URL
 *   ctci_contributor_day    – Will join Contributor Day? (Yes/No)
 *   ctci_qr_sent            – datetime QR email was sent
 *   camptix_checkin_time    – datetime attendee was checked in
 */
defined( 'ABSPATH' ) || exit;

class CTCI_Attendee_CPT {

	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
	}

	public function register_post_type(): void {
		register_post_type( 'ctci_attendee', [
			'label'           => __( 'Attendees', 'camptix-checkin' ),
			'labels'          => [
				'name'          => __( 'Attendees', 'camptix-checkin' ),
				'singular_name' => __( 'Attendee', 'camptix-checkin' ),
			],
			'public'          => false,
			'show_ui'         => false,
			'show_in_rest'    => false,
			'supports'        => [ 'title' ],
			'capability_type' => 'post',
			'map_meta_cap'    => true,
		] );
	}

	/**
	 * Upsert a single attendee. Matches by ctci_remote_id (Attendee ID).
	 * Falls back to email match if remote_id is absent (CSV import without IDs).
	 *
	 * @param array $data  Normalised attendee data array.
	 * @return int  Local post ID.
	 */
	public static function upsert( array $data ): int {
		$remote_id = (int) ( $data['remote_id'] ?? 0 );
		$email     = sanitize_email( $data['email'] ?? '' );

		// Find existing by remote_id first, then email.
		$existing = [];
		if ( $remote_id ) {
			$existing = get_posts( [
				'post_type'      => 'ctci_attendee',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'meta_key'       => 'ctci_remote_id',
				'meta_value'     => $remote_id,
				'fields'         => 'ids',
			] );
		}
		if ( empty( $existing ) && $email ) {
			$existing = get_posts( [
				'post_type'      => 'ctci_attendee',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'meta_key'       => 'ctci_email',
				'meta_value'     => $email,
				'fields'         => 'ids',
			] );
		}

		$first     = sanitize_text_field( $data['first_name']  ?? '' );
		$last      = sanitize_text_field( $data['last_name']   ?? '' );
		$badge     = sanitize_text_field( $data['badge_name']  ?? '' );
		// Post title = badge name > full name > email.
		$title     = $badge ?: ( trim( "$first $last" ) ?: $email );

		$post_arr = [
			'post_type'   => 'ctci_attendee',
			'post_title'  => $title,
			'post_status' => 'publish',
		];

		if ( ! empty( $existing ) ) {
			$post_arr['ID'] = $existing[0];
			$post_id        = wp_update_post( $post_arr, true );
		} else {
			$post_id = wp_insert_post( $post_arr, true );
		}

		if ( is_wp_error( $post_id ) ) {
			return 0;
		}

		$meta_map = [
			'ctci_remote_id'          => $remote_id,
			'ctci_first_name'         => $first,
			'ctci_last_name'          => $last,
			'ctci_email'              => $email,
			'ctci_ticket_type'        => sanitize_text_field( $data['ticket_type']        ?? '' ),
			'ctci_order_status'       => sanitize_text_field( $data['order_status']       ?? '' ),
			'ctci_badge_name'         => $badge,
			'ctci_company'            => sanitize_text_field( $data['company']            ?? '' ),
			'ctci_wordpress_username' => sanitize_text_field( $data['wordpress_username'] ?? '' ),
			'ctci_social'             => sanitize_text_field( $data['social']             ?? '' ),
			'ctci_website'            => esc_url_raw( $data['website'] ?? '' ),
			'ctci_contributor_day'    => sanitize_text_field( $data['contributor_day']    ?? '' ),
		];

		foreach ( $meta_map as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}

		return $post_id;
	}

	/**
	 * Return all displayable fields for a local ctci_attendee post.
	 *
	 * @param int $post_id
	 * @return array
	 */
	public static function get_data( int $post_id ): array {
		$meta     = get_post_meta( $post_id );
		$meta_key = get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );

		$first      = $meta['ctci_first_name'][0]         ?? '';
		$last       = $meta['ctci_last_name'][0]          ?? '';
		$badge_name = $meta['ctci_badge_name'][0]         ?? '';
		$full_name  = trim( "$first $last" ) ?: get_the_title( $post_id );
		// Display name: prefer badge name.
		$display    = $badge_name ?: $full_name;

		// Resolve Twitter/X: strip leading @, strip URL prefixes.
		$raw_social = $meta['ctci_social'][0] ?? '';
		$social     = self::clean_social( $raw_social );

		return [
			'id'                   => $post_id,
			'remote_id'            => (int) ( $meta['ctci_remote_id'][0]          ?? 0 ),
			'name'                 => $display,
			'full_name'            => $full_name,
			'first_name'           => $first,
			'last_name'            => $last,
			'badge_name'           => $badge_name,
			'email'                => $meta['ctci_email'][0]              ?? '',
			'ticket_type'          => $meta['ctci_ticket_type'][0]        ?? '',
			'order_status'         => $meta['ctci_order_status'][0]       ?? '',
			'company'              => $meta['ctci_company'][0]            ?? '',
			'wordpress_username'   => $meta['ctci_wordpress_username'][0] ?? '',
			'social'               => $social,
			'website'              => $meta['ctci_website'][0]            ?? '',
			'contributor_day'      => $meta['ctci_contributor_day'][0]    ?? '',
			'checked_in'           => ! empty( $meta[ $meta_key ][0] ),
			'checked_in_at'        => $meta[ $meta_key ][0]              ?? null,
			'qr_sent'              => $meta['ctci_qr_sent'][0]           ?? null,
			'qr_url'               => CTCI_QR_Generator::get_qr_url( $post_id, 250 ),
			'badge_url'            => admin_url( "admin.php?page=camptix-checkin-badge&attendee_id={$post_id}" ),
		];
	}

	/**
	 * Normalise a raw social/Twitter value:
	 * strips URLs, leading @, surrounding quotes.
	 */
	public static function clean_social( string $raw ): string {
		$s = trim( $raw, " \t\n\r\0\x0B'@\"" );
		// Strip full URL e.g. https://x.com/handle or https://twitter.com/handle
		if ( filter_var( $s, FILTER_VALIDATE_URL ) ) {
			$path = trim( (string) parse_url( $s, PHP_URL_PATH ), '/' );
			// Take only the first path segment (the username).
			$parts = explode( '/', $path );
			$s     = $parts[0] ?? $s;
		}
		// Strip remaining leading @.
		$s = ltrim( $s, '@' );
		return $s;
	}
}

new CTCI_Attendee_CPT();
