<?php
/**
 * QR Code Generator
 *
 * Uses api.qrserver.com to generate QR images, fetched server-side
 * and embedded as base64 data URIs. The browser never makes an external
 * request — works offline/in print dialogs, no CORS issues.
 *
 * Payload: <attendee_id>|<hmac-sha256>
 */
defined( 'ABSPATH' ) || exit;

class CTCI_QR_Generator {

	/** Build the signed payload string for an attendee. */
	public static function build_payload( int $attendee_id ): string {
		$hash = hash_hmac( 'sha256', (string) $attendee_id, CTCI_SECRET_KEY );
		return $attendee_id . '|' . $hash;
	}

	/** Verify a scanned payload. Returns attendee post ID or false. */
	public static function verify_payload( string $payload ) {
		$parts = explode( '|', $payload, 2 );
		if ( count( $parts ) !== 2 ) {
			return false;
		}
		[ $attendee_id, $hash ] = $parts;
		$expected = hash_hmac( 'sha256', $attendee_id, CTCI_SECRET_KEY );
		if ( ! hash_equals( $expected, $hash ) ) {
			return false;
		}
		return absint( $attendee_id );
	}

	/**
	 * Build the QR API URL for a given attendee.
	 * Uses api.qrserver.com (free, reliable, no key needed).
	 */
	public static function get_qr_url( int $attendee_id, int $size = 250 ): string {
		$payload = self::build_payload( $attendee_id );
		return 'https://api.qrserver.com/v1/create-qr-code/?' . http_build_query( [
			'size'       => "{$size}x{$size}",
			'data'       => $payload,
			'ecc'        => 'M',
			'format'     => 'png',
			'color'      => '000000',
			'bgcolor'    => 'ffffff',
			'qzone'      => 2,
		] );
	}

	/**
	 * Fetch the QR image server-side and return a base64 data URI.
	 * Cached for 24 hours per attendee so repeated badge loads are instant.
	 *
	 * @param int $attendee_id
	 * @param int $size         Pixel dimensions (square).
	 * @return string  data:image/png;base64,... on success, fallback URL on failure.
	 */
	public static function get_qr_data_uri( int $attendee_id, int $size = 200 ): string {
		$cache_key = 'ctci_qr_' . $attendee_id . '_' . $size;
		$cached    = get_transient( $cache_key );
		if ( $cached ) {
			return $cached;
		}

		$api_url  = self::get_qr_url( $attendee_id, $size );
		$response = wp_remote_get( $api_url, [
			'timeout'   => 15,
			'sslverify' => true,
		] );

		if ( ! is_wp_error( $response ) ) {
			$code = wp_remote_retrieve_response_code( $response );
			$body = wp_remote_retrieve_body( $response );
			if ( $code === 200 && ! empty( $body ) ) {
				$uri = 'data:image/png;base64,' . base64_encode( $body );
				set_transient( $cache_key, $uri, DAY_IN_SECONDS );
				return $uri;
			}
		}

		// Fallback: return the remote URL — may still render if online.
		return $api_url;
	}

	/**
	 * Return a self-contained <img> tag with an embedded QR code.
	 * Safe to use in print views — no external browser requests.
	 */
	public static function get_qr_img_tag( int $attendee_id, int $size = 200, string $alt = '' ): string {
		$src = self::get_qr_data_uri( $attendee_id, $size );
		$alt = $alt ?: sprintf( 'QR Code – attendee #%d', $attendee_id );
		return sprintf(
			'<img src="%s" width="%d" height="%d" alt="%s" style="display:block;" />',
			esc_attr( $src ),
			$size,
			$size,
			esc_attr( $alt )
		);
	}
}
