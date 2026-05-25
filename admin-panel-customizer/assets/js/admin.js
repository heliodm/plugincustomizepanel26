/* Admin Panel Customizer — scripts da página de configurações */
(function ($) {
	'use strict';

	/* Sincroniza color picker ↔ input de texto hex */
	$(document).on('input', 'input[type="color"]', function () {
		var hex = $(this).val();
		$('.apc-hex[data-for="' + $(this).attr('id') + '"]').val(hex);
	});

	$(document).on('input', '.apc-hex', function () {
		var val = $(this).val();
		if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
			$('#' + $(this).data('for')).val(val);
		}
	});

	/* Troca de abas */
	$(document).on('click', '.apc-tab', function () {
		var tab = $(this).data('tab');
		$('.apc-tab').removeClass('active');
		$(this).addClass('active');
		$('.apc-panel').removeClass('active');
		$('#apc-tab-' + tab).addClass('active');
	});

	/* Restaurar padrões */
	$('#apc-reset').on('click', function () {
		if (!window.confirm('Restaurar todos os valores para o padrão do WordPress?')) {
			return;
		}
		var defaults = {
			menu_bg:             '#1d2327',
			menu_text:           '#a7aaad',
			menu_highlight_bg:   '#2271b1',
			menu_highlight_text: '#ffffff',
			menu_submenu_bg:     '#2c3338',
			adminbar_bg:         '#1d2327',
			adminbar_text:       '#a7aaad',
			content_bg:          '#f0f0f1',
			content_text:        '#3c434a',
			button_bg:           '#2271b1',
			button_text:         '#ffffff',
			link_color:          '#2271b1',
			login_bg:            '#f0f0f1',
			login_button_bg:     '#2271b1',
			login_button_text:   '#ffffff'
		};
		$.each(defaults, function (key, val) {
			$('#' + key).val(val);
			$('.apc-hex[data-for="' + key + '"]').val(val);
		});
	});

}(jQuery));
