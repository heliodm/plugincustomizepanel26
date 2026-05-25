<?php
defined( 'ABSPATH' ) || exit;

/* ── Menu em Configurações ──────────────────────────────────────────── */

add_action( 'admin_menu', 'apc_add_menu' );
function apc_add_menu() {
	add_options_page(
		__( 'Personalizar Painel', 'apc' ),
		__( 'Personalizar Painel', 'apc' ),
		'manage_options',
		'apc-settings',
		'apc_render_page'
	);
}

/* ── Exportar configurações (GET) ───────────────────────────────────── */

add_action( 'admin_init', 'apc_handle_export' );
function apc_handle_export() {
	if (
		! isset( $_GET['apc_action'] ) ||
		$_GET['apc_action'] !== 'export_settings' ||
		! wp_verify_nonce( sanitize_key( $_GET['nonce'] ?? '' ), 'apc_export' ) ||
		! current_user_can( 'manage_options' )
	) {
		return;
	}

	$payload = [
		'settings' => apc_get(),
		'widgets'  => apc_get_widgets(),
	];

	$json = wp_json_encode( $payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE );

	header( 'Content-Type: application/json; charset=utf-8' );
	header( 'Content-Disposition: attachment; filename="apc-settings-' . gmdate( 'Y-m-d' ) . '.json"' );
	header( 'Pragma: no-cache' );
	echo $json;
	exit;
}

/* ── Salvar configurações (POST) ────────────────────────────────────── */

add_action( 'admin_init', 'apc_save_settings' );
function apc_save_settings() {
	if (
		! isset( $_POST['apc_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apc_nonce'] ) ), 'apc_save' ) ||
		! current_user_can( 'manage_options' )
	) {
		return;
	}

	$defaults = apc_defaults();
	$saved    = (array) get_option( APC_OPTION, [] );

	// Inicia com os dados atuais (preserva campos não presentes no POST)
	$data = wp_parse_args( $saved, $defaults );

	// ── Campos de cor ─────────────────────────────────────────────────
	$color_fields = [
		'menu_bg', 'menu_text', 'menu_highlight_bg', 'menu_highlight_text',
		'menu_submenu_bg', 'menu_submenu_text', 'menu_submenu_hover_bg', 'menu_submenu_hover_text',
		'menu_border_color', 'adminbar_bg', 'adminbar_text', 'adminbar_hover_bg',
		'content_bg', 'content_text', 'content_box_bg', 'content_box_border',
		'table_header_bg', 'metabox_header_bg', 'metabox_header_text',
		'button_bg', 'button_text', 'link_color',
		'input_bg', 'input_border', 'input_focus_border', 'input_text',
		'badge_bg', 'badge_text',
		'notice_success_border', 'notice_error_border', 'notice_warning_border', 'notice_info_border',
		'login_bg', 'login_form_bg', 'login_form_border', 'login_button_bg', 'login_button_text',
	];
	foreach ( $color_fields as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$color = sanitize_hex_color( wp_unslash( $_POST[ $key ] ) );
			if ( $color ) {
				$data[ $key ] = $color;
			}
		}
	}

	// ── Campos numéricos com faixas ───────────────────────────────────
	$numeric_ranges = [
		'menu_width'        => [ 80,  400, 160 ],
		'adminbar_height'   => [ 20,   80,  32 ],
		'border_radius'     => [  0,   24,   3 ],
		'font_size_base'    => [ 10,   24,  13 ],
		'menu_font_size'    => [ 10,   24,  13 ],
		'login_logo_width'  => [  1,  600,  84 ],
		'login_logo_height' => [  1,  600,  84 ],
	];
	foreach ( $numeric_ranges as $key => [ $min, $max, $default ] ) {
		if ( isset( $_POST[ $key ] ) ) {
			$val          = absint( wp_unslash( $_POST[ $key ] ) );
			$data[ $key ] = (string) ( ( $val >= $min && $val <= $max ) ? $val : $default );
		}
	}
	// Largura máxima do conteúdo (pode ser vazia)
	if ( isset( $_POST['content_max_width'] ) ) {
		$cmw = absint( wp_unslash( $_POST['content_max_width'] ) );
		$data['content_max_width'] = $cmw >= 600 ? (string) $cmw : '';
	}

	// ── Campos de texto simples ───────────────────────────────────────
	foreach ( [ 'font_family', 'login_title', 'login_back_text', 'mail_from_name' ] as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$data[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		}
	}

	// ── E-mail (sanitize_email) ───────────────────────────────────────
	$data['mail_from_email'] = sanitize_email( wp_unslash( $_POST['mail_from_email'] ?? '' ) );

	// ── URLs (bloqueia javascript:) ───────────────────────────────────
	foreach ( [ 'google_font_url', 'login_redirect_url' ] as $key ) {
		$raw          = esc_url_raw( wp_unslash( $_POST[ $key ] ?? '' ) );
		$data[ $key ] = preg_match( '/^javascript:/i', $raw ) ? '' : $raw;
	}

	// ── Textareas ─────────────────────────────────────────────────────
	foreach ( [ 'custom_css', 'login_custom_css', 'footer_left', 'footer_right' ] as $key ) {
		$data[ $key ] = sanitize_textarea_field( wp_unslash( $_POST[ $key ] ?? '' ) );
	}

	// ── Checkboxes ────────────────────────────────────────────────────
	$checkboxes = [
		'hide_wp_version', 'hide_wp_logo', 'hide_help_tab', 'hide_screen_options',
		'hide_update_nag', 'disable_animations', 'hide_adminbar_frontend',
		'hide_dashboard_welcome', 'hide_dashboard_at_glance', 'hide_dashboard_activity',
		'hide_dashboard_quick_draft', 'hide_dashboard_news', 'hide_dashboard_site_health',
	];
	foreach ( $checkboxes as $key ) {
		$data[ $key ] = isset( $_POST[ $key ] ) ? '1' : '0';
	}

	// ── Uploads de imagem ─────────────────────────────────────────────
	foreach ( [ 'login_logo_url' => 'login_logo_file', 'menu_icon_url' => 'menu_icon_file', 'favicon_url' => 'favicon_file' ] as $field => $file_key ) {
		$url = apc_handle_upload( $file_key );
		if ( is_wp_error( $url ) ) {
			add_settings_error( 'apc', 'upload_error', $url->get_error_message() );
		} elseif ( $url !== null ) {
			$data[ $field ] = $url;
		}
		// sem upload = mantém valor já em $data
	}
	foreach ( [ 'login_logo' => 'login_logo_url', 'menu_icon' => 'menu_icon_url', 'favicon' => 'favicon_url' ] as $btn => $field ) {
		if ( isset( $_POST[ 'remove_' . $btn ] ) ) {
			$data[ $field ] = '';
		}
	}

	// ── Importar JSON ─────────────────────────────────────────────────
	if ( ! empty( $_FILES['apc_import_file']['tmp_name'] ) ) {
		apc_process_import( $_FILES['apc_import_file']['tmp_name'], $data );
	}

	update_option( APC_OPTION, $data );

	wp_safe_redirect(
		add_query_arg( [ 'page' => 'apc-settings', 'updated' => '1' ], admin_url( 'options-general.php' ) )
	);
	exit;
}

