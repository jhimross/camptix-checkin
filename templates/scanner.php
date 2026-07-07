<?php defined( 'ABSPATH' ) || exit; ?>
<div class="wrap ctci-wrap">
	<h1 class="ctci-page-title">
		<span class="dashicons dashicons-camera"></span>
		<?php esc_html_e( 'QR Code Scanner', 'camptix-checkin' ); ?>
	</h1>

	<div class="ctci-scanner-layout">

		<!-- Camera column -->
		<div class="ctci-camera-col">
			<div class="ctci-camera-box">
				<video id="ctci-video" autoplay muted playsinline></video>
				<canvas id="ctci-canvas" hidden></canvas>
				<div class="ctci-scan-overlay">
					<div class="ctci-scan-crosshair"></div>
				</div>
				<div id="ctci-scan-status" class="ctci-scan-status">
					<?php esc_html_e( 'Initialising camera…', 'camptix-checkin' ); ?>
				</div>
			</div>

			<div class="ctci-camera-controls">
				<button id="ctci-btn-start" class="button button-primary button-hero">
					▶ <?php esc_html_e( 'Start Scanner', 'camptix-checkin' ); ?>
				</button>
				<button id="ctci-btn-stop" class="button button-secondary button-hero" disabled>
					⏹ <?php esc_html_e( 'Stop Scanner', 'camptix-checkin' ); ?>
				</button>
			</div>

			<!-- Manual entry fallback -->
			<div class="ctci-manual-entry">
				<h3><?php esc_html_e( 'Manual Entry', 'camptix-checkin' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Enter attendee ID or paste QR payload if camera is unavailable.', 'camptix-checkin' ); ?></p>
				<div class="ctci-manual-row">
					<input id="ctci-manual-input" type="text" class="regular-text"
						placeholder="<?php esc_attr_e( 'Attendee ID or QR payload…', 'camptix-checkin' ); ?>" />
					<button id="ctci-manual-submit" class="button button-primary">
						<?php esc_html_e( 'Check In', 'camptix-checkin' ); ?>
					</button>
				</div>
			</div>
		</div>

		<!-- Result column -->
		<div class="ctci-result-col">
			<div id="ctci-result-panel" class="ctci-result-panel ctci-result-idle">
				<div class="ctci-result-icon" id="ctci-result-icon">🎟️</div>
				<div class="ctci-result-body">
					<p class="ctci-result-placeholder">
						<?php esc_html_e( 'Scan an attendee QR code to check them in.', 'camptix-checkin' ); ?>
					</p>
				</div>
			</div>

			<!-- Recent scans log -->
			<div class="ctci-scan-log">
				<h3><?php esc_html_e( 'Recent Scans', 'camptix-checkin' ); ?></h3>
				<table class="wp-list-table widefat fixed striped ctci-log-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Name', 'camptix-checkin' ); ?></th>
							<th><?php esc_html_e( 'Status', 'camptix-checkin' ); ?></th>
							<th><?php esc_html_e( 'Time', 'camptix-checkin' ); ?></th>
							<th><?php esc_html_e( 'Badge', 'camptix-checkin' ); ?></th>
						</tr>
					</thead>
					<tbody id="ctci-log-body">
						<tr class="ctci-log-empty">
							<td colspan="4"><?php esc_html_e( 'No scans yet.', 'camptix-checkin' ); ?></td>
						</tr>
					</tbody>
				</table>
			</div>
		</div>

	</div><!-- .ctci-scanner-layout -->
</div><!-- .wrap -->
