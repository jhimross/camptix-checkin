<?php
/**
 * WordCamp → Check-In Site Attendee Sync
 *
 * Pulls attendees from the WordCamp site's REST API and upserts them
 * into local ctci_attendee posts.
 *
 * Authentication: WordPress Application Password
 *   (User → Profile → Application Passwords on the WordCamp site)
 *
 * The WordCamp site exposes CampTix attendees at:
 *   GET /wp-json/wp/v2/tix_attendee?per_page=100&page=N
 *   (requires authentication — attendee emails are private)
 *
 * Additionally supports pulling from the WordCamp.org central API:
 *   https://central.wordcamp.org/wp-json/wp/v2/
 */
defined( 'ABSPATH' ) || exit;

class CTCI_WordCamp_Sync {

	/** Option keys */
	const OPT_URL      = 'ctci_wc_url';
	const OPT_USER     = 'ctci_wc_user';
	const OPT_PASS     = 'ctci_wc_app_pass';
	const OPT_LAST     = 'ctci_wc_last_sync';
	const OPT_SCHEDULE = 'ctci_wc_schedule';
	const CRON_HOOK    = 'ctci_scheduled_sync';

	/** Question field key mappings (configurable in Settings) */
	const OPT_Q_COMPANY = 'ctci_q_company';
	const OPT_Q_SOCIAL  = 'ctci_q_social';
	const OPT_Q_WEBSITE = 'ctci_q_website';
	const OPT_Q_MEAL    = 'ctci_q_meal';

	public function __construct() {
		add_action( self::CRON_HOOK, [ $this, 'run_sync' ] );
		add_action( 'ctci_schedule_changed', [ $this, 'reschedule_cron' ] );
		add_action( 'admin_post_ctci_manual_sync', [ $this, 'handle_manual_sync' ] );
		add_action( 'admin_post_ctci_csv_import',  [ $this, 'handle_csv_import'  ] );
	}

	/* ----------------------------------------------------------
	 * Public sync entry-point
	 * -------------------------------------------------------- */

	/**
	 * Pull all attendees from the WordCamp site and upsert locally.
	 *
	 * @return array{ imported: int, updated: int, errors: string[] }
	 */
	public function run_sync(): array {
		$result = [ 'imported' => 0, 'updated' => 0, 'errors' => [] ];

		$base_url = get_option( self::OPT_URL, '' );
		$username = get_option( self::OPT_USER, '' );
		$app_pass = get_option( self::OPT_PASS, '' );

		if ( ! $base_url || ! $username || ! $app_pass ) {
			$result['errors'][] = __( 'WordCamp connection not configured. Go to Settings to add the site URL and Application Password.', 'camptix-checkin' );
			return $result;
		}

		$base_url  = trailingslashit( $base_url );
		$auth      = 'Basic ' . base64_encode( "$username:$app_pass" );
		$page      = 1;
		$per_page  = 100;

		do {
			$endpoint = $base_url . "wp-json/wp/v2/tix_attendee?per_page={$per_page}&page={$page}&status=publish&_fields=id,title,meta,acf";
			$response = wp_remote_get( $endpoint, [
				'timeout' => 30,
				'headers' => [
					'Authorization' => $auth,
					'Accept'        => 'application/json',
				],
			] );

			if ( is_wp_error( $response ) ) {
				$result['errors'][] = $response->get_error_message();
				break;
			}

			$code = wp_remote_retrieve_response_code( $response );
			if ( $code === 400 || $code === 404 ) {
				// No more pages or endpoint not found.
				break;
			}
			if ( $code !== 200 ) {
				$result['errors'][] = sprintf( __( 'HTTP %d from WordCamp API (page %d).', 'camptix-checkin' ), $code, $page );
				break;
			}

			$body  = json_decode( wp_remote_retrieve_body( $response ), true );
			$total_pages = (int) wp_remote_retrieve_header( $response, 'x-wp-totalpages' );

			if ( empty( $body ) || ! is_array( $body ) ) {
				break;
			}

			foreach ( $body as $raw ) {
				$local_data = $this->normalise_attendee( $raw );
				$is_new     = empty( get_posts( [
					'post_type'      => 'ctci_attendee',
					'posts_per_page' => 1,
					'post_status'    => 'any',
					'meta_key'       => 'ctci_remote_id',
					'meta_value'     => $local_data['remote_id'],
					'fields'         => 'ids',
				] ) );

				$local_id = CTCI_Attendee_CPT::upsert( $local_data );
				if ( $local_id ) {
					$is_new ? $result['imported']++ : $result['updated']++;
				} else {
					$result['errors'][] = sprintf( __( 'Failed to upsert remote attendee #%d.', 'camptix-checkin' ), $local_data['remote_id'] );
				}
			}

			$page++;
		} while ( $page <= $total_pages );

		update_option( self::OPT_LAST, current_time( 'mysql' ) );
		return $result;
	}

