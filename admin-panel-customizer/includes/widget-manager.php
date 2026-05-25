<?php
defined( 'ABSPATH' ) || exit;

define( 'APC_WIDGETS_OPTION', 'apc_custom_widgets' );

/* ── CRUD ───────────────────────────────────────────────────────────── */

function apc_get_widgets() {
	return (array) get_option( APC_WIDGETS_OPTION, [] );
}

function apc_sanitize_widget( $raw ) {
	if ( ! is_array( $raw ) || empty( $raw['title'] ) ) {
		return null;
	}

	$allowed_types      = [ 'html', 'links', 'iframe' ];
	$allowed_positions  = [ 'normal', 'side' ];
	$allowed_priorities = [ 'default', 'high', 'low' ];
	$allowed_caps       = [ 'manage_options', 'edit_posts', 'read' ];

	$widget = [
		'id'            => preg_match( '/^apcw_[a-f0-9]+$/', $raw['id'] ?? '' )
			? $raw['id']
			: 'apcw_' . bin2hex( random_bytes( 6 ) ),
		'title'         => sanitize_text_field( $raw['title'] ),
		'type'          => in_array( $raw['type'] ?? '', $allowed_types, true ) ? $raw['type'] : 'html',
		'content'       => wp_kses_post( $raw['content'] ?? '' ),
		'iframe_url'    => esc_url_raw( $raw['iframe_url'] ?? '' ),
		'iframe_height' => max( 50, min( 1200, absint( $raw['iframe_height'] ?? 300 ) ) ),
		'position'      => in_array( $raw['position'] ?? '', $allowed_positions, true ) ? $raw['position'] : 'normal',
		'priority'      => in_array( $raw['priority'] ?? '', $allowed_priorities, true ) ? $raw['priority'] : 'default',
		'capability'    => in_array( $raw['capability'] ?? '', $allowed_caps, true ) ? $raw['capability'] : 'manage_options',
		'links'         => [],
	];

	// Block javascript: in iframe
	if ( preg_match( '/^javascript:/i', $widget['iframe_url'] ) ) {
		$widget['iframe_url'] = '';
	}

	// Sanitize links array
	if ( ! empty( $raw['links'] ) && is_array( $raw['links'] ) ) {
		foreach ( $raw['links'] as $link ) {
			if ( empty( $link['url'] ) ) {
				continue;
			}
			$url = esc_url_raw( $link['url'] );
			if ( preg_match( '/^javascript:/i', $url ) ) {
				continue;
			}
			$widget['links'][] = [
				'text'    => sanitize_text_field( $link['text'] ?? '' ),
				'url'     => $url,
				'new_tab' => ! empty( $link['new_tab'] ) ? '1' : '0',
			];
		}
	}

	return $widget;
}

/* ── Salvar widget ──────────────────────────────────────────────────── */

