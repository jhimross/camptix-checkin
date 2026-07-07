<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ctci-wrap">
	<h1 class="ctci-page-title">
		<span class="dashicons dashicons-admin-settings"></span>
		<?php esc_html_e( 'Check-In Settings', 'camptix-checkin' ); ?>
	</h1>

	<form method="post" class="ctci-card">
		<?php wp_nonce_field( 'ctci_settings_nonce' ); ?>

		<h2><?php esc_html_e( 'CampTix Integration', 'camptix-checkin' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="ctci_meta_key"><?php esc_html_e( 'Check-In Meta Key', 'camptix-checkin' ); ?></label></th>
				<td>
					<input id="ctci_meta_key" name="ctci_meta_key" type="text" class="regular-text"
						value="<?php echo esc_attr( get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'The post meta key used to store the check-in timestamp on tix_attendee posts.', 'camptix-checkin' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="ctci_social_meta_field"><?php esc_html_e( 'Social Handle Meta Field', 'camptix-checkin' ); ?></label></th>
				<td>
					<input id="ctci_social_meta_field" name="ctci_social_meta_field" type="text" class="regular-text"
						value="<?php echo esc_attr( get_option( 'ctci_social_meta_field', 'ctci_social' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Meta key or tix_questions array key for social handle (e.g. Twitter/X handle). Default: ctci_social', 'camptix-checkin' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="ctci_website_meta_field"><?php esc_html_e( 'Website Meta Field', 'camptix-checkin' ); ?></label></th>
				<td>
					<input id="ctci_website_meta_field" name="ctci_website_meta_field" type="text" class="regular-text"
						value="<?php echo esc_attr( get_option( 'ctci_website_meta_field', 'ctci_website' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Meta key or tix_questions array key for the attendee website URL. Default: ctci_website', 'camptix-checkin' ); ?></p>
				</td>
			</tr>
			<tr>
				<th><label for="ctci_company_meta_field"><?php esc_html_e( 'Company Meta Field', 'camptix-checkin' ); ?></label></th>
				<td>
					<input id="ctci_company_meta_field" name="ctci_company_meta_field" type="text" class="regular-text"
						value="<?php echo esc_attr( get_option( 'ctci_company_meta_field', 'ctci_company' ) ); ?>" />
					<p class="description"><?php esc_html_e( 'Meta key or tix_questions array key for the attendee company / organization. Default: ctci_company', 'camptix-checkin' ); ?></p>
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Email Settings', 'camptix-checkin' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><label for="ctci_email_subject"><?php esc_html_e( 'QR Email Subject', 'camptix-checkin' ); ?></label></th>
				<td>
					<input id="ctci_email_subject" name="ctci_email_subject" type="text" class="large-text"
						value="<?php echo esc_attr( get_option( 'ctci_email_subject', __( 'Your WordCamp Check-In QR Code', 'camptix-checkin' ) ) ); ?>" />
				</td>
			</tr>
		</table>

		<h2><?php esc_html_e( 'Badge / Name Tag', 'camptix-checkin' ); ?></h2>
		<table class="form-table">
			<tr>
				<th><?php esc_html_e( 'Show on Badge', 'camptix-checkin' ); ?></th>
				<td>
					<label><input type="checkbox" name="ctci_badge_website" value="1"
						<?php checked( 1, get_option( 'ctci_badge_show_website', 1 ) ); ?> />
						<?php esc_html_e( 'Website URL', 'camptix-checkin' ); ?></label><br>
					<label><input type="checkbox" name="ctci_badge_social" value="1"
						<?php checked( 1, get_option( 'ctci_badge_show_social', 1 ) ); ?> />
						<?php esc_html_e( 'Social Media Handle', 'camptix-checkin' ); ?></label><br>
					<label><input type="checkbox" name="ctci_badge_company" value="1"
						<?php checked( 1, get_option( 'ctci_badge_show_company', 1 ) ); ?> />
						<?php esc_html_e( 'Company / Organization', 'camptix-checkin' ); ?></label>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" name="ctci_save_settings" class="button button-primary">
				<?php esc_html_e( 'Save Settings', 'camptix-checkin' ); ?>
			</button>
		</p>
	</form>

	<!-- ── Danger zone ──────────────────────────────────────────────── -->
	<div class="ctci-card" style="margin-top:30px;border-color:#b32d2e;background:#fff8f8;">
		<h2 style="color:#b32d2e;">&#x26A0; <?php esc_html_e( 'Danger Zone', 'camptix-checkin' ); ?></h2>
		<p><?php esc_html_e( 'This will permanently delete ALL attendees, check-in records, QR cache, and sent-email flags. This cannot be undone.', 'camptix-checkin' ); ?></p>

		<?php if ( isset( $_GET['reset'] ) ) : ?>
			<div class="notice notice-success is-dismissible" style="margin:0 0 16px;"><p><?php esc_html_e( 'All attendee data has been deleted.', 'camptix-checkin' ); ?></p></div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
			onsubmit="return confirm('<?php echo esc_js( __( 'Are you sure? This will delete ALL attendees and cannot be undone.', 'camptix-checkin' ) ); ?>')">
			<?php wp_nonce_field( 'ctci_reset_all' ); ?>
			<input type="hidden" name="action" value="ctci_reset_all" />
			<button type="submit" class="button" style="background:#b32d2e;color:#fff;border-color:#8b1a1a;">
				&#x1F5D1; <?php esc_html_e( 'Reset All — Delete All Attendees', 'camptix-checkin' ); ?>
			</button>
		</form>
	</div>

</div>