	/* ----------------------------------------------------------
	 * Normalise a raw REST API attendee object
	 * -------------------------------------------------------- */
	private function normalise_attendee( array $raw ): array {
		$meta = $raw['meta'] ?? [];

		// CampTix stores answers to custom questions in tix_questions (serialised)
		// OR the REST API may expose them individually as meta fields.
		$questions = [];
		if ( ! empty( $meta['tix_questions'] ) ) {
			$q = $meta['tix_questions'];
			if ( is_string( $q ) ) {
				$q = maybe_unserialize( $q );
			}
			if ( is_array( $q ) ) {
				$questions = $q;
			}
		}

		$q_company = get_option( self::OPT_Q_COMPANY, 'company' );
		$q_social  = get_option( self::OPT_Q_SOCIAL,  'social'  );
		$q_website = get_option( self::OPT_Q_WEBSITE, 'website' );
		$q_meal    = get_option( self::OPT_Q_MEAL,    'meal'    );

		// Resolve the ticket post ID if available.
		$ticket_id   = $meta['tix_ticket_id'][0]   ?? ( $meta['tix_ticket_id']   ?? '' );
		$order_status = $meta['tix_order_status'][0] ?? ( $meta['tix_order_status'] ?? 'publish' );

		return [
			'remote_id'    => (int) ( $raw['id'] ?? 0 ),
			'first_name'   => $meta['tix_first_name'][0] ?? ( $meta['tix_first_name'] ?? ( $raw['title']['rendered'] ?? '' ) ),
			'last_name'    => $meta['tix_last_name'][0]  ?? ( $meta['tix_last_name']  ?? '' ),
			'email'        => $meta['tix_email'][0]      ?? ( $meta['tix_email']      ?? '' ),
			'ticket_type'  => $meta['tix_ticket_name'][0] ?? ( $meta['tix_ticket_name'] ?? '' ),
			'ticket_id'    => is_array( $ticket_id ) ? ( $ticket_id[0] ?? '' ) : $ticket_id,
			'order_status' => is_array( $order_status ) ? ( $order_status[0] ?? '' ) : $order_status,
			'company'      => $questions[ $q_company ] ?? ( $meta[ $q_company ][0] ?? ( $meta[ $q_company ] ?? '' ) ),
			'social'       => $questions[ $q_social  ] ?? ( $meta[ $q_social  ][0] ?? ( $meta[ $q_social  ] ?? '' ) ),
			'website'      => $questions[ $q_website ] ?? ( $meta[ $q_website ][0] ?? ( $meta[ $q_website ] ?? '' ) ),
			'meal_preference' => $questions[ $q_meal ] ?? ( $meta[ $q_meal ][0] ?? ( $meta[ $q_meal ] ?? '' ) ),
		];
	}

	/* ----------------------------------------------------------
	 * CSV Import (manual fallback)
	 * -------------------------------------------------------- */

