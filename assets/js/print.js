/**
 * CampTix Check-In — Badge printing
 *
 * "Print Badge" always opens the standalone badge page at /print with the
 * badge data as query parameters, so the badge is always visible.
 *
 * When a printer HTTP URL is configured in Settings, the badge page is
 * opened with autoprint=1: it sends the data straight to the printer URL and
 * automatically opens the browser print dialog if the printer cannot be
 * reached, so a printer connected to the device can still be used.
 *
 * Uses window.ctciConfig.printerUrl / printUrl (localized by the plugin).
 */
/* global ctciConfig */
( function () {
	'use strict';

	window.ctciPrinterWorking = false;

	function getFullPrinterUrl( baseUrl ) {
		if ( ! baseUrl ) return '';
		var parts = baseUrl.trim().split( '?' );
		var path = parts[0].replace( /\/+$/, '' );
		if ( ! path.endsWith( '/print' ) ) {
			path += '/print';
		}
		return path + ( parts[1] ? '?' + parts[1] : '' );
	}

	function updatePrinterStatusUI( status ) {
		var badges = document.querySelectorAll( '.ctci-printer-status' );
		badges.forEach( function ( badge ) {
			badge.style.display = 'inline-flex';
			badge.className = 'ctci-printer-status'; // Reset
			
			if ( status === 'checking' ) {
				badge.classList.add( 'ctci-printer-status-checking' );
				badge.innerHTML = '&#9679; Checking Printer...';
			} else if ( status === 'online' ) {
				badge.classList.add( 'ctci-printer-status-online' );
				badge.innerHTML = '&#9679; Printer Online';
			} else if ( status === 'offline' ) {
				badge.classList.add( 'ctci-printer-status-offline' );
				badge.innerHTML = '&#9679; Printer Offline';
			} else if ( status === 'empty' ) {
				if ( badge.id === 'ctci-settings-printer-status' ) {
					badge.classList.add( 'ctci-printer-status-empty' );
					badge.innerHTML = '&#9679; No Printer Configured';
				} else {
					badge.style.display = 'none';
				}
			} else {
				badge.style.display = 'none';
			}
		} );
	}

	function checkPrinterStatus( customUrl ) {
		var cfg = window.ctciConfig || {};
		var url = typeof customUrl === 'string' ? customUrl : ( cfg.printerUrl || '' );

		if ( ! url ) {
			window.ctciPrinterWorking = false;
			updatePrinterStatusUI( 'empty' );
			return;
		}

		var pingUrl = getFullPrinterUrl( url );
		updatePrinterStatusUI( 'checking' );

		var controller = new AbortController();
		var timer      = setTimeout( function () { controller.abort(); }, 2000 );

		fetch( pingUrl, {
			method: 'GET',
			mode: 'no-cors',
			cache: 'no-store',
			signal: controller.signal,
		} )
			.then( function () {
				clearTimeout( timer );
				window.ctciPrinterWorking = true;
				updatePrinterStatusUI( 'online' );
			} )
			.catch( function () {
				clearTimeout( timer );
				window.ctciPrinterWorking = false;
				updatePrinterStatusUI( 'offline' );
			} );
	}

	// Run initial status check on load
	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', function () {
			checkPrinterStatus();
		} );
	} else {
		checkPrinterStatus();
	}

	// Real-time settings input listener
	document.addEventListener( 'DOMContentLoaded', function () {
		var inputEl = document.getElementById( 'ctci_printer_url' );
		if ( inputEl ) {
			var debounceTimer = null;
			inputEl.addEventListener( 'input', function () {
				clearTimeout( debounceTimer );
				debounceTimer = setTimeout( function () {
					checkPrinterStatus( inputEl.value.trim() );
				}, 500 );
			} );
		}
	} );

	function buildParams( attendee ) {
		var params = new URLSearchParams();

		// Name is always sent.
		params.set( 'name', String( attendee.badge_name || attendee.name || '' ) );

		// "Regular" ticket displays as the generic "Attendee" label.
		var ticket = attendee.ticket || attendee.ticket_type;
		if ( ticket && String( ticket ).toLowerCase() === 'regular' ) {
			ticket = 'Attendee';
		}

		// Empty optional fields are omitted; website only when present.
		var optional = {
			company:           attendee.company,
			wordpress_username: attendee.wordpress_username,
			social:            attendee.social,
			meal_preference:   attendee.meal_preference,
			ticket_type:       ticket,
		};
		for ( var key in optional ) {
			if ( optional[ key ] ) {
				params.set( key, String( optional[ key ] ) );
			}
		}
		if ( attendee.website ) {
			params.set( 'website', String( attendee.website ) );
		}

		return params;
	}

	function joinQuery( base, params ) {
		if ( base.indexOf( '?' ) === -1 ) {
			return base + '?' + params;
		}
		if ( base.endsWith( '?' ) || base.endsWith( '&' ) ) {
			return base + params;
		}
		return base + '&' + params;
	}

	function buildPrinterUrl( attendee ) {
		var cfg      = window.ctciConfig || {};
		var printer  = cfg.printerUrl || '';
		var printUrl = cfg.printUrl || '';
		var params   = buildParams( attendee );

		// Printer override: send the data straight to the printer.
		if ( printer ) {
			return joinQuery( getFullPrinterUrl( printer ), params.toString() );
		}

		// Default: open the standalone badge page with the data in the query string.
		return joinQuery( printUrl, params.toString() );
	}

	/**
	 * Print a badge for an attendee.
	 *
	 * Always opens the standalone badge page so the badge is visible. When a
	 * printer URL is configured, autoprint=1 makes that page send the data to
	 * the printer and fall back to the browser print dialog if the printer
	 * cannot be reached.
	 *
	 * @param {Object} attendee Badge data for a single attendee.
	 */
	window.ctciPrintBadge = function ( attendee ) {
		var cfg    = window.ctciConfig || {};
		var params = buildParams( attendee || {} );

		// If a working printer is detected, send directly to the printer URL
		if ( window.ctciPrinterWorking && cfg.printerUrl ) {
			window.open( buildPrinterUrl( attendee ) );
		} else {
			// Otherwise open standalone page. With a printer URL configured,
			// auto-print makes that page send to printer and fall back to print dialog.
			if ( cfg.printerUrl ) {
				params.set( 'autoprint', '1' );
			}
			window.open( joinQuery( cfg.printUrl || '', params.toString() ) );
		}
	};

	window.ctciPrintUrlFor = function ( attendee ) {
		return buildPrinterUrl( attendee || {} );
	};
} )();