/* ── Importar configurações de JSON ─────────────────────────────────── */

function apc_process_import( $tmp_file, &$data ) {
	$json = file_get_contents( $tmp_file ); // phpcs:ignore
	if ( $json === false ) {
		return;
	}
	$imported = json_decode( $json, true );
	if ( ! is_array( $imported ) ) {
		return;
	}

	$settings = $imported['settings'] ?? $imported;
	if ( ! is_array( $settings ) ) {
		return;
	}

	$color_keys = [
		'menu_bg', 'menu_text', 'menu_highlight_bg', 'menu_highlight_text',
		'menu_submenu_bg', 'menu_submenu_text', 'menu_submenu_hover_bg', 'menu_submenu_hover_text',
		'menu_border_color', 'adminbar_bg', 'adminbar_text', 'adminbar_hover_bg',
		'content_bg', 'content_text', 'content_box_bg', 'content_box_border',
		'table_header_bg', 'metabox_header_bg', 'metabox_header_text',
		'button_bg', 'button_text', 'link_color',
		'input_bg', 'input_border', 'input_focus_border', 'input_text',
		'badge_bg', 'badge_text',
		'notice_success_border', 'notice_error_border', 'notice_warning_border', 'notice_info_border',
		'login_bg', 'login_form_bg', 'login_form_border', 'login_button_bg', 'login_button_text',
	];

	$defaults = apc_defaults();
	foreach ( $settings as $key => $value ) {
		if ( ! array_key_exists( $key, $defaults ) ) {
			continue;
		}
		if ( in_array( $key, $color_keys, true ) ) {
			$color = sanitize_hex_color( (string) $value );
			if ( $color ) {
				$data[ $key ] = $color;
			}
		} else {
			$data[ $key ] = sanitize_text_field( (string) $value );
		}
	}

	// Importar widgets se presentes
	if ( ! empty( $imported['widgets'] ) && is_array( $imported['widgets'] ) ) {
		$widgets = array_filter( array_map( 'apc_sanitize_widget', $imported['widgets'] ) );
		update_option( APC_WIDGETS_OPTION, array_values( $widgets ) );
	}
}

/* ── Assets ─────────────────────────────────────────────────────────── */

add_action( 'admin_enqueue_scripts', 'apc_enqueue' );
function apc_enqueue( $hook ) {
	if ( $hook !== 'settings_page_apc-settings' ) {
		return;
	}
	wp_enqueue_style( 'apc-admin', APC_URL . 'assets/css/admin.css', [], APC_VERSION );
	wp_enqueue_script( 'apc-admin', APC_URL . 'assets/js/admin.js', [ 'jquery' ], APC_VERSION, true );
	wp_localize_script( 'apc-admin', 'apcDefaults', apc_defaults() );
}

/* ── Roteador principal ─────────────────────────────────────────────── */

function apc_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$tab = sanitize_key( $_GET['apc_tab'] ?? '' );

	if ( $tab === 'widgets' ) {
		apc_render_widgets_page();
	} else {
		apc_render_settings_page();
	}
}

/* ── Navegação de abas (compartilhada) ──────────────────────────────── */

