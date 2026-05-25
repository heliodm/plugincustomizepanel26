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
	$cmw = absint( $s['content_max_width'] );
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

	<?php if ( $cmw > 0 ) : ?>
	#wpbody-content .wrap { max-width: <?php echo $cmw; ?>px; }
	<?php endif; ?>

	/* ── Caixas / cards ── */
	.postbox, .card, #wpbody-content .notice {
		background: <?php echo esc_attr( $s['content_box_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['content_box_border'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}

	/* ── Metabox — cabeçalho ── */
	.postbox .postbox-header {
		background: <?php echo esc_attr( $s['metabox_header_bg'] ); ?> !important;
		border-bottom-color: <?php echo esc_attr( $s['content_box_border'] ); ?> !important;
		border-radius: <?php echo $br; ?>px <?php echo $br; ?>px 0 0 !important;
	}
	.postbox .postbox-header h2,
	.postbox .postbox-header .hndle { color: <?php echo esc_attr( $s['metabox_header_text'] ); ?> !important; }

	/* ── Tabelas ── */
	.wp-list-table { background: <?php echo esc_attr( $s['content_box_bg'] ); ?> !important; border-radius: <?php echo $br; ?>px !important; }
	.wp-list-table thead th,
	.wp-list-table tfoot th { background: <?php echo esc_attr( $s['table_header_bg'] ); ?> !important; }

	/* ── Formulários ── */
	input[type="text"], input[type="email"], input[type="url"],
	input[type="password"], input[type="number"], input[type="search"],
	input[type="tel"], textarea, select {
		background: <?php echo esc_attr( $s['input_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['input_border'] ); ?> !important;
		color: <?php echo esc_attr( $s['input_text'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}
	input[type="text"]:focus, input[type="email"]:focus, input[type="url"]:focus,
	input[type="password"]:focus, input[type="number"]:focus, input[type="search"]:focus,
	input[type="tel"]:focus, textarea:focus, select:focus {
		border-color: <?php echo esc_attr( $s['input_focus_border'] ); ?> !important;
		box-shadow: 0 0 0 1px <?php echo esc_attr( $s['input_focus_border'] ); ?> !important;
	}

	/* ── Badges / contadores ── */
	#adminmenu .awaiting-mod, #adminmenu .update-plugins,
	.update-count, .plugin-count, #wp-admin-bar-updates .ab-label {
		background: <?php echo esc_attr( $s['badge_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['badge_text'] ); ?> !important;
	}

	/* ── Avisos (notices) ── */
	.notice-success { border-left-color: <?php echo esc_attr( $s['notice_success_border'] ); ?> !important; }
	.notice-error   { border-left-color: <?php echo esc_attr( $s['notice_error_border'] ); ?> !important; }
	.notice-warning { border-left-color: <?php echo esc_attr( $s['notice_warning_border'] ); ?> !important; }
	.notice-info    { border-left-color: <?php echo esc_attr( $s['notice_info_border'] ); ?> !important; }

	/* ── Botões ── */
	.button-primary, input[type="submit"].button-primary {
		background: <?php echo esc_attr( $s['button_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['button_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['button_text'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}
	.button { border-radius: <?php echo $br; ?>px !important; }

	/* ── Ícone WP na admin bar ── */
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

	<?php if ( apc_get( 'disable_animations' ) === '1' ) : ?>
	*, *::before, *::after { transition: none !important; animation: none !important; }
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

/* ── Ocultar admin bar no frontend ─────────────────────────────────── */

add_filter( 'show_admin_bar', 'apc_show_admin_bar' );
function apc_show_admin_bar( $show ) {
	if ( ! is_admin() && apc_get( 'hide_adminbar_frontend' ) === '1' ) {
		return false;
	}
	return $show;
}

/* ── Widgets do Dashboard ───────────────────────────────────────────── */

add_action( 'wp_dashboard_setup', 'apc_remove_dashboard_widgets' );
function apc_remove_dashboard_widgets() {
	$s = apc_get();

	if ( $s['hide_dashboard_welcome'] === '1' ) {
		remove_action( 'welcome_panel', 'wp_welcome_panel' );
	}
	if ( $s['hide_dashboard_at_glance'] === '1' ) {
		remove_meta_box( 'dashboard_right_now', 'dashboard', 'normal' );
	}
	if ( $s['hide_dashboard_activity'] === '1' ) {
		remove_meta_box( 'dashboard_activity', 'dashboard', 'normal' );
	}
	if ( $s['hide_dashboard_quick_draft'] === '1' ) {
		remove_meta_box( 'dashboard_quick_press', 'dashboard', 'side' );
	}
	if ( $s['hide_dashboard_news'] === '1' ) {
		remove_meta_box( 'dashboard_primary', 'dashboard', 'side' );
	}
	if ( $s['hide_dashboard_site_health'] === '1' ) {
		remove_meta_box( 'dashboard_site_health', 'dashboard', 'normal' );
	}
}

/* ── E-mail: nome e endereço do remetente ───────────────────────────── */

add_filter( 'wp_mail_from_name', 'apc_mail_from_name' );
function apc_mail_from_name( $name ) {
	$custom = apc_get( 'mail_from_name' );
	return ! empty( $custom ) ? $custom : $name;
}

add_filter( 'wp_mail_from', 'apc_mail_from_email' );
function apc_mail_from_email( $email ) {
	$custom = apc_get( 'mail_from_email' );
	return ! empty( $custom ) ? $custom : $email;
}

/* ── Login: redirecionamento após login ─────────────────────────────── */

add_filter( 'login_redirect', 'apc_login_redirect', 10, 3 );
function apc_login_redirect( $redirect_to, $requested, $user ) {
	$custom = apc_get( 'login_redirect_url' );
	if ( ! empty( $custom ) && ! is_wp_error( $user ) ) {
		return $custom;
	}
	return $redirect_to;
}

/* ── Login: texto do link "← Voltar para o site" ───────────────────── */

add_filter( 'login_site_html_link', 'apc_login_back_link' );
function apc_login_back_link( $link ) {
	$text = apc_get( 'login_back_text' );
	if ( empty( $text ) ) {
		return $link;
	}
	return preg_replace( '/(<a[^>]*>)[^<]*(<\/a>)/', '$1' . esc_html( $text ) . '$2', $link );
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

	#login, #loginform {
		background: <?php echo esc_attr( $s['login_form_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['login_form_border'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}

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

	.login input[type="text"], .login input[type="password"] {
		background: <?php echo esc_attr( $s['input_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['input_border'] ); ?> !important;
		color: <?php echo esc_attr( $s['input_text'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}
	.login input[type="text"]:focus, .login input[type="password"]:focus {
		border-color: <?php echo esc_attr( $s['input_focus_border'] ); ?> !important;
		box-shadow: 0 0 0 1px <?php echo esc_attr( $s['input_focus_border'] ); ?> !important;
	}

	.login .button-primary {
		background: <?php echo esc_attr( $s['login_button_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['login_button_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['login_button_text'] ); ?> !important;
		border-radius: <?php echo $br; ?>px !important;
	}

	<?php if ( ! empty( $s['login_custom_css'] ) ) : ?>
	<?php echo wp_strip_all_tags( $s['login_custom_css'] ); ?>
	<?php endif; ?>
	</style>
	<?php
}

/* ── URL do logo ────────────────────────────────────────────────────── */

add_filter( 'login_headerurl', 'apc_login_logo_url' );
function apc_login_logo_url() {
	return home_url();
}

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