add_action( 'admin_init', 'apc_handle_widget_save' );
function apc_handle_widget_save() {
	if (
		! isset( $_POST['apc_widget_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apc_widget_nonce'] ) ), 'apc_widget_save' ) ||
		! current_user_can( 'manage_options' )
	) {
		return;
	}

	$widgets    = apc_get_widgets();
	$id         = sanitize_key( $_POST['apc_widget_id'] ?? '' );
	$is_new     = empty( $id );

	$raw = [
		'id'            => $is_new ? 'apcw_' . bin2hex( random_bytes( 6 ) ) : $id,
		'title'         => $_POST['widget_title']    ?? '',
		'type'          => $_POST['widget_type']     ?? 'html',
		'content'       => $_POST['widget_content']  ?? '',
		'iframe_url'    => $_POST['iframe_url']       ?? '',
		'iframe_height' => $_POST['iframe_height']    ?? '300',
		'position'      => $_POST['widget_position']  ?? 'normal',
		'priority'      => $_POST['widget_priority']  ?? 'default',
		'capability'    => $_POST['widget_capability'] ?? 'manage_options',
		'links'         => [],
	];

	// Links array from POST
	$link_texts   = array_values( (array) ( $_POST['link_text']    ?? [] ) );
	$link_urls    = array_values( (array) ( $_POST['link_url']     ?? [] ) );
	$link_newtabs = array_values( (array) ( $_POST['link_new_tab'] ?? [] ) );
	foreach ( $link_urls as $i => $url ) {
		if ( empty( $url ) ) {
			continue;
		}
		$raw['links'][] = [
			'text'    => $link_texts[ $i ]   ?? '',
			'url'     => $url,
			'new_tab' => $link_newtabs[ $i ] ?? '0',
		];
	}

	$widget = apc_sanitize_widget( $raw );
	if ( ! $widget ) {
		wp_safe_redirect( add_query_arg( [ 'page' => 'apc-settings', 'apc_tab' => 'widgets', 'werror' => '1' ], admin_url( 'options-general.php' ) ) );
		exit;
	}

	// Replace existing or append
	$found = false;
	foreach ( $widgets as &$w ) {
		if ( $w['id'] === $widget['id'] ) {
			$w     = $widget;
			$found = true;
			break;
		}
	}
	unset( $w );
	if ( ! $found ) {
		$widgets[] = $widget;
	}

	update_option( APC_WIDGETS_OPTION, $widgets );

	wp_safe_redirect( add_query_arg( [ 'page' => 'apc-settings', 'apc_tab' => 'widgets', 'wupdated' => '1' ], admin_url( 'options-general.php' ) ) );
	exit;
}

/* ── Excluir widget ─────────────────────────────────────────────────── */

