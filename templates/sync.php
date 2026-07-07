<?php defined( 'ABSPATH' ) || exit; ?>
<?php
// Show sync result notices.
$imported  = isset( $_GET['ctci_imported']  ) ? (int) $_GET['ctci_imported']  : null;
$updated   = isset( $_GET['ctci_updated']   ) ? (int) $_GET['ctci_updated']   : null;
$err_count = isset( $_GET['ctci_errors']    ) ? (int) $_GET['ctci_errors']    : null;
$err_msg   = isset( $_GET['ctci_error_msg'] ) ? sanitize_text_field( urldecode( $_GET['ctci_error_msg'] ) ) : '';
?>

<div class="wrap ctci-wrap">
	<h1 class="ctci-page-title">
		<span class="dashicons dashicons-update"></span>
		<?php esc_html_e( 'Sync Attendees from WordCamp', 'camptix-checkin' ); ?>
	</h1>

	<?php if ( null !== $imported ) : ?>
		<div class="notice notice-success is-dismissible">
			<p>
				<?php printf(
					esc_html__( 'Sync complete: %1$d new attendees imported, %2$d updated.', 'camptix-checkin' ),
					$imported, $updated
				); ?>
				<?php if ( $err_count ) printf( esc_html__( ' (%d errors — see below).', 'camptix-checkin' ), $err_count ); ?>
			</p>
			<?php if ( $err_msg ) : ?>
				<p><small><?php echo esc_html( $err_msg ); ?></small></p>
			<?php endif; ?>
		</div>
	<?php endif; ?>

	<!-- ── WordCamp Connection ────────────────────────────────── -->
	<div class="ctci-card">
		<h2>🔌 <?php esc_html_e( 'WordCamp Connection', 'camptix-checkin' ); ?></h2>

		<p><?php esc_html_e( 'Enter the URL of your WordCamp site and an Application Password from any admin or editor account that can read attendee data.', 'camptix-checkin' ); ?></p>

		<div class="ctci-steps">
			<div class="ctci-step">
				<span class="ctci-step-num">1</span>
				<div>
					<strong><?php esc_html_e( 'On the WordCamp site', 'camptix-checkin' ); ?></strong><br>
					<?php esc_html_e( 'Go to ', 'camptix-checkin' ); ?>
					<code>Users → Your Profile → Application Passwords</code>
					<?php esc_html_e( ' → add a new password named "Check-In App" → copy the generated password.', 'camptix-checkin' ); ?>
				</div>
			</div>
			<div class="ctci-step">
				<span class="ctci-step-num">2</span>
				<div>
					<strong><?php esc_html_e( 'Make sure tix_attendee is REST-visible', 'camptix-checkin' ); ?></strong><br>
					<?php esc_html_e( 'CampTix does not expose tix_attendee via REST by default. Install the companion snippet or mu-plugin (see documentation below).', 'camptix-checkin' ); ?>
				</div>
			</div>
			<div class="ctci-step">
				<span class="ctci-step-num">3</span>
				<div>
					<strong><?php esc_html_e( 'Paste credentials here', 'camptix-checkin' ); ?></strong><br>
					<?php esc_html_e( 'Fill the form below, save, then run a manual sync to test.', 'camptix-checkin' ); ?>
				</div>
			</div>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'ctci_save_sync_settings_nonce' ); ?>
			<input type="hidden" name="action" value="ctci_save_sync_settings" />

			<table class="form-table">
				<tr>
					<th><label for="ctci_wc_url"><?php esc_html_e( 'WordCamp Site URL', 'camptix-checkin' ); ?></label></th>
					<td>
						<input id="ctci_wc_url" name="ctci_wc_url" type="url" class="large-text"
							placeholder="https://city.wordcamp.org/2025/"
							value="<?php echo esc_attr( get_option( CTCI_WordCamp_Sync::OPT_URL, '' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Must include the year path, e.g. https://rome.wordcamp.org/2025/', 'camptix-checkin' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="ctci_wc_user"><?php esc_html_e( 'Admin Username', 'camptix-checkin' ); ?></label></th>
					<td>
						<input id="ctci_wc_user" name="ctci_wc_user" type="text" class="regular-text"
							autocomplete="off"
							value="<?php echo esc_attr( get_option( CTCI_WordCamp_Sync::OPT_USER, '' ) ); ?>" />
					</td>
				</tr>
				<tr>
					<th><label for="ctci_wc_app_pass"><?php esc_html_e( 'Application Password', 'camptix-checkin' ); ?></label></th>
					<td>
						<input id="ctci_wc_app_pass" name="ctci_wc_app_pass" type="password" class="regular-text"
							autocomplete="new-password"
							value="<?php echo esc_attr( get_option( CTCI_WordCamp_Sync::OPT_PASS, '' ) ); ?>" />
						<p class="description"><?php esc_html_e( 'Stored encrypted in the database. Spaces in the generated password are fine — paste as-is.', 'camptix-checkin' ); ?></p>
					</td>
				</tr>
				<tr>
					<th><label for="ctci_wc_schedule"><?php esc_html_e( 'Auto-Sync Schedule', 'camptix-checkin' ); ?></label></th>
					<td>
						<select id="ctci_wc_schedule" name="ctci_wc_schedule">
							<?php
							$current = get_option( CTCI_WordCamp_Sync::OPT_SCHEDULE, 'off' );
							$options = [
								'off'        => __( 'Off (manual only)', 'camptix-checkin' ),
								'hourly'     => __( 'Every hour', 'camptix-checkin' ),
								'twicedaily' => __( 'Twice daily', 'camptix-checkin' ),
								'daily'      => __( 'Once daily', 'camptix-checkin' ),
							];
							foreach ( $options as $val => $label ) :
								?>
								<option value="<?php echo esc_attr( $val ); ?>" <?php selected( $current, $val ); ?>>
									<?php echo esc_html( $label ); ?>
								</option>
							<?php endforeach; ?>
						</select>
					</td>
				</tr>
			</table>

			<h3><?php esc_html_e( 'CampTix Question Field Mapping', 'camptix-checkin' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Enter the exact key used in CampTix questions for each field (check your CampTix ticket setup for the field slugs).', 'camptix-checkin' ); ?></p>
			<table class="form-table">
				<tr>
					<th><label for="ctci_q_company"><?php esc_html_e( 'Company / Org field key', 'camptix-checkin' ); ?></label></th>
					<td><input id="ctci_q_company" name="ctci_q_company" type="text" class="regular-text"
						value="<?php echo esc_attr( get_option( CTCI_WordCamp_Sync::OPT_Q_COMPANY, 'company' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="ctci_q_social"><?php esc_html_e( 'Social handle field key', 'camptix-checkin' ); ?></label></th>
					<td><input id="ctci_q_social" name="ctci_q_social" type="text" class="regular-text"
						value="<?php echo esc_attr( get_option( CTCI_WordCamp_Sync::OPT_Q_SOCIAL, 'social' ) ); ?>" /></td>
				</tr>
				<tr>
					<th><label for="ctci_q_website"><?php esc_html_e( 'Website field key', 'camptix-checkin' ); ?></label></th>
					<td><input id="ctci_q_website" name="ctci_q_website" type="text" class="regular-text"
						value="<?php echo esc_attr( get_option( CTCI_WordCamp_Sync::OPT_Q_WEBSITE, 'website' ) ); ?>" /></td>
				</tr>
			</table>

			<p class="submit">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Connection Settings', 'camptix-checkin' ); ?></button>
			</p>
		</form>

		<!-- Manual sync trigger -->
		<?php if ( get_option( CTCI_WordCamp_Sync::OPT_URL ) ) : ?>
			<hr>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:12px;">
				<?php wp_nonce_field( 'ctci_manual_sync_nonce' ); ?>
				<input type="hidden" name="action" value="ctci_manual_sync" />
				<button type="submit" class="button button-primary button-large">
					⟳ <?php esc_html_e( 'Sync Now from WordCamp', 'camptix-checkin' ); ?>
				</button>
			</form>
			<?php
			$last = get_option( CTCI_WordCamp_Sync::OPT_LAST, '' );
			if ( $last ) {
				echo '<small>' . esc_html__( 'Last sync: ', 'camptix-checkin' ) . esc_html( $last ) . '</small>';
			}
			?>
		<?php endif; ?>
	</div>

	<!-- ── CSV Import ──────────────────────────────────────────── -->
	<div class="ctci-card">
		<h2>📥 <?php esc_html_e( 'CSV Import (Manual Fallback)', 'camptix-checkin' ); ?></h2>
		<p><?php esc_html_e( 'If API sync is not available, export attendees from CampTix (Tickets → Export) and upload the CSV here. Existing attendees are matched by email and updated rather than duplicated.', 'camptix-checkin' ); ?></p>

		<div class="ctci-csv-columns">
			<p><strong><?php esc_html_e( 'Accepted column headers (case-insensitive):', 'camptix-checkin' ); ?></strong></p>
			<code>first_name, last_name, email, ticket_type, company, social, website, remote_id</code><br>
			<small><?php esc_html_e( 'Aliases accepted: "First Name", "Last Name", "E-mail", "Ticket", "Organization", "Twitter", "URL"', 'camptix-checkin' ); ?></small>
		</div>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" enctype="multipart/form-data">
			<?php wp_nonce_field( 'ctci_csv_import_nonce' ); ?>
			<input type="hidden" name="action" value="ctci_csv_import" />
			<table class="form-table">
				<tr>
					<th><label for="ctci_csv"><?php esc_html_e( 'CSV File', 'camptix-checkin' ); ?></label></th>
					<td>
						<input id="ctci_csv" name="ctci_csv" type="file" accept=".csv,text/csv" required />
						<p class="description"><?php esc_html_e( 'UTF-8 encoded CSV with a header row.', 'camptix-checkin' ); ?></p>
					</td>
				</tr>
			</table>
			<p class="submit">
				<button type="submit" class="button button-secondary button-large">
					📤 <?php esc_html_e( 'Import CSV', 'camptix-checkin' ); ?>
				</button>
			</p>
		</form>
	</div>

	<!-- ── mu-plugin snippet ───────────────────────────────────── -->
	<div class="ctci-card ctci-card-code">
		<h2>🛠 <?php esc_html_e( 'Required: Enable REST API on WordCamp Site', 'camptix-checkin' ); ?></h2>
		<p><?php esc_html_e( 'CampTix does not expose tix_attendee via the REST API by default. Add this snippet as a Must-Use plugin on the WordCamp site (wp-content/mu-plugins/camptix-rest.php):', 'camptix-checkin' ); ?></p>
		<div class="ctci-code-block">
			<button class="ctci-copy-btn button button-small" data-target="ctci-snippet">
				📋 <?php esc_html_e( 'Copy', 'camptix-checkin' ); ?>
			</button>
			<pre id="ctci-snippet">&lt;?php
/**
 * mu-plugin: camptix-rest.php
 * Expose tix_attendee via the REST API (authenticated reads only).
 * Place in wp-content/mu-plugins/ on the WordCamp site.
 */
add_action( 'init', function() {
    global $wp_post_types;
    if ( isset( $wp_post_types['tix_attendee'] ) ) {
        $wp_post_types['tix_attendee']-&gt;show_in_rest = true;
        $wp_post_types['tix_attendee']-&gt;rest_base    = 'tix_attendee';
        // Expose the meta fields the check-in plugin needs.
        $fields = [
            'tix_first_name', 'tix_last_name', 'tix_email',
            'tix_ticket_id', 'tix_ticket_name', 'tix_order_status',
            'tix_questions',
        ];
        foreach ( $fields as $key ) {
            register_post_meta( 'tix_attendee', $key, [
                'show_in_rest'  =&gt; true,
                'single'        =&gt; true,
                'type'          =&gt; 'string',
                'auth_callback' =&gt; fn() =&gt; current_user_can( 'edit_posts' ),
            ] );
        }
    }
}, 20 );
</pre>
		</div>
		<p class="description">
			<?php esc_html_e( 'On WordCamp.org-hosted sites, share this snippet with your WordCamp deputies or the central tech team — they can add it via Playground or a trusted organiser account.', 'camptix-checkin' ); ?>
		</p>
	</div>
</div>

<script>
document.querySelectorAll('.ctci-copy-btn').forEach(function(btn) {
	btn.addEventListener('click', function() {
		var target = document.getElementById(btn.dataset.target);
		if (!target) return;
		navigator.clipboard.writeText(target.textContent).then(function() {
			btn.textContent = '✅ Copied!';
			setTimeout(function() { btn.textContent = '📋 Copy'; }, 2000);
		});
	});
});
</script>
