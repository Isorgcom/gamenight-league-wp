(function () {
	'use strict';

	if (typeof window.GNLJoin === 'undefined') {
		return;
	}

	function setStatus(form, message, kind) {
		var node = form.querySelector('.gnl-join__status');
		if (!node) { return; }
		node.textContent = message;
		node.className = 'gnl-join__status' + (kind ? ' gnl-join__status--' + kind : '');
	}

	function bindForm(form) {
		if (form.dataset.gnlJoinBound === '1') { return; }
		form.dataset.gnlJoinBound = '1';

		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var btn = form.querySelector('button[type="submit"]');
			if (btn) { btn.disabled = true; }
			setStatus(form, window.GNLJoin.strings.submitting, '');

			var data = new FormData(form);
			var payload = {
				display_name: (data.get('display_name') || '').toString().trim(),
				email: (data.get('email') || '').toString().trim(),
				phone: (data.get('phone') || '').toString().trim()
			};

			fetch(window.GNLJoin.endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': window.GNLJoin.nonce
				},
				body: JSON.stringify(payload)
			})
				.then(function (resp) {
					return resp.json().then(function (json) {
						return { status: resp.status, body: json };
					});
				})
				.then(function (result) {
					if (result.status >= 200 && result.status < 300 && result.body && result.body.ok) {
						setStatus(form, result.body.message || window.GNLJoin.strings.welcome, 'ok');
						form.reset();
					} else {
						var msg = (result.body && (result.body.message || result.body.code)) || window.GNLJoin.strings.error;
						setStatus(form, msg, 'err');
					}
				})
				.catch(function () {
					setStatus(form, window.GNLJoin.strings.error, 'err');
				})
				.then(function () {
					if (btn) { btn.disabled = false; }
				});
		});
	}

	// Standalone forms.
	document.querySelectorAll('form[data-gnl-join]').forEach(bindForm);

	// Roster reveal-then-bind: a [data-gnl-join-reveal] container holds a button
	// that, when clicked, expands a form already in the DOM but hidden.
	document.querySelectorAll('[data-gnl-join-reveal]').forEach(function (container) {
		var btn = container.querySelector('.gnl-join-reveal__button');
		var slot = container.querySelector('.gnl-join-reveal__slot');
		if (!btn || !slot) { return; }
		btn.addEventListener('click', function () {
			slot.hidden = false;
			btn.style.display = 'none';
			var form = slot.querySelector('form[data-gnl-join]');
			if (form) {
				bindForm(form);
				var first = form.querySelector('input[name="display_name"]');
				if (first) { first.focus(); }
			}
		});
	});
})();