function apc_render_tabs( $active = 'colors' ) {
	$settings_url = admin_url( 'options-general.php?page=apc-settings' );
	$widgets_url  = admin_url( 'options-general.php?page=apc-settings&apc_tab=widgets' );
	$on_widgets   = $active === 'widgets';

	$tabs = [
		'colors'    => __( 'Cores', 'apc' ),
		'forms'     => __( 'Formulários', 'apc' ),
		'typo'      => __( 'Tipografia', 'apc' ),
		'layout'    => __( 'Layout', 'apc' ),
		'logos'     => __( 'Logotipos', 'apc' ),
		'footer'    => __( 'Rodapé', 'apc' ),
		'login'     => __( 'Login', 'apc' ),
		'dashboard' => __( 'Dashboard', 'apc' ),
		'email'     => __( 'E-mail', 'apc' ),
		'advanced'  => __( 'Avançado', 'apc' ),
	];

	echo '<div class="apc-tabs">';

	foreach ( $tabs as $id => $label ) {
		if ( $on_widgets ) {
			// Na página de widgets, todos os tabs são links
			printf(
				'<a href="%s" class="apc-tab">%s</a>',
				esc_url( $settings_url . '#' . $id ),
				esc_html( $label )
			);
		} else {
			// Na página de configurações, tabs são botões JS
			printf(
				'<button type="button" class="apc-tab%s" data-tab="%s">%s</button>',
				$active === $id ? ' active' : '',
				esc_attr( $id ),
				esc_html( $label )
			);
		}
	}

	// Tab Widgets — sempre um link
	printf(
		'<a href="%s" class="apc-tab%s">%s</a>',
		esc_url( $widgets_url ),
		$active === 'widgets' ? ' active' : '',
		esc_html__( 'Widgets', 'apc' )
	);

	echo '</div>';
}

/* ── Renderiza a página de configurações ────────────────────────────── */

