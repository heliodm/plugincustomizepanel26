<?php
defined( 'ABSPATH' ) || exit;

/* ── CSS dinâmico no painel ─────────────────────────────────────────── */

add_action( 'admin_head', 'apc_admin_css' );
function apc_admin_css() {
	$s  = apc_get();
	$br = absint( $s['border_radius'] );
	$mw = absint( $s['menu_width'] );
	$bh = absint( $s['adminbar_height'] );
	$fs = absint( $s['font_size_base'] );
	$mf = absint( $s['menu_font_size'] );
	?>
	<style id="apc-admin-css">
	/* ── Tipografia ── */
	<?php if ( ! empty( $s['font_family'] ) ) : ?>
	#wpwrap, #wpcontent, #wpbody, #adminmenu { font-family: <?php echo esc_html( $s['font_family'] ); ?>; }
	<?php endif; ?>
	<?php if ( $fs && $fs !== 13 ) : ?>
	#wpcontent, #wpbody { font-size: <?php echo $fs; ?>px; }
	<?php endif; ?>

	/* ── Menu lateral ── */
	#adminmenu, #adminmenuback, #adminmenuwrap {
		background: <?php echo esc_attr( $s['menu_bg'] ); ?> !important;
		width: <?php echo $mw; ?>px !important;
	}
	#adminmenu a, #adminmenu .wp-menu-name {
		color: <?php echo esc_attr( $s['menu_text'] ); ?> !important;
		font-size: <?php echo $mf; ?>px !important;
	}
	#adminmenu .wp-menu-separator { border-color: <?php echo esc_attr( $s['menu_border_color'] ); ?> !important; }
	#adminmenu li.current a.menu-top,
	#adminmenu li.wp-has-current-submenu a.wp-has-current-submenu,
	#adminmenu li:hover a.menu-top,
	#adminmenu li a.menu-top:focus {
		background: <?php echo esc_attr( $s['menu_highlight_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['menu_highlight_text'] ); ?> !important;
	}
	#adminmenu .wp-submenu {
		background: <?php echo esc_attr( $s['menu_submenu_bg'] ); ?> !important;
	}
	#adminmenu .wp-submenu a {
		color: <?php echo esc_attr( $s['menu_submenu_text'] ); ?> !important;
	}
	#adminmenu .wp-submenu li:hover a,
	#adminmenu .wp-submenu li.current a {
		background: <?php echo esc_attr( $s['menu_submenu_hover_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['menu_submenu_hover_text'] ); ?> !important;
	}
	#wpcontent, #wpfooter { margin-left: <?php echo $mw; ?>px !important; }

	/* ── Barra superior ── */
	#wpadminbar {
		background: <?php echo esc_attr( $s['adminbar_bg'] ); ?> !important;
		min-height: <?php echo $bh; ?>px !important;
	}
	#wpadminbar * { color: <?php echo esc_attr( $s['adminbar_text'] ); ?> !important; }
	#wpadminbar .ab-top-menu > li:hover > .ab-item,
	#wpadminbar .ab-top-menu > li.hover > .ab-item {
		background: <?php echo esc_attr( $s['adminbar_hover_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['adminbar_text'] ); ?> !important;
	}
	html { margin-top: <?php echo $bh; ?>px !important; }

	/* ── Conteúdo ── */
	#wpcontent, #wpbody-content {
		background: <?php echo esc_attr( $s['content_bg'] ); ?>;
		color: <?php echo esc_attr( $s['content_text'] ); ?>;
	}
	.wrap h1, .wrap h2 { color: <?php echo esc_attr( $s['content_text'] ); ?>; }
	a { color: <?php echo esc_attr( $s['link_color'] ); ?>; }

	/* ── Caixas / metaboxes ── */
	.postbox, .card, .wp-list-table, #wpbody-content .notice {
		background: <?php echo esc_attr( $s['content_box_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['content_box_border'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}

	/* ── Tabelas ── */
	.wp-list-table thead th,
	.wp-list-table tfoot th {
		background: <?php echo esc_attr( $s['table_header_bg'] ); ?> !important;
	}

	/* ── Botões primários ── */
	.button-primary, input[type="submit"].button-primary {
		background: <?php echo esc_attr( $s['button_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['button_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['button_text'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}
	.button {
		border-radius: <?php echo $br; ?>px !important;
	}

	/* ── Ícone do WP na admin bar ── */
	<?php if ( ! empty( $s['menu_icon_url'] ) ) : ?>
	#wp-admin-bar-wp-logo .ab-icon::before { display: none !important; }
	#wp-admin-bar-wp-logo .ab-icon {
		background-image: url('<?php echo esc_url( $s['menu_icon_url'] ); ?>') !important;
		background-size: contain !important;
		background-repeat: no-repeat !important;
		background-position: center !important;
		width: 20px !important; height: 20px !important;
	}
	<?php endif; ?>

	<?php if ( apc_get( 'hide_wp_logo' ) === '1' ) : ?>
	#wp-admin-bar-wp-logo { display: none !important; }
	<?php endif; ?>

	<?php if ( ! empty( $s['custom_css'] ) ) : ?>
	/* ── CSS personalizado ── */
	<?php echo wp_strip_all_tags( $s['custom_css'] ); ?>
	<?php endif; ?>
	</style>
	<?php
}

/* ── Google Fonts ───────────────────────────────────────────────────── */

add_action( 'admin_enqueue_scripts', 'apc_load_google_font', 1 );
function apc_load_google_font() {
	$url = apc_get( 'google_font_url' );
	if ( ! empty( $url ) ) {
		wp_enqueue_style( 'apc-google-font', esc_url_raw( $url ), [], null );
	}
}

/* ── Favicon personalizado ──────────────────────────────────────────── */

add_action( 'admin_head', 'apc_favicon' );
add_action( 'login_head',  'apc_favicon' );
function apc_favicon() {
	$url = apc_get( 'favicon_url' );
	if ( ! empty( $url ) ) {
		printf( '<link rel="shortcut icon" href="%s">' . "\n", esc_url( $url ) );
	}
}

/* ── Ocultar aba Ajuda ──────────────────────────────────────────────── */

add_action( 'admin_head', 'apc_hide_help_tab' );
function apc_hide_help_tab() {
	if ( apc_get( 'hide_help_tab' ) !== '1' ) {
		return;
	}
	echo '<style>#contextual-help-link-wrap { display:none !important; }</style>' . "\n";
}

/* ── Ocultar Opções de Tela ─────────────────────────────────────────── */

add_action( 'admin_head', 'apc_hide_screen_options' );
function apc_hide_screen_options() {
	if ( apc_get( 'hide_screen_options' ) !== '1' ) {
		return;
	}
	echo '<style>#show-settings-link { display:none !important; }</style>' . "\n";
}

/* ── Ocultar aviso de atualização ───────────────────────────────────── */

add_action( 'admin_head', 'apc_hide_update_nag' );
function apc_hide_update_nag() {
	if ( apc_get( 'hide_update_nag' ) !== '1' ) {
		return;
	}
	echo '<style>.update-nag, .updated.notice.update-nag { display:none !important; }</style>' . "\n";
}

/* ── CSS dinâmico na tela de login ──────────────────────────────────── */

add_action( 'login_head', 'apc_login_css' );
function apc_login_css() {
	$s  = apc_get();
	$br = absint( $s['border_radius'] );
	?>
	<style id="apc-login-css">
	<?php if ( ! empty( $s['font_family'] ) ) : ?>
	body.login { font-family: <?php echo esc_html( $s['font_family'] ); ?>; }
	<?php endif; ?>
	body.login { background: <?php echo esc_attr( $s['login_bg'] ); ?>; }

	<?php if ( ! empty( $s['login_logo_url'] ) ) : ?>
	#login h1 a, .login h1 a {
		background-image: url('<?php echo esc_url( $s['login_logo_url'] ); ?>') !important;
		background-size: contain !important;
		background-repeat: no-repeat !important;
		background-position: center !important;
		width:  <?php echo absint( $s['login_logo_width'] ); ?>px !important;
		height: <?php echo absint( $s['login_logo_height'] ); ?>px !important;
	}
	<?php endif; ?>

	.login #nav a, .login #backtoblog a { color: <?php echo esc_attr( $s['link_color'] ); ?>; }

	.login .button-primary {
		background: <?php echo esc_attr( $s['login_button_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['login_button_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['login_button_text'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}
	#loginform, #login { border-radius: <?php echo $br; ?>px !important; }
	</style>
	<?php
}

/* ── URL do logo na tela de login ───────────────────────────────────── */

add_filter( 'login_headerurl', 'apc_login_logo_url' );
function apc_login_logo_url() {
	return home_url();
}

/* ── Título do logo na tela de login ───────────────────────────────── */

add_filter( 'login_headertext', 'apc_login_logo_title' );
function apc_login_logo_title( $text ) {
	$custom = apc_get( 'login_title' );
	return $custom !== '' ? esc_html( $custom ) : $text;
}

/* ── Rodapé esquerdo ────────────────────────────────────────────────── */

add_filter( 'admin_footer_text', 'apc_footer_left' );
function apc_footer_left( $text ) {
	$custom = apc_get( 'footer_left' );
	return $custom !== '' ? wp_kses_post( $custom ) : $text;
}

/* ── Rodapé direito (versão) ────────────────────────────────────────── */

add_filter( 'update_footer', 'apc_footer_right', 99 );
function apc_footer_right( $text ) {
	if ( apc_get( 'hide_wp_version' ) === '1' ) {
		return '';
	}
	$custom = apc_get( 'footer_right' );
	return $custom !== '' ? wp_kses_post( $custom ) : $text;
}
