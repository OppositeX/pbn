<?php
/**
 * REST API endpoints for PBN Core.
 *
 * Namespace : pbn/v1
 * Auth      : Bearer token matching wp_option `pbn_api_secret`
 *
 * @package PBN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Registration
// ---------------------------------------------------------------------------
add_action( 'rest_api_init', 'pbn_register_routes' );

function pbn_register_routes() {

	$ns = 'pbn/v1';

	// -- Status ---------------------------------------------------------------
	register_rest_route( $ns, '/status', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_status',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Posts ----------------------------------------------------------------
	register_rest_route( $ns, '/posts', array(
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'pbn_route_list_posts',
			'permission_callback' => 'pbn_check_auth',
		),
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'pbn_route_create_post',
			'permission_callback' => 'pbn_check_auth',
		),
	) );

	register_rest_route( $ns, '/posts/(?P<id>\d+)', array(
		array(
			'methods'             => WP_REST_Server::EDITABLE,   // PUT|PATCH
			'callback'            => 'pbn_route_update_post',
			'permission_callback' => 'pbn_check_auth',
		),
		array(
			'methods'             => WP_REST_Server::DELETABLE,
			'callback'            => 'pbn_route_delete_post',
			'permission_callback' => 'pbn_check_auth',
		),
	) );

	// -- Campaigns ------------------------------------------------------------
	register_rest_route( $ns, '/campaigns', array(
		array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'pbn_route_list_campaigns',
			'permission_callback' => 'pbn_check_auth',
		),
		array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'pbn_route_create_campaign',
			'permission_callback' => 'pbn_check_auth',
		),
	) );

	register_rest_route( $ns, '/campaigns/(?P<id>[a-zA-Z0-9_-]+)', array(
		'methods'             => WP_REST_Server::DELETABLE,
		'callback'            => 'pbn_route_delete_campaign',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Site config ----------------------------------------------------------
	register_rest_route( $ns, '/site-config', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_site_config',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Trigger update -------------------------------------------------------
	register_rest_route( $ns, '/trigger-update', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_trigger_update',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Lead capture (public, no auth) ---------------------------------------
	register_rest_route( $ns, '/lead-capture', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_lead_capture',
		'permission_callback' => '__return_true',
	) );

	// -- Plugin Management ---------------------------------------------------
	register_rest_route( $ns, '/plugins', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_list_plugins',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/plugins/activate', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_activate_plugin',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/plugins/deactivate', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_deactivate_plugin',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/plugins/update', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_update_plugin',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/plugins/install', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_install_plugin',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/plugins', array(
		'methods'             => WP_REST_Server::DELETABLE,
		'callback'            => 'pbn_route_delete_plugin',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- WordPress Core -------------------------------------------------------
	register_rest_route( $ns, '/core/update', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_update_core',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/core/updates', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_check_core_updates',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Health & Errors --------------------------------------------------
	register_rest_route( $ns, '/health', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_health_check',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/error-log', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_error_log',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Cache ----------------------------------------------------------------
	register_rest_route( $ns, '/cache/clear', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_clear_cache',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Options ----------------------------------------------------------
	register_rest_route( $ns, '/options', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_get_options',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/options', array(
		'methods'             => WP_REST_Server::EDITABLE,
		'callback'            => 'pbn_route_update_options',
		'permission_callback' => 'pbn_check_auth',
	) );

	// -- Maintenance ----------------------------------------------------------
	register_rest_route( $ns, '/maintenance', array(
		'methods'             => WP_REST_Server::READABLE,
		'callback'            => 'pbn_route_get_maintenance',
		'permission_callback' => 'pbn_check_auth',
	) );

	register_rest_route( $ns, '/maintenance', array(
		'methods'             => WP_REST_Server::CREATABLE,
		'callback'            => 'pbn_route_set_maintenance',
		'permission_callback' => 'pbn_check_auth',
	) );
}

// ---------------------------------------------------------------------------
// Auth helper
// ---------------------------------------------------------------------------

/**
 * Validates the Bearer token in the Authorization header.
 *
 * @param WP_REST_Request $request
 * @return bool|WP_Error
 */
function pbn_check_auth( WP_REST_Request $request ) {
	$header = $request->get_header( 'Authorization' );

	if ( empty( $header ) ) {
		return new WP_Error( 'pbn_unauthorized', 'Missing Authorization header.', array( 'status' => 401 ) );
	}

	if ( ! preg_match( '/^Bearer\s+(.+)$/i', $header, $matches ) ) {
		return new WP_Error( 'pbn_unauthorized', 'Invalid Authorization format. Use: Bearer <token>', array( 'status' => 401 ) );
	}

	$provided = trim( $matches[1] );
	$stored   = (string) get_option( 'pbn_api_secret', '' );

	if ( ! hash_equals( $stored, $provided ) ) {
		return new WP_Error( 'pbn_forbidden', 'Invalid API secret.', array( 'status' => 403 ) );
	}

	return true;
}

