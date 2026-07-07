<?php
/**
 * Standalone badge / name tag template.
 *
 * Variables injected by CTCI_Badge_Endpoint::render_badge():
 *   string $badge_name      – Name on Badge (attendee's chosen display name)
 *   string $company         – Company / Organisation
 *   string $ticket_type     – Ticket type (Regular, Student, Professional…)
 *   string $blog_name       – Site / event name
 *   string $wp_username     – WordPress.org username (may be empty)
 *   string $twitter         – Twitter/X handle, already cleaned (may be empty)
 *   string $website_display – Website URL stripped of protocol (may be empty)
 *
 * No QR code on the badge — QR is sent by email for check-in only.
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo esc_html( $badge_name ); ?> — Badge</title>
<style>
/* ── Reset ──────────────────────────────────────────────── */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── Print page ─────────────────────────────────────────── */
@page { size: 3.375in 4.25in; margin: 0; }

/* ── Screen wrapper ─────────────────────────────────────── */
html, body {
	height: 100%;
	background: #e5e7eb;
	font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
	-webkit-print-color-adjust: exact;
	print-color-adjust: exact;
}

body {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: flex-start;
	padding: 28px 16px 48px;
}

/* ── Print / close bar ──────────────────────────────────── */
.print-bar {
	display: flex;
	gap: 10px;
	margin-bottom: 22px;
}
.print-bar button {
	padding: 10px 22px;
	font-size: 14px;
	font-weight: 600;
	border: none;
	border-radius: 6px;
	cursor: pointer;
	line-height: 1;
}
.btn-print { background: #0073aa; color: #fff; }
.btn-close  { background: #fff; color: #374151; border: 1px solid #d1d5db; }

/* ── Badge card ─────────────────────────────────────────── */
.badge {
	width: 324px;
	background: #fff;
	border-radius: 10px;
	overflow: hidden;
	box-shadow: 0 6px 32px rgba(0,0,0,.20);
	display: flex;
	flex-direction: column;
}

/* Body */
.badge-body {
	flex: 1;
	padding: 36px 24px 22px;
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
}

/* Name — dominant */
.badge-name {
	font-size: 34px;
	font-weight: 900;
	line-height: 1.1;
	color: #111827;
	word-break: break-word;
	margin-bottom: 8px;
}

/* Company */
.badge-company {
	font-size: 14px;
	font-weight: 500;
	color: #6b7280;
	word-break: break-word;
	margin-bottom: 20px;
}

/* Divider */
.badge-divider {
	width: 40px;
	height: 3px;
	background: #0073aa;
	border-radius: 2px;
	margin-bottom: 18px;
}

/* Social / web fields */
.badge-socials {
	list-style: none;
	width: 100%;
	display: flex;
	flex-direction: column;
	gap: 8px;
}
.badge-socials li {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;
	font-size: 14px;
	color: #1f2937;
	font-weight: 600;
	word-break: break-all;
}
.badge-socials .social-label {
	font-size: 10px;
	font-weight: 700;
	letter-spacing: 1px;
	text-transform: uppercase;
	color: #9ca3af;
	min-width: 28px;
	text-align: right;
}

/* Ticket type row — only shown for non-Regular tickets */
.badge-ticket-item {
	margin-top: 6px;
	padding-top: 10px;
	border-top: 1px solid #e5e7eb;
	width: 100%;
	justify-content: center;
	color: #0073aa !important;
	text-transform: uppercase;
	letter-spacing: .5px;
}

/* ── Print overrides ────────────────────────────────────── */
@media print {
	html, body  { background: #fff; padding: 0; }
	.print-bar  { display: none !important; }
	.badge      { box-shadow: none; border-radius: 0; width: 100%; height: 100vh; }
}
</style>
</head>
<body>

<div class="print-bar">
	<button class="btn-print" onclick="window.print()">&#x1F5A8;&nbsp; Print Badge</button>
	<button class="btn-close"  onclick="window.close()">&#x2715;&nbsp; Close</button>
</div>

<div class="badge">

	<div class="badge-body">

		<div class="badge-name"><?php echo esc_html( $badge_name ); ?></div>

		<?php if ( $company ) : ?>
			<div class="badge-company"><?php echo esc_html( $company ); ?></div>
		<?php endif; ?>

		<?php
		$show_ticket = $ticket_type && strtolower( $ticket_type ) !== 'regular';
		$has_items   = $wp_username || $twitter || $website_display || $show_ticket;
		?>
		<?php if ( $has_items ) : ?>
			<div class="badge-divider"></div>
			<ul class="badge-socials">

				<?php if ( $wp_username ) : ?>
					<li>
						<span class="social-label">WP</span>
						<span>@<?php echo esc_html( $wp_username ); ?></span>
					</li>
				<?php endif; ?>

				<?php if ( $twitter ) : ?>
					<li>
						<span class="social-label">X</span>
						<span>@<?php echo esc_html( $twitter ); ?></span>
					</li>
				<?php endif; ?>

				<?php if ( $website_display ) : ?>
					<li>
						<span class="social-label">Web</span>
						<span><?php echo esc_html( $website_display ); ?></span>
					</li>
				<?php endif; ?>

				<?php if ( $show_ticket ) : ?>
					<li class="badge-ticket-item">
						<span><?php echo esc_html( $ticket_type ); ?></span>
					</li>
				<?php endif; ?>

			</ul>
		<?php endif; ?>

	</div><!-- .badge-body -->

</div><!-- .badge -->

</body>
</html>
