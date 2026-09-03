(function ($) {
	'use strict';

	let sequence = Date.now();

	function uniqueKey(prefix) {
		sequence += 1;
		return prefix + sequence;
	}

	function template(id, replacements) {
		let html = $(id).html();
		Object.keys(replacements).forEach(function (token) {
			html = html.split(token).join(replacements[token]);
		});
		return html;
	}

	function initializeEditors(scope) {
		$(scope).find('textarea.ida-rich-editor').each(function () {
			const editor = this;
			if (!editor.id || $(editor).data('ida-ready')) {
				return;
			}
			$(editor).data('ida-ready', true);
			if (window.wp && wp.editor && wp.editor.initialize) {
				wp.editor.initialize(editor.id, {
					tinymce: {
						wpautop: true,
						toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,link,unlink,undo,redo'
					},
					quicktags: true,
					mediaButtons: false
				});
			}
		});
	}

	function updateLayout(section) {
		const selected = section.find('.ida-layout-select').val();
		section.find('.ida-layout-panel').hide().filter('[data-layout-panel="' + selected + '"]').show();
	}

	$(function () {
		const sections = $('#ida-sections');
		initializeEditors(document);
		sections.find('.ida-section').each(function () {
			updateLayout($(this));
		});

		$('#ida-add-section').on('click', function () {
			const sectionKey = uniqueKey('s');
			const node = $(template('#tmpl-ida-section', { '__SECTION__': sectionKey }));
			sections.append(node);
			initializeEditors(node);
			updateLayout(node);
			node.find('.ida-title-input').trigger('focus');
		});

		sections.on('change', '.ida-layout-select', function () {
			updateLayout($(this).closest('.ida-section'));
		});

		sections.on('input', '.ida-title-input', function () {
			$(this).closest('.ida-section').find('.ida-section-label').text(this.value || 'New section');
		});

		sections.on('click', '.ida-remove-section', function () {
			if (!window.confirm(idaAdmin.confirmRemove)) {
				return;
			}
			const section = $(this).closest('.ida-section');
			section.find('.ida-rich-editor').each(function () {
				if (window.wp && wp.editor && wp.editor.remove && this.id) {
					wp.editor.remove(this.id);
				}
			});
			section.remove();
		});

		sections.on('click', '.ida-move-up', function () {
			const section = $(this).closest('.ida-section');
			const previous = section.prev('.ida-section');
			if (previous.length) {
				section.insertBefore(previous);
			}
		});

		sections.on('click', '.ida-move-down', function () {
			const section = $(this).closest('.ida-section');
			const next = section.next('.ida-section');
			if (next.length) {
				section.insertAfter(next);
			}
		});

		sections.on('click', '.ida-add-rate-row', function () {
			const section = $(this).closest('.ida-section');
			const row = template('#tmpl-ida-rate-row', {
				'__SECTION__': section.data('section'),
				'__ROW__': uniqueKey('r')
			});
			section.find('.ida-rate-rows').append(row);
		});

		sections.on('click', '.ida-add-itinerary-row', function () {
			const section = $(this).closest('.ida-section');
			const row = template('#tmpl-ida-itinerary-row', {
				'__SECTION__': section.data('section'),
				'__ROW__': uniqueKey('r')
			});
			section.find('.ida-itinerary-rows').append(row);
		});

		sections.on('click', '.ida-remove-row', function () {
			$(this).closest('.ida-data-row').remove();
		});

		$('#post').on('submit', function () {
			if (window.tinyMCE) {
				window.tinyMCE.triggerSave();
			}
		});
	});
})(jQuery);