function apc_render_settings_page() {
	$s          = apc_get();
	$export_url = wp_nonce_url(
		add_query_arg( [ 'page' => 'apc-settings', 'apc_action' => 'export_settings' ], admin_url( 'options-general.php' ) ),
		'apc_export',
		'nonce'
	);
	?>
	<div class="wrap apc-wrap">
		<h1><?php esc_html_e( 'Personalizar Painel Administrativo', 'apc' ); ?></h1>

		<?php if ( isset( $_GET['updated'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p><?php esc_html_e( 'Configurações salvas com sucesso!', 'apc' ); ?></p>
			</div>
		<?php endif; ?>
		<?php settings_errors( 'apc' ); ?>

		<form method="post" action="" enctype="multipart/form-data" id="apc-form">
			<?php wp_nonce_field( 'apc_save', 'apc_nonce' ); ?>

			<!-- Barra de presets -->
			<div class="apc-presets-bar">
				<span class="apc-presets-label"><?php esc_html_e( 'Temas rápidos:', 'apc' ); ?></span>
				<?php
				foreach ( apc_preset_list() as $id => $preset ) {
					printf(
						'<button type="button" class="apc-preset-btn" data-preset="%s" title="%s">
							<span class="apc-preset-swatches">%s</span>
							<span>%s</span>
						</button>',
						esc_attr( $id ),
						esc_attr( $preset['label'] ),
						apc_render_swatches( $preset ),
						esc_html( $preset['label'] )
					);
				}
				?>
				<script id="apc-preset-data" type="application/json"><?php echo wp_json_encode( apc_preset_list() ); ?></script>
			</div>

			<?php apc_render_tabs( 'colors' ); ?>

			<!-- ═══════════ CORES ══════════════════════════════════════ -->
			<div class="apc-panel active" id="apc-tab-colors">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Menu Lateral', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'menu_bg',                 __( 'Fundo do menu', 'apc' ),          $s );
						apc_color_field( 'menu_text',               __( 'Texto do menu', 'apc' ),           $s );
						apc_color_field( 'menu_highlight_bg',       __( 'Item ativo — fundo', 'apc' ),      $s );
						apc_color_field( 'menu_highlight_text',     __( 'Item ativo — texto', 'apc' ),      $s );
						apc_color_field( 'menu_border_color',       __( 'Divisor/borda', 'apc' ),           $s );
						apc_color_field( 'menu_submenu_bg',         __( 'Submenu — fundo', 'apc' ),         $s );
						apc_color_field( 'menu_submenu_text',       __( 'Submenu — texto', 'apc' ),         $s );
						apc_color_field( 'menu_submenu_hover_bg',   __( 'Submenu hover — fundo', 'apc' ),   $s );
						apc_color_field( 'menu_submenu_hover_text', __( 'Submenu hover — texto', 'apc' ),   $s );
						?>
					</div>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Barra Superior (Admin Bar)', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'adminbar_bg',       __( 'Fundo', 'apc' ),       $s );
						apc_color_field( 'adminbar_text',     __( 'Texto', 'apc' ),        $s );
						apc_color_field( 'adminbar_hover_bg', __( 'Hover — fundo', 'apc' ),$s );
						?>
					</div>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Área de Conteúdo', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'content_bg',          __( 'Fundo da página', 'apc' ),           $s );
						apc_color_field( 'content_text',        __( 'Texto geral', 'apc' ),                $s );
						apc_color_field( 'content_box_bg',      __( 'Fundo das caixas', 'apc' ),           $s );
						apc_color_field( 'content_box_border',  __( 'Borda das caixas', 'apc' ),           $s );
						apc_color_field( 'table_header_bg',     __( 'Cabeçalho de tabelas', 'apc' ),       $s );
						apc_color_field( 'metabox_header_bg',   __( 'Metabox cabeçalho — fundo', 'apc' ),  $s );
						apc_color_field( 'metabox_header_text', __( 'Metabox cabeçalho — texto', 'apc' ),  $s );
						apc_color_field( 'button_bg',           __( 'Botão primário — fundo', 'apc' ),     $s );
						apc_color_field( 'button_text',         __( 'Botão primário — texto', 'apc' ),     $s );
						apc_color_field( 'link_color',          __( 'Cor dos links', 'apc' ),               $s );
						?>
					</div>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Badges e Avisos', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'badge_bg',              __( 'Badge — fundo', 'apc' ),              $s );
						apc_color_field( 'badge_text',            __( 'Badge — texto', 'apc' ),               $s );
						apc_color_field( 'notice_success_border', __( 'Notice sucesso — borda', 'apc' ),      $s );
						apc_color_field( 'notice_error_border',   __( 'Notice erro — borda', 'apc' ),         $s );
						apc_color_field( 'notice_warning_border', __( 'Notice aviso — borda', 'apc' ),        $s );
						apc_color_field( 'notice_info_border',    __( 'Notice informação — borda', 'apc' ),   $s );
						?>
					</div>
				</div>
			</div>

			<!-- ═══════════ FORMULÁRIOS ════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-forms">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Campos de Formulário', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Aplica-se a inputs, selects e textareas no painel e na tela de login.', 'apc' ); ?></p>
					<div class="apc-grid" style="margin-top:16px;">
						<?php
						apc_color_field( 'input_bg',           __( 'Fundo do campo', 'apc' ),   $s );
						apc_color_field( 'input_border',       __( 'Borda do campo', 'apc' ),   $s );
						apc_color_field( 'input_focus_border', __( 'Borda em foco', 'apc' ),    $s );
						apc_color_field( 'input_text',         __( 'Texto do campo', 'apc' ),   $s );
						?>
					</div>
				</div>
			</div>

			<!-- ═══════════ TIPOGRAFIA ═════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-typo">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Fonte', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="font_family"><?php esc_html_e( 'Família de fonte', 'apc' ); ?></label></th>
							<td>
								<input type="text" id="font_family" name="font_family" value="<?php echo esc_attr( $s['font_family'] ); ?>" class="regular-text" placeholder="Ex.: Inter, sans-serif">
								<p class="description"><?php esc_html_e( 'Vazio = padrão do WordPress.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="google_font_url"><?php esc_html_e( 'URL do Google Fonts', 'apc' ); ?></label></th>
							<td>
								<input type="url" id="google_font_url" name="google_font_url" value="<?php echo esc_attr( $s['google_font_url'] ); ?>" class="large-text" placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
								<p class="description"><?php esc_html_e( 'Cole o link do Google Fonts para carregar a fonte no painel.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="font_size_base"><?php esc_html_e( 'Tamanho base (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="font_size_base" name="font_size_base" value="<?php echo esc_attr( $s['font_size_base'] ); ?>" class="small-text" min="10" max="24"></td>
						</tr>
						<tr>
							<th scope="row"><label for="menu_font_size"><?php esc_html_e( 'Tamanho no menu (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="menu_font_size" name="menu_font_size" value="<?php echo esc_attr( $s['menu_font_size'] ); ?>" class="small-text" min="10" max="24"></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ═══════════ LAYOUT ═════════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-layout">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Dimensões', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="menu_width"><?php esc_html_e( 'Largura do menu lateral (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="menu_width" name="menu_width" value="<?php echo esc_attr( $s['menu_width'] ); ?>" class="small-text" min="80" max="400"><p class="description"><?php esc_html_e( 'Padrão: 160 px', 'apc' ); ?></p></td>
						</tr>
						<tr>
							<th scope="row"><label for="adminbar_height"><?php esc_html_e( 'Altura da barra superior (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="adminbar_height" name="adminbar_height" value="<?php echo esc_attr( $s['adminbar_height'] ); ?>" class="small-text" min="20" max="80"><p class="description"><?php esc_html_e( 'Padrão: 32 px', 'apc' ); ?></p></td>
						</tr>
						<tr>
							<th scope="row"><label for="border_radius"><?php esc_html_e( 'Raio de borda (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="border_radius" name="border_radius" value="<?php echo esc_attr( $s['border_radius'] ); ?>" class="small-text" min="0" max="24"><p class="description"><?php esc_html_e( 'Caixas, botões, inputs. Padrão: 3 px', 'apc' ); ?></p></td>
						</tr>
						<tr>
							<th scope="row"><label for="content_max_width"><?php esc_html_e( 'Largura máxima do conteúdo (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="content_max_width" name="content_max_width" value="<?php echo esc_attr( $s['content_max_width'] ); ?>" class="small-text" min="600" max="2400"><p class="description"><?php esc_html_e( 'Vazio = sem limite.', 'apc' ); ?></p></td>
						</tr>
					</table>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Comportamento', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Animações', 'apc' ); ?></th>
							<td><label><input type="checkbox" name="disable_animations" value="1" <?php checked( $s['disable_animations'], '1' ); ?>> <?php esc_html_e( 'Desativar todas as animações e transições CSS no painel', 'apc' ); ?></label></td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Admin bar no site', 'apc' ); ?></th>
							<td><label><input type="checkbox" name="hide_adminbar_frontend" value="1" <?php checked( $s['hide_adminbar_frontend'], '1' ); ?>> <?php esc_html_e( 'Ocultar a barra de administração no frontend (para todos os usuários logados)', 'apc' ); ?></label></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ═══════════ LOGOTIPOS ══════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-logos">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Ícone na Admin Bar', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Substitui o logo do WordPress no topo do painel. PNG ou SVG 20×20 px.', 'apc' ); ?></p>
					<?php apc_image_field( 'menu_icon', 'menu_icon_file', $s['menu_icon_url'], 60 ); ?>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Favicon Personalizado', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Substitui o favicon no painel e na tela de login. ICO, PNG 32×32 px ou SVG.', 'apc' ); ?></p>
					<?php apc_image_field( 'favicon', 'favicon_file', $s['favicon_url'], 32 ); ?>
				</div>
			</div>

			<!-- ═══════════ RODAPÉ ═════════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-footer">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Texto do Rodapé', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="footer_left"><?php esc_html_e( 'Rodapé esquerdo', 'apc' ); ?></label></th>
							<td>
								<textarea id="footer_left" name="footer_left" rows="3" class="large-text"><?php echo esc_textarea( $s['footer_left'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Substitui "Obrigado por criar com WordPress." Aceita HTML básico.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="footer_right"><?php esc_html_e( 'Rodapé direito (versão)', 'apc' ); ?></label></th>
							<td>
								<textarea id="footer_right" name="footer_right" rows="3" class="large-text"><?php echo esc_textarea( $s['footer_right'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Substitui o número de versão. Aceita HTML básico.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Ocultar versão', 'apc' ); ?></th>
							<td><label><input type="checkbox" name="hide_wp_version" value="1" <?php checked( $s['hide_wp_version'], '1' ); ?>> <?php esc_html_e( 'Ocultar completamente o número de versão no rodapé direito', 'apc' ); ?></label></td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ═══════════ LOGIN ══════════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-login">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Logotipo', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Substitui o logo do WordPress na tela de login. PNG ou SVG 320×84 px.', 'apc' ); ?></p>
					<?php apc_image_field( 'login_logo', 'login_logo_file', $s['login_logo_url'], 84 ); ?>
					<table class="form-table" style="margin-top:12px;">
						<tr>
							<th scope="row"><label for="login_logo_width"><?php esc_html_e( 'Largura do logo (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="login_logo_width" name="login_logo_width" value="<?php echo esc_attr( $s['login_logo_width'] ); ?>" class="small-text" min="1" max="600"></td>
						</tr>
						<tr>
							<th scope="row"><label for="login_logo_height"><?php esc_html_e( 'Altura do logo (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="login_logo_height" name="login_logo_height" value="<?php echo esc_attr( $s['login_logo_height'] ); ?>" class="small-text" min="1" max="600"></td>
						</tr>
						<tr>
							<th scope="row"><label for="login_title"><?php esc_html_e( 'Título/alt do logo', 'apc' ); ?></label></th>
							<td><input type="text" id="login_title" name="login_title" value="<?php echo esc_attr( $s['login_title'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Nome do seu site', 'apc' ); ?>"></td>
						</tr>
					</table>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Cores da Tela de Login', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'login_bg',          __( 'Fundo da página', 'apc' ),    $s );
						apc_color_field( 'login_form_bg',     __( 'Fundo do formulário', 'apc' ), $s );
						apc_color_field( 'login_form_border', __( 'Borda do formulário', 'apc' ), $s );
						apc_color_field( 'login_button_bg',   __( 'Botão — fundo', 'apc' ),       $s );
						apc_color_field( 'login_button_text', __( 'Botão — texto', 'apc' ),        $s );
						?>
					</div>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Comportamento', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="login_redirect_url"><?php esc_html_e( 'Redirecionar após login', 'apc' ); ?></label></th>
							<td>
								<input type="url" id="login_redirect_url" name="login_redirect_url" value="<?php echo esc_attr( $s['login_redirect_url'] ); ?>" class="large-text" placeholder="https://seusite.com/wp-admin/">
								<p class="description"><?php esc_html_e( 'Vazio = comportamento padrão do WordPress.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="login_back_text"><?php esc_html_e( 'Texto "← Voltar para o site"', 'apc' ); ?></label></th>
							<td><input type="text" id="login_back_text" name="login_back_text" value="<?php echo esc_attr( $s['login_back_text'] ); ?>" class="regular-text"></td>
						</tr>
					</table>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'CSS Exclusivo da Tela de Login', 'apc' ); ?></h2>
					<textarea id="login_custom_css" name="login_custom_css" rows="8" class="large-text code" spellcheck="false"><?php echo esc_textarea( $s['login_custom_css'] ); ?></textarea>
				</div>
			</div>

			<!-- ═══════════ DASHBOARD ══════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-dashboard">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Ocultar Widgets Padrão do Dashboard', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Escolha quais painéis nativos do WordPress serão removidos da tela inicial.', 'apc' ); ?></p>
					<table class="form-table">
						<tr><th><?php esc_html_e( 'Painel de boas-vindas', 'apc' ); ?></th><td><label><input type="checkbox" name="hide_dashboard_welcome"     value="1" <?php checked( $s['hide_dashboard_welcome'],     '1' ); ?>> <?php esc_html_e( 'Ocultar banner "Bem-vindo ao WordPress!"', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( 'De relance', 'apc' ); ?></th>           <td><label><input type="checkbox" name="hide_dashboard_at_glance"   value="1" <?php checked( $s['hide_dashboard_at_glance'],   '1' ); ?>> <?php esc_html_e( 'Ocultar widget "De relance" (posts, páginas, comentários)', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( 'Atividade', 'apc' ); ?></th>             <td><label><input type="checkbox" name="hide_dashboard_activity"    value="1" <?php checked( $s['hide_dashboard_activity'],    '1' ); ?>> <?php esc_html_e( 'Ocultar widget "Atividade"', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( 'Rascunho rápido', 'apc' ); ?></th>       <td><label><input type="checkbox" name="hide_dashboard_quick_draft" value="1" <?php checked( $s['hide_dashboard_quick_draft'], '1' ); ?>> <?php esc_html_e( 'Ocultar widget "Rascunho rápido"', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( 'Notícias do WordPress', 'apc' ); ?></th>  <td><label><input type="checkbox" name="hide_dashboard_news"        value="1" <?php checked( $s['hide_dashboard_news'],        '1' ); ?>> <?php esc_html_e( 'Ocultar notícias e eventos do WordPress.org', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( 'Saúde do site', 'apc' ); ?></th>          <td><label><input type="checkbox" name="hide_dashboard_site_health" value="1" <?php checked( $s['hide_dashboard_site_health'], '1' ); ?>> <?php esc_html_e( 'Ocultar widget "Saúde do site"', 'apc' ); ?></label></td></tr>
					</table>
				</div>
			</div>

			<!-- ═══════════ E-MAIL ═════════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-email">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Remetente dos E-mails do WordPress', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Personaliza o nome e endereço que aparecem nos e-mails enviados pelo WordPress.', 'apc' ); ?></p>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="mail_from_name"><?php esc_html_e( 'Nome do remetente', 'apc' ); ?></label></th>
							<td><input type="text" id="mail_from_name" name="mail_from_name" value="<?php echo esc_attr( $s['mail_from_name'] ); ?>" class="regular-text" placeholder="WordPress"></td>
						</tr>
						<tr>
							<th scope="row"><label for="mail_from_email"><?php esc_html_e( 'E-mail do remetente', 'apc' ); ?></label></th>
							<td>
								<input type="email" id="mail_from_email" name="mail_from_email" value="<?php echo esc_attr( $s['mail_from_email'] ); ?>" class="regular-text" placeholder="wordpress@seusite.com">
								<p class="description"><?php esc_html_e( 'Use um e-mail do seu domínio para evitar spam.', 'apc' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ═══════════ AVANÇADO ═══════════════════════════════════ -->
			<div class="apc-panel" id="apc-tab-advanced">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Ocultar Elementos do WordPress', 'apc' ); ?></h2>
					<table class="form-table">
						<tr><th><?php esc_html_e( 'Logo do WordPress', 'apc' ); ?></th>      <td><label><input type="checkbox" name="hide_wp_logo"        value="1" <?php checked( $s['hide_wp_logo'],        '1' ); ?>> <?php esc_html_e( 'Ocultar o ícone/logo na admin bar', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( 'Aba "Ajuda"', 'apc' ); ?></th>             <td><label><input type="checkbox" name="hide_help_tab"       value="1" <?php checked( $s['hide_help_tab'],       '1' ); ?>> <?php esc_html_e( 'Ocultar botão de ajuda contextual', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( '"Opções de tela"', 'apc' ); ?></th>        <td><label><input type="checkbox" name="hide_screen_options" value="1" <?php checked( $s['hide_screen_options'], '1' ); ?>> <?php esc_html_e( 'Ocultar botão "Opções de Tela"', 'apc' ); ?></label></td></tr>
						<tr><th><?php esc_html_e( 'Aviso de atualização', 'apc' ); ?></th>    <td><label><input type="checkbox" name="hide_update_nag"     value="1" <?php checked( $s['hide_update_nag'],     '1' ); ?>> <?php esc_html_e( 'Ocultar aviso de atualização do WordPress', 'apc' ); ?></label></td></tr>
					</table>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'CSS Personalizado (Painel)', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Regras CSS injetadas em todas as páginas do painel. Não inclua &lt;style&gt;.', 'apc' ); ?></p>
					<textarea id="custom_css" name="custom_css" rows="12" class="large-text code" spellcheck="false"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
				</div>
				<div class="apc-section">
					<h2><?php esc_html_e( 'Exportar / Importar Configurações', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'O arquivo JSON inclui todas as configurações e os widgets personalizados.', 'apc' ); ?></p>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Exportar', 'apc' ); ?></th>
							<td>
								<a href="<?php echo esc_url( $export_url ); ?>" class="button button-secondary">
									⬇ <?php esc_html_e( 'Baixar apc-settings.json', 'apc' ); ?>
								</a>
								<p class="description"><?php esc_html_e( 'Salva um backup de todas as configurações atuais.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Importar', 'apc' ); ?></th>
							<td>
								<input type="file" name="apc_import_file" accept=".json,application/json">
								<p class="description"><?php esc_html_e( 'Carregue um arquivo JSON exportado anteriormente. As configurações atuais serão substituídas.', 'apc' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<div class="apc-footer-actions">
				<button type="submit" class="button button-primary button-large"><?php esc_html_e( 'Salvar Configurações', 'apc' ); ?></button>
				<button type="button" id="apc-reset" class="button button-secondary"><?php esc_html_e( 'Restaurar Padrões', 'apc' ); ?></button>
			</div>
		</form>
	</div>
	<?php
}

/* ── Helpers ────────────────────────────────────────────────────────── */

function apc_color_field( $key, $label, $s ) {
	$value = esc_attr( $s[ $key ] ?? '#000000' );
	printf(
		'<div class="apc-field">
			<label for="%1$s">%2$s</label>
			<div class="apc-color-wrap">
				<input type="color" id="%1$s" name="%1$s" value="%3$s">
				<input type="text" class="apc-hex" data-for="%1$s" value="%3$s" maxlength="7" pattern="#[0-9A-Fa-f]{6}">
			</div>
		</div>',
		esc_attr( $key ),
		esc_html( $label ),
		$value
	);
}

function apc_image_field( $id, $file_key, $current_url, $max_height = 80 ) {
	if ( ! empty( $current_url ) ) {
		printf( '<div class="apc-img-preview"><img src="%s" alt="" style="max-height:%dpx;"></div>', esc_url( $current_url ), (int) $max_height );
		printf( '<p><label><input type="checkbox" name="remove_%s" value="1"> %s</label></p>', esc_attr( $id ), esc_html__( 'Remover imagem atual', 'apc' ) );
	}
	printf( '<input type="file" name="%s" accept="image/*">', esc_attr( $file_key ) );
}

/* ── Lista de presets ───────────────────────────────────────────────── */

function apc_preset_list() {
	return [
		'wp-default' => [ 'label' => 'WordPress Padrão', 'menu_bg' => '#1d2327', 'menu_text' => '#a7aaad', 'menu_highlight_bg' => '#2271b1', 'menu_highlight_text' => '#ffffff', 'menu_submenu_bg' => '#2c3338', 'menu_submenu_text' => '#c3c4c7', 'menu_submenu_hover_bg' => '#2271b1', 'menu_submenu_hover_text' => '#ffffff', 'menu_border_color' => '#2c3338', 'adminbar_bg' => '#1d2327', 'adminbar_text' => '#a7aaad', 'adminbar_hover_bg' => '#2c3338', 'content_bg' => '#f0f0f1', 'content_text' => '#3c434a', 'content_box_bg' => '#ffffff', 'content_box_border' => '#c3c4c7', 'table_header_bg' => '#f6f7f7', 'metabox_header_bg' => '#f6f7f7', 'metabox_header_text' => '#1d2327', 'button_bg' => '#2271b1', 'button_text' => '#ffffff', 'link_color' => '#2271b1', 'input_bg' => '#ffffff', 'input_border' => '#8c8f94', 'input_focus_border' => '#2271b1', 'input_text' => '#2c3338', 'badge_bg' => '#d63638', 'badge_text' => '#ffffff', 'notice_success_border' => '#00a32a', 'notice_error_border' => '#d63638', 'notice_warning_border' => '#dba617', 'notice_info_border' => '#72aee6', 'login_bg' => '#f0f0f1', 'login_form_bg' => '#ffffff', 'login_form_border' => '#c3c4c7', 'login_button_bg' => '#2271b1', 'login_button_text' => '#ffffff' ],
		'dark'       => [ 'label' => 'Modo Escuro', 'menu_bg' => '#0d1117', 'menu_text' => '#8b949e', 'menu_highlight_bg' => '#238636', 'menu_highlight_text' => '#ffffff', 'menu_submenu_bg' => '#161b22', 'menu_submenu_text' => '#8b949e', 'menu_submenu_hover_bg' => '#238636', 'menu_submenu_hover_text' => '#ffffff', 'menu_border_color' => '#21262d', 'adminbar_bg' => '#161b22', 'adminbar_text' => '#c9d1d9', 'adminbar_hover_bg' => '#21262d', 'content_bg' => '#0d1117', 'content_text' => '#c9d1d9', 'content_box_bg' => '#161b22', 'content_box_border' => '#30363d', 'table_header_bg' => '#21262d', 'metabox_header_bg' => '#21262d', 'metabox_header_text' => '#c9d1d9', 'button_bg' => '#238636', 'button_text' => '#ffffff', 'link_color' => '#58a6ff', 'input_bg' => '#0d1117', 'input_border' => '#30363d', 'input_focus_border' => '#238636', 'input_text' => '#c9d1d9', 'badge_bg' => '#da3633', 'badge_text' => '#ffffff', 'notice_success_border' => '#238636', 'notice_error_border' => '#da3633', 'notice_warning_border' => '#d29922', 'notice_info_border' => '#388bfd', 'login_bg' => '#0d1117', 'login_form_bg' => '#161b22', 'login_form_border' => '#30363d', 'login_button_bg' => '#238636', 'login_button_text' => '#ffffff' ],
		'corporate'  => [ 'label' => 'Azul Corporativo', 'menu_bg' => '#003366', 'menu_text' => '#b3c6e0', 'menu_highlight_bg' => '#0055cc', 'menu_highlight_text' => '#ffffff', 'menu_submenu_bg' => '#002244', 'menu_submenu_text' => '#8fafc8', 'menu_submenu_hover_bg' => '#0055cc', 'menu_submenu_hover_text' => '#ffffff', 'menu_border_color' => '#004080', 'adminbar_bg' => '#002244', 'adminbar_text' => '#b3c6e0', 'adminbar_hover_bg' => '#004080', 'content_bg' => '#eef3f9', 'content_text' => '#1a2b3c', 'content_box_bg' => '#ffffff', 'content_box_border' => '#ccd6e0', 'table_header_bg' => '#e6eef6', 'metabox_header_bg' => '#e6eef6', 'metabox_header_text' => '#1a2b3c', 'button_bg' => '#0055cc', 'button_text' => '#ffffff', 'link_color' => '#0055cc', 'input_bg' => '#ffffff', 'input_border' => '#99b3cc', 'input_focus_border' => '#0055cc', 'input_text' => '#1a2b3c', 'badge_bg' => '#cc3300', 'badge_text' => '#ffffff', 'notice_success_border' => '#006600', 'notice_error_border' => '#cc0000', 'notice_warning_border' => '#cc7700', 'notice_info_border' => '#0055cc', 'login_bg' => '#eef3f9', 'login_form_bg' => '#ffffff', 'login_form_border' => '#ccd6e0', 'login_button_bg' => '#0055cc', 'login_button_text' => '#ffffff' ],
		'nature'     => [ 'label' => 'Verde Natureza', 'menu_bg' => '#1a3a1a', 'menu_text' => '#8fbb7f', 'menu_highlight_bg' => '#4a8a3a', 'menu_highlight_text' => '#ffffff', 'menu_submenu_bg' => '#0f2a0f', 'menu_submenu_text' => '#6a9a5a', 'menu_submenu_hover_bg' => '#4a8a3a', 'menu_submenu_hover_text' => '#ffffff', 'menu_border_color' => '#2a4a2a', 'adminbar_bg' => '#0f2a0f', 'adminbar_text' => '#8fbb7f', 'adminbar_hover_bg' => '#2a4a2a', 'content_bg' => '#f0f5ec', 'content_text' => '#1a2a14', 'content_box_bg' => '#ffffff', 'content_box_border' => '#c5d9bb', 'table_header_bg' => '#e8f0e3', 'metabox_header_bg' => '#e8f0e3', 'metabox_header_text' => '#1a2a14', 'button_bg' => '#4a8a3a', 'button_text' => '#ffffff', 'link_color' => '#3a7a2a', 'input_bg' => '#ffffff', 'input_border' => '#9ab88a', 'input_focus_border' => '#4a8a3a', 'input_text' => '#1a2a14', 'badge_bg' => '#c04030', 'badge_text' => '#ffffff', 'notice_success_border' => '#3a7a2a', 'notice_error_border' => '#c04030', 'notice_warning_border' => '#8a6a10', 'notice_info_border' => '#4a8a3a', 'login_bg' => '#f0f5ec', 'login_form_bg' => '#ffffff', 'login_form_border' => '#c5d9bb', 'login_button_bg' => '#4a8a3a', 'login_button_text' => '#ffffff' ],
		'purple'     => [ 'label' => 'Roxo Criativo', 'menu_bg' => '#1a0a2e', 'menu_text' => '#c4a0ff', 'menu_highlight_bg' => '#7c3aed', 'menu_highlight_text' => '#ffffff', 'menu_submenu_bg' => '#120720', 'menu_submenu_text' => '#9a70dd', 'menu_submenu_hover_bg' => '#7c3aed', 'menu_submenu_hover_text' => '#ffffff', 'menu_border_color' => '#2d1465', 'adminbar_bg' => '#120720', 'adminbar_text' => '#c4a0ff', 'adminbar_hover_bg' => '#2d1465', 'content_bg' => '#f5f0ff', 'content_text' => '#1a0a2e', 'content_box_bg' => '#ffffff', 'content_box_border' => '#d4baff', 'table_header_bg' => '#ede5ff', 'metabox_header_bg' => '#ede5ff', 'metabox_header_text' => '#1a0a2e', 'button_bg' => '#7c3aed', 'button_text' => '#ffffff', 'link_color' => '#7c3aed', 'input_bg' => '#ffffff', 'input_border' => '#c4a0ff', 'input_focus_border' => '#7c3aed', 'input_text' => '#1a0a2e', 'badge_bg' => '#e63946', 'badge_text' => '#ffffff', 'notice_success_border' => '#059669', 'notice_error_border' => '#e63946', 'notice_warning_border' => '#d97706', 'notice_info_border' => '#7c3aed', 'login_bg' => '#f5f0ff', 'login_form_bg' => '#ffffff', 'login_form_border' => '#d4baff', 'login_button_bg' => '#7c3aed', 'login_button_text' => '#ffffff' ],
		'coral'      => [ 'label' => 'Coral Quente', 'menu_bg' => '#2d1008', 'menu_text' => '#f5b89a', 'menu_highlight_bg' => '#e04020', 'menu_highlight_text' => '#ffffff', 'menu_submenu_bg' => '#1a0805', 'menu_submenu_text' => '#c88870', 'menu_submenu_hover_bg' => '#e04020', 'menu_submenu_hover_text' => '#ffffff', 'menu_border_color' => '#4a2015', 'adminbar_bg' => '#1a0805', 'adminbar_text' => '#f5b89a', 'adminbar_hover_bg' => '#4a2015', 'content_bg' => '#fff5f0', 'content_text' => '#2d1008', 'content_box_bg' => '#ffffff', 'content_box_border' => '#f5c8b8', 'table_header_bg' => '#feeae0', 'metabox_header_bg' => '#feeae0', 'metabox_header_text' => '#2d1008', 'button_bg' => '#e04020', 'button_text' => '#ffffff', 'link_color' => '#c03010', 'input_bg' => '#ffffff', 'input_border' => '#f0a080', 'input_focus_border' => '#e04020', 'input_text' => '#2d1008', 'badge_bg' => '#2271b1', 'badge_text' => '#ffffff', 'notice_success_border' => '#4a8a3a', 'notice_error_border' => '#e04020', 'notice_warning_border' => '#c07010', 'notice_info_border' => '#e04020', 'login_bg' => '#fff5f0', 'login_form_bg' => '#ffffff', 'login_form_border' => '#f5c8b8', 'login_button_bg' => '#e04020', 'login_button_text' => '#ffffff' ],
	];
}

function apc_render_swatches( $preset ) {
	$out = '';
	foreach ( [ 'menu_bg', 'menu_highlight_bg', 'content_bg', 'button_bg' ] as $k ) {
		if ( ! empty( $preset[ $k ] ) ) {
			$out .= sprintf( '<i style="background:%s"></i>', esc_attr( $preset[ $k ] ) );
		}
	}
	return $out;
}
