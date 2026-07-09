<?php defined( 'ABSPATH' ) || exit; ?>

<!-- QR Modal overlay -->
<div id="ctci-qr-modal" class="ctci-modal" style="display:none;" role="dialog" aria-modal="true">
	<div class="ctci-modal-backdrop"></div>
	<div class="ctci-modal-box">
		<button class="ctci-modal-close" aria-label="Close">&times;</button>
		<h2 id="ctci-modal-name" class="ctci-modal-title"></h2>
		<p id="ctci-modal-meta" class="ctci-modal-meta"></p>
		<div class="ctci-modal-qr">
			<img id="ctci-modal-qr-img" src="" alt="QR Code" width="260" height="260" />
		</div>
		<p class="ctci-modal-hint"><?php esc_html_e( 'Attendee presents this QR at the check-in desk.', 'camptix-checkin' ); ?></p>
		<div class="ctci-modal-actions">
			<a id="ctci-modal-badge-link" href="#" target="_blank" class="button button-primary">
				<?php esc_html_e( 'Print Badge', 'camptix-checkin' ); ?>
			</a>
			<a id="ctci-modal-qr-download" href="#" target="_blank" class="button">
				<?php esc_html_e( 'Open QR Image', 'camptix-checkin' ); ?>
			</a>
		</div>
	</div>
</div>

