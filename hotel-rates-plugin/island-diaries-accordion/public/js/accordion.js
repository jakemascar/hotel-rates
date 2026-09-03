(function () {
	'use strict';

	function setState(item, open) {
		const button = item.querySelector('.ida-trigger');
		const panel = item.querySelector('.ida-panel');
		item.classList.toggle('is-open', open);
		button.setAttribute('aria-expanded', open ? 'true' : 'false');
		panel.hidden = !open;
	}

	document.addEventListener('click', function (event) {
		const button = event.target.closest('.ida-trigger');
		if (!button) {
			return;
		}
		const item = button.closest('.ida-item');
		const accordion = button.closest('[data-ida-accordion]');
		const willOpen = button.getAttribute('aria-expanded') !== 'true';

		if (willOpen && accordion.dataset.multiple !== 'true') {
			accordion.querySelectorAll('.ida-item.is-open').forEach(function (openItem) {
				if (openItem !== item) {
					setState(openItem, false);
				}
			});
		}

		setState(item, willOpen);
	});
})();
