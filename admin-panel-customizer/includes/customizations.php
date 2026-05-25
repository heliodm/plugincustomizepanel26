<?php
defined( 'ABSPATH' ) || exit;

/* ── CSS dinâmico no painel ─────────────────────────────────────────── */

add_action( 'admin_head', 'apc_admin_css' );
function apc_admin_css() {
	$s = apc_get();
	?>
	<style id="apc-admin-css">
	/* Menu lateral */
	#adminmenu, #adminmenuback, #adminmenuwrap {
		background: <?php echo esc_attr( $s['menu_bg'] ); ?> !important;
	}
	#adminmenu a, #adminmenu .wp-menu-name {
		color: <?php echo esc_attr( $s['menu_text'] ); ?> !important;
	}
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

	/* Barra superior */
	#wpadminbar {
		background: <?php echo esc_attr( $s['adminbar_bg'] ); ?> !important;
	}
	#wpadminbar * { color: <?php echo esc_attr( $s['adminbar_text'] ); ?> !important; }
	#wpadminbar .ab-top-menu > li:hover > .ab-item,
	#wpadminbar .ab-top-menu > li.hover > .ab-item {
		background: <?php echo esc_attr( $s['menu_highlight_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['menu_highlight_text'] ); ?> !important;
	}

	/* Conteúdo */
	#wpcontent, #wpbody-content {
		background: <?php echo esc_attr( $s['content_bg'] ); ?>;
		color: <?php echo esc_attr( $s['content_text'] ); ?>;
	}
	.wrap h1, .wrap h2 { color: <?php echo esc_attr( $s['content_text'] ); ?>; }
	a { color: <?php echo esc_attr( $s['link_color'] ); ?>; }

	/* Botões primários */
	.button-primary, input[type="submit"].button-primary {
		background: <?php echo esc_attr( $s['button_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['button_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['button_text'] ); ?> !important;
	}

	/* Ícone menu (logo WP no topo do menu) */
	<?php if ( ! empty( $s['menu_icon_url'] ) ) : ?>
	#adminmenu #wp-admin-bar-wp-logo > .ab-item .ab-icon:before,
	#wp-admin-bar-wp-logo .ab-icon::before {
		display: none;
	}
	#adminmenu #wp-admin-bar-wp-logo > .ab-item .ab-icon,
	#wp-admin-bar-wp-logo .ab-icon {
		background-image: url('<?php echo esc_url( $s['menu_icon_url'] ); ?>') !important;
		background-size: contain !important;
		background-repeat: no-repeat !important;
		background-position: center !important;
		width: 20px !important; height: 20px !important;
	}
	<?php endif; ?>
	</style>
	<?php
}

/* ── CSS dinâmico na tela de login ──────────────────────────────────── */

add_action( 'login_head', 'apc_login_css' );
function apc_login_css() {
	$s = apc_get();
	?>
	<style id="apc-login-css">
	body.login {
		background: <?php echo esc_attr( $s['login_bg'] ); ?>;
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
	.login .button-primary {
		background: <?php echo esc_attr( $s['login_button_bg'] ); ?> !important;
		border-color: <?php echo esc_attr( $s['login_button_bg'] ); ?> !important;
		color: <?php echo esc_attr( $s['login_button_text'] ); ?> !important;
	}
	</style>
	<?php
}

/* ── URL do logo na tela de login ───────────────────────────────────── */

add_filter( 'login_headerurl', 'apc_login_logo_url' );
function apc_login_logo_url() {
	return home_url();
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