// ---------------------------------------------------------------------------
// Endpoint: GET /status
// ---------------------------------------------------------------------------
function pbn_route_status( WP_REST_Request $request ) {
	$active = get_option( 'active_plugins', array() );

	return rest_ensure_response( array(
		'url'                  => get_site_url(),
		'name'                 => get_bloginfo( 'name' ),
		'wp_version'           => get_bloginfo( 'version' ),
		'pbn_version'          => PBN_VERSION,
		'php_version'          => PHP_VERSION,
		'active_plugins_count' => count( $active ),
		'language'             => get_bloginfo( 'language' ),
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: GET /posts
// ---------------------------------------------------------------------------
function pbn_route_list_posts( WP_REST_Request $request ) {
	$status   = sanitize_text_field( $request->get_param( 'status' ) ?: 'publish' );
	$per_page = min( 100, absint( $request->get_param( 'per_page' ) ?: 10 ) );
	$page     = max( 1, absint( $request->get_param( 'page' ) ?: 1 ) );
	$lang     = sanitize_text_field( $request->get_param( 'lang' ) ?: '' );

	$args = array(
		'post_type'      => 'post',
		'post_status'    => $status,
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'no_found_rows'  => false,
	);

	if ( $lang && function_exists( 'pbn_get_languages' ) ) {
		$args['lang'] = $lang; // Polylang respects this query var
	}

	$query = new WP_Query( $args );
	$posts = array();

	foreach ( $query->posts as $post ) {
		$posts[] = pbn_format_post( $post );
	}

	return rest_ensure_response( array(
		'posts'       => $posts,
		'total'       => (int) $query->found_posts,
		'total_pages' => (int) $query->max_num_pages,
		'page'        => $page,
		'per_page'    => $per_page,
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: POST /posts
// ---------------------------------------------------------------------------
function pbn_route_create_post( WP_REST_Request $request ) {
	$body = $request->get_json_params();
	if ( empty( $body ) ) {
		$body = $request->get_params();
	}

	$post_data = pbn_build_post_array( $body );

	// Validate required fields.
	if ( empty( $post_data['post_title'] ) ) {
		return new WP_Error( 'pbn_bad_request', 'Title is required.', array( 'status' => 400 ) );
	}

	$post_id = wp_insert_post( $post_data, true );
	if ( is_wp_error( $post_id ) ) {
		return $post_id;
	}

	pbn_save_post_meta( $post_id, $body );

	// Polylang language assignment.
	if ( ! empty( $body['lang'] ) ) {
		pbn_set_post_language( $post_id, sanitize_text_field( $body['lang'] ) );
	}

	return rest_ensure_response( array(
		'post_id' => $post_id,
		'post'    => pbn_format_post( get_post( $post_id ) ),
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: PUT /posts/{id}
// ---------------------------------------------------------------------------
function pbn_route_update_post( WP_REST_Request $request ) {
	$post_id = absint( $request->get_param( 'id' ) );
	$post    = get_post( $post_id );

	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'pbn_not_found', 'Post not found.', array( 'status' => 404 ) );
	}

	$body = $request->get_json_params();
	if ( empty( $body ) ) {
		$body = $request->get_params();
	}

	$post_data         = pbn_build_post_array( $body );
	$post_data['ID']   = $post_id;

	$result = wp_update_post( $post_data, true );
	if ( is_wp_error( $result ) ) {
		return $result;
	}

	pbn_save_post_meta( $post_id, $body );

	if ( ! empty( $body['lang'] ) ) {
		pbn_set_post_language( $post_id, sanitize_text_field( $body['lang'] ) );
	}

	return rest_ensure_response( array(
		'post_id' => $post_id,
		'post'    => pbn_format_post( get_post( $post_id ) ),
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: DELETE /posts/{id}
// ---------------------------------------------------------------------------
function pbn_route_delete_post( WP_REST_Request $request ) {
	$post_id = absint( $request->get_param( 'id' ) );
	$post    = get_post( $post_id );

	if ( ! $post || 'post' !== $post->post_type ) {
		return new WP_Error( 'pbn_not_found', 'Post not found.', array( 'status' => 404 ) );
	}

	// Soft delete — move to trash.
	$result = wp_trash_post( $post_id );
	if ( ! $result ) {
		return new WP_Error( 'pbn_error', 'Failed to trash post.', array( 'status' => 500 ) );
	}

	return rest_ensure_response( array(
		'deleted' => true,
		'post_id' => $post_id,
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: GET /campaigns
// ---------------------------------------------------------------------------
function pbn_route_list_campaigns( WP_REST_Request $request ) {
	$campaigns = get_option( 'pbn_campaigns', array() );
	return rest_ensure_response( array( 'campaigns' => array_values( $campaigns ) ) );
}

// ---------------------------------------------------------------------------
// Endpoint: POST /campaigns
// ---------------------------------------------------------------------------
function pbn_route_create_campaign( WP_REST_Request $request ) {
	$body = $request->get_json_params();
	if ( empty( $body ) ) {
		$body = $request->get_params();
	}

	$required = array( 'id', 'name', 'email_destination', 'form_title', 'form_cta' );
	foreach ( $required as $field ) {
		if ( empty( $body[ $field ] ) ) {
			return new WP_Error( 'pbn_bad_request', "Field '{$field}' is required.", array( 'status' => 400 ) );
		}
	}

	$campaign_id = sanitize_key( $body['id'] );
	$campaigns   = get_option( 'pbn_campaigns', array() );

	if ( isset( $campaigns[ $campaign_id ] ) ) {
		return new WP_Error( 'pbn_conflict', 'A campaign with this ID already exists.', array( 'status' => 409 ) );
	}

	$campaign = array(
		'id'                => $campaign_id,
		'name'              => sanitize_text_field( $body['name'] ),
		'email_destination' => sanitize_email( $body['email_destination'] ),
		'form_title'        => sanitize_text_field( $body['form_title'] ),
		'form_cta'          => sanitize_text_field( $body['form_cta'] ),
		'created_at'        => current_time( 'c' ),
	);

	$campaigns[ $campaign_id ] = $campaign;
	update_option( 'pbn_campaigns', $campaigns, false );

	return rest_ensure_response( array(
		'campaign' => $campaign,
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: DELETE /campaigns/{id}
// ---------------------------------------------------------------------------
function pbn_route_delete_campaign( WP_REST_Request $request ) {
	$campaign_id = sanitize_key( $request->get_param( 'id' ) );
	$campaigns   = get_option( 'pbn_campaigns', array() );

	if ( ! isset( $campaigns[ $campaign_id ] ) ) {
		return new WP_Error( 'pbn_not_found', 'Campaign not found.', array( 'status' => 404 ) );
	}

	unset( $campaigns[ $campaign_id ] );
	update_option( 'pbn_campaigns', $campaigns, false );

	return rest_ensure_response( array( 'deleted' => true, 'id' => $campaign_id ) );
}

// ---------------------------------------------------------------------------
// Endpoint: GET /site-config
// ---------------------------------------------------------------------------
function pbn_route_site_config( WP_REST_Request $request ) {
	$secret = (string) get_option( 'pbn_api_secret', '' );
	$masked = ! empty( $secret )
		? substr( $secret, 0, 4 ) . str_repeat( '*', max( 0, strlen( $secret ) - 8 ) ) . substr( $secret, -4 )
		: '';

	$languages = array();
	if ( function_exists( 'pbn_get_languages' ) ) {
		$languages = pbn_get_languages();
	}

	return rest_ensure_response( array(
		'site_url'       => get_site_url(),
		'site_name'      => get_bloginfo( 'name' ),
		'api_secret'     => $masked,
		'update_channel' => 'github',
		'last_update_check' => get_option( 'pbn_last_update_check', null ),
		'pbn_version'    => PBN_VERSION,
		'wp_version'     => get_bloginfo( 'version' ),
		'languages'      => $languages,
		'campaigns_count' => count( get_option( 'pbn_campaigns', array() ) ),
		'leads_count'    => count( get_option( 'pbn_leads', array() ) ),
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: POST /trigger-update
// ---------------------------------------------------------------------------
function pbn_route_trigger_update( WP_REST_Request $request ) {
	// Delete the cached transient to force a fresh check.
	delete_site_transient( 'update_plugins' );
	delete_option( 'pbn_last_update_check' );

	// Run the check synchronously.
	$result = pbn_check_for_update( get_site_transient( 'update_plugins' ) );

	return rest_ensure_response( array(
		'triggered'  => true,
		'checked_at' => current_time( 'c' ),
	) );
}

// ---------------------------------------------------------------------------
// Endpoint: POST /lead-capture  (public)
// ---------------------------------------------------------------------------
function pbn_route_lead_capture( WP_REST_Request $request ) {
	$body = $request->get_json_params();
	if ( empty( $body ) ) {
		$body = $request->get_params();
	}

	// Required fields.
	if ( empty( $body['name'] ) || empty( $body['email'] ) || empty( $body['campaign_id'] ) ) {
		return new WP_Error( 'pbn_bad_request', 'name, email and campaign_id are required.', array( 'status' => 400 ) );
	}

	if ( ! is_email( $body['email'] ) ) {
		return new WP_Error( 'pbn_bad_request', 'Invalid email address.', array( 'status' => 400 ) );
	}

	$campaign_id = sanitize_key( $body['campaign_id'] );
	$campaigns   = get_option( 'pbn_campaigns', array() );

	if ( ! isset( $campaigns[ $campaign_id ] ) ) {
		return new WP_Error( 'pbn_not_found', 'Campaign not found.', array( 'status' => 404 ) );
	}

	$campaign = $campaigns[ $campaign_id ];

	$lead = array(
		'id'          => wp_generate_uuid4(),
		'name'        => sanitize_text_field( $body['name'] ),
		'email'       => sanitize_email( $body['email'] ),
		'phone'       => sanitize_text_field( $body['phone'] ?? '' ),
		'campaign_id' => $campaign_id,
		'post_id'     => absint( $body['post_id'] ?? 0 ),
		'post_url'    => esc_url_raw( $body['post_url'] ?? '' ),
		'captured_at' => current_time( 'c' ),
	);

	// Store lead (FIFO, max 500).
	$leads = get_option( 'pbn_leads', array() );
	array_unshift( $leads, $lead );
	if ( count( $leads ) > 500 ) {
		$leads = array_slice( $leads, 0, 500 );
	}
	update_option( 'pbn_leads', $leads, false );

	// Send notification email.
	$subject = sprintf( '[PBN Lead] %s — %s', $campaign['name'], $lead['name'] );
	$message = sprintf(
		"New lead captured on %s\n\nCampaign: %s\nName: %s\nEmail: %s\nPhone: %s\nPost: %s\nCaptured: %s",
		get_site_url(),
		$campaign['name'],
		$lead['name'],
		$lead['email'],
		$lead['phone'] ?: 'N/A',
		$lead['post_url'] ?: 'N/A',
		$lead['captured_at']
	);
	wp_mail( $campaign['email_destination'], $subject, $message );

	return rest_ensure_response( array(
		'success' => true,
		'lead_id' => $lead['id'],
	) );
}

// =========================================================================
// NEW ENDPOINTS: Plugin Management
// =========================================================================

/**
 * GET /pbn/v1/plugins — List all installed plugins
 */
function pbn_route_list_plugins( WP_REST_Request $request ) {
	try {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$all_plugins = get_plugins();
		$active_list = get_option( 'active_plugins', array() );
		$updates_data = get_site_transient( 'update_plugins' );

		$plugins = array();
		foreach ( $all_plugins as $plugin_path => $plugin_data ) {
			$slug = dirname( $plugin_path );
			if ( '.' === $slug ) {
				$slug = basename( $plugin_path, '.php' );
			}

			$is_active = in_array( $plugin_path, $active_list, true );
			$update_available = false;
			$new_version = $plugin_data['Version'];

			if ( $updates_data && isset( $updates_data->response[ $plugin_path ] ) ) {
				$update_available = true;
				$new_version = $updates_data->response[ $plugin_path ]->new_version;
			}

			$plugins[] = array(
				'slug'              => $slug,
				'name'              => $plugin_data['Name'],
				'version'           => $plugin_data['Version'],
				'status'            => $is_active ? 'active' : 'inactive',
				'update_available'  => $update_available,
				'new_version'       => $new_version,
				'description'       => $plugin_data['Description'],
				'author'            => $plugin_data['Author'],
			);
		}

		return rest_ensure_response( array(
			'plugins' => $plugins,
			'total'   => count( $plugins ),
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * POST /pbn/v1/plugins/activate — Activate a plugin
 * Body: {slug}
 */
function pbn_route_activate_plugin( WP_REST_Request $request ) {
	try {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		if ( empty( $body['slug'] ) ) {
			return new WP_Error( 'pbn_bad_request', 'slug is required.', array( 'status' => 400 ) );
		}

		$slug = sanitize_text_field( $body['slug'] );
		$plugin_path = pbn_get_plugin_path( $slug );

		if ( ! $plugin_path ) {
			return new WP_Error( 'pbn_not_found', 'Plugin not found.', array( 'status' => 404 ) );
		}

		$result = activate_plugin( $plugin_path );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array(
			'success' => true,
			'slug'    => $slug,
			'status'  => 'active',
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * POST /pbn/v1/plugins/deactivate — Deactivate a plugin
 * Body: {slug}
 */
function pbn_route_deactivate_plugin( WP_REST_Request $request ) {
	try {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		if ( empty( $body['slug'] ) ) {
			return new WP_Error( 'pbn_bad_request', 'slug is required.', array( 'status' => 400 ) );
		}

		$slug = sanitize_text_field( $body['slug'] );
		$plugin_path = pbn_get_plugin_path( $slug );

		if ( ! $plugin_path ) {
			return new WP_Error( 'pbn_not_found', 'Plugin not found.', array( 'status' => 404 ) );
		}

		deactivate_plugins( $plugin_path );

		return rest_ensure_response( array(
			'success' => true,
			'slug'    => $slug,
			'status'  => 'inactive',
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * POST /pbn/v1/plugins/update — Update plugins
 * Body: {slug} or {slug: "all"}
 */
function pbn_route_update_plugin( WP_REST_Request $request ) {
	try {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		if ( empty( $body['slug'] ) ) {
			return new WP_Error( 'pbn_bad_request', 'slug is required.', array( 'status' => 400 ) );
		}

		$slug = sanitize_text_field( $body['slug'] );
		$updated = array();
		$failed = array();
		$errors = array();

		if ( 'all' === $slug ) {
			// Update all plugins with available updates
			$updates_data = get_site_transient( 'update_plugins' );
			if ( $updates_data && ! empty( $updates_data->response ) ) {
				foreach ( array_keys( $updates_data->response ) as $plugin_path ) {
					$result = pbn_upgrade_single_plugin( $plugin_path );
					if ( is_wp_error( $result ) ) {
						$slug_name = dirname( $plugin_path );
						$failed[] = $slug_name;
						$errors[ $slug_name ] = $result->get_error_message();
					} else {
						$updated[] = $result;
					}
				}
			}
		} else {
			// Update single plugin
			$plugin_path = pbn_get_plugin_path( $slug );
			if ( ! $plugin_path ) {
				return new WP_Error( 'pbn_not_found', 'Plugin not found.', array( 'status' => 404 ) );
			}

			$result = pbn_upgrade_single_plugin( $plugin_path );
			if ( is_wp_error( $result ) ) {
				$failed[] = $slug;
				$errors[ $slug ] = $result->get_error_message();
			} else {
				$updated[] = $result;
			}
		}

		return rest_ensure_response( array(
			'updated' => $updated,
			'failed'  => $failed,
			'errors'  => $errors,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * POST /pbn/v1/plugins/install — Install a plugin
 * Body: {source, activate: bool (optional)}
 * source: wp.org slug OR full URL to zip
 */
function pbn_route_install_plugin( WP_REST_Request $request ) {
	try {
		if ( ! class_exists( 'Plugin_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}
		if ( ! function_exists( 'plugins_api' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
		}

		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		if ( empty( $body['source'] ) ) {
			return new WP_Error( 'pbn_bad_request', 'source is required.', array( 'status' => 400 ) );
		}

		$source = sanitize_text_field( $body['source'] );
		$activate = isset( $body['activate'] ) ? (bool) $body['activate'] : false;

		$download_url = $source;

		// If source is a slug, fetch the plugin info from wp.org
		if ( ! preg_match( '#^https?://#', $source ) ) {
			$api = plugins_api( 'plugin_information', array( 'slug' => $source ) );
			if ( is_wp_error( $api ) ) {
				return $api;
			}
			$download_url = $api->download_link;
		}

		$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );
		$result = $upgrader->install( $download_url );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $activate && $result ) {
			// Get the plugin path from the result
			$plugin_path = pbn_get_plugin_path_from_zip( $download_url );
			if ( $plugin_path ) {
				activate_plugin( $plugin_path );
			}
		}

		return rest_ensure_response( array(
			'success'  => true,
			'source'   => $source,
			'activated' => $activate,
			'destination' => $result,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * DELETE /pbn/v1/plugins — Delete a plugin
 * Body: {slug}
 */
function pbn_route_delete_plugin( WP_REST_Request $request ) {
	try {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		if ( empty( $body['slug'] ) ) {
			return new WP_Error( 'pbn_bad_request', 'slug is required.', array( 'status' => 400 ) );
		}

		$slug = sanitize_text_field( $body['slug'] );
		$plugin_path = pbn_get_plugin_path( $slug );

		if ( ! $plugin_path ) {
			return new WP_Error( 'pbn_not_found', 'Plugin not found.', array( 'status' => 404 ) );
		}

		// Deactivate first
		deactivate_plugins( $plugin_path );

		// Delete
		$result = delete_plugins( array( $plugin_path ) );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return rest_ensure_response( array(
			'deleted' => true,
			'slug'    => $slug,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

// =========================================================================
// NEW ENDPOINTS: WordPress Core
// =========================================================================

/**
 * POST /pbn/v1/core/update — Update WordPress core
 */
function pbn_route_update_core( WP_REST_Request $request ) {
	try {
		if ( ! class_exists( 'Core_Upgrader' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
		}

		$from_version = get_bloginfo( 'version' );
		$upgrader = new Core_Upgrader( new WP_Ajax_Upgrader_Skin() );
		$result = $upgrader->upgrade();

		if ( is_wp_error( $result ) ) {
			return rest_ensure_response( array(
				'updated'     => false,
				'from_version' => $from_version,
				'to_version'  => $from_version,
				'error'       => $result->get_error_message(),
			) );
		}

		$to_version = get_bloginfo( 'version' );

		return rest_ensure_response( array(
			'updated'      => true,
			'from_version' => $from_version,
			'to_version'   => $to_version,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * GET /pbn/v1/core/updates — Check if WordPress core update is available
 */
function pbn_route_check_core_updates( WP_REST_Request $request ) {
	try {
		$current_version = get_bloginfo( 'version' );
		$updates = get_core_updates();

		$latest_version = $current_version;
		$needs_update = false;

		if ( ! empty( $updates ) ) {
			// First item is the latest available version
			$latest = array_shift( $updates );
			$latest_version = $latest->version;
			$needs_update = version_compare( $current_version, $latest_version, '<' );
		}

		return rest_ensure_response( array(
			'current'       => $current_version,
			'latest'        => $latest_version,
			'needs_update'  => $needs_update,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

// =========================================================================
// NEW ENDPOINTS: Health & Errors
// =========================================================================

/**
 * GET /pbn/v1/health — Comprehensive health check
 */
function pbn_route_health_check( WP_REST_Request $request ) {
	try {
		$active_plugins = get_option( 'active_plugins', array() );
		$all_plugins = array();

		if ( function_exists( 'get_plugins' ) ) {
			$all_plugins = get_plugins();
		}

		$inactive_count = count( $all_plugins ) - count( $active_plugins );

		// Get disk free space
		$disk_free_mb = floor( disk_free_space( ABSPATH ) / 1024 / 1024 );

		// Get memory info
		$memory_limit = wp_convert_hr_to_bytes( WP_MEMORY_LIMIT );
		$memory_usage = memory_get_usage( true );

		// Get MySQL version
		global $wpdb;
		$mysql_version = $wpdb->db_version();

		// Get PHP version
		$php_version = PHP_VERSION;

		// Get WordPress version
		$wp_version = get_bloginfo( 'version' );

		// Get last errors from debug.log
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';
		$last_errors = array();
		if ( file_exists( $debug_log_path ) ) {
			$lines = file( $debug_log_path );
			$last_errors = array_slice( array_filter( $lines ), -5 );
		}

		// Get update counts
		$updates_core = 0;
		$updates_plugins = 0;
		$updates_themes = 0;

		$core_updates = get_core_updates();
		if ( ! empty( $core_updates ) ) {
			$updates_core = 1;
		}

		if ( function_exists( 'get_plugins' ) ) {
			$plugin_updates = get_site_transient( 'update_plugins' );
			if ( $plugin_updates && ! empty( $plugin_updates->response ) ) {
				$updates_plugins = count( $plugin_updates->response );
			}
		}

		$theme_updates = get_site_transient( 'update_themes' );
		if ( $theme_updates && ! empty( $theme_updates->response ) ) {
			$updates_themes = count( $theme_updates->response );
		}

		return rest_ensure_response( array(
			'wp_version'       => $wp_version,
			'php_version'      => $php_version,
			'mysql_version'    => $mysql_version,
			'active_plugins'   => count( $active_plugins ),
			'inactive_plugins' => $inactive_count,
			'disk_free_mb'     => $disk_free_mb,
			'last_errors'      => array_map( 'trim', $last_errors ),
			'memory_limit_mb'  => round( $memory_limit / 1024 / 1024 ),
			'memory_usage_mb'  => round( $memory_usage / 1024 / 1024 ),
			'is_multisite'     => is_multisite(),
			'update_counts'    => array(
				'core'   => $updates_core,
				'plugins' => $updates_plugins,
				'themes'  => $updates_themes,
			),
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * GET /pbn/v1/error-log — Get error log lines
 * Query params: ?lines=50 (default 50, max 200)
 */
function pbn_route_error_log( WP_REST_Request $request ) {
	try {
		$lines_param = min( 200, absint( $request->get_param( 'lines' ) ?: 50 ) );
		$debug_log_path = WP_CONTENT_DIR . '/debug.log';

		if ( ! file_exists( $debug_log_path ) ) {
			return rest_ensure_response( array(
				'lines' => array(),
				'total' => 0,
			) );
		}

		$lines = file( $debug_log_path );
		$lines = array_filter( $lines );
		$lines = array_slice( $lines, -$lines_param );

		return rest_ensure_response( array(
			'lines' => array_map( 'trim', $lines ),
			'total' => count( $lines ),
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

// =========================================================================
// NEW ENDPOINTS: Cache
// =========================================================================

/**
 * POST /pbn/v1/cache/clear — Clear all known caches
 */
function pbn_route_clear_cache( WP_REST_Request $request ) {
	try {
		$cleared = array();

		// WordPress object cache
		if ( wp_cache_flush() ) {
			$cleared[] = 'wp_object_cache';
		}

		// WP Super Cache
		if ( function_exists( 'wp_cache_clear_cache' ) ) {
			wp_cache_clear_cache();
			$cleared[] = 'wp_super_cache';
		}

		// W3 Total Cache
		if ( function_exists( 'w3tc_flush_all' ) ) {
			w3tc_flush_all();
			$cleared[] = 'w3_total_cache';
		}

		// WP Rocket
		if ( function_exists( 'rocket_clean_domain' ) ) {
			do_action( 'rocket_clean_domain' );
			$cleared[] = 'wp_rocket';
		}

		// Elementor CSS cache
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$elementor = \Elementor\Plugin::$instance;
			if ( $elementor && isset( $elementor->files_manager ) ) {
				$elementor->files_manager->clear_cache();
				$cleared[] = 'elementor_css';
			}
		}

		// LiteSpeed Cache
		if ( class_exists( 'LiteSpeed_Cache' ) ) {
			do_action( 'litespeed_purge_all' );
			$cleared[] = 'litespeed_cache';
		}

		return rest_ensure_response( array(
			'cleared' => $cleared,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

// =========================================================================
// NEW ENDPOINTS: Options
// =========================================================================

/**
 * GET /pbn/v1/options — Get key site options
 */
function pbn_route_get_options( WP_REST_Request $request ) {
	try {
		$active_plugins = get_option( 'active_plugins', array() );

		return rest_ensure_response( array(
			'blogname'             => get_option( 'blogname' ),
			'blogdescription'      => get_option( 'blogdescription' ),
			'siteurl'              => get_option( 'siteurl' ),
			'home'                 => get_option( 'home' ),
			'timezone_string'      => get_option( 'timezone_string' ),
			'date_format'          => get_option( 'date_format' ),
			'time_format'          => get_option( 'time_format' ),
			'language'             => get_option( 'WPLANG' ),
			'admin_email'          => get_option( 'admin_email' ),
			'active_plugins_count' => count( $active_plugins ),
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * PATCH /pbn/v1/options — Update allowed options
 * Whitelist: blogname, blogdescription, timezone_string, date_format, time_format, language, admin_email
 */
function pbn_route_update_options( WP_REST_Request $request ) {
	try {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		$whitelist = array(
			'blogname',
			'blogdescription',
			'timezone_string',
			'date_format',
			'time_format',
			'language',
			'admin_email',
		);

		$updated = array();
		foreach ( $whitelist as $option_key ) {
			if ( isset( $body[ $option_key ] ) ) {
				$value = $body[ $option_key ];

				// Sanitize based on option type
				if ( 'admin_email' === $option_key ) {
					$value = sanitize_email( $value );
					if ( ! is_email( $value ) ) {
						continue;
					}
				} elseif ( 'timezone_string' === $option_key ) {
					$value = sanitize_text_field( $value );
				} else {
					$value = sanitize_text_field( $value );
				}

				update_option( $option_key, $value );
				$updated[ $option_key ] = $value;
			}
		}

		return rest_ensure_response( array(
			'updated' => $updated,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

// =========================================================================
// NEW ENDPOINTS: Maintenance
// =========================================================================

/**
 * GET /pbn/v1/maintenance — Check if maintenance mode is active
 */
function pbn_route_get_maintenance( WP_REST_Request $request ) {
	try {
		$maintenance_file = ABSPATH . '.maintenance';
		$is_active = file_exists( $maintenance_file );

		return rest_ensure_response( array(
			'enabled' => $is_active,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

/**
 * POST /pbn/v1/maintenance — Enable/disable maintenance mode
 * Body: {enabled: true/false}
 */
function pbn_route_set_maintenance( WP_REST_Request $request ) {
	try {
		$body = $request->get_json_params();
		if ( empty( $body ) ) {
			$body = $request->get_params();
		}

		if ( ! isset( $body['enabled'] ) ) {
			return new WP_Error( 'pbn_bad_request', 'enabled is required.', array( 'status' => 400 ) );
		}

		$enabled = (bool) $body['enabled'];
		$maintenance_file = ABSPATH . '.maintenance';

		if ( $enabled ) {
			// Create maintenance file
			$maint_content = "<?php\ndefine( 'ABSPATH', '" . ABSPATH . "' );\nrequire ABSPATH . 'wp-load.php';\nwp_die( 'Briefly unavailable for scheduled maintenance. Check back in a minute.' );\n";
			file_put_contents( $maintenance_file, $maint_content );
		} else {
			// Delete maintenance file
			if ( file_exists( $maintenance_file ) ) {
				unlink( $maintenance_file );
			}
		}

		return rest_ensure_response( array(
			'enabled' => $enabled,
		) );
	} catch ( Exception $e ) {
		return new WP_Error( 'pbn_error', $e->getMessage(), array( 'status' => 500 ) );
	}
}

// =========================================================================
// Helpers
// =========================================================================

/**
 * Builds a wp_insert_post / wp_update_post compatible array from API body.
 *
 * @param array $body
 * @return array
 */
function pbn_build_post_array( array $body ) {
	$data = array(
		'post_type'   => 'post',
		'post_status' => ! empty( $body['status'] ) ? sanitize_text_field( $body['status'] ) : 'publish',
	);

	if ( ! empty( $body['title'] ) ) {
		$data['post_title'] = sanitize_text_field( $body['title'] );
	}

	if ( isset( $body['content'] ) ) {
		$data['post_content'] = wp_kses_post( $body['content'] );
	}

	if ( ! empty( $body['date'] ) ) {
		// ISO8601 → MySQL datetime.
		$ts = strtotime( $body['date'] );
		if ( $ts ) {
			$data['post_date']     = get_date_from_gmt( gmdate( 'Y-m-d H:i:s', $ts ) );
			$data['post_date_gmt'] = gmdate( 'Y-m-d H:i:s', $ts );
		}
	}

	if ( ! empty( $body['categories'] ) && is_array( $body['categories'] ) ) {
		$data['post_category'] = array_map( 'absint', $body['categories'] );
	}

	if ( ! empty( $body['tags'] ) && is_array( $body['tags'] ) ) {
		$data['tags_input'] = array_map( 'sanitize_text_field', $body['tags'] );
	}

	return $data;
}

/**
 * Saves post meta fields from API body.
 *
 * @param int   $post_id
 * @param array $body
 */
function pbn_save_post_meta( int $post_id, array $body ) {
	if ( ! empty( $body['lead_form_campaign_id'] ) ) {
		update_post_meta( $post_id, 'pbn_lead_form_campaign_id', sanitize_key( $body['lead_form_campaign_id'] ) );
	}

	if ( ! empty( $body['meta_title'] ) ) {
		update_post_meta( $post_id, 'pbn_meta_title', sanitize_text_field( $body['meta_title'] ) );
	}

	if ( ! empty( $body['meta_description'] ) ) {
		update_post_meta( $post_id, 'pbn_meta_description', sanitize_textarea_field( $body['meta_description'] ) );
	}
}

/**
 * Formats a WP_Post object into the API response shape.
 *
 * @param WP_Post $post
 * @return array
 */
function pbn_format_post( WP_Post $post ) {
	return array(
		'id'                     => $post->ID,
		'title'                  => $post->post_title,
		'slug'                   => $post->post_name,
		'status'                 => $post->post_status,
		'date'                   => $post->post_date_gmt ? $post->post_date_gmt . 'Z' : null,
		'modified'               => $post->post_modified_gmt ? $post->post_modified_gmt . 'Z' : null,
		'link'                   => get_permalink( $post->ID ),
		'categories'             => wp_get_post_categories( $post->ID ),
		'tags'                   => wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) ),
		'lead_form_campaign_id'  => get_post_meta( $post->ID, 'pbn_lead_form_campaign_id', true ),
		'meta_title'             => get_post_meta( $post->ID, 'pbn_meta_title', true ),
		'meta_description'       => get_post_meta( $post->ID, 'pbn_meta_description', true ),
	);
}

/**
 * Get plugin file path from slug.
 *
 * @param string $slug
 * @return string|null
 */
function pbn_get_plugin_path( $slug ) {
	if ( ! function_exists( 'get_plugins' ) ) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$all_plugins = get_plugins();

	foreach ( $all_plugins as $plugin_path => $plugin_data ) {
		$plugin_slug = dirname( $plugin_path );
		if ( '.' === $plugin_slug ) {
			$plugin_slug = basename( $plugin_path, '.php' );
		}

		if ( $plugin_slug === $slug ) {
			return $plugin_path;
		}
	}

	return null;
}

/**
 * Upgrade a single plugin by plugin path.
 *
 * @param string $plugin_path
 * @return string|WP_Error Plugin slug on success, WP_Error on failure
 */
function pbn_upgrade_single_plugin( $plugin_path ) {
	if ( ! class_exists( 'Plugin_Upgrader' ) ) {
		require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
	}

	$upgrader = new Plugin_Upgrader( new WP_Ajax_Upgrader_Skin() );

	// Get the plugin's download link from the update transient
	$updates = get_site_transient( 'update_plugins' );
	if ( ! $updates || ! isset( $updates->response[ $plugin_path ] ) ) {
		return new WP_Error( 'pbn_no_update', 'No update available for this plugin.' );
	}

	$plugin_update_info = $updates->response[ $plugin_path ];
	$result = $upgrader->upgrade( $plugin_path );

	if ( is_wp_error( $result ) ) {
		return $result;
	}

	$slug = dirname( $plugin_path );
	if ( '.' === $slug ) {
		$slug = basename( $plugin_path, '.php' );
	}

	return $slug;
}

/**
 * Get plugin path from a zip file URL (attempts to extract from typical wp.org pattern).
 *
 * @param string $download_url
 * @return string|null
 */
function pbn_get_plugin_path_from_zip( $download_url ) {
	// Try to extract slug from URL
	if ( preg_match( '#/([a-z0-9-]+)\.zip$#i', $download_url, $matches ) ) {
		$slug = $matches[1];
		return pbn_get_plugin_path( $slug );
	}

	return null;
}
