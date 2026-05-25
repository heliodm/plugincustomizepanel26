<?php
defined( 'ABSPATH' ) || exit;

/* ── Menu de configurações ──────────────────────────────────────────── */

add_action( 'admin_menu', 'apc_add_menu' );
function apc_add_menu() {
	add_menu_page(
		__( 'Personalizar Painel', 'apc' ),
		__( 'Personalizar Painel', 'apc' ),
		'manage_options',
		'apc-settings',
		'apc_render_page',
		'dashicons-art',
		80
	);
}

/* ── Salvar configurações ───────────────────────────────────────────── */

add_action( 'admin_init', 'apc_save_settings' );
function apc_save_settings() {
	if (
		! isset( $_POST['apc_nonce'] ) ||
		! wp_verify_nonce( $_POST['apc_nonce'], 'apc_save' ) ||
		! current_user_can( 'manage_options' )
	) {
		return;
	}

	$defaults = apc_defaults();
	$saved    = (array) get_option( APC_OPTION, [] );
	$data     = [];

	foreach ( $defaults as $key => $default ) {
		if ( isset( $_POST[ $key ] ) ) {
			$data[ $key ] = sanitize_text_field( wp_unslash( $_POST[ $key ] ) );
		} else {
			// checkboxes desmarcados não vêm no POST
			$data[ $key ] = $default;
		}
	}

	// Trata checkbox hide_wp_version
	$data['hide_wp_version'] = isset( $_POST['hide_wp_version'] ) ? '1' : '0';

	// Trata uploads
	foreach ( [ 'login_logo_url' => 'login_logo_file', 'menu_icon_url' => 'menu_icon_file' ] as $field => $file_key ) {
		$url = apc_handle_upload( $file_key );
		if ( is_wp_error( $url ) ) {
			add_settings_error( 'apc', 'upload_error', $url->get_error_message() );
		} elseif ( $url !== null ) {
			$data[ $field ] = $url;
		} else {
			// mantém o valor já salvo
			$data[ $field ] = $saved[ $field ] ?? $defaults[ $field ];
		}
	}

	// Permite apagar logotipo
	if ( isset( $_POST['remove_login_logo'] ) ) {
		$data['login_logo_url'] = '';
	}
	if ( isset( $_POST['remove_menu_icon'] ) ) {
		$data['menu_icon_url'] = '';
	}

	update_option( APC_OPTION, $data );

	wp_safe_redirect( add_query_arg( [ 'page' => 'apc-settings', 'updated' => '1' ], admin_url( 'admin.php' ) ) );
	exit;
}

/* ── Carrega assets na página do plugin ─────────────────────────────── */

