<?php
/**
 * Email — send QR codes to attendees for check-in.
 *
 * The QR image is fetched server-side, saved to a temp file, and sent
 * as an inline CID attachment. This is the only method that renders
 * reliably in Gmail, Outlook, and Apple Mail — data: URIs are blocked
 * by every major email client for security reasons.
 */
defined( 'ABSPATH' ) || exit;

class CTCI_Email {

	/**
	 * Fetch the QR PNG bytes for an attendee.
	 * Returns raw PNG bytes on success, or false on failure.
	 *
	 * @param int $attendee_id
	 * @return string|false
	 */
	private static function fetch_qr_png( int $attendee_id ) {
		// Check transient cache (raw bytes, not data URI).
		$cache_key = 'ctci_qr_bytes_' . $attendee_id;
		$cached    = get_transient( $cache_key );
		if ( $cached !== false ) {
			return $cached;
		}

		$api_url  = CTCI_QR_Generator::get_qr_url( $attendee_id, 300 );
		$response = wp_remote_get( $api_url, [ 'timeout' => 20, 'sslverify' => true ] );

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		if ( $code !== 200 || empty( $body ) ) {
			return false;
		}

		set_transient( $cache_key, $body, DAY_IN_SECONDS );
		return $body;
	}

	/**
	 * Send the check-in QR code email to a single attendee.
	 *
	 * @param int $attendee_id  Local ctci_attendee post ID.
	 * @return bool
	 */
	public static function send_qr_to_attendee( int $attendee_id ): bool {
		$d = CTCI_Attendee_CPT::get_data( $attendee_id );

		$name  = $d['badge_name'] ?: $d['name'];
		$email = $d['email'];

		if ( ! is_email( $email ) ) {
			return false;
		}

		$subject   = get_option( 'ctci_email_subject', 'Your WordCamp Check-In QR Code' );
		$blog_name = get_bloginfo( 'name' );

		// ── Fetch QR PNG and write to a temp file ──────────────────────────
		$png_bytes = self::fetch_qr_png( $attendee_id );
		$tmp_file  = null;
		$use_cid   = false;

		if ( $png_bytes ) {
			$tmp_file = wp_tempnam( 'ctci-qr-' . $attendee_id . '.png' );
			// wp_tempnam creates the file; overwrite with PNG content.
			if ( $tmp_file && file_put_contents( $tmp_file, $png_bytes ) !== false ) {
				$use_cid = true;
			}
		}

		// ── Email HTML ──────────────────────────────────────────────────────
		// Use cid:qr-code when we have a local file, otherwise fall back to
		// the remote URL (some servers can reach external URLs fine).
		$img_src = $use_cid
			? 'cid:qr-code@camptix-checkin'
			: CTCI_QR_Generator::get_qr_url( $attendee_id, 300 );

		ob_start();
		?>
<!DOCTYPE html>
<html lang="en">
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:32px 16px;">
  <tr><td align="center">
    <table width="560" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:10px;overflow:hidden;max-width:560px;width:100%;">

      <!-- Header -->
      <tr>
        <td style="background:#0073aa;padding:24px 32px;text-align:center;">
          <p style="margin:0;font-size:11px;font-weight:700;letter-spacing:3px;text-transform:uppercase;color:rgba(255,255,255,.85);"><?php echo esc_html( $blog_name ); ?></p>
          <h1 style="margin:8px 0 0;font-size:22px;font-weight:800;color:#ffffff;">Your Check-In QR Code</h1>
        </td>
      </tr>

      <!-- Body -->
      <tr>
        <td style="padding:32px 40px;text-align:center;">
          <p style="margin:0 0 6px;font-size:15px;color:#374151;">Hi <strong><?php echo esc_html( $name ); ?></strong>,</p>
          <p style="margin:0 0 28px;font-size:14px;color:#6b7280;line-height:1.6;">
            Please present the QR code below at the registration desk when you arrive.<br>
            The volunteer will scan it to mark you as checked in.
          </p>

          <!-- QR code -->
          <table cellpadding="0" cellspacing="0" style="margin:0 auto 28px;">
            <tr>
              <td style="background:#ffffff;border:2px solid #e5e7eb;border-radius:12px;padding:16px;">
                <img
                  src="<?php echo esc_attr( $img_src ); ?>"
                  width="300" height="300"
                  alt="Your check-in QR code"
                  style="display:block;border:0;"
                />
              </td>
            </tr>
          </table>

          <?php if ( $d['ticket_type'] ) : ?>
          <p style="margin:0 0 4px;font-size:11px;color:#9ca3af;letter-spacing:1.5px;text-transform:uppercase;">Ticket Type</p>
          <p style="margin:0 0 28px;font-size:18px;font-weight:800;color:#111827;"><?php echo esc_html( $d['ticket_type'] ); ?></p>
          <?php endif; ?>

          <p style="margin:0;font-size:13px;color:#6b7280;line-height:1.7;border-top:1px solid #e5e7eb;padding-top:24px;">
            &#x26A0;&#xFE0F; This QR code is unique to you — please do not share it.<br>
            If you have questions, contact the WordCamp organizers.
          </p>
        </td>
      </tr>

      <!-- Footer -->
      <tr>
        <td style="background:#f9fafb;border-top:1px solid #e5e7eb;padding:16px 32px;text-align:center;">
          <p style="margin:0;font-size:11px;color:#9ca3af;"><?php echo esc_html( $blog_name ); ?> &bull; Sent by CampTix Check-In</p>
        </td>
      </tr>

    </table>
  </td></tr>
</table>
</body>
</html>
		<?php
		$body = ob_get_clean();

		// ── Headers & attachments ───────────────────────────────────────────
		$headers = [ 'Content-Type: text/html; charset=UTF-8' ];

		// PHPMailer CID attachment via wp_mail filter.
		$cid_filter = null;
		if ( $use_cid ) {
			$cid_filter = static function( $phpmailer ) use ( $tmp_file ) {
				$phpmailer->addEmbeddedImage(
					$tmp_file,
					'qr-code@camptix-checkin',   // must match cid: in HTML
					'qr-code.png',
					'base64',
					'image/png'
				);
			};
			add_action( 'phpmailer_init', $cid_filter );
		}

		$result = wp_mail( $email, $subject, $body, $headers );

		// ── Cleanup ─────────────────────────────────────────────────────────
		if ( $cid_filter ) {
			remove_action( 'phpmailer_init', $cid_filter );
		}
		if ( $tmp_file && file_exists( $tmp_file ) ) {
			@unlink( $tmp_file );
		}

		return $result;
	}

