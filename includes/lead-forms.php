<?php
/**
 * Lead form injection for PBN Core.
 *
 * Appends a campaign-specific contact form to post content via the
 * `the_content` filter. Submissions are handled over AJAX / REST API.
 *
 * @package PBN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Hooks
// ---------------------------------------------------------------------------
add_filter( 'the_content',  'pbn_inject_lead_form' );
add_action( 'wp_enqueue_scripts', 'pbn_enqueue_lead_form_assets' );

// ---------------------------------------------------------------------------
// Content filter
// ---------------------------------------------------------------------------

/**
 * Appends the lead form HTML to post content when the post has a campaign meta.
 *
 * @param  string $content Post content.
 * @return string          Possibly modified post content.
 */
function pbn_inject_lead_form( string $content ) : string {
	// Only inject on single post pages in the main loop.
	if ( ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$post_id     = get_the_ID();
	$campaign_id = get_post_meta( $post_id, 'pbn_lead_form_campaign_id', true );

	if ( empty( $campaign_id ) ) {
		return $content;
	}

	$campaigns = get_option( 'pbn_campaigns', array() );
	if ( ! isset( $campaigns[ $campaign_id ] ) ) {
		return $content;
	}

	$campaign = $campaigns[ $campaign_id ];
	$form     = pbn_render_lead_form( $campaign, $post_id );

	return $content . $form;
}

// ---------------------------------------------------------------------------
// Form renderer
// ---------------------------------------------------------------------------

/**
 * Renders the lead capture form HTML.
 *
 * @param  array $campaign Campaign data array.
 * @param  int   $post_id  Current post ID.
 * @return string           Form HTML.
 */
function pbn_render_lead_form( array $campaign, int $post_id ) : string {
	$nonce        = wp_create_nonce( 'pbn_lead_capture' );
	$api_endpoint = esc_url( rest_url( 'pbn/v1/lead-capture' ) );
	$form_title   = esc_html( $campaign['form_title'] );
	$form_cta     = esc_html( $campaign['form_cta'] );
	$campaign_id  = esc_attr( $campaign['id'] );
	$post_url     = esc_url( get_permalink( $post_id ) );

	ob_start();
	?>
	<div class="pbn-lead-form-wrap" id="pbn-lead-form-<?php echo $campaign_id; ?>">
		<h3 class="pbn-lead-form__title"><?php echo $form_title; ?></h3>

		<form class="pbn-lead-form"
		      data-endpoint="<?php echo $api_endpoint; ?>"
		      data-campaign-id="<?php echo $campaign_id; ?>"
		      data-post-id="<?php echo absint( $post_id ); ?>"
		      data-post-url="<?php echo $post_url; ?>"
		      novalidate>

			<?php wp_nonce_field( 'pbn_lead_capture', 'pbn_nonce' ); ?>

			<div class="pbn-lead-form__row">
				<label class="pbn-lead-form__label" for="pbn-name-<?php echo $campaign_id; ?>">
					<?php esc_html_e( 'Name', 'pbn-core' ); ?> <span aria-hidden="true">*</span>
				</label>
				<input
					class="pbn-lead-form__input"
					type="text"
					id="pbn-name-<?php echo $campaign_id; ?>"
					name="name"
					required
					autocomplete="name"
					placeholder="<?php esc_attr_e( 'Your name', 'pbn-core' ); ?>"
				/>
			</div>

			<div class="pbn-lead-form__row">
				<label class="pbn-lead-form__label" for="pbn-email-<?php echo $campaign_id; ?>">
					<?php esc_html_e( 'Email', 'pbn-core' ); ?> <span aria-hidden="true">*</span>
				</label>
				<input
					class="pbn-lead-form__input"
					type="email"
					id="pbn-email-<?php echo $campaign_id; ?>"
					name="email"
					required
					autocomplete="email"
					placeholder="<?php esc_attr_e( 'your@email.com', 'pbn-core' ); ?>"
				/>
			</div>

			<div class="pbn-lead-form__row">
				<label class="pbn-lead-form__label" for="pbn-phone-<?php echo $campaign_id; ?>">
					<?php esc_html_e( 'Phone', 'pbn-core' ); ?>
				</label>
				<input
					class="pbn-lead-form__input"
					type="tel"
					id="pbn-phone-<?php echo $campaign_id; ?>"
					name="phone"
					autocomplete="tel"
					placeholder="<?php esc_attr_e( '+1 555 000 0000', 'pbn-core' ); ?>"
				/>
			</div>

			<div class="pbn-lead-form__row pbn-lead-form__row--submit">
				<button class="pbn-lead-form__submit" type="submit">
					<?php echo $form_cta; ?>
				</button>
			</div>

			<div class="pbn-lead-form__message" aria-live="polite" role="status"></div>
		</form>
	</div>

	<script>
	(function () {
		document.addEventListener('DOMContentLoaded', function () {
			var forms = document.querySelectorAll('.pbn-lead-form');
			forms.forEach(function (form) {
				form.addEventListener('submit', function (e) {
					e.preventDefault();
					pbnSubmitLeadForm(form);
				});
			});
		});

		function pbnSubmitLeadForm(form) {
			var endpoint   = form.dataset.endpoint;
			var campaignId = form.dataset.campaignId;
			var postId     = form.dataset.postId;
			var postUrl    = form.dataset.postUrl;
			var msgEl      = form.querySelector('.pbn-lead-form__message');
			var submitBtn  = form.querySelector('.pbn-lead-form__submit');

			var name  = form.querySelector('[name="name"]').value.trim();
			var email = form.querySelector('[name="email"]').value.trim();
			var phone = form.querySelector('[name="phone"]') ? form.querySelector('[name="phone"]').value.trim() : '';

			if (!name || !email) {
				pbnSetMsg(msgEl, '<?php echo esc_js( __( 'Please fill in your name and email.', 'pbn-core' ) ); ?>', 'error');
				return;
			}

			submitBtn.disabled = true;
			pbnSetMsg(msgEl, '<?php echo esc_js( __( 'Sending…', 'pbn-core' ) ); ?>', '');

			fetch(endpoint, {
				method  : 'POST',
				headers : { 'Content-Type': 'application/json' },
				body    : JSON.stringify({
					name        : name,
					email       : email,
					phone       : phone,
					campaign_id : campaignId,
					post_id     : parseInt(postId, 10),
					post_url    : postUrl
				})
			})
			.then(function (res) { return res.json(); })
			.then(function (data) {
				if (data && data.success) {
					pbnSetMsg(msgEl, '<?php echo esc_js( __( 'Thank you! We\'ll be in touch soon.', 'pbn-core' ) ); ?>', 'success');
					form.reset();
				} else {
					var msg = (data && data.message) ? data.message : '<?php echo esc_js( __( 'Something went wrong. Please try again.', 'pbn-core' ) ); ?>';
					pbnSetMsg(msgEl, msg, 'error');
				}
			})
			.catch(function () {
				pbnSetMsg(msgEl, '<?php echo esc_js( __( 'Network error. Please try again.', 'pbn-core' ) ); ?>', 'error');
			})
			.finally(function () {
				submitBtn.disabled = false;
			});
		}

		function pbnSetMsg(el, text, type) {
			el.textContent = text;
			el.className = 'pbn-lead-form__message' + (type ? ' pbn-lead-form__message--' + type : '');
		}
	}());
	</script>
	<?php
	return ob_get_clean();
}

// ---------------------------------------------------------------------------
// Assets
// ---------------------------------------------------------------------------

/**
 * Enqueues the lead-form CSS on the front end.
 */
function pbn_enqueue_lead_form_assets() {
	if ( ! is_singular( 'post' ) ) {
		return;
	}

	$post_id     = get_queried_object_id();
	$campaign_id = get_post_meta( $post_id, 'pbn_lead_form_campaign_id', true );

	if ( empty( $campaign_id ) ) {
		return;
	}

	wp_enqueue_style(
		'pbn-lead-form',
		PBN_PLUGIN_URL . 'assets/lead-form.css',
		array(),
		PBN_VERSION
	);
}
