(function () {
	'use strict';

	if (typeof window.GNLRsvp === 'undefined') {
		return;
	}

	function setStatus(form, message, kind) {
		var node = form.querySelector('.gnl-rsvp__status');
		if (!node) { return; }
		node.textContent = message;
		node.className = 'gnl-rsvp__status' + (kind ? ' gnl-rsvp__status--' + kind : '');
	}

	function bind(form) {
		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var btn = form.querySelector('button[type="submit"]');
			if (btn) { btn.disabled = true; }
			setStatus(form, window.GNLRsvp.strings.submitting, '');

			var data = new FormData(form);
			var payload = {
				event_id: parseInt(data.get('event_id'), 10),
				display_name: data.get('display_name') || '',
				email: data.get('email') || '',
				phone: data.get('phone') || '',
				rsvp: data.get('rsvp') || ''
			};

			fetch(window.GNLRsvp.endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/json',
					'X-WP-Nonce': window.GNLRsvp.nonce
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
						setStatus(form, result.body.message || window.GNLRsvp.strings.thanks, 'ok');
						form.reset();
					} else {
						var msg = (result.body && (result.body.message || result.body.code)) || window.GNLRsvp.strings.error;
						setStatus(form, msg, 'err');
					}
				})
				.catch(function () {
					setStatus(form, window.GNLRsvp.strings.error, 'err');
				})
				.then(function () {
					if (btn) { btn.disabled = false; }
				});
		});
	}

	document.querySelectorAll('form[data-gnl-rsvp]').forEach(bind);
})();
