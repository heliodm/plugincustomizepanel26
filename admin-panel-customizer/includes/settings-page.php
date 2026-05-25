<?php
defined( 'ABSPATH' ) || exit;

/* ── Adiciona em Configurações → Personalizar Painel ────────────────── */

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

/* ── Salvar configurações ───────────────────────────────────────────── */

add_action( 'admin_init', 'apc_save_settings' );
function apc_save_settings() {
	if (
		! isset( $_POST['apc_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apc_nonce'] ) ), 'apc_save' ) ||
		! current_user_can( 'manage_options' )
	) {
		return;
	}

	$defaults  = apc_defaults();
	$saved     = (array) get_option( APC_OPTION, [] );
	$data      = [];

	// Campos de texto simples (cor, número, texto curto)
	$text_fields = array_keys( $defaults );
	foreach ( $text_fields as $key ) {
		if ( isset( $_POST[ $key ] ) ) {
			$data[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		}
	}

	// Campos textarea (CSS personalizado, rodapé)
	foreach ( [ 'custom_css', 'footer_left', 'footer_right' ] as $key ) {
		$data[ $key ] = isset( $_POST[ $key ] )
			? sanitize_textarea_field( wp_unslash( $_POST[ $key ] ) )
			: '';
	}

	// Checkboxes (ausentes no POST = desmarcado)
	foreach ( [ 'hide_wp_version', 'hide_wp_logo', 'hide_help_tab', 'hide_screen_options', 'hide_update_nag' ] as $key ) {
		$data[ $key ] = isset( $_POST[ $key ] ) ? '1' : '0';
	}

	// URL do Google Fonts
	$data['google_font_url'] = isset( $_POST['google_font_url'] )
		? esc_url_raw( wp_unslash( $_POST['google_font_url'] ) )
		: '';

	// ── Uploads de imagem ──────────────────────────────────────────────
	$upload_map = [
		'login_logo_url' => 'login_logo_file',
		'menu_icon_url'  => 'menu_icon_file',
		'favicon_url'    => 'favicon_file',
	];
	foreach ( $upload_map as $field => $file_key ) {
		$url = apc_handle_upload( $file_key );
		if ( is_wp_error( $url ) ) {
			add_settings_error( 'apc', 'upload_error', $url->get_error_message() );
			$data[ $field ] = $saved[ $field ] ?? $defaults[ $field ];
		} elseif ( $url !== null ) {
			$data[ $field ] = $url;
		} else {
			$data[ $field ] = $saved[ $field ] ?? $defaults[ $field ];
		}
	}

	// Botões para remover imagem
	foreach ( [ 'login_logo' => 'login_logo_url', 'menu_icon' => 'menu_icon_url', 'favicon' => 'favicon_url' ] as $btn => $field ) {
		if ( isset( $_POST[ 'remove_' . $btn ] ) ) {
			$data[ $field ] = '';
		}
	}

	update_option( APC_OPTION, $data );

	wp_safe_redirect(
		add_query_arg( [ 'page' => 'apc-settings', 'updated' => '1' ], admin_url( 'options-general.php' ) )
	);
	exit;
}

/* ── Assets da página ───────────────────────────────────────────────── */

add_action( 'admin_enqueue_scripts', 'apc_enqueue' );
function apc_enqueue( $hook ) {
	if ( $hook !== 'settings_page_apc-settings' ) {
		return;
	}
	wp_enqueue_style( 'apc-admin', APC_URL . 'assets/css/admin.css', [], APC_VERSION );
	wp_enqueue_script( 'apc-admin', APC_URL . 'assets/js/admin.js', [ 'jquery' ], APC_VERSION, true );

	wp_localize_script( 'apc-admin', 'apcDefaults', apc_defaults() );
}

/* ── Renderiza a página ─────────────────────────────────────────────── */

