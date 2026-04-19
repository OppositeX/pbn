<?php
/**
 * Polylang helpers for PBN Core.
 *
 * All functions are safe to call even when Polylang is not installed —
 * they simply become no-ops and return sensible defaults.
 *
 * @package PBN_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

/**
 * Returns the current language code, or an empty string when Polylang is
 * inactive or no language is set.
 *
 * @return string e.g. 'en', 'fr', ''
 */
function pbn_get_language() : string {
	if ( ! function_exists( 'pll_current_language' ) ) {
		return '';
	}

	$lang = pll_current_language( 'slug' );
	return is_string( $lang ) ? $lang : '';
}

/**
 * Sets the Polylang language for a given post.
 *
 * Does nothing when Polylang is not active.
 *
 * @param int    $post_id  WordPress post ID.
 * @param string $lang     Language slug, e.g. 'en', 'fr'.
 * @return bool            True if the language was set, false otherwise.
 */
function pbn_set_post_language( int $post_id, string $lang ) : bool {
	if ( ! function_exists( 'pll_set_post_language' ) ) {
		return false;
	}

	if ( empty( $lang ) || empty( $post_id ) ) {
		return false;
	}

	// Validate that the language is actually registered in Polylang.
	$languages = pbn_get_languages();
	$slugs     = wp_list_pluck( $languages, 'slug' );

	if ( ! in_array( $lang, $slugs, true ) ) {
		return false;
	}

	pll_set_post_language( $post_id, $lang );
	return true;
}

/**
 * Returns an array of active Polylang languages, or an empty array when
 * Polylang is not active.
 *
 * Each entry is an associative array with at least:
 *   - slug        (string) e.g. 'en'
 *   - name        (string) e.g. 'English'
 *   - locale      (string) e.g. 'en_US'
 *   - is_default  (bool)
 *
 * @return array
 */
function pbn_get_languages() : array {
	if ( ! function_exists( 'pll_languages_list' ) ) {
		return array();
	}

	$raw = pll_languages_list( array( 'fields' => '' ) ); // All PLL_Language fields.

	if ( ! is_array( $raw ) ) {
		return array();
	}

	$default_slug = function_exists( 'pll_default_language' ) ? pll_default_language() : '';

	$languages = array();
	foreach ( $raw as $lang_obj ) {
		// pll_languages_list with fields='' returns PLL_Language objects or arrays depending on version.
		if ( is_object( $lang_obj ) ) {
			$languages[] = array(
				'slug'       => $lang_obj->slug,
				'name'       => $lang_obj->name,
				'locale'     => $lang_obj->locale,
				'is_default' => ( $lang_obj->slug === $default_slug ),
				'flag_url'   => isset( $lang_obj->flag_url ) ? $lang_obj->flag_url : '',
			);
		} elseif ( is_array( $lang_obj ) ) {
			$slug = $lang_obj['slug'] ?? '';
			$languages[] = array(
				'slug'       => $slug,
				'name'       => $lang_obj['name'] ?? '',
				'locale'     => $lang_obj['locale'] ?? '',
				'is_default' => ( $slug === $default_slug ),
				'flag_url'   => $lang_obj['flag_url'] ?? '',
			);
		}
	}

	return $languages;
}

/**
 * Returns the translated version of a post in the given language, or null.
 *
 * @param  int    $post_id  Source post ID.
 * @param  string $lang     Target language slug.
 * @return int|null         Translated post ID, or null when not found.
 */
function pbn_get_post_translation( int $post_id, string $lang ) {
	if ( ! function_exists( 'pll_get_post' ) ) {
		return null;
	}

	$translated_id = pll_get_post( $post_id, $lang );
	return ( $translated_id && $translated_id !== $post_id ) ? (int) $translated_id : null;
}

/**
 * Returns all translations of a post as an array keyed by language slug.
 *
 * @param  int $post_id  Source post ID.
 * @return array         e.g. ['en' => 1, 'fr' => 2]
 */
function pbn_get_post_translations( int $post_id ) : array {
	if ( ! function_exists( 'pll_get_post_translations' ) ) {
		return array();
	}

	$translations = pll_get_post_translations( $post_id );
	return is_array( $translations ) ? $translations : array();
}
