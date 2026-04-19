<?php
/**
 * GitHub auto-updater for PBN Core.
 *
 * Hooks into WordPress's update API to check the GitHub releases endpoint
 * and notify / install new versions automatically.
 *
 * @package PBN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PBN_GITHUB_API_URL', 'https://api.github.com/repos/OppositeX/pbn/releases/latest' );
define( 'PBN_PLUGIN_SLUG',    'pbn-core/pbn-core.php' );

// ---------------------------------------------------------------------------
// Hooks
// ---------------------------------------------------------------------------
add_filter( 'pre_set_site_transient_update_plugins', 'pbn_check_for_update' );
add_filter( 'plugins_api',                           'pbn_plugins_api_info', 20, 3 );
add_action( 'upgrader_process_complete',             'pbn_after_update', 10, 2 );
add_action( 'admin_notices',                         'pbn_update_admin_notice' );
add_action( 'admin_init',                            'pbn_dismiss_update_notice' );

// ---------------------------------------------------------------------------
// Check for update
// ---------------------------------------------------------------------------

/**
 * Injected into `pre_set_site_transient_update_plugins`.
 * Fetches the latest GitHub release and, if newer, adds the plugin to the
 * transient so WordPress shows the update in the admin.
 *
 * @param  mixed $transient
 * @return mixed
 */
function pbn_check_for_update( $transient ) {
	if ( empty( $transient->checked ) ) {
		return $transient;
	}

	$release = pbn_fetch_latest_release();

	if ( is_wp_error( $release ) || empty( $release ) ) {
		return $transient;
	}

	$latest_version = ltrim( $release['tag_name'] ?? '', 'v' );

	if ( version_compare( $latest_version, PBN_VERSION, '>' ) ) {
		$transient->response[ PBN_PLUGIN_SLUG ] = (object) array(
			'slug'        => 'pbn-core',
			'plugin'      => PBN_PLUGIN_SLUG,
			'new_version' => $latest_version,
			'url'         => 'https://github.com/OppositeX/pbn',
			'package'     => $release['zipball_url'] ?? '',
			'icons'       => array(),
			'banners'     => array(),
			'tested'      => get_bloginfo( 'version' ),
			'requires_php' => '7.4',
		);

		// Cache the available version for the admin notice.
		update_option( 'pbn_available_update', $latest_version, false );
	} else {
		delete_option( 'pbn_available_update' );
	}

	update_option( 'pbn_last_update_check', current_time( 'c' ), false );

	return $transient;
}

// ---------------------------------------------------------------------------
// plugins_api — "View Details" in WP admin
// ---------------------------------------------------------------------------

/**
 * Populate plugin information modal with GitHub release data.
 *
 * @param  false|object|array $result
 * @param  string             $action
 * @param  object             $args
 * @return false|object
 */
function pbn_plugins_api_info( $result, $action, $args ) {
	if ( 'plugin_information' !== $action ) {
		return $result;
	}

	if ( ! isset( $args->slug ) || 'pbn-core' !== $args->slug ) {
		return $result;
	}

	$release = pbn_fetch_latest_release();

	if ( is_wp_error( $release ) || empty( $release ) ) {
		return $result;
	}

	$latest_version = ltrim( $release['tag_name'] ?? '', 'v' );

	return (object) array(
		'name'          => 'PBN Core',
		'slug'          => 'pbn-core',
		'version'       => $latest_version,
		'author'        => '<a href="https://github.com/OppositeX">OppositeX</a>',
		'homepage'      => 'https://github.com/OppositeX/pbn',
		'requires'      => '5.6',
		'tested'        => get_bloginfo( 'version' ),
		'requires_php'  => '7.4',
		'last_updated'  => $release['published_at'] ?? '',
		'sections'      => array(
			'description' => 'Central control plugin for PBN network — REST API, auto-updates, lead forms, and Polylang helpers.',
			'changelog'   => nl2br( esc_html( $release['body'] ?? '' ) ),
		),
		'download_link' => $release['zipball_url'] ?? '',
	);
}