add_action( 'admin_enqueue_scripts', 'apc_enqueue' );
function apc_enqueue( $hook ) {
	if ( $hook !== 'toplevel_page_apc-settings' ) {
		return;
	}
	wp_enqueue_style(
		'apc-admin',
		APC_URL . 'assets/css/admin.css',
		[],
		APC_VERSION
	);
	wp_enqueue_script(
		'apc-admin',
		APC_URL . 'assets/js/admin.js',
		[ 'jquery' ],
		APC_VERSION,
		true
	);
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
				<button type="button" class="apc-tab" data-tab="logos"><?php esc_html_e( 'Logotipos', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="footer"><?php esc_html_e( 'Rodapé', 'apc' ); ?></button>
				<button type="button" class="apc-tab" data-tab="login"><?php esc_html_e( 'Login', 'apc' ); ?></button>
			</div>

			<!-- ── ABA: CORES ───────────────────────────────────────── -->
			<div class="apc-panel active" id="apc-tab-colors">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Menu Lateral', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'menu_bg',             __( 'Fundo do menu', 'apc' ),           $s );
						apc_color_field( 'menu_text',           __( 'Texto do menu', 'apc' ),            $s );
						apc_color_field( 'menu_highlight_bg',   __( 'Destaque — fundo', 'apc' ),         $s );
						apc_color_field( 'menu_highlight_text', __( 'Destaque — texto', 'apc' ),         $s );
						apc_color_field( 'menu_submenu_bg',     __( 'Fundo do submenu', 'apc' ),         $s );
						?>
					</div>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'Barra Superior (Admin Bar)', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'adminbar_bg',   __( 'Fundo da barra', 'apc' ), $s );
						apc_color_field( 'adminbar_text', __( 'Texto da barra', 'apc' ), $s );
						?>
					</div>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'Área de Conteúdo', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'content_bg',    __( 'Fundo do conteúdo', 'apc' ), $s );
						apc_color_field( 'content_text',  __( 'Texto do conteúdo', 'apc' ), $s );
						apc_color_field( 'button_bg',     __( 'Botão primário — fundo', 'apc' ), $s );
						apc_color_field( 'button_text',   __( 'Botão primário — texto', 'apc' ), $s );
						apc_color_field( 'link_color',    __( 'Cor dos links', 'apc' ), $s );
						?>
					</div>
				</div>
			</div>

			<!-- ── ABA: LOGOTIPOS ───────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-logos">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Ícone na Barra Superior (logo WordPress)', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Substitui o logo do WordPress exibido no topo do painel. Use PNG/SVG de 20×20 px ou maior.', 'apc' ); ?></p>
					<?php if ( ! empty( $s['menu_icon_url'] ) ) : ?>
						<div class="apc-img-preview">
							<img src="<?php echo esc_url( $s['menu_icon_url'] ); ?>" alt="" style="max-height:60px;">
						</div>
						<label>
							<input type="checkbox" name="remove_menu_icon" value="1">
							<?php esc_html_e( 'Remover ícone personalizado', 'apc' ); ?>
						</label>
					<?php endif; ?>
					<input type="file" name="menu_icon_file" accept="image/*">
					<input type="hidden" name="menu_icon_url" value="<?php echo esc_attr( $s['menu_icon_url'] ); ?>">
				</div>
			</div>

			<!-- ── ABA: RODAPÉ ──────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-footer">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Texto do Rodapé', 'apc' ); ?></h2>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="footer_left"><?php esc_html_e( 'Rodapé esquerdo', 'apc' ); ?></label>
							</th>
							<td>
								<textarea id="footer_left" name="footer_left" rows="3" class="large-text"><?php echo esc_textarea( $s['footer_left'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Substitui "Obrigado por criar com WordPress." Aceita HTML básico.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="footer_right"><?php esc_html_e( 'Rodapé direito (versão)', 'apc' ); ?></label>
							</th>
							<td>
								<textarea id="footer_right" name="footer_right" rows="3" class="large-text"><?php echo esc_textarea( $s['footer_right'] ); ?></textarea>
								<p class="description"><?php esc_html_e( 'Substitui o número da versão do WordPress exibido no canto direito. Aceita HTML básico.', 'apc' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Ocultar versão', 'apc' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="hide_wp_version" value="1" <?php checked( $s['hide_wp_version'], '1' ); ?>>
									<?php esc_html_e( 'Ocultar completamente o número de versão do WordPress no rodapé', 'apc' ); ?>
								</label>
							</td>
						</tr>
					</table>
				</div>
			</div>

			<!-- ── ABA: LOGIN ───────────────────────────────────────── -->
			<div class="apc-panel" id="apc-tab-login">
				<div class="apc-section">
					<h2><?php esc_html_e( 'Tela de Login', 'apc' ); ?></h2>
					<div class="apc-grid">
						<?php
						apc_color_field( 'login_bg',           __( 'Fundo da tela de login', 'apc' ), $s );
						apc_color_field( 'login_button_bg',    __( 'Botão — fundo', 'apc' ),           $s );
						apc_color_field( 'login_button_text',  __( 'Botão — texto', 'apc' ),           $s );
						?>
					</div>
				</div>

				<div class="apc-section">
					<h2><?php esc_html_e( 'Logotipo da Tela de Login', 'apc' ); ?></h2>
					<p class="description"><?php esc_html_e( 'Substitui o logo do WordPress na tela de login. Use PNG/SVG 320×84 px.', 'apc' ); ?></p>

					<?php if ( ! empty( $s['login_logo_url'] ) ) : ?>
						<div class="apc-img-preview">
							<img src="<?php echo esc_url( $s['login_logo_url'] ); ?>" alt="" style="max-height:84px;">
						</div>
						<label>
							<input type="checkbox" name="remove_login_logo" value="1">
							<?php esc_html_e( 'Remover logotipo personalizado', 'apc' ); ?>
						</label>
					<?php endif; ?>

					<input type="file" name="login_logo_file" accept="image/*">
					<input type="hidden" name="login_logo_url" value="<?php echo esc_attr( $s['login_logo_url'] ); ?>">

					<table class="form-table" style="margin-top:12px;">
						<tr>
							<th scope="row"><label for="login_logo_width"><?php esc_html_e( 'Largura (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="login_logo_width"  name="login_logo_width"  value="<?php echo esc_attr( $s['login_logo_width'] ); ?>"  class="small-text" min="1" max="600"></td>
						</tr>
						<tr>
							<th scope="row"><label for="login_logo_height"><?php esc_html_e( 'Altura (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="login_logo_height" name="login_logo_height" value="<?php echo esc_attr( $s['login_logo_height'] ); ?>" class="small-text" min="1" max="600"></td>
						</tr>
					</table>
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

/* ── Helper: campo de cor ───────────────────────────────────────────── */

function apc_color_field( $key, $label, $s ) {
	$value = esc_attr( $s[ $key ] ?? '#000000' );
	printf(
		'<div class="apc-field">
			<label for="%1$s">%2$s</label>
			<div class="apc-color-wrap">
				<input type="color" id="%1$s" name="%1$s" value="%3$s">
				<input type="text"  class="apc-hex" data-for="%1$s" value="%3$s" maxlength="7" pattern="#[0-9A-Fa-f]{6}">
			</div>
		</div>',
		esc_attr( $key ),
		esc_html( $label ),
		$value
	);
}
