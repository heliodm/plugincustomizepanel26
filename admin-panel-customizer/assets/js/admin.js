/* Admin Panel Customizer */
(function ($) {
	'use strict';

	/* ── Color picker ↔ hex input ── */
	$(document).on('input', 'input[type="color"]', function () {
		$('.apc-hex[data-for="' + $(this).attr('id') + '"]').val($(this).val());
	});
	$(document).on('input', '.apc-hex', function () {
		var val = $(this).val().trim();
		if (/^#[0-9A-Fa-f]{6}$/.test(val)) { $('#' + $(this).data('for')).val(val); }
	});

	/* ── Troca de abas (JS tabs na página de configurações) ── */
	function activateTab(tab) {
		$('.apc-tab[data-tab]').removeClass('active');
		$('.apc-tab[data-tab="' + tab + '"]').addClass('active');
		$('.apc-panel').removeClass('active');
		$('#apc-tab-' + tab).addClass('active');
	}

	$(document).on('click', '.apc-tab[data-tab]', function () {
		var tab = $(this).data('tab');
		activateTab(tab);
		history.replaceState(null, '', '#' + tab);
	});

	var hash = window.location.hash.replace('#', '');
	if (hash && $('#apc-tab-' + hash).length) { activateTab(hash); }

	/* ── Aplicar conjunto de cores ── */
	function applyColors(colors) {
		$.each(colors, function (key, val) {
			if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
				$('#' + key).val(val);
				$('.apc-hex[data-for="' + key + '"]').val(val);
			}
		});
	}

	/* ── Presets ── */
	var presetsData = null;
	try {
		var raw = document.getElementById('apc-preset-data');
		if (raw) { presetsData = JSON.parse(raw.textContent); }
	} catch(e) {}

	$(document).on('click', '.apc-preset-btn', function () {
		var id = $(this).data('preset');
		if (!presetsData || !presetsData[id]) { return; }
		$('.apc-preset-btn').removeClass('active');
		$(this).addClass('active');
		applyColors(presetsData[id]);
	});

	/* ── Restaurar padrões ── */
	$('#apc-reset').on('click', function () {
		if (!window.confirm('Restaurar todos os valores para o padrão do WordPress? Esta ação não pode ser desfeita.')) { return; }

		var d = window.apcDefaults || {};

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
			if (d[key]) { $('#' + key).val(d[key]); $('.apc-hex[data-for="' + key + '"]').val(d[key]); }
		});

		['menu_width','adminbar_height','border_radius','font_size_base','menu_font_size',
		 'login_logo_width','login_logo_height'].forEach(function(key) {
			if (d[key] !== undefined) { $('#' + key).val(d[key]); }
		});

		['font_family','google_font_url','footer_left','footer_right','login_title',
		 'login_redirect_url','login_back_text','content_max_width',
		 'mail_from_name','mail_from_email','custom_css','login_custom_css'].forEach(function(key) {
			$('#' + key).val('');
		});

		$('input[type="checkbox"][name]').prop('checked', false);
		$('.apc-preset-btn').removeClass('active');
	});

	/* ════════════════════════════════════════════════════════
	   WIDGETS
	   ════════════════════════════════════════════════════════ */

	/* ── Mostrar formulário ao clicar em Adicionar ── */
	$('#apc-add-widget-btn').on('click', function (e) {
		e.preventDefault();
		var $form = $('#apc-widget-form');
		$form.show();
		$('html, body').animate({ scrollTop: $form.offset().top - 40 }, 300);
	});

	/* ── Trocar seção conforme o tipo de widget ── */
	function switchWidgetType(type) {
		$('.apc-wtype-section').hide();
		$('#apc-wtype-' + type).show();
	}

	// Inicializa com o tipo atual
	var currentType = $('#widget_type').val();
	if (currentType) { switchWidgetType(currentType); }

	$(document).on('change', '#widget_type', function () {
		switchWidgetType($(this).val());
	});

	/* ── Template de linha de link ── */
	var linkRowTpl = '<div class="apc-link-row">' +
		'<input type="text" name="link_text[]" placeholder="Texto" class="regular-text">' +
		'<input type="url"  name="link_url[]"  placeholder="https://" class="regular-text">' +
		'<label title="Abrir em nova aba"><input type="checkbox" name="link_new_tab[]" value="1"> ↗</label>' +
		'<button type="button" class="button apc-remove-link">✕</button>' +
		'</div>';

	$(document).on('click', '#apc-add-link', function () {
		$('#apc-links-list').append(linkRowTpl);
	});

	$(document).on('click', '.apc-remove-link', function () {
		if ($('.apc-link-row').length > 1) {
			$(this).closest('.apc-link-row').remove();
		} else {
			$(this).closest('.apc-link-row').find('input[type="text"], input[type="url"]').val('');
		}
	});

}(jQuery));
