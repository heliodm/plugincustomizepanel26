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

		// ── Formulários ───────────────────────────────────────────────────
		'input_bg'               => '#ffffff',
		'input_border'           => '#8c8f94',
		'input_focus_border'     => '#2271b1',
		'input_text'             => '#2c3338',

		// ── Badges / contadores ───────────────────────────────────────────
		'badge_bg'               => '#d63638',
		'badge_text'             => '#ffffff',

		// ── Metaboxes ─────────────────────────────────────────────────────
		'metabox_header_bg'      => '#f6f7f7',
		'metabox_header_text'    => '#1d2327',

		// ── Avisos (notices) ──────────────────────────────────────────────
		'notice_success_border'  => '#00a32a',
		'notice_error_border'    => '#d63638',
		'notice_warning_border'  => '#dba617',
		'notice_info_border'     => '#72aee6',

		// ── Tipografia ────────────────────────────────────────────────────
		'font_family'            => '',
		'font_size_base'         => '13',
		'menu_font_size'         => '13',
		'google_font_url'        => '',

		// ── Layout ────────────────────────────────────────────────────────
		'border_radius'          => '3',
		'content_max_width'      => '',
		'disable_animations'     => '0',
		'hide_adminbar_frontend' => '0',

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
		'login_form_bg'          => '#ffffff',
		'login_form_border'      => '#c3c4c7',
		'login_button_bg'        => '#2271b1',
		'login_button_text'      => '#ffffff',
		'login_title'            => '',
		'login_redirect_url'     => '',
		'login_back_text'        => '',
		'login_custom_css'       => '',

		// ── Dashboard ─────────────────────────────────────────────────────
		'hide_dashboard_welcome'     => '0',
		'hide_dashboard_at_glance'   => '0',
		'hide_dashboard_activity'    => '0',
		'hide_dashboard_quick_draft' => '0',
		'hide_dashboard_news'        => '0',
		'hide_dashboard_site_health' => '0',

		// ── E-mail ────────────────────────────────────────────────────────
		'mail_from_name'         => '',
		'mail_from_email'        => '',

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
