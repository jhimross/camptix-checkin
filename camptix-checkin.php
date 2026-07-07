<?php
/**
 * Plugin Name:       CampTix Check-In
 * Plugin URI:        https://github.com/jhimross/camptix-checkin
 * Description:       QR-code check-in system for CampTix: generate attendee QR codes, scan them on arrival, and print name badges.
 * Version:           1.0.0
 * Author:            Jhimross
 * License:           GPL-2.0-or-later
 * Text Domain:       camptix-checkin
 */

defined( 'ABSPATH' ) || exit;

define( 'CTCI_VERSION',     '1.0.0' );
define( 'CTCI_PLUGIN_DIR',  plugin_dir_path( __FILE__ ) );
define( 'CTCI_PLUGIN_URL',  plugin_dir_url( __FILE__ ) );
define( 'CTCI_SECRET_KEY',  defined( 'AUTH_KEY' ) ? AUTH_KEY : 'camptix-checkin-fallback-secret' );

/* ---------------------------------------------------------------
 * Autoload includes
 * ------------------------------------------------------------- */
require_once CTCI_PLUGIN_DIR . 'includes/class-qr-generator.php';
require_once CTCI_PLUGIN_DIR . 'includes/class-attendee-cpt.php';
require_once CTCI_PLUGIN_DIR . 'includes/class-wordcamp-sync.php';
require_once CTCI_PLUGIN_DIR . 'includes/class-checkin-api.php';
require_once CTCI_PLUGIN_DIR . 'includes/class-badge-print.php';
require_once CTCI_PLUGIN_DIR . 'includes/class-badge-endpoint.php';
require_once CTCI_PLUGIN_DIR . 'includes/class-admin-ui.php';
require_once CTCI_PLUGIN_DIR . 'includes/class-email.php';

/* ---------------------------------------------------------------
 * Activation / Deactivation
 * ------------------------------------------------------------- */
register_activation_hook( __FILE__, 'ctci_activate' );
function ctci_activate() {
	add_option( 'ctci_checkin_meta_key', 'camptix_checkin_time' );
	// Register the badge rewrite rule and flush.
	$endpoint = new CTCI_Badge_Endpoint();
	$endpoint->add_rewrite_rule();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'ctci_deactivate' );
function ctci_deactivate() {
	// Nothing destructive — leave attendee meta intact.
}
