<?php
/**
 * Standalone badge / name tag template.
 *
 * Variables injected by CTCI_Badge_Endpoint::render_badge():
 *   string $badge_name      – Name on Badge (attendee's chosen display name)
 *   string $company         – Company / Organisation
 *   string $ticket_type     – Raw ticket type (Regular, Student, Professional…)
 *   string $ticket_label    – Display label — "Attendee" when ticket_type is Regular
 *   string $meal_pref       – Meal Preference (may be empty)
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
@page { size: 4.25in 3.375in; margin: 0; }

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
	width: 408px;   /* 4.25in @ 96dpi */
	height: 324px;  /* 3.375in @ 96dpi */
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
	padding: 24px 36px;
	text-align: center;
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	min-width: 0;
}

/* Name — dominant */
.badge-name {
	font-size: 30px;
	font-weight: 900;
	line-height: 1.1;
	color: #111827;
	word-break: break-word;
	margin-bottom: 6px;
	max-width: 100%;
	text-align: center;
}

/* Company */
.badge-company {
	font-size: 14px;
	font-weight: 500;
	color: #6b7280;
	word-break: break-word;
	margin-bottom: 14px;
}

/* Divider */
.badge-divider {
	width: 36px;
	height: 3px;
	background: #0073aa;
	border-radius: 2px;
	margin-bottom: 12px;
}

/* Social / web fields — icon inline with handle/url */
.badge-socials {
	list-style: none;
	width: 100%;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 7px;
}
.badge-socials li {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 7px;
	font-size: 13px;
	color: #1f2937;
	font-weight: 600;
	word-break: break-all;
}
.badge-socials svg {
	width: 16px;
	height: 16px;
	flex-shrink: 0;
	fill: #6b7280;
}

/* Meal preference — subtle, small pill below the socials */
.badge-meal {
	margin-top: 14px;
	font-size: 10.5px;
	font-weight: 600;
	letter-spacing: .3px;
	color: #9ca3af;
	display: inline-flex;
	align-items: center;
	gap: 5px;
}
.badge-meal svg {
	width: 12px;
	height: 12px;
	fill: #b0b6bf;
	flex-shrink: 0;
}

/* Ticket type pill — always shown (Regular displays as "Attendee") */
.badge-ticket-item {
	margin-top: 12px;
	padding-top: 10px;
	border-top: 1px solid #e5e7eb;
	width: 100%;
	text-align: center;
	color: #0073aa;
	font-size: 12px;
	font-weight: 700;
	text-transform: uppercase;
	letter-spacing: .8px;
}

/* ── Print overrides ────────────────────────────────────── */
@media print {
	html, body  { background: #fff; padding: 0; }
	.print-bar  { display: none !important; }
	.badge      { box-shadow: none; border-radius: 0; width: 100%; height: 100vh; flex-direction: column; }
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

		<?php $has_socials = $wp_username || $twitter || $website_display; ?>
		<?php if ( $has_socials ) : ?>
			<div class="badge-divider"></div>
			<ul class="badge-socials">

				<?php if ( $wp_username ) : ?>
					<li>
						<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 .5a9.5 9.5 0 1 0 0 19 9.5 9.5 0 0 0 0-19ZM1.68 10c0-1.24.27-2.42.74-3.48l4.4 12.06A8.32 8.32 0 0 1 1.68 10Zm8.32 8.32c-.83 0-1.63-.12-2.38-.35l2.53-7.35 2.59 7.1c.02.04.04.08.06.11a8.3 8.3 0 0 1-2.8.49Zm1.15-12.22c.5-.03.96-.08.96-.08.45-.05.4-.72-.06-.7 0 0-1.36.11-2.24.11-.83 0-2.21-.11-2.21-.11-.46-.02-.51.68-.05.7 0 0 .43.05.88.08l1.31 3.59-1.84 5.51-3.05-9.1c.5-.03.96-.08.96-.08.45-.05.4-.72-.06-.7 0 0-1.36.11-2.24.11-.16 0-.34 0-.53-.01A8.31 8.31 0 0 1 10 1.68c1.96 0 3.75.75 5.09 1.98-.03 0-.06-.01-.1-.01-.83 0-1.42.72-1.42 1.5 0 .7.4 1.29.83 1.99.32.57.7 1.3.7 2.36 0 .73-.28 1.58-.65 2.76l-.85 2.85-3.1-9.27Zm2.99 12.16 2.6-7.51c.48-1.22.65-2.2.65-3.07 0-.32-.02-.61-.06-.88a8.31 8.31 0 0 1-3.19 11.46Z"/></svg>
						<span>@<?php echo esc_html( $wp_username ); ?></span>
					</li>
				<?php endif; ?>

				<?php if ( $twitter ) : ?>
					<li>
						<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M11.9 8.63 18.6 1h-1.6l-5.8 6.62L6.55 1H1l7.03 9.9L1 19h1.6l6.13-6.98L13.65 19H19l-7.1-10.37Zm-2.17 2.47-.71-.99L3.4 2.15h2.44l4.55 6.37.71.99 5.92 8.28h-2.44l-4.85-6.7Z"/></svg>
						<span>@<?php echo esc_html( $twitter ); ?></span>
					</li>
				<?php endif; ?>

				<?php if ( $website_display ) : ?>
					<li>
						<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 1a9 9 0 1 0 0 18 9 9 0 0 0 0-18Zm6.93 8.25h-3.05a13.4 13.4 0 0 0-1.14-5.4 7.52 7.52 0 0 1 4.19 5.4Zm-6.93-6.5c.7 0 1.8 1.94 2.12 5h-4.24c.32-3.06 1.42-5 2.12-5ZM7.7 3.85A13.4 13.4 0 0 0 6.56 9.25H3.51a7.52 7.52 0 0 1 4.2-5.4Zm-4.2 6.9h3.05c.1 1.98.5 3.83 1.14 5.4a7.52 7.52 0 0 1-4.2-5.4Zm4.42 0h4.24c-.32 3.06-1.42 5-2.12 5-.7 0-1.8-1.94-2.12-5Zm4.5 5.4c.63-1.57 1.03-3.42 1.13-5.4h3.05a7.52 7.52 0 0 1-4.19 5.4Z"/></svg>
						<span><?php echo esc_html( $website_display ); ?></span>
					</li>
				<?php endif; ?>

			</ul>
		<?php endif; ?>

		<?php if ( $meal_pref ) : ?>
			<div class="badge-meal">
				<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M4 1v7a2 2 0 0 0 1.5 1.94V19h1V9.94A2 2 0 0 0 8 8V1H7v6H6V1H5v6H4.5V1H4Zm10.5 0a2.5 2.5 0 0 0-2.5 2.5V9a1 1 0 0 0 1 1h.5v9h1v-9H15V1h-.5Z"/></svg>
				<span><?php echo esc_html( $meal_pref ); ?></span>
			</div>
		<?php endif; ?>

		<div class="badge-ticket-item"><?php echo esc_html( $ticket_label ); ?></div>

	</div><!-- .badge-body -->

</div><!-- .badge -->

</body>
</html>