	/**
	 * Bulk-send QR codes to all attendees who haven't received one yet.
	 *
	 * @return array{ sent: int, skipped: int, failed: int }
	 */
	public static function send_all_qr_codes(): array {
		$result = [ 'sent' => 0, 'skipped' => 0, 'failed' => 0 ];

		$posts = get_posts( [
			'post_type'      => 'ctci_attendee',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'fields'         => 'ids',
			'meta_query'     => [
				[
					'key'     => 'ctci_qr_sent',
					'compare' => 'NOT EXISTS',
				],
			],
		] );

		foreach ( $posts as $post_id ) {
			$email      = get_post_meta( $post_id, 'ctci_email', true );
			$first_name = get_post_meta( $post_id, 'ctci_first_name', true );

			if ( ! is_email( $email ) || $first_name === 'Unknown' ) {
				$result['skipped']++;
				continue;
			}

			if ( self::send_qr_to_attendee( $post_id ) ) {
				update_post_meta( $post_id, 'ctci_qr_sent', current_time( 'mysql' ) );
				$result['sent']++;
			} else {
				$result['failed']++;
			}
		}

		return $result;
	}

	/**
	 * Resend to a specific attendee (ignores the already-sent flag).
	 *
	 * @param int $attendee_id
	 * @return bool
	 */
	public static function resend_qr( int $attendee_id ): bool {
		$result = self::send_qr_to_attendee( $attendee_id );
		if ( $result ) {
			update_post_meta( $attendee_id, 'ctci_qr_sent', current_time( 'mysql' ) );
		}
		return $result;
	}
}
