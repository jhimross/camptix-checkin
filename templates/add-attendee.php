<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ctci-wrap">
	<h1 class="ctci-page-title">
		<span class="dashicons dashicons-plus-alt"></span>
		<?php esc_html_e( 'Add Attendee', 'camptix-checkin' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-attendees' ) ); ?>" class="page-title-action">
			&larr; <?php esc_html_e( 'Back to Attendees', 'camptix-checkin' ); ?>
		</a>
	</h1>

	<?php if ( isset( $_GET['error'] ) ) : ?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html( urldecode( $_GET['error'] ) ); ?></p>
		</div>
	<?php endif; ?>

	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<?php wp_nonce_field( 'ctci_add_attendee' ); ?>
		<input type="hidden" name="action" value="ctci_add_attendee" />

		<div class="ctci-two-col">

			<div class="ctci-card">
				<h2><?php esc_html_e( 'Identity', 'camptix-checkin' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="ctci_first_name"><?php esc_html_e( 'First Name', 'camptix-checkin' ); ?> <span style="color:red;">*</span></label></th>
						<td><input id="ctci_first_name" name="ctci_first_name" type="text" class="regular-text" required /></td>
					</tr>
					<tr>
						<th><label for="ctci_last_name"><?php esc_html_e( 'Last Name', 'camptix-checkin' ); ?></label></th>
						<td><input id="ctci_last_name" name="ctci_last_name" type="text" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="ctci_badge_name"><?php esc_html_e( 'Name on Badge', 'camptix-checkin' ); ?></label></th>
						<td>
							<input id="ctci_badge_name" name="ctci_badge_name" type="text" class="regular-text" />
							<p class="description"><?php esc_html_e( 'Leave blank to use first + last name.', 'camptix-checkin' ); ?></p>
						</td>
					</tr>
					<tr>
						<th><label for="ctci_email"><?php esc_html_e( 'Email', 'camptix-checkin' ); ?> <span style="color:red;">*</span></label></th>
						<td><input id="ctci_email" name="ctci_email" type="email" class="regular-text" required /></td>
					</tr>
					<tr>
						<th><label for="ctci_ticket_type"><?php esc_html_e( 'Ticket Type', 'camptix-checkin' ); ?></label></th>
						<td>
							<select id="ctci_ticket_type" name="ctci_ticket_type">
								<?php foreach ( [ 'Regular', 'Student', 'Professional', 'Microsponsor', 'Bulk Ticket', 'Organizer', 'Speaker', 'Volunteer', 'Sponsor' ] as $t ) : ?>
									<option value="<?php echo esc_attr( $t ); ?>"><?php echo esc_html( $t ); ?></option>
								<?php endforeach; ?>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ctci_contributor_day"><?php esc_html_e( 'Contributor Day', 'camptix-checkin' ); ?></label></th>
						<td>
							<select id="ctci_contributor_day" name="ctci_contributor_day">
								<option value="No">No</option>
								<option value="Yes">Yes</option>
							</select>
						</td>
					</tr>
					<tr>
						<th><label for="ctci_meal_preference"><?php esc_html_e( 'Meal Preference', 'camptix-checkin' ); ?></label></th>
						<td><input id="ctci_meal_preference" name="ctci_meal_preference" type="text" class="regular-text" placeholder="e.g. Vegetarian, Halal, No Pork" /></td>
					</tr>
				</table>
			</div>

			<div class="ctci-card">
				<h2><?php esc_html_e( 'Online Presence', 'camptix-checkin' ); ?></h2>
				<table class="form-table">
					<tr>
						<th><label for="ctci_company"><?php esc_html_e( 'Company / Org', 'camptix-checkin' ); ?></label></th>
						<td><input id="ctci_company" name="ctci_company" type="text" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="ctci_wordpress_username"><?php esc_html_e( 'WordPress.org Username', 'camptix-checkin' ); ?></label></th>
						<td><input id="ctci_wordpress_username" name="ctci_wordpress_username" type="text" class="regular-text" /></td>
					</tr>
					<tr>
						<th><label for="ctci_social"><?php esc_html_e( 'Twitter / X Handle', 'camptix-checkin' ); ?></label></th>
						<td><input id="ctci_social" name="ctci_social" type="text" class="regular-text" placeholder="handle (no @)" /></td>
					</tr>
					<tr>
						<th><label for="ctci_website"><?php esc_html_e( 'Website URL', 'camptix-checkin' ); ?></label></th>
						<td><input id="ctci_website" name="ctci_website" type="url" class="regular-text" placeholder="https://" /></td>
					</tr>
				</table>
			</div>

		</div>

		<p style="margin-top:20px;">
			<button type="submit" class="button button-primary button-large">
				&#x2795; <?php esc_html_e( 'Add Attendee', 'camptix-checkin' ); ?>
			</button>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-attendees' ) ); ?>" class="button button-large" style="margin-left:8px;">
				<?php esc_html_e( 'Cancel', 'camptix-checkin' ); ?>
			</a>
		</p>
	</form>
</div>
