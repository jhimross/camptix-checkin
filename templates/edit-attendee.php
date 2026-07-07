<?php
defined( 'ABSPATH' ) || exit;

$attendee_id = absint( $_GET['attendee_id'] ?? 0 );
if ( ! $attendee_id ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'No attendee ID provided.', 'camptix-checkin' ) . '</p></div></div>';
	return;
}

$post = get_post( $attendee_id );
if ( ! $post || $post->post_type !== 'ctci_attendee' ) {
	echo '<div class="wrap"><div class="notice notice-error"><p>' . esc_html__( 'Attendee not found.', 'camptix-checkin' ) . '</p></div></div>';
	return;
}

$d        = CTCI_Attendee_CPT::get_data( $attendee_id );
$meta_key = get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );
$checkin_time = get_post_meta( $attendee_id, $meta_key, true );

$back_url = admin_url( 'admin.php?page=camptix-checkin-attendees' );
$saved    = isset( $_GET['saved'] );
?>
<div class="wrap ctci-wrap">
	<h1 class="ctci-page-title">
		<span class="dashicons dashicons-edit"></span>
		<?php esc_html_e( 'Edit Attendee', 'camptix-checkin' ); ?>
		<a href="<?php echo esc_url( $back_url ); ?>" class="page-title-action">
			&larr; <?php esc_html_e( 'Back to Attendees', 'camptix-checkin' ); ?>
		</a>
	</h1>

	<?php if ( $saved ) : ?>
		<div class="notice notice-success is-dismissible">
			<p><?php esc_html_e( 'Attendee saved successfully.', 'camptix-checkin' ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ctci_save_attendee_' . $attendee_id ); ?>
		<input type="hidden" name="action"            value="ctci_save_attendee" />
		<input type="hidden" name="ctci_attendee_id"  value="<?php echo esc_attr( $attendee_id ); ?>" />

		<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;align-items:start;">

			<!-- ── Left column: identity ───────────────────── -->
			<div>
				<div class="ctci-card">
					<h2><?php esc_html_e( 'Identity', 'camptix-checkin' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><label for="ctci_first_name"><?php esc_html_e( 'First Name', 'camptix-checkin' ); ?></label></th>
							<td><input id="ctci_first_name" name="ctci_first_name" type="text" class="regular-text"
								value="<?php echo esc_attr( $d['first_name'] ); ?>" /></td>
						</tr>
						<tr>
							<th><label for="ctci_last_name"><?php esc_html_e( 'Last Name', 'camptix-checkin' ); ?></label></th>
							<td><input id="ctci_last_name" name="ctci_last_name" type="text" class="regular-text"
								value="<?php echo esc_attr( $d['last_name'] ); ?>" /></td>
						</tr>
						<tr>
							<th><label for="ctci_badge_name"><?php esc_html_e( 'Name on Badge', 'camptix-checkin' ); ?></label></th>
							<td>
								<input id="ctci_badge_name" name="ctci_badge_name" type="text" class="regular-text"
									value="<?php echo esc_attr( $d['badge_name'] ); ?>" />
								<p class="description"><?php esc_html_e( 'What appears large on the printed badge.', 'camptix-checkin' ); ?></p>
							</td>
						</tr>
						<tr>
							<th><label for="ctci_email"><?php esc_html_e( 'Email', 'camptix-checkin' ); ?></label></th>
							<td><input id="ctci_email" name="ctci_email" type="email" class="regular-text"
								value="<?php echo esc_attr( $d['email'] ); ?>" /></td>
						</tr>
						<tr>
							<th><label for="ctci_ticket_type"><?php esc_html_e( 'Ticket Type', 'camptix-checkin' ); ?></label></th>
							<td>
								<select id="ctci_ticket_type" name="ctci_ticket_type">
									<?php
									$types = [ 'Regular', 'Student', 'Professional', 'Microsponsor', 'Bulk Ticket', 'Organizer', 'Speaker', 'Volunteer', 'Sponsor' ];
									foreach ( $types as $type ) {
										printf(
											'<option value="%s"%s>%s</option>',
											esc_attr( $type ),
											selected( $d['ticket_type'], $type, false ),
											esc_html( $type )
										);
									}
									?>
								</select>
							</td>
						</tr>
						<tr>
							<th><label for="ctci_contributor_day"><?php esc_html_e( 'Contributor Day', 'camptix-checkin' ); ?></label></th>
							<td>
								<select id="ctci_contributor_day" name="ctci_contributor_day">
									<option value="Yes" <?php selected( $d['contributor_day'], 'Yes' ); ?>>Yes</option>
									<option value="No"  <?php selected( $d['contributor_day'], 'No'  ); ?>>No</option>
									<option value=""    <?php selected( $d['contributor_day'], ''    ); ?>>&mdash;</option>
								</select>
							</td>
						</tr>
					</table>
				</div>

				<!-- ── Social / web ──────────────────────────── -->
				<div class="ctci-card" style="margin-top:16px;">
					<h2><?php esc_html_e( 'Online Presence', 'camptix-checkin' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><label for="ctci_company"><?php esc_html_e( 'Company / Org', 'camptix-checkin' ); ?></label></th>
							<td><input id="ctci_company" name="ctci_company" type="text" class="regular-text"
								value="<?php echo esc_attr( $d['company'] ); ?>" /></td>
						</tr>
						<tr>
							<th><label for="ctci_wordpress_username"><?php esc_html_e( 'WordPress.org Username', 'camptix-checkin' ); ?></label></th>
							<td>
								<input id="ctci_wordpress_username" name="ctci_wordpress_username" type="text" class="regular-text"
									value="<?php echo esc_attr( $d['wordpress_username'] ); ?>" />
								<?php if ( $d['wordpress_username'] ) : ?>
									<a href="https://profiles.wordpress.org/<?php echo esc_attr( $d['wordpress_username'] ); ?>/" target="_blank" class="button button-small" style="margin-left:6px;">View Profile</a>
								<?php endif; ?>
							</td>
						</tr>
						<tr>
							<th><label for="ctci_social"><?php esc_html_e( 'Twitter / X Handle', 'camptix-checkin' ); ?></label></th>
							<td>
								<input id="ctci_social" name="ctci_social" type="text" class="regular-text"
									value="<?php echo esc_attr( $d['social'] ); ?>"
									placeholder="handle (no @)" />
							</td>
						</tr>
						<tr>
							<th><label for="ctci_website"><?php esc_html_e( 'Website URL', 'camptix-checkin' ); ?></label></th>
							<td><input id="ctci_website" name="ctci_website" type="url" class="regular-text"
								value="<?php echo esc_attr( $d['website'] ); ?>"
								placeholder="https://" /></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ── Right column: check-in status ───────────── -->
			<div>
				<div class="ctci-card ctci-checkin-card <?php echo $d['checked_in'] ? 'ctci-card-checked' : 'ctci-card-unchecked'; ?>">
					<h2><?php esc_html_e( 'Check-In Status', 'camptix-checkin' ); ?></h2>

					<div class="ctci-checkin-status-display">
						<?php if ( $d['checked_in'] ) : ?>
							<div class="ctci-status-icon ctci-status-in">&#10003;</div>
							<p class="ctci-status-label"><?php esc_html_e( 'Checked In', 'camptix-checkin' ); ?></p>
							<p class="ctci-status-time"><?php echo esc_html( $checkin_time ); ?></p>
						<?php else : ?>
							<div class="ctci-status-icon ctci-status-out">&#10005;</div>
							<p class="ctci-status-label"><?php esc_html_e( 'Not Checked In', 'camptix-checkin' ); ?></p>
						<?php endif; ?>
					</div>

					<table class="form-table" style="margin-top:16px;">
						<tr>
							<th><?php esc_html_e( 'Mark as Checked In', 'camptix-checkin' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="ctci_checked_in" value="1"
										<?php checked( $d['checked_in'] ); ?> />
									<?php esc_html_e( 'Attended the event', 'camptix-checkin' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th><label for="ctci_checkin_time"><?php esc_html_e( 'Check-In Time', 'camptix-checkin' ); ?></label></th>
							<td>
								<input id="ctci_checkin_time" name="ctci_checkin_time" type="datetime-local"
									value="<?php echo esc_attr( $checkin_time ? date( 'Y-m-d\TH:i', strtotime( $checkin_time ) ) : '' ); ?>" />
								<p class="description"><?php esc_html_e( 'Leave blank to use current time when saving. Clear the checkbox above to remove check-in entirely.', 'camptix-checkin' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- ── Quick actions ─────────────────────────── -->
				<div class="ctci-card" style="margin-top:16px;">
					<h2><?php esc_html_e( 'Quick Actions', 'camptix-checkin' ); ?></h2>
					<div style="display:flex;flex-direction:column;gap:10px;">
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-badge&attendee_id=' . $attendee_id ) ); ?>"
							class="button button-large" target="_blank" style="text-align:center;">
							&#x1F5A8; <?php esc_html_e( 'Print Badge', 'camptix-checkin' ); ?>
						</a>
						<button type="button" class="button button-large" id="ctci-preview-qr" style="text-align:center;">
							&#x1F4F7; <?php esc_html_e( 'Preview QR Code', 'camptix-checkin' ); ?>
						</button>
					</div>

					<!-- QR preview (hidden until clicked) -->
					<div id="ctci-qr-preview" style="display:none;margin-top:16px;text-align:center;">
						<p style="font-size:12px;color:#6b7280;margin-bottom:8px;">
							<?php esc_html_e( 'This QR is emailed to the attendee for check-in.', 'camptix-checkin' ); ?>
						</p>
						<img
							src="<?php echo esc_attr( CTCI_QR_Generator::get_qr_url( $attendee_id, 200 ) ); ?>"
							width="200" height="200"
							alt="QR Code"
							style="border:1px solid #e5e7eb;border-radius:8px;padding:8px;"
						/>
					</div>
				</div>

				<!-- ── Attendee info (read-only) ──────────────── -->
				<div class="ctci-card" style="margin-top:16px;">
					<h2><?php esc_html_e( 'Import Info', 'camptix-checkin' ); ?></h2>
					<table class="form-table">
						<tr>
							<th><?php esc_html_e( 'Remote ID', 'camptix-checkin' ); ?></th>
							<td><code><?php echo esc_html( $d['remote_id'] ?: '—' ); ?></code></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'QR Email Sent', 'camptix-checkin' ); ?></th>
							<td><?php echo esc_html( $d['qr_sent'] ?: '—' ); ?></td>
						</tr>
						<tr>
							<th><?php esc_html_e( 'Order Status', 'camptix-checkin' ); ?></th>
							<td><?php echo esc_html( $d['order_status'] ?: '—' ); ?></td>
						</tr>
					</table>
				</div>
			</div>

		</div><!-- grid -->

		<p style="margin-top:20px;">
			<button type="submit" class="button button-primary button-large">
				&#x1F4BE; <?php esc_html_e( 'Save Changes', 'camptix-checkin' ); ?>
			</button>
			<a href="<?php echo esc_url( $back_url ); ?>" class="button button-large" style="margin-left:8px;">
				<?php esc_html_e( 'Cancel', 'camptix-checkin' ); ?>
			</a>
		</p>

	</form>
</div>

<script>
document.getElementById('ctci-preview-qr').addEventListener('click', function() {
	var box = document.getElementById('ctci-qr-preview');
	box.style.display = box.style.display === 'none' ? 'block' : 'none';
	this.textContent = box.style.display === 'none' ? '\uD83D\uDCF7 Preview QR Code' : '\uD83D\uDCF7 Hide QR Code';
});
</script>
