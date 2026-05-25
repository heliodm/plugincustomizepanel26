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

	/* ── Troca de abas (preserva aba no hash) ── */
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

	// Restaura aba do hash ao carregar
	var hash = window.location.hash.replace('#', '');
	if ( hash && $('#apc-tab-' + hash).length ) {
		activateTab(hash);
	}

	/* ── Restaurar padrões ── */
	$('#apc-reset').on('click', function () {
		if ( !window.confirm('Restaurar todos os valores para o padrão do WordPress? Esta ação não pode ser desfeita.') ) {
			return;
		}

		// Campos de cor
		var colorDefaults = {
			menu_bg:                 '#1d2327',
			menu_text:               '#a7aaad',
			menu_highlight_bg:       '#2271b1',
			menu_highlight_text:     '#ffffff',
			menu_submenu_bg:         '#2c3338',
			menu_submenu_text:       '#c3c4c7',
			menu_submenu_hover_bg:   '#2271b1',
			menu_submenu_hover_text: '#ffffff',
			menu_border_color:       '#2c3338',
			adminbar_bg:             '#1d2327',
			adminbar_text:           '#a7aaad',
			adminbar_hover_bg:       '#2c3338',
			content_bg:              '#f0f0f1',
			content_text:            '#3c434a',
			content_box_bg:          '#ffffff',
			content_box_border:      '#c3c4c7',
			table_header_bg:         '#f6f7f7',
			button_bg:               '#2271b1',
			button_text:             '#ffffff',
			link_color:              '#2271b1',
			login_bg:                '#f0f0f1',
			login_button_bg:         '#2271b1',
			login_button_text:       '#ffffff'
		};

		$.each(colorDefaults, function (key, val) {
			$('#' + key).val(val);
			$('.apc-hex[data-for="' + key + '"]').val(val);
		});

		// Campos numéricos
		$('#menu_width').val('160');
		$('#adminbar_height').val('32');
		$('#border_radius').val('3');
		$('#font_size_base').val('13');
		$('#menu_font_size').val('13');

		// Campos de texto
		$('#font_family').val('');
		$('#google_font_url').val('');
		$('#footer_left').val('');
		$('#footer_right').val('');
		$('#login_logo_width').val('84');
		$('#login_logo_height').val('84');
		$('#login_title').val('');
		$('#custom_css').val('');

		// Checkboxes
		$('input[name="hide_wp_version"], input[name="hide_wp_logo"], input[name="hide_help_tab"], input[name="hide_screen_options"], input[name="hide_update_nag"]').prop('checked', false);
	});

}(jQuery));
