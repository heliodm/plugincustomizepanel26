<?php
defined( 'ABSPATH' ) || exit;

function apc_defaults() {
	return [
		// ── Menu lateral ──────────────────────────────────────────────────
		'menu_bg'                => '#1d2327',
		'menu_text'              => '#a7aaad',
		'menu_highlight_bg'      => '#2271b1',
		'menu_highlight_text'    => '#ffffff',
		'menu_submenu_bg'        => '#2c3338',
		'menu_submenu_text'      => '#c3c4c7',
		'menu_submenu_hover_bg'  => '#2271b1',
		'menu_submenu_hover_text'=> '#ffffff',
		'menu_border_color'      => '#2c3338',
		'menu_width'             => '160',

		// ── Admin bar ─────────────────────────────────────────────────────
		'adminbar_bg'            => '#1d2327',
		'adminbar_text'          => '#a7aaad',
		'adminbar_hover_bg'      => '#2c3338',
		'adminbar_height'        => '32',

		// ── Área de conteúdo ──────────────────────────────────────────────
		'content_bg'             => '#f0f0f1',
		'content_text'           => '#3c434a',
		'content_box_bg'         => '#ffffff',
		'content_box_border'     => '#c3c4c7',
		'table_header_bg'        => '#f6f7f7',
		'button_bg'              => '#2271b1',
		'button_text'            => '#ffffff',
		'link_color'             => '#2271b1',

		// ── Tipografia ────────────────────────────────────────────────────
		'font_family'            => '',
		'font_size_base'         => '13',
		'menu_font_size'         => '13',
		'google_font_url'        => '',

		// ── Layout ────────────────────────────────────────────────────────
		'border_radius'          => '3',

		// ── Logotipos ─────────────────────────────────────────────────────
		'login_logo_url'         => '',
		'login_logo_width'       => '84',
		'login_logo_height'      => '84',
		'menu_icon_url'          => '',
		'favicon_url'            => '',

		// ── Rodapé ────────────────────────────────────────────────────────
		'footer_left'            => '',
		'footer_right'           => '',
		'hide_wp_version'        => '0',

		// ── Login ─────────────────────────────────────────────────────────
		'login_bg'               => '#f0f0f1',
		'login_button_bg'        => '#2271b1',
		'login_button_text'      => '#ffffff',
		'login_title'            => '',

		// ── Avançado ──────────────────────────────────────────────────────
		'custom_css'             => '',
		'hide_wp_logo'           => '0',
		'hide_help_tab'          => '0',
		'hide_screen_options'    => '0',
		'hide_update_nag'        => '0',
	];
}

function apc_get( $key = null ) {
	$saved    = (array) get_option( APC_OPTION, [] );
	$settings = wp_parse_args( $saved, apc_defaults() );

	if ( $key !== null ) {
		return $settings[ $key ] ?? '';
	}

	return $settings;
}
