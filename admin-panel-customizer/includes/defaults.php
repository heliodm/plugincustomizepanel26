<?php
defined( 'ABSPATH' ) || exit;

function apc_defaults() {
	return [
		// Cores do menu lateral
		'menu_bg'           => '#1d2327',
		'menu_text'         => '#a7aaad',
		'menu_highlight_bg' => '#2271b1',
		'menu_highlight_text' => '#ffffff',
		'menu_submenu_bg'   => '#2c3338',

		// Barra superior (adminbar)
		'adminbar_bg'       => '#1d2327',
		'adminbar_text'     => '#a7aaad',

		// Área de conteúdo
		'content_bg'        => '#f0f0f1',
		'content_text'      => '#3c434a',
		'button_bg'         => '#2271b1',
		'button_text'       => '#ffffff',
		'link_color'        => '#2271b1',

		// Logotipos
		'login_logo_url'    => '',
		'login_logo_width'  => '84',
		'login_logo_height' => '84',
		'menu_icon_url'     => '',

		// Rodapé
		'footer_left'       => '',
		'footer_right'      => '',
		'hide_wp_version'   => '0',

		// Login
		'login_bg'          => '#f0f0f1',
		'login_button_bg'   => '#2271b1',
		'login_button_text' => '#ffffff',
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