// ---------------------------------------------------------------------------
// Post-update hook
// ---------------------------------------------------------------------------

/**
 * Fires after WordPress completes a plugin upgrade.
 * Clears cached update data for PBN Core.
 *
 * @param WP_Upgrader $upgrader
 * @param array       $hook_extra
 */
function pbn_after_update( $upgrader, $hook_extra ) {
	if ( empty( $hook_extra['plugins'] ) ) {
		return;
	}

	if ( in_array( PBN_PLUGIN_SLUG, (array) $hook_extra['plugins'], true ) ) {
		delete_option( 'pbn_available_update' );
		delete_option( 'pbn_last_update_check' );
		delete_site_transient( 'update_plugins' );
	}
}

// ---------------------------------------------------------------------------
// Admin notice
// ---------------------------------------------------------------------------

/**
 * Shows a dismissible admin notice when an update is available.
 */
function pbn_update_admin_notice() {
	if ( ! current_user_can( 'update_plugins' ) ) {
		return;
	}

	// Check if the notice has been dismissed.
	$dismissed = get_user_meta( get_current_user_id(), 'pbn_update_notice_dismissed', true );
	if ( $dismissed ) {
		return;
	}

	$available = get_option( 'pbn_available_update' );
	if ( ! $available ) {
		return;
	}

	$nonce = wp_create_nonce( 'pbn_dismiss_update_notice' );
	?>
	<div class="notice notice-warning is-dismissible pbn-update-notice">
		<p>
			<?php
			printf(
				/* translators: %1$s = new version number, %2$s = dismiss URL */
				wp_kses(
					__( '<strong>PBN Core</strong> version <strong>%1$s</strong> is available on GitHub. <a href="%2$s">Dismiss</a>', 'pbn-core' ),
					array( 'strong' => array(), 'a' => array( 'href' => array() ) )
				),
				esc_html( $available ),
				esc_url( admin_url( 'admin.php?pbn_dismiss_notice=1&_wpnonce=' . $nonce ) )
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Handles the dismiss action for the update notice.
 */
function pbn_dismiss_update_notice() {
	if ( empty( $_GET['pbn_dismiss_notice'] ) ) {
		return;
	}

	if ( ! isset( $_GET['_wpnonce'] ) || ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'pbn_dismiss_update_notice' ) ) {
		return;
	}

	update_user_meta( get_current_user_id(), 'pbn_update_notice_dismissed', 1 );
	wp_safe_redirect( admin_url() );
	exit;
}

// ---------------------------------------------------------------------------
// GitHub API fetch (cached for 12 hours)
// ---------------------------------------------------------------------------

/**
 * Fetches the latest GitHub release, with a 12-hour transient cache.
 *
 * @return array|WP_Error
 */
function pbn_fetch_latest_release() {
	$cached = get_transient( 'pbn_github_release' );
	if ( false !== $cached ) {
		return $cached;
	}

	$response = wp_remote_get( PBN_GITHUB_API_URL, array(
		'timeout'    => 15,
		'user-agent' => 'WordPress/PBN-Core-Updater/' . PBN_VERSION,
		'headers'    => array(
			'Accept' => 'application/vnd.github+json',
		),
	) );

	if ( is_wp_error( $response ) ) {
		return $response;
	}

	$code = wp_remote_retrieve_response_code( $response );
	if ( 200 !== (int) $code ) {
		return new WP_Error( 'pbn_github_error', 'GitHub API returned HTTP ' . $code );
	}

	$body = json_decode( wp_remote_retrieve_body( $response ), true );
	if ( empty( $body ) || ! is_array( $body ) ) {
		return new WP_Error( 'pbn_github_error', 'Invalid JSON from GitHub API.' );
	}

	set_transient( 'pbn_github_release', $body, 12 * HOUR_IN_SECONDS );

	return $body;
}
