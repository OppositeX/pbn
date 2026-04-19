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

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

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
