<?php
/**
 * Check-in REST API
 *
 * POST /wp-json/camptix-checkin/v1/scan
 *   Body JSON: { "payload": "<attendee_id>|<hash>" }
 *   Returns: attendee info + check-in status
 *
 * GET /wp-json/camptix-checkin/v1/attendee/<id>
 *   Returns: attendee info card
 */
defined( 'ABSPATH' ) || exit;

class CTCI_Checkin_API {

	public function __construct() {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	public function register_routes(): void {
		$namespace = 'camptix-checkin/v1';

		register_rest_route( $namespace, '/scan', [
			'methods'             => 'POST',
			'callback'            => [ $this, 'handle_scan' ],
			'permission_callback' => [ $this, 'checkin_permission' ],
			'args'                => [
				'payload' => [
					'required'          => true,
					'sanitize_callback' => 'sanitize_text_field',
				],
			],
		] );

		register_rest_route( $namespace, '/attendee/(?P<id>\d+)', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'get_attendee' ],
			'permission_callback' => [ $this, 'checkin_permission' ],
			'args'                => [
				'id' => [
					'validate_callback' => fn( $v ) => is_numeric( $v ),
				],
			],
		] );

		register_rest_route( $namespace, '/attendees', [
			'methods'             => 'GET',
			'callback'            => [ $this, 'list_attendees' ],
			'permission_callback' => [ $this, 'checkin_permission' ],
		] );