add_action( 'admin_init', 'apc_handle_widget_delete' );
function apc_handle_widget_delete() {
	if (
		! isset( $_POST['apc_widget_delete_nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['apc_widget_delete_nonce'] ) ), 'apc_widget_delete' ) ||
		! current_user_can( 'manage_options' )
	) {
		return;
	}

	$id      = sanitize_key( $_POST['apc_widget_id'] ?? '' );
	$widgets = array_filter( apc_get_widgets(), fn( $w ) => $w['id'] !== $id );
	update_option( APC_WIDGETS_OPTION, array_values( $widgets ) );

	wp_safe_redirect( add_query_arg( [ 'page' => 'apc-settings', 'apc_tab' => 'widgets', 'wdeleted' => '1' ], admin_url( 'options-general.php' ) ) );
	exit;
}

/* ── Registra widgets no dashboard ──────────────────────────────────── */

add_action( 'wp_dashboard_setup', 'apc_register_custom_widgets' );
function apc_register_custom_widgets() {
	foreach ( apc_get_widgets() as $widget ) {
		if ( empty( $widget['id'] ) || empty( $widget['title'] ) ) {
			continue;
		}
		$cap = $widget['capability'] ?? 'manage_options';
		if ( ! current_user_can( $cap ) ) {
			continue;
		}
		wp_add_dashboard_widget(
			esc_attr( $widget['id'] ),
			esc_html( $widget['title'] ),
			function () use ( $widget ) {
				apc_render_widget_content( $widget );
			},
			null,
			null,
			$widget['position'] ?? 'normal',
			$widget['priority']  ?? 'default'
		);
	}
}

/* ── Renderiza conteúdo do widget ───────────────────────────────────── */

function apc_render_widget_content( $widget ) {
	$type = $widget['type'] ?? 'html';

	if ( $type === 'html' ) {
		echo wp_kses_post( $widget['content'] ?? '' );
		return;
	}

	if ( $type === 'links' ) {
		$links = $widget['links'] ?? [];
		if ( empty( $links ) ) {
			return;
		}
		echo '<ul class="apc-dash-links">';
		foreach ( $links as $link ) {
			if ( empty( $link['url'] ) ) {
				continue;
			}
			$target = ! empty( $link['new_tab'] ) && $link['new_tab'] === '1' ? ' target="_blank" rel="noopener noreferrer"' : '';
			$text   = ! empty( $link['text'] ) ? $link['text'] : $link['url'];
			printf(
				'<li><a href="%s"%s>%s</a></li>',
				esc_url( $link['url'] ),
				$target,
				esc_html( $text )
			);
		}
		echo '</ul>';
		return;
	}

	if ( $type === 'iframe' ) {
		$url    = $widget['iframe_url']    ?? '';
		$height = absint( $widget['iframe_height'] ?? 300 );
		if ( empty( $url ) ) {
			return;
		}
		printf(
			'<iframe src="%s" width="100%%" height="%d" frameborder="0" style="border:none;display:block;"></iframe>',
			esc_url( $url ),
			$height
		);
	}
}

/* ── CSS dos widgets no dashboard ───────────────────────────────────── */

add_action( 'admin_head', 'apc_dashboard_widget_css' );
function apc_dashboard_widget_css() {
	if ( get_current_screen()->id !== 'dashboard' ) {
		return;
	}
	echo '<style>.apc-dash-links{margin:0;padding:0;list-style:none;}.apc-dash-links li{padding:4px 0;border-bottom:1px solid #f0f0f1;}.apc-dash-links li:last-child{border-bottom:none;}.apc-dash-links a{text-decoration:none;}</style>' . "\n";
}

/* ── Renderiza a página de Widgets ──────────────────────────────────── */

function apc_render_widgets_page() {
	$widgets = apc_get_widgets();
	$edit_id = sanitize_key( $_GET['edit'] ?? '' );
	$editing = null;
	if ( $edit_id ) {
		foreach ( $widgets as $w ) {
			if ( $w['id'] === $edit_id ) {
				$editing = $w;
				break;
			}
		}
	}
	$base_url = admin_url( 'options-general.php?page=apc-settings&apc_tab=widgets' );
	?>
	<div class="wrap apc-wrap">
		<h1><?php esc_html_e( 'Personalizar Painel Administrativo', 'apc' ); ?></h1>

		<?php
		if ( isset( $_GET['wupdated'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Widget salvo com sucesso!', 'apc' ) . '</p></div>';
		} elseif ( isset( $_GET['wdeleted'] ) ) {
			echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Widget excluído.', 'apc' ) . '</p></div>';
		} elseif ( isset( $_GET['werror'] ) ) {
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Erro: o título do widget é obrigatório.', 'apc' ) . '</p></div>';
		}
		?>

		<?php apc_render_tabs( 'widgets' ); ?>

		<div class="apc-section" style="margin-bottom:20px;">
			<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;">
				<h2 style="margin:0;padding:0;border:none;font-size:14px;font-weight:600;"><?php esc_html_e( 'Widgets do Dashboard', 'apc' ); ?></h2>
				<?php if ( ! $editing ) : ?>
					<a href="#apc-widget-form" id="apc-add-widget-btn" class="button button-primary"><?php esc_html_e( '+ Adicionar Widget', 'apc' ); ?></a>
				<?php endif; ?>
			</div>

			<?php if ( empty( $widgets ) ) : ?>
				<p class="description"><?php esc_html_e( 'Nenhum widget criado ainda. Clique em "Adicionar Widget" para começar.', 'apc' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Título', 'apc' ); ?></th>
							<th style="width:120px;"><?php esc_html_e( 'Tipo', 'apc' ); ?></th>
							<th style="width:100px;"><?php esc_html_e( 'Posição', 'apc' ); ?></th>
							<th style="width:130px;"><?php esc_html_e( 'Ações', 'apc' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					$type_labels     = [ 'html' => 'HTML', 'links' => 'Links', 'iframe' => 'Iframe' ];
					$position_labels = [ 'normal' => __( 'Normal', 'apc' ), 'side' => __( 'Lateral', 'apc' ) ];
					foreach ( $widgets as $w ) :
						?>
						<tr class="<?php echo $editing && $editing['id'] === $w['id'] ? 'apc-row-editing' : ''; ?>">
							<td><strong><?php echo esc_html( $w['title'] ); ?></strong></td>
							<td><?php echo esc_html( $type_labels[ $w['type'] ] ?? $w['type'] ); ?></td>
							<td><?php echo esc_html( $position_labels[ $w['position'] ] ?? $w['position'] ); ?></td>
							<td>
								<a href="<?php echo esc_url( $base_url . '&edit=' . $w['id'] . '#apc-widget-form' ); ?>" class="button button-small">
									<?php esc_html_e( 'Editar', 'apc' ); ?>
								</a>
								<form method="post" style="display:inline;" onsubmit="return confirm('<?php esc_attr_e( 'Excluir este widget?', 'apc' ); ?>')">
									<?php wp_nonce_field( 'apc_widget_delete', 'apc_widget_delete_nonce' ); ?>
									<input type="hidden" name="apc_widget_id" value="<?php echo esc_attr( $w['id'] ); ?>">
									<button type="submit" class="button button-small apc-btn-danger"><?php esc_html_e( 'Excluir', 'apc' ); ?></button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>

		<!-- ── Formulário add / edit ── -->
		<div class="apc-section" id="apc-widget-form" style="<?php echo ( ! $editing && ! isset( $_GET['add'] ) ) ? 'display:none;' : ''; ?>">
			<h2 style="font-size:14px;font-weight:600;margin:0 0 16px;padding:0 0 10px;border-bottom:1px solid #f0f0f1;">
				<?php echo $editing ? esc_html__( 'Editar Widget', 'apc' ) : esc_html__( 'Novo Widget', 'apc' ); ?>
			</h2>

			<?php $w = $editing ?? []; ?>
			<form method="post" id="apc-widget-form-inner">
				<?php wp_nonce_field( 'apc_widget_save', 'apc_widget_nonce' ); ?>
				<input type="hidden" name="apc_widget_id" value="<?php echo esc_attr( $w['id'] ?? '' ); ?>">

				<table class="form-table">
					<tr>
						<th scope="row"><label for="widget_title"><?php esc_html_e( 'Título *', 'apc' ); ?></label></th>
						<td><input type="text" id="widget_title" name="widget_title" value="<?php echo esc_attr( $w['title'] ?? '' ); ?>" class="regular-text" required></td>
					</tr>
					<tr>
						<th scope="row"><label for="widget_type"><?php esc_html_e( 'Tipo', 'apc' ); ?></label></th>
						<td>
							<select id="widget_type" name="widget_type">
								<option value="html"   <?php selected( $w['type'] ?? 'html', 'html' ); ?>><?php esc_html_e( 'HTML / Texto', 'apc' ); ?></option>
								<option value="links"  <?php selected( $w['type'] ?? '', 'links' ); ?>><?php esc_html_e( 'Lista de Links', 'apc' ); ?></option>
								<option value="iframe" <?php selected( $w['type'] ?? '', 'iframe' ); ?>><?php esc_html_e( 'Iframe (URL externa)', 'apc' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<!-- HTML content -->
				<div id="apc-wtype-html" class="apc-wtype-section" <?php echo ( ( $w['type'] ?? 'html' ) !== 'html' ) ? 'style="display:none"' : ''; ?>>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="widget_content"><?php esc_html_e( 'Conteúdo', 'apc' ); ?></label></th>
							<td>
								<?php
								wp_editor(
									$w['content'] ?? '',
									'widget_content',
									[
										'textarea_name' => 'widget_content',
										'textarea_rows' => 8,
										'media_buttons' => false,
										'teeny'         => true,
									]
								);
								?>
								<p class="description"><?php esc_html_e( 'Aceita HTML (links, imagens, tabelas, estilos inline).', 'apc' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Links -->
				<div id="apc-wtype-links" class="apc-wtype-section" <?php echo ( ( $w['type'] ?? '' ) !== 'links' ) ? 'style="display:none"' : ''; ?>>
					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Links', 'apc' ); ?></th>
							<td>
								<div id="apc-links-list">
									<?php
									$links = $w['links'] ?? [];
									if ( empty( $links ) ) {
										$links = [ [ 'text' => '', 'url' => '', 'new_tab' => '0' ] ];
									}
									foreach ( $links as $i => $link ) :
										?>
										<div class="apc-link-row">
											<input type="text"  name="link_text[]"    value="<?php echo esc_attr( $link['text'] ?? '' ); ?>"   placeholder="<?php esc_attr_e( 'Texto', 'apc' ); ?>"  class="regular-text">
											<input type="url"   name="link_url[]"     value="<?php echo esc_attr( $link['url'] ?? '' ); ?>"    placeholder="https://"                                  class="regular-text">
											<label title="<?php esc_attr_e( 'Abrir em nova aba', 'apc' ); ?>">
												<input type="checkbox" name="link_new_tab[]" value="1" <?php checked( $link['new_tab'] ?? '0', '1' ); ?>> ↗
											</label>
											<button type="button" class="button apc-remove-link">✕</button>
										</div>
									<?php endforeach; ?>
								</div>
								<button type="button" id="apc-add-link" class="button"><?php esc_html_e( '+ Adicionar link', 'apc' ); ?></button>
							</td>
						</tr>
					</table>
				</div>

				<!-- Iframe -->
				<div id="apc-wtype-iframe" class="apc-wtype-section" <?php echo ( ( $w['type'] ?? '' ) !== 'iframe' ) ? 'style="display:none"' : ''; ?>>
					<table class="form-table">
						<tr>
							<th scope="row"><label for="iframe_url"><?php esc_html_e( 'URL do iframe', 'apc' ); ?></label></th>
							<td><input type="url" id="iframe_url" name="iframe_url" value="<?php echo esc_attr( $w['iframe_url'] ?? '' ); ?>" class="large-text" placeholder="https://"></td>
						</tr>
						<tr>
							<th scope="row"><label for="iframe_height"><?php esc_html_e( 'Altura (px)', 'apc' ); ?></label></th>
							<td><input type="number" id="iframe_height" name="iframe_height" value="<?php echo esc_attr( $w['iframe_height'] ?? '300' ); ?>" class="small-text" min="50" max="1200"></td>
						</tr>
					</table>
				</div>

				<!-- Common settings -->
				<table class="form-table">
					<tr>
						<th scope="row"><label for="widget_position"><?php esc_html_e( 'Posição', 'apc' ); ?></label></th>
						<td>
							<select id="widget_position" name="widget_position">
								<option value="normal" <?php selected( $w['position'] ?? 'normal', 'normal' ); ?>><?php esc_html_e( 'Normal (área principal)', 'apc' ); ?></option>
								<option value="side"   <?php selected( $w['position'] ?? '', 'side' ); ?>><?php esc_html_e( 'Lateral (coluna direita)', 'apc' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="widget_priority"><?php esc_html_e( 'Prioridade', 'apc' ); ?></label></th>
						<td>
							<select id="widget_priority" name="widget_priority">
								<option value="default" <?php selected( $w['priority'] ?? 'default', 'default' ); ?>><?php esc_html_e( 'Padrão', 'apc' ); ?></option>
								<option value="high"    <?php selected( $w['priority'] ?? '', 'high' ); ?>><?php esc_html_e( 'Alta (aparece primeiro)', 'apc' ); ?></option>
								<option value="low"     <?php selected( $w['priority'] ?? '', 'low' ); ?>><?php esc_html_e( 'Baixa (aparece por último)', 'apc' ); ?></option>
							</select>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="widget_capability"><?php esc_html_e( 'Visível para', 'apc' ); ?></label></th>
						<td>
							<select id="widget_capability" name="widget_capability">
								<option value="manage_options" <?php selected( $w['capability'] ?? 'manage_options', 'manage_options' ); ?>><?php esc_html_e( 'Administradores', 'apc' ); ?></option>
								<option value="edit_posts"     <?php selected( $w['capability'] ?? '', 'edit_posts' ); ?>><?php esc_html_e( 'Editores e acima', 'apc' ); ?></option>
								<option value="read"           <?php selected( $w['capability'] ?? '', 'read' ); ?>><?php esc_html_e( 'Todos os usuários logados', 'apc' ); ?></option>
							</select>
						</td>
					</tr>
				</table>

				<div class="apc-footer-actions">
					<button type="submit" class="button button-primary button-large"><?php echo $editing ? esc_html__( 'Salvar Widget', 'apc' ) : esc_html__( 'Criar Widget', 'apc' ); ?></button>
					<a href="<?php echo esc_url( $base_url ); ?>" class="button button-secondary"><?php esc_html_e( 'Cancelar', 'apc' ); ?></a>
				</div>
			</form>
		</div>
	</div>
	<?php
}
