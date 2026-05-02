(function () {
	'use strict';

	if (typeof window.GNLAdmin === 'undefined') { return; }

	var S = window.GNLAdmin.strings || {};

	function api(path, opts) {
		opts = opts || {};
		var headers = { 'X-WP-Nonce': window.GNLAdmin.nonce, 'Accept': 'application/json' };
		var init = { method: opts.method || 'GET', credentials: 'same-origin', headers: headers };
		if (opts.body !== undefined) {
			headers['Content-Type'] = 'application/json';
			init.body = JSON.stringify(opts.body);
		}
		return fetch(window.GNLAdmin.endpoint + path.replace(/^\//, ''), init).then(function (resp) {
			return resp.json().then(function (json) {
				if (!resp.ok || (json && json.ok === false)) {
					var msg = (json && (json.message || json.code)) || ('HTTP ' + resp.status);
					var err = new Error(msg);
					err.status = resp.status;
					err.body = json;
					throw err;
				}
				return json;
			});
		});
	}

	function setStatus(node, msg, kind) {
		if (!node) { return; }
		node.textContent = msg || '';
		node.className = (node.className.split(' ').filter(function (c) {
			return c.indexOf('gnl-status--') !== 0;
		}).join(' ') + ' gnl-status--' + (kind || '')).trim();
	}

	function toLocalDateInput(d) {
		var pad = function (n) { return String(n).padStart(2, '0'); };
		return d.getFullYear() + '-' + pad(d.getMonth() + 1) + '-' + pad(d.getDate())
			+ 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
	}

	function fromLocalInputToIso(value) {
		if (!value) { return ''; }
		var d = new Date(value);
		if (isNaN(d.getTime())) { return ''; }
		return d.toISOString().replace(/\.\d{3}Z$/, 'Z');
	}

	/* ---------- Page handlers ---------- */

	function bindMembers() {
		document.querySelectorAll('.gnl-role-select').forEach(function (sel) {
			sel.addEventListener('change', function () {
				var row = sel.closest('tr');
				var userId = row.getAttribute('data-user-id');
				var status = row.querySelector('.gnl-row-status');
				var prev = sel.getAttribute('data-current');
				setStatus(status, S.saving, '');
				sel.disabled = true;
				api('admin/members/' + userId, { method: 'PATCH', body: { role: sel.value } })
					.then(function () {
						sel.setAttribute('data-current', sel.value);
						setStatus(status, S.role_updated, 'ok');
					})
					.catch(function (err) {
						sel.value = prev;
						setStatus(status, (S.role_failed + ' ' + err.message).trim(), 'err');
					})
					.then(function () { sel.disabled = false; });
			});
		});
	}

	function bindEvents() {
		document.querySelectorAll('.gnl-event-delete').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var row = btn.closest('tr');
				var id = row.getAttribute('data-event-id');
				var title = row.getAttribute('data-event-title') || '';
				var msg = (S.confirm_delete || 'Delete "%s"?').replace('%s', title);
				if (!window.confirm(msg)) { return; }
				var status = row.querySelector('.gnl-row-status');
				setStatus(status, S.saving, '');
				btn.disabled = true;
				api('admin/events/' + id, { method: 'DELETE' })
					.then(function () {
						setStatus(status, S.event_deleted, 'ok');
						row.style.transition = 'opacity .3s';
						row.style.opacity = '0.4';
					})
					.catch(function (err) {
						setStatus(status, (S.delete_failed + ' ' + err.message).trim(), 'err');
						btn.disabled = false;
					});
			});
		});
	}

	function bindEventEdit(root) {
		var eventId = parseInt(root.getAttribute('data-event-id'), 10);
		var form = document.getElementById('gnl-event-form');
		if (!form) { return; }

		var pokerToggle = document.getElementById('gnl-is-poker');
		var pokerFields = document.querySelector('.gnl-poker-fields');
		if (pokerToggle && pokerFields) {
			pokerToggle.addEventListener('change', function () {
				pokerFields.style.display = pokerToggle.checked ? '' : 'none';
			});
		}

		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var status = form.querySelector('.gnl-form-status');
			var btn = form.querySelector('button[type="submit"]');
			setStatus(status, S.saving, '');
			btn.disabled = true;

			var fd = new FormData(form);
			var payload = {
				title: fd.get('title') || '',
				description: fd.get('description') || '',
				color: fd.get('color') || '',
				is_poker: !!fd.get('is_poker'),
				waitlist_enabled: !!fd.get('waitlist_enabled'),
				reminders_enabled: !!fd.get('reminders_enabled'),
				start_at: fromLocalInputToIso(fd.get('start_at')),
			};
			var endLocal = fd.get('end_at');
			if (endLocal) { payload.end_at = fromLocalInputToIso(endLocal); }
			var deadline = fd.get('rsvp_deadline_hours');
			if (deadline !== null && deadline !== '') {
				payload.rsvp_deadline_hours = parseInt(deadline, 10);
			}
			var offsets = (fd.get('reminder_offsets') || '').toString().trim();
			if (offsets) {
				payload.reminder_offsets = offsets.split(',')
					.map(function (s) { return parseInt(s.trim(), 10); })
					.filter(function (n) { return !isNaN(n) && n >= 0; });
			}
			if (payload.is_poker) {
				['poker_buyin', 'poker_tables', 'poker_seats'].forEach(function (k) {
					var v = fd.get(k);
					if (v !== null && v !== '') { payload[k] = parseInt(v, 10); }
				});
				var gt = fd.get('poker_game_type');
				if (gt) { payload.poker_game_type = gt; }
			}

			var path = eventId > 0 ? ('admin/events/' + eventId) : 'admin/events';
			var method = eventId > 0 ? 'PATCH' : 'POST';
			api(path, { method: method, body: payload })
				.then(function () {
					setStatus(status, S.event_saved, 'ok');
					setTimeout(function () { window.location = window.GNLAdmin.eventsPage; }, 600);
				})
				.catch(function (err) {
					setStatus(status, (S.event_failed + ' ' + err.message).trim(), 'err');
					btn.disabled = false;
				});
		});
	}

	function bindEventInvitees(root) {
		var eventId = parseInt(root.getAttribute('data-event-id'), 10);

		document.querySelectorAll('.gnl-rsvp-select').forEach(function (sel) {
			sel.addEventListener('change', function () {
				var row = sel.closest('tr');
				var userId = row.getAttribute('data-user-id');
				var status = row.querySelector('.gnl-row-status');
				var prev = sel.getAttribute('data-current');
				var val = sel.value || 'clear';
				setStatus(status, S.saving, '');
				sel.disabled = true;
				api('admin/events/' + eventId + '/invitees/' + userId, { method: 'PATCH', body: { rsvp: val } })
					.then(function () {
						sel.setAttribute('data-current', sel.value);
						setStatus(status, S.rsvp_updated, 'ok');
					})
					.catch(function (err) {
						sel.value = prev;
						setStatus(status, (S.rsvp_failed + ' ' + err.message).trim(), 'err');
					})
					.then(function () { sel.disabled = false; });
			});
		});

		document.querySelectorAll('.gnl-invitee-remove').forEach(function (btn) {
			btn.addEventListener('click', function () {
				if (!window.confirm(S.confirm_remove || 'Remove this invitee?')) { return; }
				var row = btn.closest('tr');
				var userId = row.getAttribute('data-user-id');
				var status = row.querySelector('.gnl-row-status');
				setStatus(status, S.saving, '');
				btn.disabled = true;
				api('admin/events/' + eventId + '/invitees/' + userId, { method: 'DELETE' })
					.then(function () {
						setStatus(status, S.invitee_removed, 'ok');
						row.style.opacity = '0.4';
						setTimeout(function () { row.parentNode.removeChild(row); }, 400);
					})
					.catch(function (err) {
						setStatus(status, (S.remove_failed + ' ' + err.message).trim(), 'err');
						btn.disabled = false;
					});
			});
		});

		var addForm = document.getElementById('gnl-add-invitee-form');
		if (addForm) {
			addForm.addEventListener('submit', function (ev) {
				ev.preventDefault();
				var sel = document.getElementById('gnl-add-invitee-select');
				var status = addForm.querySelector('.gnl-form-status');
				var userId = parseInt(sel.value, 10);
				if (!userId) { return; }
				var managerInput = addForm.querySelector('input[name="manager"]');
				var manager = !!(managerInput && managerInput.checked);
				setStatus(status, S.saving, '');
				api('admin/events/' + eventId + '/invitees', { method: 'POST', body: { user_id: userId, manager: manager } })
					.then(function () {
						setStatus(status, S.invitee_added, 'ok');
						setTimeout(function () { window.location.reload(); }, 400);
					})
					.catch(function (err) {
						setStatus(status, (S.invitee_failed + ' ' + err.message).trim(), 'err');
					});
			});
		}

		var newPersonForm = document.getElementById('gnl-add-new-person-form');
		if (newPersonForm) {
			newPersonForm.addEventListener('submit', function (ev) {
				ev.preventDefault();
				var status = newPersonForm.querySelector('.gnl-form-status');
				var btn = newPersonForm.querySelector('button[type="submit"]');
				var fd = new FormData(newPersonForm);
				var payload = {
					display_name: (fd.get('display_name') || '').toString().trim(),
					email: (fd.get('email') || '').toString().trim(),
					phone: (fd.get('phone') || '').toString().trim(),
					manager: !!fd.get('manager')
				};
				if (!payload.display_name) { return; }
				if (!payload.email && !payload.phone) {
					setStatus(status, S.person_failed + ' Email or phone required.', 'err');
					return;
				}
				setStatus(status, S.saving, '');
				btn.disabled = true;
				api('admin/events/' + eventId + '/invitees/new-person', { method: 'POST', body: payload })
					.then(function () {
						setStatus(status, S.person_added, 'ok');
						setTimeout(function () { window.location.reload(); }, 600);
					})
					.catch(function (err) {
						setStatus(status, (S.person_failed + ' ' + err.message).trim(), 'err');
						btn.disabled = false;
					});
			});
		}
	}

	function bindPosts() {
		document.querySelectorAll('.gnl-post-delete').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var row = btn.closest('tr');
				var id = row.getAttribute('data-post-id');
				var title = row.getAttribute('data-post-title') || '';
				var msg = (S.confirm_post_delete || 'Delete "%s"?').replace('%s', title);
				if (!window.confirm(msg)) { return; }
				var status = row.querySelector('.gnl-row-status');
				setStatus(status, S.saving, '');
				btn.disabled = true;
				api('admin/posts/' + id, { method: 'DELETE' })
					.then(function () {
						setStatus(status, S.post_deleted, 'ok');
						row.style.transition = 'opacity .3s';
						row.style.opacity = '0.4';
					})
					.catch(function (err) {
						setStatus(status, (S.post_failed + ' ' + err.message).trim(), 'err');
						btn.disabled = false;
					});
			});
		});
	}

	function bindPostEdit(root) {
		var postId = parseInt(root.getAttribute('data-post-id'), 10);
		var form = document.getElementById('gnl-post-form');
		if (!form) { return; }

		form.addEventListener('submit', function (ev) {
			ev.preventDefault();
			var status = form.querySelector('.gnl-form-status');
			var btn = form.querySelector('button[type="submit"]');
			setStatus(status, S.saving, '');
			btn.disabled = true;

			// Sync TinyMCE → underlying textarea before reading.
			if (window.tinyMCE && window.tinyMCE.triggerSave) {
				window.tinyMCE.triggerSave();
			}

			var fd = new FormData(form);
			var payload = {
				title: (fd.get('title') || '').toString(),
				content: (fd.get('content') || '').toString(),
				pinned: !!fd.get('pinned'),
				hidden: !!fd.get('hidden')
			};
			if (postId === 0) {
				var pubLocal = fd.get('published_at');
				if (pubLocal) { payload.published_at = fromLocalInputToIso(pubLocal); }
			}

			var path = postId > 0 ? ('admin/posts/' + postId) : 'admin/posts';
			var method = postId > 0 ? 'PATCH' : 'POST';
			api(path, { method: method, body: payload })
				.then(function () {
					setStatus(status, S.post_saved, 'ok');
					setTimeout(function () { window.location = window.GNLAdmin.postsPage; }, 600);
				})
				.catch(function (err) {
					setStatus(status, (S.post_failed + ' ' + err.message).trim(), 'err');
					btn.disabled = false;
				});
		});
	}

	/* ---------- Boot ---------- */

	document.addEventListener('DOMContentLoaded', function () {
		var root = document.querySelector('[data-gnl-admin-page]');
		if (!root) { return; }
		var page = root.getAttribute('data-gnl-admin-page');
		if (page === 'members') { bindMembers(); }
		else if (page === 'events') { bindEvents(); }
		else if (page === 'event-edit') { bindEventEdit(root); }
		else if (page === 'event-invitees') { bindEventInvitees(root); }
		else if (page === 'posts') { bindPosts(); }
		else if (page === 'post-edit') { bindPostEdit(root); }
	});
})();