		register_rest_route( $namespace, '/attendee/(?P<id>\d+)/delete', [
			'methods'             => 'DELETE',
			'callback'            => [ $this, 'delete_attendee' ],
			'permission_callback' => fn() => current_user_can( 'delete_posts' ),
			'args'                => [
				'id' => [ 'validate_callback' => fn( $v ) => is_numeric( $v ) ],
			],
		] );
	}

	/** Require edit_posts capability (organizers / volunteers). */
	public function checkin_permission(): bool {
		return current_user_can( 'edit_posts' );
	}

	/** Handle a QR scan. */
	public function handle_scan( WP_REST_Request $request ): WP_REST_Response {
		$payload     = $request->get_param( 'payload' );
		$attendee_id = CTCI_QR_Generator::verify_payload( $payload );

		if ( ! $attendee_id ) {
			return new WP_REST_Response( [ 'error' => __( 'Invalid or tampered QR code.', 'camptix-checkin' ) ], 400 );
		}

		$attendee = get_post( $attendee_id );
		// Accept both local ctci_attendee and (if CampTix installed) tix_attendee posts.
		$valid_types = [ 'ctci_attendee', 'tix_attendee' ];
		if ( ! $attendee || ! in_array( $attendee->post_type, $valid_types, true ) ) {
			return new WP_REST_Response( [ 'error' => __( 'Attendee not found.', 'camptix-checkin' ) ], 404 );
		}

		$meta_key   = get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );
		$already_in = get_post_meta( $attendee_id, $meta_key, true );

		if ( $already_in ) {
			return new WP_REST_Response(
				array_merge(
					$this->build_attendee_data( $attendee_id, $attendee->post_type ),
					[
						'status'        => 'already_checked_in',
						'checked_in_at' => $already_in,
					]
				),
				200
			);
		}

		$time = current_time( 'mysql' );
		update_post_meta( $attendee_id, $meta_key, $time );

		return new WP_REST_Response(
			array_merge(
				$this->build_attendee_data( $attendee_id, $attendee->post_type ),
				[
					'status'        => 'checked_in',
					'checked_in_at' => $time,
				]
			),
			200
		);
	}

	/** Return a single attendee info card. */
	public function get_attendee( WP_REST_Request $request ): WP_REST_Response {
		$attendee_id = (int) $request->get_param( 'id' );
		$attendee    = get_post( $attendee_id );
		$valid_types = [ 'ctci_attendee', 'tix_attendee' ];

		if ( ! $attendee || ! in_array( $attendee->post_type, $valid_types, true ) ) {
			return new WP_REST_Response( [ 'error' => __( 'Attendee not found.', 'camptix-checkin' ) ], 404 );
		}

		return new WP_REST_Response( $this->build_attendee_data( $attendee_id, $attendee->post_type ), 200 );
	}

	/** List all attendees with check-in status. */
	public function list_attendees( WP_REST_Request $request ): WP_REST_Response {
		// Prefer local ctci_attendee CPT; fall back to tix_attendee if CampTix is installed directly.
		$local_count = wp_count_posts( 'ctci_attendee' );
		$post_type   = ( $local_count->publish > 0 ) ? 'ctci_attendee' : 'tix_attendee';

		$posts = get_posts( [
			'post_type'      => $post_type,
			'posts_per_page' => -1,
			'post_status'    => [ 'publish', 'pending' ],
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );

		$attendees = array_map( fn( $p ) => $this->build_attendee_data( $p->ID, $post_type ), $posts );

		return new WP_REST_Response( $attendees, 200 );
	}

	/** Delete an attendee (force-delete, no trash). */
	public function delete_attendee( WP_REST_Request $request ): WP_REST_Response {
		$id   = (int) $request->get_param( 'id' );
		$post = get_post( $id );
		if ( ! $post || $post->post_type !== 'ctci_attendee' ) {
			return new WP_REST_Response( [ 'error' => 'Attendee not found.' ], 404 );
		}
		wp_delete_post( $id, true );
		return new WP_REST_Response( [ 'deleted' => true, 'id' => $id ], 200 );
	}

	/**
	 * Build a normalised attendee data array.
	 * Supports both ctci_attendee (local CPT) and tix_attendee (CampTix native).
	 */
	public function build_attendee_data( int $attendee_id, string $post_type = 'ctci_attendee' ): array {
		// Local CPT — use the dedicated helper.
		if ( $post_type === 'ctci_attendee' ) {
			$d = CTCI_Attendee_CPT::get_data( $attendee_id );
			return [
				'id'                   => $d['id'],
				'remote_id'            => $d['remote_id'],
				'name'                 => $d['name'],
				'full_name'            => $d['full_name'],
				'first_name'           => $d['first_name'],
				'last_name'            => $d['last_name'],
				'badge_name'           => $d['badge_name'],
				'email'                => $d['email'],
				'ticket'               => $d['ticket_type'],
				'company'              => $d['company'],
				'wordpress_username'   => $d['wordpress_username'],
				'social'               => $d['social'],
				'website'              => $d['website'],
				'contributor_day'      => $d['contributor_day'],
				'checked_in'           => $d['checked_in'],
				'checked_in_at'        => $d['checked_in_at'],
				'qr_url'               => $d['qr_url'],
				'badge_url'            => $d['badge_url'],
			];
		}

		// Fallback: tix_attendee (CampTix installed directly on this site).
		$meta     = get_post_meta( $attendee_id );
		$meta_key = get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );
		$first    = $meta['tix_first_name'][0] ?? '';
		$last     = $meta['tix_last_name'][0]  ?? '';
		$name     = trim( "$first $last" ) ?: get_the_title( $attendee_id );
		$q_key    = get_option( 'ctci_social_meta_field', 'social' );
		$q_web    = get_option( 'ctci_website_meta_field', 'website' );
		$q_co     = get_option( 'ctci_company_meta_field', 'company' );
		$questions = [];
		if ( ! empty( $meta['tix_questions'][0] ) ) {
			$questions = maybe_unserialize( $meta['tix_questions'][0] );
			if ( ! is_array( $questions ) ) $questions = [];
		}

		return [
			'id'            => $attendee_id,
			'name'          => $name,
			'first_name'    => $first,
			'last_name'     => $last,
			'email'         => $meta['tix_email'][0]     ?? '',
			'ticket'        => $meta['tix_ticket_id'][0] ?? '',
			'website'       => $questions[ $q_web ] ?? ( $meta[ $q_web ][0] ?? '' ),
			'social'        => $questions[ $q_key ] ?? ( $meta[ $q_key ][0] ?? '' ),
			'company'       => $questions[ $q_co  ] ?? ( $meta[ $q_co  ][0] ?? '' ),
			'checked_in'    => ! empty( $meta[ $meta_key ][0] ),
			'checked_in_at' => $meta[ $meta_key ][0] ?? null,
			'qr_url'        => CTCI_QR_Generator::get_qr_url( $attendee_id, 250 ),
			'badge_url'     => admin_url( "admin.php?page=camptix-checkin-badge&attendee_id={$attendee_id}" ),
		];
	}
}

new CTCI_Checkin_API();
