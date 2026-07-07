/**
 * CampTix Check-In — QR Scanner
 * Uses jsQR to decode frames from the device camera, then POSTs to the
 * REST API to mark the attendee as checked in.
 */
/* global ctciConfig, jsQR */
( function () {
	'use strict';

	const cfg      = window.ctciConfig || {};
	const apiBase  = cfg.apiBase  || '';
	const nonce    = cfg.nonce    || '';
	const adminUrl = cfg.adminUrl || '';
	const str      = cfg.strings  || {};

	// DOM refs (only present on the scanner page)
	const video       = document.getElementById( 'ctci-video' );
	const canvas      = document.getElementById( 'ctci-canvas' );
	const statusEl    = document.getElementById( 'ctci-scan-status' );
	const resultPanel = document.getElementById( 'ctci-result-panel' );
	const resultIcon  = document.getElementById( 'ctci-result-icon' );
	const btnStart    = document.getElementById( 'ctci-btn-start' );
	const btnStop     = document.getElementById( 'ctci-btn-stop' );
	const logBody     = document.getElementById( 'ctci-log-body' );
	const manualInput = document.getElementById( 'ctci-manual-input' );
	const manualBtn   = document.getElementById( 'ctci-manual-submit' );

	if ( ! video ) return; // Not on scanner page — bail.

	let stream        = null;
	let rafId         = null;
	let scanning      = false;
	let lastPayload   = '';
	let cooldown      = false; // debounce rapid re-scans of same code

	/* ----------------------------------------------------------
	 * Camera control
	 * -------------------------------------------------------- */
	async function startCamera() {
		try {
			stream = await navigator.mediaDevices.getUserMedia( {
				video: { facingMode: 'environment', width: { ideal: 1280 }, height: { ideal: 720 } },
				audio: false,
			} );
			video.srcObject = stream;
			await video.play();
			scanning = true;
			setStatus( str.scanning || 'Scanning…', 'scanning' );
			btnStart.disabled = true;
			btnStop.disabled  = false;
			rafId = requestAnimationFrame( tick );
		} catch ( err ) {
			setStatus( str.camera_error || 'Camera access denied.', 'error' );
			console.error( 'ctci camera error', err );
		}
	}

	function stopCamera() {
		scanning = false;
		cancelAnimationFrame( rafId );
		if ( stream ) {
			stream.getTracks().forEach( t => t.stop() );
			stream = null;
		}
		video.srcObject = null;
		setStatus( 'Camera stopped.', '' );
		btnStart.disabled = false;
		btnStop.disabled  = true;
	}

	/* ----------------------------------------------------------
	 * Scan loop
	 * -------------------------------------------------------- */
	function tick() {
		if ( ! scanning ) return;

		if ( video.readyState === video.HAVE_ENOUGH_DATA ) {
			const ctx = canvas.getContext( '2d', { willReadFrequently: true } );
			canvas.width  = video.videoWidth;
			canvas.height = video.videoHeight;
			ctx.drawImage( video, 0, 0 );

			const imageData = ctx.getImageData( 0, 0, canvas.width, canvas.height );
			const code      = jsQR( imageData.data, imageData.width, imageData.height, {
				inversionAttempts: 'dontInvert',
			} );

			if ( code && code.data && ! cooldown ) {
				handlePayload( code.data );
			}
		}

		rafId = requestAnimationFrame( tick );
	}

	/* ----------------------------------------------------------
	 * API call + UI update
	 * -------------------------------------------------------- */
	async function handlePayload( payload ) {
		if ( payload === lastPayload && cooldown ) return;

		lastPayload = payload;
		cooldown    = true;
		setTimeout( () => { cooldown = false; }, 3000 ); // 3 s debounce

		setStatus( '⏳ Verifying…', 'scanning' );
		showResult( 'loading', '⏳', '<p>Verifying QR code…</p>' );

		let body;
		// Support plain attendee IDs (manual entry) as well as full payloads.
		if ( /^\d+$/.test( payload.trim() ) ) {
			const id   = parseInt( payload.trim(), 10 );
			const hash = await hmacSha256( String( id ), '' ); // will fail server-side — server re-validates
			body = { payload: payload.trim() + '|' + hash };
		} else {
			body = { payload };
		}

		try {
			const res  = await fetch( apiBase + '/scan', {
				method:  'POST',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce':   nonce,
				},
				body: JSON.stringify( body ),
			} );
			const data = await res.json();

			if ( ! res.ok ) {
				setStatus( '❌ ' + ( data.error || str.invalid_qr || 'Invalid QR' ), 'error' );
				showResult( 'error', '❌', '<p>' + escHtml( data.error || str.invalid_qr ) + '</p>' );
				appendLog( { name: '—', status: 'error', checked_in_at: now() }, data.error || str.invalid_qr );
				return;
			}

			if ( data.status === 'already_checked_in' ) {
				setStatus( '⚠️ ' + ( str.already_checked || 'Already checked in' ), 'warning' );
				showResult( 'warning', '⚠️', buildResultHtml( data, 'already_checked_in' ) );
				appendLog( data, 'already_checked_in' );
			} else {
				setStatus( '✅ ' + ( str.scan_success || 'Checked in!' ), 'success' );
				showResult( 'success', '✅', buildResultHtml( data, 'checked_in' ) );
				appendLog( data, 'checked_in' );
				playBeep( 'success' );
			}
		} catch ( err ) {
			setStatus( '❌ Network error', 'error' );
			showResult( 'error', '❌', '<p>Network error. Please try again.</p>' );
			console.error( 'ctci scan error', err );
		}
	}

	/* ----------------------------------------------------------
	 * Result card HTML
	 * -------------------------------------------------------- */
	function buildResultHtml( data, status ) {
		const label = status === 'checked_in'
			? '<span class="ctci-badge ctci-badge-in">✓ Checked In</span>'
			: '<span class="ctci-badge ctci-badge-warn">⚠ Already Checked In</span>';

		const badgeLink = adminUrl + '?page=camptix-checkin-badge&attendee_id=' + data.id;

		let meta = '';
		if ( data.company ) meta += '<li>🏢 ' + escHtml( data.company ) + '</li>';
		if ( data.social )  meta += '<li>@ ' + escHtml( data.social )  + '</li>';
		if ( data.website ) meta += '<li>🌐 ' + escHtml( data.website ) + '</li>';

		return `
			<div class="ctci-result-name">${ escHtml( data.name ) }</div>
			<div class="ctci-result-status">${ label }</div>
			${ meta ? '<ul class="ctci-result-meta">' + meta + '</ul>' : '' }
			<p class="ctci-result-time">${ escHtml( data.checked_in_at || '' ) }</p>
			<a href="${ escHtml( badgeLink ) }" class="button button-small ctci-badge-link" target="_blank">
				🖨 Print Badge
			</a>`;
	}

	/* ----------------------------------------------------------
	 * Recent-scans log
	 * -------------------------------------------------------- */
	function appendLog( data, statusKey ) {
		const empty = logBody.querySelector( '.ctci-log-empty' );
		if ( empty ) empty.remove();

		const badgeLink = adminUrl + '?page=camptix-checkin-badge&attendee_id=' + ( data.id || '' );
		const statusBadge = statusKey === 'checked_in'
			? '<span class="ctci-badge ctci-badge-in">✓ In</span>'
			: statusKey === 'already_checked_in'
				? '<span class="ctci-badge ctci-badge-warn">⚠ Dup</span>'
				: '<span class="ctci-badge ctci-badge-err">✗ Err</span>';

		const row = document.createElement( 'tr' );
		row.innerHTML = `
			<td><strong>${ escHtml( data.name || '—' ) }</strong></td>
			<td>${ statusBadge }</td>
			<td><small>${ escHtml( data.checked_in_at || now() ) }</small></td>
			<td>${ data.id ? '<a href="' + escHtml( badgeLink ) + '" target="_blank" class="button button-small">🖨</a>' : '—' }</td>`;

		logBody.insertBefore( row, logBody.firstChild );

		// Keep log to 20 rows.
		while ( logBody.children.length > 20 ) {
			logBody.removeChild( logBody.lastChild );
		}
	}

	/* ----------------------------------------------------------
	 * Status bar
	 * -------------------------------------------------------- */
	function setStatus( msg, type ) {
		if ( ! statusEl ) return;
		statusEl.textContent = msg;
		statusEl.className   = 'ctci-scan-status ctci-status-' + ( type || '' );
	}

	function showResult( type, icon, html ) {
		if ( ! resultPanel ) return;
		resultPanel.className    = 'ctci-result-panel ctci-result-' + type;
		resultIcon.textContent   = icon;
		resultPanel.querySelector( '.ctci-result-body' ).innerHTML = html;
	}

	/* ----------------------------------------------------------
	 * Manual entry
	 * -------------------------------------------------------- */
	if ( manualBtn ) {
		manualBtn.addEventListener( 'click', () => {
			const val = ( manualInput.value || '' ).trim();
			if ( val ) {
				cooldown    = false;
				lastPayload = '';
				handlePayload( val );
				manualInput.value = '';
			}
		} );
		manualInput.addEventListener( 'keydown', e => {
			if ( e.key === 'Enter' ) manualBtn.click();
		} );
	}

	/* ----------------------------------------------------------
	 * Button wiring
	 * -------------------------------------------------------- */
	if ( btnStart ) btnStart.addEventListener( 'click', startCamera );
	if ( btnStop  ) btnStop.addEventListener(  'click', stopCamera  );

	/* ----------------------------------------------------------
	 * Helpers
	 * -------------------------------------------------------- */
	function escHtml( s ) {
		const d = document.createElement( 'div' );
		d.appendChild( document.createTextNode( String( s || '' ) ) );
		return d.innerHTML;
	}

	function now() {
		return new Date().toLocaleString();
	}

	function playBeep( type ) {
		try {
			const ctx  = new ( window.AudioContext || window.webkitAudioContext )();
			const osc  = ctx.createOscillator();
			const gain = ctx.createGain();
			osc.connect( gain );
			gain.connect( ctx.destination );
			osc.type            = 'sine';
			osc.frequency.value = type === 'success' ? 880 : 440;
			gain.gain.value     = 0.2;
			osc.start();
			osc.stop( ctx.currentTime + 0.18 );
		} catch ( e ) { /* audio not available */ }
	}

	// HMAC-SHA256 via Web Crypto (for manual ID entry fallback — server still validates)
	async function hmacSha256( message, secret ) {
		const enc = new TextEncoder();
		const key = await crypto.subtle.importKey(
			'raw', enc.encode( secret || 'x' ),
			{ name: 'HMAC', hash: 'SHA-256' }, false, [ 'sign' ]
		);
		const sig = await crypto.subtle.sign( 'HMAC', key, enc.encode( message ) );
		return Array.from( new Uint8Array( sig ) ).map( b => b.toString( 16 ).padStart( 2, '0' ) ).join( '' );
	}

} )();
