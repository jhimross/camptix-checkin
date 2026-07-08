<?php defined( 'ABSPATH' ) || exit;

$meta_key = get_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );

// ── Core counts ──────────────────────────────────────────────────────────────
$all_ids = get_posts( [ 'post_type' => 'ctci_attendee', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids' ] );
$total   = count( $all_ids );

$checked_in_ids = get_posts( [
	'post_type' => 'ctci_attendee', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids',
	'meta_query' => [ [ 'key' => $meta_key, 'compare' => 'EXISTS' ] ],
] );
$checked_in  = count( $checked_in_ids );
$not_checked = $total - $checked_in;
$pct         = $total > 0 ? round( ( $checked_in / $total ) * 100 ) : 0;

// ── QR emails sent ────────────────────────────────────────────────────────────
$qr_sent_ids = get_posts( [
	'post_type' => 'ctci_attendee', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids',
	'meta_query' => [ [ 'key' => 'ctci_qr_sent', 'compare' => 'EXISTS' ] ],
] );
$qr_sent = count( $qr_sent_ids );

// ── Contributor Day ───────────────────────────────────────────────────────────
$contrib_ids = get_posts( [
	'post_type' => 'ctci_attendee', 'posts_per_page' => -1, 'post_status' => 'publish', 'fields' => 'ids',
	'meta_query' => [ [ 'key' => 'ctci_contributor_day', 'value' => 'Yes', 'compare' => '=' ] ],
] );
$contrib_count = count( $contrib_ids );

// ── Ticket type breakdown ─────────────────────────────────────────────────────
$ticket_counts = [];
foreach ( $all_ids as $pid ) {
	$t = get_post_meta( $pid, 'ctci_ticket_type', true ) ?: 'Unknown';
	$ticket_counts[ $t ] = ( $ticket_counts[ $t ] ?? 0 ) + 1;
}
arsort( $ticket_counts );

// ── Recent check-ins (last 10) ────────────────────────────────────────────────
$recent = get_posts( [
	'post_type' => 'ctci_attendee', 'posts_per_page' => 10, 'post_status' => 'publish',
	'meta_key'  => $meta_key, 'orderby' => 'meta_value', 'order' => 'DESC',
	'meta_query' => [ [ 'key' => $meta_key, 'compare' => 'EXISTS' ] ],
] );

// ── Check-in timeline (by hour today) ────────────────────────────────────────
$today = current_time( 'Y-m-d' );
$hourly = array_fill( 7, 14, 0 ); // hours 07–20
foreach ( $checked_in_ids as $pid ) {
	$t = get_post_meta( $pid, $meta_key, true );
	if ( $t && str_starts_with( $t, $today ) ) {
		$h = (int) date( 'G', strtotime( $t ) );
		if ( isset( $hourly[ $h ] ) ) $hourly[ $h ]++;
	}
}
?>
<div class="wrap ctci-wrap">
<h1 class="ctci-page-title">
	<span class="dashicons dashicons-chart-bar"></span>
	<?php esc_html_e( 'Dashboard', 'camptix-checkin' ); ?>
	<span style="font-size:13px;font-weight:400;color:#888;margin-left:12px;">
		<?php echo esc_html( current_time( 'F j, Y' ) ); ?>
	</span>
</h1>

<!-- ── Stat cards ────────────────────────────────────────────────────────── -->
<div class="ctci-dash-stats">

	<div class="ctci-dash-stat ctci-dash-stat-blue">
		<div class="ctci-dash-stat-num"><?php echo $total; ?></div>
		<div class="ctci-dash-stat-label"><?php esc_html_e( 'Total Attendees', 'camptix-checkin' ); ?></div>
	</div>

	<div class="ctci-dash-stat ctci-dash-stat-green">
		<div class="ctci-dash-stat-num"><?php echo $checked_in; ?></div>
		<div class="ctci-dash-stat-label"><?php esc_html_e( 'Checked In', 'camptix-checkin' ); ?></div>
	</div>

	<div class="ctci-dash-stat ctci-dash-stat-orange">
		<div class="ctci-dash-stat-num"><?php echo $not_checked; ?></div>
		<div class="ctci-dash-stat-label"><?php esc_html_e( 'Not Yet Arrived', 'camptix-checkin' ); ?></div>
	</div>

	<div class="ctci-dash-stat ctci-dash-stat-purple">
		<div class="ctci-dash-stat-num"><?php echo $qr_sent; ?></div>
		<div class="ctci-dash-stat-label"><?php esc_html_e( 'QR Emails Sent', 'camptix-checkin' ); ?></div>
	</div>

	<div class="ctci-dash-stat ctci-dash-stat-teal">
		<div class="ctci-dash-stat-num"><?php echo $contrib_count; ?></div>
		<div class="ctci-dash-stat-label"><?php esc_html_e( 'Contributor Day', 'camptix-checkin' ); ?></div>
	</div>

</div>

<!-- ── Progress bar ──────────────────────────────────────────────────────── -->
<div class="ctci-card" style="margin-top:20px;">
	<div style="display:flex;justify-content:space-between;align-items:baseline;margin-bottom:10px;">
		<h2 style="margin:0;"><?php esc_html_e( 'Check-In Progress', 'camptix-checkin' ); ?></h2>
		<span style="font-size:28px;font-weight:900;color:#0073aa;"><?php echo $pct; ?>%</span>
	</div>
	<div style="background:#e5e7eb;border-radius:99px;height:18px;overflow:hidden;">
		<div style="background:#0073aa;width:<?php echo $pct; ?>%;height:100%;border-radius:99px;transition:width .4s;"></div>
	</div>
	<p style="margin:8px 0 0;font-size:12px;color:#888;">
		<?php printf( esc_html__( '%1$d of %2$d attendees checked in', 'camptix-checkin' ), $checked_in, $total ); ?>
	</p>
</div>

<!-- ── Two-col row: ticket breakdown + recent check-ins ─────────────────── -->
<div class="ctci-two-col" style="margin-top:20px;">

	<!-- Ticket breakdown -->
	<div class="ctci-card">
		<h2><?php esc_html_e( 'Ticket Breakdown', 'camptix-checkin' ); ?></h2>
		<?php if ( $ticket_counts ) : ?>
		<table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
			<thead><tr>
				<th><?php esc_html_e( 'Ticket Type', 'camptix-checkin' ); ?></th>
				<th style="width:60px;text-align:right;"><?php esc_html_e( 'Count', 'camptix-checkin' ); ?></th>
				<th style="width:100px;"><?php esc_html_e( 'Share', 'camptix-checkin' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $ticket_counts as $type => $count ) :
				$share = $total > 0 ? round( ( $count / $total ) * 100 ) : 0;
			?>
				<tr>
					<td><span class="ctci-ticket-badge ctci-ticket-<?php echo esc_attr( strtolower( str_replace( ' ', '-', $type ) ) ); ?>"><?php echo esc_html( $type ); ?></span></td>
					<td style="text-align:right;font-weight:700;"><?php echo $count; ?></td>
					<td>
						<div style="background:#e5e7eb;border-radius:99px;height:8px;">
							<div style="background:#0073aa;width:<?php echo $share; ?>%;height:100%;border-radius:99px;"></div>
						</div>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No attendees yet.', 'camptix-checkin' ); ?></p>
		<?php endif; ?>
	</div>

	<!-- Recent check-ins -->
	<div class="ctci-card">
		<h2><?php esc_html_e( 'Recent Check-Ins', 'camptix-checkin' ); ?></h2>
		<?php if ( $recent ) : ?>
		<table class="wp-list-table widefat fixed striped" style="margin-top:10px;">
			<thead><tr>
				<th><?php esc_html_e( 'Name', 'camptix-checkin' ); ?></th>
				<th style="width:130px;"><?php esc_html_e( 'Time', 'camptix-checkin' ); ?></th>
			</tr></thead>
			<tbody>
			<?php foreach ( $recent as $p ) :
				$d  = CTCI_Attendee_CPT::get_data( $p->ID );
				$ts = get_post_meta( $p->ID, $meta_key, true );
			?>
				<tr>
					<td>
						<strong><?php echo esc_html( $d['badge_name'] ?: $d['name'] ); ?></strong>
						<?php if ( $d['ticket_type'] ) : ?>
							<span class="ctci-ticket-badge ctci-ticket-<?php echo esc_attr( strtolower( str_replace( ' ', '-', $d['ticket_type'] ) ) ); ?>" style="font-size:10px;">
								<?php echo esc_html( $d['ticket_type'] ); ?>
							</span>
						<?php endif; ?>
					</td>
					<td style="font-size:12px;color:#666;">
						<?php echo esc_html( date( 'H:i', strtotime( $ts ) ) ); ?>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php else : ?>
			<p class="description"><?php esc_html_e( 'No check-ins recorded yet.', 'camptix-checkin' ); ?></p>
		<?php endif; ?>
	</div>

</div>

<!-- ── Today's hourly timeline ───────────────────────────────────────────── -->
<div class="ctci-card" style="margin-top:20px;">
	<h2><?php esc_html_e( "Today's Check-In Timeline", 'camptix-checkin' ); ?></h2>
	<div class="ctci-timeline">
	<?php
	$max_h = max( array_values( $hourly ) ) ?: 1;
	foreach ( $hourly as $h => $cnt ) :
		$bar_h = round( ( $cnt / $max_h ) * 80 );
		$label = date( 'g A', mktime( $h, 0, 0 ) );
	?>
		<div class="ctci-timeline-col">
			<div class="ctci-timeline-count"><?php echo $cnt ?: ''; ?></div>
			<div class="ctci-timeline-bar" style="height:<?php echo max( $bar_h, 2 ); ?>px;<?php echo $cnt ? '' : 'background:#e5e7eb;'; ?>"></div>
			<div class="ctci-timeline-label"><?php echo esc_html( $label ); ?></div>
		</div>
	<?php endforeach; ?>
	</div>
	<p style="margin:6px 0 0;font-size:11px;color:#aaa;">
		<?php esc_html_e( 'Shows check-ins by hour for today only.', 'camptix-checkin' ); ?>
	</p>
</div>

<!-- ── Quick actions ────────────────────────────────────────────────────── -->
<div class="ctci-card" style="margin-top:20px;">
	<h2><?php esc_html_e( 'Quick Actions', 'camptix-checkin' ); ?></h2>
	<div style="display:flex;gap:12px;flex-wrap:wrap;">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-scanner' ) ); ?>" class="button button-primary button-large">
			&#x1F4F7; <?php esc_html_e( 'Open Scanner', 'camptix-checkin' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-attendees&action=add' ) ); ?>" class="button button-large">
			&#x2795; <?php esc_html_e( 'Add Attendee', 'camptix-checkin' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-send' ) ); ?>" class="button button-large">
			&#x1F4E7; <?php esc_html_e( 'Send QR Codes', 'camptix-checkin' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-sync' ) ); ?>" class="button button-large">
			&#x21BA; <?php esc_html_e( 'Sync Attendees', 'camptix-checkin' ); ?>
		</a>
	</div>
</div>

</div><!-- .wrap -->
