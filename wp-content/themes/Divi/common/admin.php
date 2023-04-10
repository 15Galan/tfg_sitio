<?php
/**
 * Common admin.
 *
 * @package \ET\Common
 */

/**
 * Get code snippets application nonces.
 */
function et_code_snippets_get_nonces() {
	return [
		'saveDomainToken'                        => wp_create_nonce( 'et_builder_ajax_save_domain_token' ),
		'et_code_snippets_save_to_local_library' => wp_create_nonce( 'et_code_snippets_save_to_local_library' ),
		'et_theme_builder_api_get_terms'         => wp_create_nonce( 'et_theme_builder_api_get_terms' ),
	];
}

/**
 * Localize common app js data.
 */
function et_common_global_js_vars() {
	if ( ! is_admin() && ! et_core_is_fb_enabled() ) {
		return;
	}

	if ( is_admin() ) {
		global $shortname;

		// phpcs:disable WordPress.Security.NonceVerification -- This function does not change any state and is therefore not susceptible to CSRF.
		$is_templates_page = isset( $_GET['page'] ) && 'et_theme_builder' === $_GET['page'];
		$current_screen    = get_current_screen();
		$toplevel_page     = 'toplevel_page_et_' . $shortname . '_options';
		$is_options_page   = $toplevel_page === $current_screen->id;

		if ( ! $is_templates_page && ! $is_options_page && ! et_builder_bfb_enabled() ) {
			return;
		}
	}

	$home_url  = wp_parse_url( get_site_url() );

	$data = [
		'config' => [
			'nonces'              => et_code_snippets_get_nonces(),
			'api'                 => admin_url( 'admin-ajax.php' ),
			'site_domain'         => $home_url['host'],
			'domainToken'         => get_option( 'et_server_domain_token', '' ),
			'layoutCategories'    => et_theme_builder_get_terms( 'layout_category' ),
			'layoutTags'          => et_theme_builder_get_terms( 'layout_tag' ),
			'localCategoriesEdit' => current_user_can( 'manage_categories' ) ? 'allowed' : 'notAllowed',
		],
		'i18n'   => [
			'library' => require ET_COMMON_DIR . 'i18n/library.php',
		],
	];

	echo '<script>var et_common_data = ' . wp_json_encode( $data ) . '</script>';
}

add_action( 'wp_head', 'et_common_global_js_vars' );
add_action( 'admin_head', 'et_common_global_js_vars' );
