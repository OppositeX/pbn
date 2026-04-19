=== PBN Core ===
Contributors: OppositeX
Tags: pbn, private blog network, rest api, auto-update, lead forms, polylang
Requires at least: 5.6
Tested up to: 6.5
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Central control plugin for a Private Blog Network (PBN) — REST API, GitHub auto-updates, lead forms, and Polylang helpers.

== Description ==

PBN Core is the foundation plugin for a network of WordPress sites. Install it on every site to gain central control via a secure REST API, receive automatic updates from GitHub, inject lead-capture forms, and work with Polylang for multilingual content.

**Features**

* Secure Bearer-token authenticated REST API (namespace `pbn/v1`)
* Site status, post management (create / read / update / delete), and campaign endpoints
* GitHub-powered auto-updater — checks `OppositeX/pbn` releases and notifies admins
* Lead form injection — appends a customisable contact form to any post
* Lead capture endpoint — saves submissions and e-mails the campaign owner
* Polylang helpers — safely wraps all Polylang functions; no-ops when Polylang is absent
* Minimal, accessible CSS for lead forms

**REST API endpoints**

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | /pbn/v1/status | Site health info |
| GET | /pbn/v1/posts | List posts |
| POST | /pbn/v1/posts | Create / schedule a post |
| PUT | /pbn/v1/posts/{id} | Update a post |
| DELETE | /pbn/v1/posts/{id} | Soft-delete (trash) a post |
| GET | /pbn/v1/campaigns | List lead-form campaigns |
| POST | /pbn/v1/campaigns | Create a campaign |
| DELETE | /pbn/v1/campaigns/{id} | Delete a campaign |
| GET | /pbn/v1/site-config | Full site configuration |
| POST | /pbn/v1/trigger-update | Force an update check |
| POST | /pbn/v1/lead-capture | Submit a lead (public) |

== Installation ==

1. Upload the `pbn-core` directory to `/wp-content/plugins/`.
2. Activate the plugin through the **Plugins** screen in WordPress.
3. On first activation a random 32-character API secret is generated and stored in `wp_options` as `pbn_api_secret`.
4. Use the secret as a Bearer token in the `Authorization` header of every API request.

== Frequently Asked Questions ==

= How do I retrieve my API secret? =

Query the database: `SELECT option_value FROM wp_options WHERE option_name = 'pbn_api_secret';`
Or call `GET /pbn/v1/site-config` with the current secret to see the masked value.

= Does this work without Polylang? =

Yes. All Polylang functions are wrapped in availability checks and become safe no-ops when Polylang is not active.

= How do I attach a lead form to a post? =

Set the post meta `pbn_lead_form_campaign_id` to a valid campaign ID, either via the REST API (`lead_form_campaign_id` field on POST/PUT) or directly in the database.

= How are updates delivered? =

The plugin checks the `OppositeX/pbn` GitHub releases API every 12 hours. When a new release is published with a higher semver tag, an admin notice appears and the standard WordPress plugin updater handles the installation.

== Changelog ==

= 1.0.0 =
* Initial release.
* REST API with status, posts, campaigns, site-config, trigger-update, and lead-capture endpoints.
* GitHub auto-updater with admin notice.
* Lead form injection via `the_content` filter.
* Polylang helper functions.

== Upgrade Notice ==

= 1.0.0 =
First stable release. No upgrade steps required.