	/**
	 * Expected CSV columns (header row required):
	 *   first_name, last_name, email, ticket_type, company, social, website
	 * Optional: remote_id, order_status
	 */
	public function handle_csv_import(): void {
		check_admin_referer( 'ctci_csv_import_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'camptix-checkin' ) );
		}

		$result = [ 'imported' => 0, 'updated' => 0, 'errors' => [] ];

		if ( empty( $_FILES['ctci_csv']['tmp_name'] ) ) {
			$result['errors'][] = __( 'No file uploaded.', 'camptix-checkin' );
			$this->redirect_with_result( $result );
			return;
		}

		$file = $_FILES['ctci_csv']['tmp_name'];
		if ( ! is_readable( $file ) ) {
			$result['errors'][] = __( 'Uploaded file is not readable.', 'camptix-checkin' );
			$this->redirect_with_result( $result );
			return;
		}

		$handle = fopen( $file, 'r' );
		$header = fgetcsv( $handle );

		if ( ! $header ) {
			$result['errors'][] = __( 'CSV file appears empty or invalid.', 'camptix-checkin' );
			fclose( $handle );
			$this->redirect_with_result( $result );
			return;
		}

		// Normalise header keys.
		$header = array_map( fn( $h ) => strtolower( trim( $h ) ), $header );

		$row_num = 1;
		while ( ( $row = fgetcsv( $handle ) ) !== false ) {
			$row_num++;

			// Pad row to header length so array_combine never fails.
			while ( count( $row ) < count( $header ) ) {
				$row[] = '';
			}
			$data = array_combine( $header, array_slice( $row, 0, count( $header ) ) );

			// ── Map exact CampTix export headers to our internal keys ──────
			$aliases = [
				// CampTix CSV exact headers (lowercased)
				'attendee id'                 => 'remote_id',
				'ticket type'                 => 'ticket_type',
				'first name'                  => 'first_name',
				'last name'                   => 'last_name',
				'e-mail address'              => 'email',
				'e-mail'                      => 'email',
				'email address'               => 'email',
				'status'                      => 'order_status',
				'company name'                => 'company',
				'wordpress.org username'      => 'wordpress_username',
				'name on badge (please provide your name as you would like it to appear on your badge.)' => 'badge_name',
				'name on badge'               => 'badge_name',
				'twitter/x handle'            => 'social',
				'twitter/x'                   => 'social',
				'twitter'                     => 'social',
				"x handle '@username"         => 'social',
				'x handle'                    => 'social',
				'website url'                 => 'website',
				'url'                         => 'website',
				'will you also join contributor day? contributor day is free for ticket holders, but separate registration is required. slots are limited to the first 300 registered participants only.' => 'contributor_day',
				'will you also join contributor day?' => 'contributor_day',
				'contributor day'             => 'contributor_day',
				'meal preferences'            => 'meal_preference',
				'meal preference'             => 'meal_preference',
				'meal'                        => 'meal_preference',
				'dietary preference'          => 'meal_preference',
				'dietary preferences'         => 'meal_preference',
				'dietary restriction'         => 'meal_preference',
				'dietary restrictions'        => 'meal_preference',
				'food preference'             => 'meal_preference',
				'food preferences'            => 'meal_preference',
			];

			foreach ( $aliases as $alias => $canonical ) {
				if ( isset( $data[ $alias ] ) && ( ! isset( $data[ $canonical ] ) || $data[ $canonical ] === '' ) ) {
					$data[ $canonical ] = $data[ $alias ];
				}
			}

			// Skip rows with no usable identity.
			if ( empty( $data['email'] ) && empty( $data['first_name'] ) ) {
				$result['errors'][] = sprintf( __( 'Row %d skipped: no name or email.', 'camptix-checkin' ), $row_num );
				continue;
			}

			// Skip "[[ unconfirmed ]]" placeholder rows.
			if (
				( $data['first_name'] ?? '' ) === 'Unknown' &&
				( $data['last_name']  ?? '' ) === 'Attendee'
			) {
				continue;
			}

			// Use Attendee ID as remote_id; fall back to email hash.
			if ( empty( $data['remote_id'] ) ) {
				$data['remote_id'] = abs( crc32( strtolower( trim( $data['email'] ?? $row_num ) ) ) );
			}

			// Clean social handle.
			if ( ! empty( $data['social'] ) ) {
				$data['social'] = CTCI_Attendee_CPT::clean_social( $data['social'] );
			}

			$is_new   = empty( get_posts( [
				'post_type'      => 'ctci_attendee',
				'posts_per_page' => 1,
				'post_status'    => 'any',
				'meta_key'       => 'ctci_remote_id',
				'meta_value'     => $data['remote_id'],
				'fields'         => 'ids',
			] ) );

			$local_id = CTCI_Attendee_CPT::upsert( $data );
			if ( $local_id ) {
				$is_new ? $result['imported']++ : $result['updated']++;
			} else {
				$result['errors'][] = sprintf( __( 'Row %d: failed to save attendee.', 'camptix-checkin' ), $row_num );
			}
		}

		fclose( $handle );
		$this->redirect_with_result( $result );
	}

	/* ----------------------------------------------------------
	 * Manual sync handler (admin-post)
	 * -------------------------------------------------------- */
	public function handle_manual_sync(): void {
		check_admin_referer( 'ctci_manual_sync_nonce' );
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Permission denied.', 'camptix-checkin' ) );
		}

		$result = $this->run_sync();
		$this->redirect_with_result( $result );
	}

	/* ----------------------------------------------------------
	 * Cron scheduling
	 * -------------------------------------------------------- */
	public function reschedule_cron(): void {
		$schedule = get_option( self::OPT_SCHEDULE, 'off' );

		// Clear existing.
		$ts = wp_next_scheduled( self::CRON_HOOK );
		if ( $ts ) {
			wp_unschedule_event( $ts, self::CRON_HOOK );
		}

		if ( $schedule !== 'off' ) {
			wp_schedule_event( time(), $schedule, self::CRON_HOOK );
		}
	}

	/* ----------------------------------------------------------
	 * Helpers
	 * -------------------------------------------------------- */
	private function redirect_with_result( array $result ): void {
		$url = add_query_arg( [
			'page'           => 'camptix-checkin-sync',
			'ctci_imported'  => $result['imported'],
			'ctci_updated'   => $result['updated'],
			'ctci_errors'    => count( $result['errors'] ),
			'ctci_error_msg' => urlencode( implode( ' | ', $result['errors'] ) ),
		], admin_url( 'admin.php' ) );
		wp_safe_redirect( $url );
		exit;
	}
}

new CTCI_WordCamp_Sync();
