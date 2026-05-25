/* Admin Panel Customizer */
(function ($) {
	'use strict';

	/* ── Color picker ↔ hex input ── */
	$(document).on('input', 'input[type="color"]', function () {
		$('.apc-hex[data-for="' + $(this).attr('id') + '"]').val( $(this).val() );
	});

	$(document).on('input', '.apc-hex', function () {
		var val = $(this).val().trim();
		if ( /^#[0-9A-Fa-f]{6}$/.test(val) ) {
			$('#' + $(this).data('for')).val(val);
		}
	});

	/* ── Troca de abas ── */
	function activateTab(tab) {
		$('.apc-tab').removeClass('active');
		$('.apc-tab[data-tab="' + tab + '"]').addClass('active');
		$('.apc-panel').removeClass('active');
		$('#apc-tab-' + tab).addClass('active');
	}

	$(document).on('click', '.apc-tab', function () {
		var tab = $(this).data('tab');
		activateTab(tab);
		history.replaceState(null, '', '#' + tab);
	});

	var hash = window.location.hash.replace('#', '');
	if ( hash && $('#apc-tab-' + hash).length ) {
		activateTab(hash);
	}

	/* ── Aplica um conjunto de cores aos campos ── */
	function applyColors(colors) {
		$.each(colors, function (key, val) {
			if ( /^#[0-9A-Fa-f]{6}$/.test(val) ) {
				$('#' + key).val(val);
				$('.apc-hex[data-for="' + key + '"]').val(val);
			}
		});
	}

	/* ── Presets de tema ── */
	var presetsData = null;
	try {
		var raw = document.getElementById('apc-preset-data');
		if (raw) { presetsData = JSON.parse(raw.textContent); }
	} catch(e) {}

	$(document).on('click', '.apc-preset-btn', function () {
		var id = $(this).data('preset');
		if ( !presetsData || !presetsData[id] ) { return; }

		$('.apc-preset-btn').removeClass('active');
		$(this).addClass('active');

		applyColors( presetsData[id] );
	});

	/* ── Restaurar padrões ── */
	$('#apc-reset').on('click', function () {
		if ( !window.confirm('Restaurar todos os valores para o padrão do WordPress? Essa ação não pode ser desfeita.') ) {
			return;
		}

		// Usa os defaults enviados pelo PHP via wp_localize_script
		var d = window.apcDefaults || {};

		// Aplica cores
		var colorKeys = [
			'menu_bg','menu_text','menu_highlight_bg','menu_highlight_text',
			'menu_submenu_bg','menu_submenu_text','menu_submenu_hover_bg','menu_submenu_hover_text',
			'menu_border_color','adminbar_bg','adminbar_text','adminbar_hover_bg',
			'content_bg','content_text','content_box_bg','content_box_border',
			'table_header_bg','metabox_header_bg','metabox_header_text',
			'button_bg','button_text','link_color',
			'input_bg','input_border','input_focus_border','input_text',
			'badge_bg','badge_text',
			'notice_success_border','notice_error_border','notice_warning_border','notice_info_border',
			'login_bg','login_form_bg','login_form_border','login_button_bg','login_button_text'
		];
		colorKeys.forEach(function(key) {
			if (d[key]) {
				$('#' + key).val(d[key]);
				$('.apc-hex[data-for="' + key + '"]').val(d[key]);
			}
		});

		// Campos numéricos
		['menu_width','adminbar_height','border_radius','font_size_base','menu_font_size',
		 'login_logo_width','login_logo_height'].forEach(function(key) {
			if (d[key] !== undefined) { $('#' + key).val(d[key]); }
		});

		// Campos de texto e URL
		['font_family','google_font_url','footer_left','footer_right','login_title',
		 'login_redirect_url','login_back_text','content_max_width',
		 'mail_from_name','mail_from_email','custom_css','login_custom_css'].forEach(function(key) {
			$('#' + key).val('');
		});

		// Checkboxes
		$('input[type="checkbox"][name]').prop('checked', false);

		// Limpa seleção de preset ativo
		$('.apc-preset-btn').removeClass('active');
	});

}(jQuery));
