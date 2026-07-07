<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ctci-wrap">
	<h1 class="ctci-page-title">
		<span class="dashicons dashicons-email-alt"></span>
		<?php esc_html_e( 'Send QR Codes', 'camptix-checkin' ); ?>
	</h1>

	<?php
	/* ── Handle bulk send ──────────────────────────────────── */
	if ( isset( $_POST['ctci_send_qr'] ) && check_admin_referer( 'ctci_send_qr_nonce' ) ) {
		$result = CTCI_Email::send_all_qr_codes();
		$class  = $result['sent'] > 0 ? 'notice-success' : 'notice-warning';
		echo '<div class="notice ' . esc_attr( $class ) . ' is-dismissible"><p>';
		printf(
			esc_html__( 'Done: %1$d QR code emails sent, %2$d skipped (already sent or placeholder), %3$d failed.', 'camptix-checkin' ),
			(int) $result['sent'],
			(int) $result['skipped'],
			(int) $result['failed']
		);
		echo '</p></div>';
	}

	/* ── Handle individual resend ──────────────────────────── */
	if ( isset( $_POST['ctci_resend_id'] ) && check_admin_referer( 'ctci_resend_nonce_' . absint( $_POST['ctci_resend_id'] ) ) ) {
		$rid = absint( $_POST['ctci_resend_id'] );
		$ok  = CTCI_Email::resend_qr( $rid );
		$d   = CTCI_Attendee_CPT::get_data( $rid );
		echo '<div class="notice ' . ( $ok ? 'notice-success' : 'notice-error' ) . ' is-dismissible"><p>';
		if ( $ok ) {
			printf( esc_html__( 'QR code resent to %s (%s).', 'camptix-checkin' ), esc_html( $d['name'] ), esc_html( $d['email'] ) );
		} else {
			printf( esc_html__( 'Failed to resend to %s — check that a valid email is on file.', 'camptix-checkin' ), esc_html( $d['name'] ) );
		}
		echo '</p></div>';
	}

	/* ── Stats ─────────────────────────────────────────────── */
	$all_ids = get_posts( [
		'post_type'      => 'ctci_attendee',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'fields'         => 'ids',
	] );
	$sent_ids = get_posts( [
		'post_type'      => 'ctci_attendee',
		'posts_per_page' => -1,
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'meta_query'     => [ [ 'key' => 'ctci_qr_sent', 'compare' => 'EXISTS' ] ],
	] );
	$total        = count( $all_ids );
	$sent_count   = count( $sent_ids );
	$unsent_count = $total - $sent_count;
	?>

	<!-- ── Stats row ──────────────────────────────────────── -->
	<div class="ctci-card">
		<h2><?php esc_html_e( 'Bulk Send', 'camptix-checkin' ); ?></h2>
		<p><?php esc_html_e( 'Send each attendee a unique QR code by email. They present this QR at the registration desk to check in — it is not printed on the badge.', 'camptix-checkin' ); ?></p>

		<div class="ctci-send-stats">
			<div class="ctci-stat">
				<span class="ctci-stat-number"><?php echo esc_html( $total ); ?></span>
				<span class="ctci-stat-label"><?php esc_html_e( 'Total Attendees', 'camptix-checkin' ); ?></span>
			</div>
			<div class="ctci-stat">
				<span class="ctci-stat-number ctci-stat-green"><?php echo esc_html( $sent_count ); ?></span>
				<span class="ctci-stat-label"><?php esc_html_e( 'QR Sent', 'camptix-checkin' ); ?></span>
			</div>
			<div class="ctci-stat">
				<span class="ctci-stat-number ctci-stat-orange"><?php echo esc_html( $unsent_count ); ?></span>
				<span class="ctci-stat-label"><?php esc_html_e( 'Pending', 'camptix-checkin' ); ?></span>
			</div>
		</div>

		<?php if ( $unsent_count > 0 ) : ?>
			<form method="post">
				<?php wp_nonce_field( 'ctci_send_qr_nonce' ); ?>
				<p>
					<button type="submit" name="ctci_send_qr" class="button button-primary button-large">
						&#x1F4E7;&nbsp;
						<?php printf( esc_html__( 'Send QR Codes to %d Pending Attendee(s)', 'camptix-checkin' ), $unsent_count ); ?>
					</button>
				</p>
			</form>
		<?php else : ?>
			<p class="ctci-all-sent">&#x2705; <?php esc_html_e( 'All attendees have received their QR codes.', 'camptix-checkin' ); ?></p>
			<form method="post" style="margin-top:10px;">
				<?php wp_nonce_field( 'ctci_send_qr_nonce' ); ?>
				<button type="submit" name="ctci_send_qr" class="button button-secondary">
					&#x21BA;&nbsp; <?php esc_html_e( 'Re-send to new/updated attendees', 'camptix-checkin' ); ?>
				</button>
			</form>
		<?php endif; ?>
	</div>

	<!-- ── Per-attendee resend table ──────────────────────── -->
	<div class="ctci-card" style="margin-top:20px;">
		<h2><?php esc_html_e( 'All Attendees — Resend Individual QR', 'camptix-checkin' ); ?></h2>
		<p class="description" style="margin-bottom:14px;">
			<?php esc_html_e( 'Use the Resend button to send a fresh QR code to a specific attendee regardless of whether they received one before.', 'camptix-checkin' ); ?>
		</p>

		<?php
		$attendees = get_posts( [
			'post_type'      => 'ctci_attendee',
			'posts_per_page' => -1,
			'post_status'    => 'publish',
			'orderby'        => 'title',
			'order'          => 'ASC',
		] );
		?>
		<div style="overflow-x:auto;">
		<table class="wp-list-table widefat fixed striped" style="min-width:780px;">
			<thead>
				<tr>
					<th style="width:160px;"><?php esc_html_e( 'Name on Badge', 'camptix-checkin' ); ?></th>
					<th style="width:160px;"><?php esc_html_e( 'Full Name', 'camptix-checkin' ); ?></th>
					<th><?php esc_html_e( 'Email', 'camptix-checkin' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Ticket', 'camptix-checkin' ); ?></th>
					<th style="width:160px;"><?php esc_html_e( 'QR Sent', 'camptix-checkin' ); ?></th>
					<th style="width:100px;"><?php esc_html_e( 'Action', 'camptix-checkin' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ( $attendees as $post ) :
				$d         = CTCI_Attendee_CPT::get_data( $post->ID );
				$sent_at   = get_post_meta( $post->ID, 'ctci_qr_sent', true );
				$full_name = trim( $d['first_name'] . ' ' . $d['last_name'] );
			?>
				<tr>
					<td><strong><?php echo esc_html( $d['badge_name'] ?: $d['name'] ); ?></strong></td>
					<td><?php echo esc_html( $full_name ?: '—' ); ?></td>
					<td><?php echo esc_html( $d['email'] ); ?></td>
					<td>
						<span class="ctci-ticket-badge ctci-ticket-<?php echo esc_attr( strtolower( str_replace( ' ', '-', $d['ticket_type'] ) ) ); ?>">
							<?php echo esc_html( $d['ticket_type'] ); ?>
						</span>
					</td>
					<td>
						<?php if ( $sent_at ) : ?>
							<span style="color:#00a32a;">&#x2713;</span> <small><?php echo esc_html( $sent_at ); ?></small>
						<?php else : ?>
							<span style="color:#aaa;">&#x2014; <?php esc_html_e( 'Not sent', 'camptix-checkin' ); ?></span>
						<?php endif; ?>
					</td>
					<td>
						<form method="post" style="display:inline;">
							<?php wp_nonce_field( 'ctci_resend_nonce_' . $post->ID ); ?>
							<input type="hidden" name="ctci_resend_id" value="<?php echo esc_attr( $post->ID ); ?>" />
							<button type="submit" class="button button-small">
								&#x21BA;&nbsp; <?php esc_html_e( 'Resend', 'camptix-checkin' ); ?>
							</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		</div><!-- scroll wrap -->
	</div>

</div><!-- .wrap -->