function apc_render_page() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}
	$s = apc_get();
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

			<div class="apc-tabs">
				<button type="button" class="apc-tab active" data-tab="colors"><?php esc_html_e( 'Cores', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="typo"><?php esc_html_e( 'Tipografia', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="layout"><?php esc_html_e( 'Layout', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="logos"><?php esc_html_e( 'Logotipos', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="footer"><?php esc_html_e( 'Rodapé', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="login"><?php esc_html_e( 'Login', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="advanced"><?php esc_html_e( 'Avançado', 'apc' ); ?></button>
			</div>

			<!-- ── CORES ────────────────────────────────────────────── -->
			<div class="apc-panel active" id="apc-tab-colors">

				<div class="apc-section">
					<h2><?php esc_html_e( 'Menu Lateral', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'menu_bg',                __( 'Fundo do menu', 'apc' ),               $s );
						apc_color_field( 'menu_text',              __( 'Texto do menu', 'apc' ),                $s );
						apc_color_field( 'menu_highlight_bg',      __( 'Item ativo — fundo', 'apc' ),           $s );
						apc_color_field( 'menu_highlight_text',    __( 'Item ativo — texto', 'apc' ),           $s );
						apc_color_field( 'menu_border_color',      __( 'Divisor/borda', 'apc' ),                $s );
						apc_color_field( 'menu_submenu_bg',        __( 'Submenu — fundo', 'apc' ),              $s );
						apc_color_field( 'menu_submenu_text',      __( 'Submenu — texto', 'apc' ),              $s );
						apc_color_field( 'menu_submenu_hover_bg',  __( 'Submenu hover — fundo', 'apc' ),        $s );
						apc_color_field( 'menu_submenu_hover_text',__( 'Submenu hover — texto', 'apc' ),        $s );
						?>
					</div>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'Barra Superior (Admin Bar)', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'adminbar_bg',       __( 'Fundo da barra', 'apc' ),      $s );
						apc_color_field( 'adminbar_text',     __( 'Texto da barra', 'apc' ),       $s );
						apc_color_field( 'adminbar_hover_bg', __( 'Hover — fundo', 'apc' ),        $s );
						?>
					</div>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'Área de Conteúdo', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'content_bg',         __( 'Fundo da página', 'apc' ),      $s );
						apc_color_field( 'content_text',       __( 'Texto geral', 'apc' ),           $s );
						apc_color_field( 'content_box_bg',     __( 'Fundo das caixas', 'apc' ),      $s );
						apc_color_field( 'content_box_border', __( 'Borda das caixas', 'apc' ),      $s );
						apc_color_field( 'table_header_bg',    __( 'Cabeçalho de tabelas', 'apc' ),  $s );
						apc_color_field( 'button_bg',          __( 'Botão primário — fundo', 'apc' ),$s );
						apc_color_field( 'button_text',        __( 'Botão primário — texto', 'apc' ),$s );
						apc_color_field( 'link_color',         __( 'Cor dos links', 'apc' ),         $s );
						?>
					</div>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'Tela de Login', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'login_bg',          __( 'Fundo do login', 'apc' ),  $s );
						apc_color_field( 'login_button_bg',   __( 'Botão — fundo', 'apc' ),   $s );
						apc_color_field( 'login_button_text', __( 'Botão — texto', 'apc' ),   $s );
						?>
					</div>
				</div>

			</div>

			<!-- ── TIPOGRAFIA ───────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-typo">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Fonte', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="font_family"><?php esc_html_e( 'Família de fonte', 'apc' ); ?></label></th>
							<td>
								<input type="text" id="font_family" name="font_family"
									value="<?php echo esc_attr( $s['font_family'] ); ?>"
									class="regular-text"
									placeholder="Ex.: Inter, sans-serif">
								<p class="description"><?php esc_html_e( 'Deixe vazio para usar a fonte padrão do WordPress. Aceita qualquer valor CSS válido para font-family.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="google_font_url"><?php esc_html_e( 'URL do Google Fonts', 'apc' ); ?></label></th>
							<td>
								<input type="url" id="google_font_url" name="google_font_url"
									value="<?php echo esc_attr( $s['google_font_url'] ); ?>"
									class="large-text"
									placeholder="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap">
								<p class="description"><?php esc_html_e( 'Cole aqui o link gerado pelo Google Fonts para carregar a fonte no painel.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="font_size_base"><?php esc_html_e( 'Tamanho base (px)', 'apc' ); ?></label></th>
							<td>
								<input type="number" id="font_size_base" name="font_size_base"
									value="<?php echo esc_attr( $s['font_size_base'] ); ?>"
									class="small-text" min="10" max="24">
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="menu_font_size"><?php esc_html_e( 'Tamanho fonte do menu (px)', 'apc' ); ?></label></th>
							<td>
								<input type="number" id="menu_font_size" name="menu_font_size"
									value="<?php echo esc_attr( $s['menu_font_size'] ); ?>"
									class="small-text" min="10" max="24">
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ── LAYOUT ───────────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-layout">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Dimensões', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="menu_width"><?php esc_html_e( 'Largura do menu lateral (px)', 'apc' ); ?></label></th>
							<td>
								<input type="number" id="menu_width" name="menu_width"
									value="<?php echo esc_attr( $s['menu_width'] ); ?>"
									class="small-text" min="80" max="400">
								<p class="description"><?php esc_html_e( 'Padrão: 160 px', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="adminbar_height"><?php esc_html_e( 'Altura da barra superior (px)', 'apc' ); ?></label></th>
							<td>
								<input type="number" id="adminbar_height" name="adminbar_height"
									value="<?php echo esc_attr( $s['adminbar_height'] ); ?>"
									class="small-text" min="20" max="80">
								<p class="description"><?php esc_html_e( 'Padrão: 32 px', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="border_radius"><?php esc_html_e( 'Raio de borda (px)', 'apc' ); ?></label></th>
							<td>
								<input type="number" id="border_radius" name="border_radius"
									value="<?php echo esc_attr( $s['border_radius'] ); ?>"
									class="small-text" min="0" max="24">
								<p class="description"><?php esc_html_e( 'Aplica-se a caixas, botões e formulários. Padrão: 3 px', 'apc' ); ?></p>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ── LOGOTIPOS ────────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-logos">

				<div class="apc-section">
					<h2><?php esc_html_e( 'Ícone na Admin Bar (substituir logo WordPress)', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'PNG ou SVG de 20×20 px. Substitui o logo do WordPress no topo do painel.', 'apc' ); ?></p>
					<?php apc_image_field( 'menu_icon', 'menu_icon_file', $s['menu_icon_url'], 60 ); ?>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'Favicon Personalizado', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Substitui o favicon no painel administrativo e na tela de login. Use ICO, PNG 32×32 px ou SVG.', 'apc' ); ?></p>
					<?php apc_image_field( 'favicon', 'favicon_file', $s['favicon_url'], 32 ); ?>
				</div>

			</div>

			<!-- ── RODAPÉ ───────────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-footer">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Texto do Rodapé', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="footer_left"><?php esc_html_e( 'Rodapé esquerdo', 'apc' ); ?></label></th>
							<td>
								<textarea id="footer_left" name="footer_left" rows="3" class="large-text"><?php echo esc_textarea( $s['footer_left'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Substitui "Obrigado por criar com WordPress." Aceita HTML básico (links, negrito, itálico).', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="footer_right"><?php esc_html_e( 'Rodapé direito', 'apc' ); ?></label></th>
							<td>
								<textarea id="footer_right" name="footer_right" rows="3" class="large-text"><?php echo esc_textarea( $s['footer_right'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Substitui o número de versão do WordPress. Aceita HTML básico.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Ocultar versão', 'apc' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="hide_wp_version" value="1" <?php checked( $s['hide_wp_version'], '1' ); ?>>
									<?php esc_html_e( 'Ocultar completamente o número de versão do WordPress no rodapé direito', 'apc' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ── LOGIN ────────────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-login">

				<div class="apc-section">
					<h2><?php esc_html_e( 'Logotipo da Tela de Login', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Substitui o logo do WordPress na tela de login. Recomendado: PNG ou SVG 320×84 px.', 'apc' ); ?></p>
					<?php apc_image_field( 'login_logo', 'login_logo_file', $s['login_logo_url'], 84 ); ?>

					<table class="form-table" style="margin-top:12px;">
						<tr>
							<th scope="row"><label for="login_logo_width"><?php esc_html_e( 'Largura do logo (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="login_logo_width"  name="login_logo_width"  value="<?php echo esc_attr( $s['login_logo_width'] ); ?>"  class="small-text" min="1" max="600"></td>
						</tr>
						<tr>
							<th scope="row"><label for="login_logo_height"><?php esc_html_e( 'Altura do logo (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="login_logo_height" name="login_logo_height" value="<?php echo esc_attr( $s['login_logo_height'] ); ?>" class="small-text" min="1" max="600"></td>
						</tr>
						<tr>
							<th scope="row"><label for="login_title"><?php esc_html_e( 'Título/alt do logo', 'apc' ); ?></label></th>
							<td>
								<input type="text" id="login_title" name="login_title" value="<?php echo esc_attr( $s['login_title'] ); ?>" class="regular-text" placeholder="<?php esc_attr_e( 'Nome do seu site', 'apc' ); ?>">
								<p class="description"><?php esc_html_e( 'Texto alternativo e tooltip exibidos sobre o logo.', 'apc' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

			</div>

			<!-- ── AVANÇADO ─────────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-advanced">

				<div class="apc-section">
					<h2><?php esc_html_e( 'Ocultar Elementos do WordPress', 'apc' ); ?></h2>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Logo do WordPress', 'apc' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="hide_wp_logo" value="1" <?php checked( $s['hide_wp_logo'], '1' ); ?>>
									<?php esc_html_e( 'Ocultar o ícone/logo do WordPress na admin bar', 'apc' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Aba "Ajuda"', 'apc' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="hide_help_tab" value="1" <?php checked( $s['hide_help_tab'], '1' ); ?>>
									<?php esc_html_e( 'Ocultar o botão de ajuda contextual no canto superior direito', 'apc' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( '"Opções de tela"', 'apc' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="hide_screen_options" value="1" <?php checked( $s['hide_screen_options'], '1' ); ?>>
									<?php esc_html_e( 'Ocultar o botão "Opções de Tela" no canto superior direito', 'apc' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Aviso de atualização', 'apc' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="hide_update_nag" value="1" <?php checked( $s['hide_update_nag'], '1' ); ?>>
									<?php esc_html_e( 'Ocultar o aviso "Atualização disponível" no topo das páginas', 'apc' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'CSS Personalizado', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Adicione regras CSS que serão injetadas em todas as páginas do painel. Não inclua a tag &lt;style&gt;.', 'apc' ); ?></p>
					<textarea id="custom_css" name="custom_css" rows="12"
						class="large-text code"
						spellcheck="false"><?php echo esc_textarea( $s['custom_css'] ); ?></textarea>
				</div>

			</div>

			<div class="apc-footer-actions">
				<button type="submit" class="button button-primary button-large">
					<?php esc_html_e( 'Salvar Configurações', 'apc' ); ?>
				</button>
				<button type="button" id="apc-reset" class="button button-secondary">
					<?php esc_html_e( 'Restaurar Padrões', 'apc' ); ?>
				</button>
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
		printf(
			'<div class="apc-img-preview"><img src="%s" alt="" style="max-height:%dpx;"></div>',
			esc_url( $current_url ),
			(int) $max_height
		);
		printf(
			'<p><label><input type="checkbox" name="remove_%s" value="1"> %s</label></p>',
			esc_attr( $id ),
			esc_html__( 'Remover imagem atual', 'apc' )
		);
	}
	printf( '<input type="file" name="%s" accept="image/*">', esc_attr( $file_key ) );
}
