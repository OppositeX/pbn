<?php
/**
 * Plugin Name: PBN Core
 * Plugin URI:  https://github.com/OppositeX/pbn
 * Description: Central control plugin for PBN network — REST API, auto-updates, lead forms, and Polylang helpers.
 * Version:     1.1.0
 * Author:      OppositeX
 * Author URI:  https://github.com/OppositeX
 * Text Domain: pbn-core
 * Domain Path: /languages
 * License:     GPL-2.0-or-later
 *
 * @package PBN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Constants
// ---------------------------------------------------------------------------
define( 'PBN_VERSION',    '1.1.0' );
define( 'PBN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'PBN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// ---------------------------------------------------------------------------
// Bootstrap includes
// ---------------------------------------------------------------------------
require_once PBN_PLUGIN_DIR . 'includes/polylang.php';
require_once PBN_PLUGIN_DIR . 'includes/lead-forms.php';
require_once PBN_PLUGIN_DIR . 'includes/updater.php';
require_once PBN_PLUGIN_DIR . 'includes/api.php';

// ---------------------------------------------------------------------------
// Activation / Deactivation
// ---------------------------------------------------------------------------

/**
 * Runs on plugin activation.
 * Generates a random API secret if one does not already exist.
 */
function pbn_activate() {
	if ( ! get_option( 'pbn_api_secret' ) ) {
		$secret = bin2hex( random_bytes( 16 ) ); // 32 hex chars
		update_option( 'pbn_api_secret', $secret, false );
	}

	// Ensure campaigns + leads containers exist.
	if ( false === get_option( 'pbn_campaigns' ) ) {
		update_option( 'pbn_campaigns', array(), false );
	}
	if ( false === get_option( 'pbn_leads' ) ) {
		update_option( 'pbn_leads', array(), false );
	}

	flush_rewrite_rules();
}
register_activation_hook( __FILE__, 'pbn_activate' );

/**
 * Runs on plugin deactivation.
 */
function pbn_deactivate() {
	flush_rewrite_rules();
}
register_deactivation_hook( __FILE__, 'pbn_deactivate' );
