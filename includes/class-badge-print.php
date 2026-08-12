<?php
/**
 * Badge/Name Tag Print helper.
 * Called from the badge template. Also provides a static helper
 * to get badge-display fields from an attendee.
 */
defined( 'ABSPATH' ) || exit;

class CTCI_Badge_Print {

	/**
	 * Return all printable fields for an attendee.
	 *
	 * CampTix stores custom question answers in `tix_questions` as a
	 * serialised array. We look there first, then fall back to direct meta
	 * keys (useful for manual / import scenarios).
	 *
	 * @param int $attendee_id
	 * @return array{
	 *   name: string,
	 *   first_name: string,
	 *   last_name: string,
	 *   email: string,
	 *   ticket: string,
	 *   website: string,
	 *   social: string,
	 *   company: string,
	 *   checked_in: bool,
	 *   checked_in_at: string|null,
	 *   qr_embed: string,
	 * }
	 */
	public static function get_badge_data( int $attendee_id ): array {
		$meta = get_post_meta( $attendee_id );

		$questions = [];
		if ( ! empty( $meta['tix_questions'][0] ) ) {
			$questions = maybe_unserialize( $meta['tix_questions'][0] );
			if ( ! is_array( $questions ) ) {
				$questions = [];
			}
		}

		// Resolve mapped field names from settings.
		$social_key  = get_option( 'ctci_social_meta_field',   'ctci_social' );
		$website_key = get_option( 'ctci_website_meta_field',  'ctci_website' );
		$company_key = get_option( 'ctci_company_meta_field',  'ctci_company' );

		$meta_key = get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );

		$first = $meta['tix_first_name'][0] ?? '';
		$last  = $meta['tix_last_name'][0]  ?? '';
		$name  = trim( "$first $last" ) ?: get_the_title( $attendee_id );

		// Use ctci_attendee CPT fields if this is a local attendee.
		$post = get_post( $attendee_id );
		if ( $post && $post->post_type === 'ctci_attendee' ) {
			$d = CTCI_Attendee_CPT::get_data( $attendee_id );
			return [
				'name'               => $d['name'],
				'full_name'          => $d['full_name'],
				'first_name'         => $d['first_name'],
				'last_name'          => $d['last_name'],
				'badge_name'         => $d['badge_name'],
				'email'              => $d['email'],
				'ticket'             => $d['ticket_type'],
				'company'            => $d['company'],
				'wordpress_username' => $d['wordpress_username'],
				'social'             => $d['social'],
				'website'            => $d['website'],
				'contributor_day'    => $d['contributor_day'],
				'meal_preference'    => $d['meal_preference'],
				'checked_in'         => $d['checked_in'],
				'checked_in_at'      => $d['checked_in_at'],
				'qr_embed'           => CTCI_QR_Generator::get_qr_img_tag( $attendee_id, 120, $d['name'] ),
			];
		}

		// Fallback: legacy tix_attendee posts (CampTix installed directly).
		return [
			'name'               => $name,
			'full_name'          => $name,
			'first_name'         => $first,
			'last_name'          => $last,
			'badge_name'         => $name,
			'email'              => $meta['tix_email'][0] ?? '',
			'ticket'             => $meta['tix_ticket_id'][0] ?? '',
			'company'            => $questions['company'] ?? $questions[ $company_key ] ?? ( $meta[ $company_key ][0] ?? '' ),
			'wordpress_username' => '',
			'social'             => $questions['social']  ?? $questions[ $social_key  ] ?? ( $meta[ $social_key  ][0] ?? '' ),
			'website'            => $questions['website'] ?? $questions[ $website_key ] ?? ( $meta[ $website_key ][0] ?? '' ),
			'contributor_day'    => '',
			'meal_preference'    => $questions['meal_preference'] ?? ( $meta['ctci_meal_preference'][0] ?? '' ),
			'checked_in'         => ! empty( $meta[ $meta_key ][0] ),
			'checked_in_at'      => $meta[ $meta_key ][0] ?? null,
			'qr_embed'           => CTCI_QR_Generator::get_qr_img_tag( $attendee_id, 120, $name ),
		];
	}

	/**
	 * Build the query parameters that carry badge data to the printer or the
	 * standalone /print badge page.
	 *
	 * Accepted $data keys are the ones returned by get_badge_data() /
	 * CTCI_Attendee_CPT::get_data() — ticket may be keyed as 'ticket' or
	 * 'ticket_type'.
	 *
	 * @param array $data Badge data for a single attendee.
	 * @return array Query args keyed by param name.
	 */
	public static function get_badge_query_args( array $data ): array {
		$ticket_type = trim( $data['ticket'] ?? $data['ticket_type'] ?? '' );

		// "Regular" ticket displays as the generic "Attendee" label.
		if ( $ticket_type && strtolower( $ticket_type ) === 'regular' ) {
			$ticket_type = __( 'Attendee', 'camptix-checkin' );
		}

		$params = [
			'name'              => trim( $data['badge_name'] ?? ( $data['name'] ?? '' ) ),
			'company'           => trim( $data['company'] ?? '' ),
			'wordpress_username'=> trim( $data['wordpress_username'] ?? '' ),
			'social'            => trim( $data['social'] ?? '' ),
			'meal_preference'   => trim( $data['meal_preference'] ?? '' ),
			'ticket_type'       => $ticket_type,
		];

		// Website is only sent when present (per requirements).
		if ( ! empty( $data['website'] ) ) {
			$params['website'] = trim( $data['website'] );
		}

		// Omit empty optional fields so only real data is passed.
		foreach ( $params as $key => $value ) {
			if ( '' === $value ) {
				unset( $params[ $key ] );
			}
		}

		return $params;
	}

	/**
	 * URL of the standalone badge page (/print) carrying the badge data as
	 * query parameters.
	 *
	 * @param array $data Badge data for a single attendee.
	 * @return string
	 */
	public static function get_print_page_url( array $data ): string {
		return home_url( '/print' ) . '?' . http_build_query( self::get_badge_query_args( $data ) );
	}

	/**
	 * Build the printer URL with attendee data as query parameters.
	 *
	 * When a printer HTTP URL is configured, "Print Badge" sends the badge
	 * fields straight to the printer instead of opening the standalone badge
	 * page. Returns an empty string when no printer is configured, so callers
	 * can fall back to the default badge page.
	 *
	 * @param array $data Badge data for a single attendee.
	 * @return string Printer URL with query args, or '' when unconfigured.
	 */
	public static function get_printer_url( array $data ): string {
		$printer = trim( (string) get_option( 'ctci_printer_url', '' ) );
		if ( ! $printer ) {
			return '';
		}

		$parts = explode( '?', $printer, 2 );
		$path  = rtrim( $parts[0], '/' );
		if ( ! str_ends_with( $path, '/print' ) ) {
			$path .= '/print';
		}
		$printer = $path . ( isset( $parts[1] ) ? '?' . $parts[1] : '' );

		$separator = '?';
		if ( str_contains( $printer, '?' ) ) {
			$separator = ( str_ends_with( $printer, '?' ) || str_ends_with( $printer, '&' ) ) ? '' : '&';
		}
		return $printer . $separator . http_build_query( self::get_badge_query_args( $data ) );
	}
}