<div class="wrap ctci-wrap">
	<h1 class="ctci-page-title">
		<span class="dashicons dashicons-groups"></span>
		<?php esc_html_e( 'Attendees', 'camptix-checkin' ); ?>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=camptix-checkin-add' ) ); ?>" class="page-title-action" style="background:#0073aa;color:#fff;border-color:#0073aa;">
			&#x2795; <?php esc_html_e( 'Add Attendee', 'camptix-checkin' ); ?>
		</a>
	</h1>

	<?php if ( isset( $_GET['deleted'] ) ) : ?>
		<div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'Attendee deleted.', 'camptix-checkin' ); ?></p></div>
	<?php endif; ?>

	<div class="ctci-attendee-toolbar">
		<input id="ctci-search" type="search" class="regular-text"
			placeholder="<?php esc_attr_e( 'Search name, email, company, WP username…', 'camptix-checkin' ); ?>" />
		<select id="ctci-filter-ticket">
			<option value=""><?php esc_html_e( 'All Ticket Types', 'camptix-checkin' ); ?></option>
			<option value="Regular">Regular</option>
			<option value="Student">Student</option>
			<option value="Professional">Professional</option>
			<option value="Microsponsor">Microsponsor</option>
			<option value="Bulk Ticket">Bulk Ticket</option>
		</select>
		<select id="ctci-filter-status">
			<option value=""><?php esc_html_e( 'All Check-in Status', 'camptix-checkin' ); ?></option>
			<option value="checked_in"><?php esc_html_e( 'Checked In', 'camptix-checkin' ); ?></option>
			<option value="not_checked"><?php esc_html_e( 'Not Checked In', 'camptix-checkin' ); ?></option>
		</select>
		<button id="ctci-refresh" class="button">
			&#x27F3; <?php esc_html_e( 'Refresh', 'camptix-checkin' ); ?>
		</button>
	</div>

	<div id="ctci-summary" class="ctci-summary-bar">
		<span id="ctci-total-count"><?php esc_html_e( 'Loading…', 'camptix-checkin' ); ?></span>
	</div>

	<div class="ctci-attendee-table-wrap">
	<table class="wp-list-table widefat fixed striped ctci-attendee-table" id="ctci-attendees-table">
		<thead>
			<tr>
				<th class="col-id">#</th>
				<th class="col-name"><?php esc_html_e( 'Name on Badge', 'camptix-checkin' ); ?></th>
				<th class="col-fullname"><?php esc_html_e( 'Full Name', 'camptix-checkin' ); ?></th>
				<th class="col-email"><?php esc_html_e( 'Email', 'camptix-checkin' ); ?></th>
				<th class="col-ticket"><?php esc_html_e( 'Ticket', 'camptix-checkin' ); ?></th>
				<th class="col-company"><?php esc_html_e( 'Company', 'camptix-checkin' ); ?></th>
				<th class="col-wp"><?php esc_html_e( 'WP Username', 'camptix-checkin' ); ?></th>
				<th class="col-social"><?php esc_html_e( 'Twitter/X', 'camptix-checkin' ); ?></th>
				<th class="col-meal"><?php esc_html_e( 'Meal Pref', 'camptix-checkin' ); ?></th>
				<th class="col-cd"><?php esc_html_e( 'Contrib Day', 'camptix-checkin' ); ?></th>
				<th class="col-status"><?php esc_html_e( 'Check-in', 'camptix-checkin' ); ?></th>
				<th class="col-actions"><?php esc_html_e( 'Actions', 'camptix-checkin' ); ?></th>
			</tr>
		</thead>
		<tbody id="ctci-attendees-body">
			<tr><td colspan="12"><?php esc_html_e( 'Loading attendees…', 'camptix-checkin' ); ?></td></tr>
		</tbody>
	</table>
	</div><!-- /.ctci-attendee-table-wrap -->
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
	const cfg      = window.ctciConfig || {};
	const apiBase  = cfg.apiBase || '';
	const nonce    = cfg.nonce  || '';
	const adminUrl = cfg.adminUrl || '';

	let allAttendees = [];

	async function loadAttendees() {
		const tbody = document.getElementById('ctci-attendees-body');
		tbody.innerHTML = '<tr><td colspan="12">Loading…</td></tr>';
		try {
			const res    = await fetch(apiBase + '/attendees', { headers: { 'X-WP-Nonce': nonce } });
			allAttendees = await res.json();
			renderTable(allAttendees);
		} catch (e) {
			tbody.innerHTML = '<tr><td colspan="12">Failed to load attendees.</td></tr>';
		}
	}

	function renderTable(rows) {
		const tbody   = document.getElementById('ctci-attendees-body');
		const summary = document.getElementById('ctci-total-count');
		const total   = rows.length;
		const checked = rows.filter(a => a.checked_in).length;
		const pct     = total ? Math.round(checked / total * 100) : 0;
		summary.textContent = total + ' attendees — ' + checked + ' checked in (' + pct + '%)';

		if (!rows.length) {
			tbody.innerHTML = '<tr><td colspan="12">No attendees found.</td></tr>';
			return;
		}

		tbody.innerHTML = rows.map(a => {
			const modalMeta = [
				a.company      ? a.company : '',
				a.wordpress_username ? '@' + a.wordpress_username : '',
				a.ticket       ? a.ticket  : '',
			].filter(Boolean).join(' · ');

			const ticketClass = {
				'Regular':      'ctci-ticket-regular',
				'Student':      'ctci-ticket-student',
				'Professional': 'ctci-ticket-pro',
				'Microsponsor': 'ctci-ticket-micro',
				'Bulk Ticket':  'ctci-ticket-bulk',
			}[a.ticket] || '';

			return `<tr class="${a.checked_in ? 'ctci-row-in' : 'ctci-row-out'}">
				<td class="col-id"><small>${escHtml(String(a.remote_id || a.id))}</small></td>
				<td class="col-name"><strong>${escHtml(a.badge_name || a.name)}</strong></td>
				<td class="col-fullname">${escHtml(a.full_name || a.name)}</td>
				<td class="col-email"><small>${escHtml(a.email)}</small></td>
				<td class="col-ticket"><span class="ctci-ticket-badge ${ticketClass}">${escHtml(a.ticket)}</span></td>
				<td class="col-company">${escHtml(a.company)}</td>
				<td class="col-wp">${a.wordpress_username ? '<a href="https://profiles.wordpress.org/' + escHtml(a.wordpress_username) + '/" target="_blank">@' + escHtml(a.wordpress_username) + '</a>' : ''}</td>
				<td class="col-social">${a.social ? '<span class="ctci-social">@' + escHtml(a.social) + '</span>' : ''}</td>
				<td class="col-meal">${a.meal_preference ? escHtml(a.meal_preference) : '<span style="color:#bbb;">—</span>'}</td>
				<td class="col-cd" style="text-align:center;">${a.contributor_day === 'Yes' ? '✅' : ''}</td>
				<td class="col-status">${a.checked_in
					? '<span class="ctci-badge ctci-badge-in">&#10003; Checked In</span><br><small>' + escHtml(a.checked_in_at || '') + '</small>'
					: '<span class="ctci-badge ctci-badge-out">&#10005; Not Yet</span>'}</td>
				<td class="col-actions ctci-action-btns">
					<a href="${escHtml(adminUrl + '?page=camptix-checkin-edit&attendee_id=' + a.id)}" class="button button-small">&#x270E; Edit</a>
					<a href="${escHtml(adminUrl + '?page=camptix-checkin-badge&attendee_id=' + a.id)}" class="button button-small" target="_blank">&#x1F5A8; Badge</a>
					<button class="button button-small ctci-show-qr"
						data-qr="${escHtml(a.qr_url)}"
						data-name="${escHtml(a.badge_name || a.name)}"
						data-meta="${escHtml(modalMeta)}"
						data-badge="${escHtml(adminUrl + '?page=camptix-checkin-badge&attendee_id=' + a.id)}"
					>&#x1F4F7; QR</button>
					<button class="button button-small ctci-delete-attendee" style="color:#b32d2e;" data-id="${a.id}" data-name="${escHtml(a.badge_name || a.name)}">&#x1F5D1; Del</button>
				</td>
			</tr>`;
		}).join('');
	}

	function escHtml(s) {
		const d = document.createElement('div');
		d.appendChild(document.createTextNode(String(s || '')));
		return d.innerHTML;
	}

	function filterAttendees() {
		const search = document.getElementById('ctci-search').value.toLowerCase();
		const ticket = document.getElementById('ctci-filter-ticket').value;
		const status = document.getElementById('ctci-filter-status').value;
		let rows = allAttendees;

		if (search) {
			rows = rows.filter(a =>
				(a.name               || '').toLowerCase().includes(search) ||
				(a.full_name          || '').toLowerCase().includes(search) ||
				(a.badge_name         || '').toLowerCase().includes(search) ||
				(a.email              || '').toLowerCase().includes(search) ||
				(a.company            || '').toLowerCase().includes(search) ||
				(a.wordpress_username || '').toLowerCase().includes(search) ||
				(a.social             || '').toLowerCase().includes(search) ||
				String(a.remote_id    || '').includes(search)
			);
		}
		if (ticket) rows = rows.filter(a => a.ticket === ticket);
		if (status === 'checked_in')  rows = rows.filter(a =>  a.checked_in);
		if (status === 'not_checked') rows = rows.filter(a => !a.checked_in);

		renderTable(rows);
	}

	document.getElementById('ctci-search').addEventListener('input', filterAttendees);
	document.getElementById('ctci-filter-ticket').addEventListener('change', filterAttendees);
	document.getElementById('ctci-filter-status').addEventListener('change', filterAttendees);
	document.getElementById('ctci-refresh').addEventListener('click', loadAttendees);

	loadAttendees();

	/* ── QR Modal ─────────────────────────────────────────────── */
	const modal       = document.getElementById('ctci-qr-modal');
	const modalImg    = document.getElementById('ctci-modal-qr-img');
	const modalName   = document.getElementById('ctci-modal-name');
	const modalMeta   = document.getElementById('ctci-modal-meta');
	const modalBadge  = document.getElementById('ctci-modal-badge-link');
	const modalQrOpen = document.getElementById('ctci-modal-qr-download');
	const backdrop    = document.querySelector('.ctci-modal-backdrop');

	function openModal(btn) {
		modalImg.src          = btn.dataset.qr    || '';
		modalName.textContent = btn.dataset.name  || '';
		modalMeta.textContent = btn.dataset.meta  || '';
		modalBadge.href       = btn.dataset.badge || '#';
		modalQrOpen.href      = btn.dataset.qr    || '#';
		modal.style.display   = 'flex';
		document.body.style.overflow = 'hidden';
		document.querySelector('.ctci-modal-close').focus();
	}

	function closeModal() {
		modal.style.display  = 'none';
		document.body.style.overflow = '';
	}

	document.addEventListener('click', function(e) {
		const btn = e.target.closest('.ctci-show-qr');
		if (btn) { openModal(btn); return; }
		if (e.target === backdrop || e.target.closest('.ctci-modal-close')) closeModal();
	});

	document.addEventListener('keydown', function(e) {
		if (e.key === 'Escape' && modal.style.display !== 'none') closeModal();
	});

	// ── Delete attendee ─────────────────────────────────────────────────────
	document.addEventListener('click', function(e) {
		const btn = e.target.closest('.ctci-delete-attendee');
		if (!btn) return;
		const id   = btn.dataset.id;
		const name = btn.dataset.name;
		if (!confirm('Delete "' + name + '"? This cannot be undone.')) return;
		const form = document.getElementById('ctci-delete-form');
		document.getElementById('ctci-delete-id').value = id;
		// Regenerate the nonce URL with the correct ID.
		form.action = ctciConfig.adminPostUrl +
			'?action=ctci_delete_attendee&attendee_id=' + id +
			'&_wpnonce=' + encodeURIComponent(document.getElementById('ctci-delete-nonce-' + id)?.value || '');
		// Nonces are per-ID — use a global fallback via REST header instead.
		fetchDeleteAttendee(id, name);
	});

	async function fetchDeleteAttendee(id, name) {
		try {
			const res = await fetch(ctciConfig.apiBase + '/attendee/' + id + '/delete', {
				method: 'DELETE',
				headers: { 'X-WP-Nonce': ctciConfig.nonce }
			});
			if (res.ok) {
				loadAttendees();
			} else {
				const d = await res.json();
				alert('Delete failed: ' + (d.message || res.status));
			}
		} catch(err) {
			alert('Delete failed: ' + err.message);
		}
	}
});
</script>
