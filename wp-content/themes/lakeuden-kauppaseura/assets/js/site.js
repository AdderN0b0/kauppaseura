(function () {
	'use strict';

	function markCurrentNavigation() {
		var route = '/';
		var classes = document.body.classList;
		var currentPath = new URL(window.location.href).pathname;

		if (currentPath.endsWith('/jaseneksi/')) {
			route = '/jaseneksi/';
		} else if (classes.contains('page-template-page-meista')) {
			route = '/meista/';
		} else if (classes.contains('page-template-page-tapahtumat') || classes.contains('single-lks_event')) {
			route = '/tapahtumat/';
		} else if (classes.contains('page-template-page-blogi') || classes.contains('single-post')) {
			route = '/blogi/';
		} else if (classes.contains('page-template-page-yhteystiedot')) {
			route = '/yhteystiedot/';
		}

		document.querySelectorAll('.lks-main-nav a, .lks-mobile-menu nav a, .lks-header-cta a, .lks-mobile-menu__contact').forEach(function (link) {
			var path = new URL(link.href, window.location.href).pathname;
			var isCurrent = route === '/' ? /\/$/.test(path) && !/\/(meista|jaseneksi|tapahtumat|blogi|yhteystiedot)\/$/.test(path) : path.endsWith(route);

			if (isCurrent) {
				link.setAttribute('aria-current', 'page');
			} else {
				link.removeAttribute('aria-current');
			}
		});
	}

	markCurrentNavigation();

	function updateEventCountdowns() {
		var today = new Date();
		var todayUtc = Date.UTC(today.getFullYear(), today.getMonth(), today.getDate());

		document.querySelectorAll('[data-event-countdown]').forEach(function (countdown) {
			var date = countdown.getAttribute('data-event-date');
			var match = date && date.match(/^(\d{4})-(\d{2})-(\d{2})$/);

			if (!match) {
				return;
			}

			var eventUtc = Date.UTC(Number(match[1]), Number(match[2]) - 1, Number(match[3]));
			var days = Math.round((eventUtc - todayUtc) / 86400000);

			if (days === 0) {
				countdown.textContent = 'Tänään';
			} else if (days === 1) {
				countdown.textContent = 'Huomenna';
			} else if (days > 1) {
				countdown.textContent = days + ' päivää tapahtumaan';
			} else {
				countdown.hidden = true;
			}
		});
	}

	updateEventCountdowns();

	function initPastEventsArchive() {
		var archive = document.querySelector('[data-past-events-archive]');

		if (!archive) {
			return;
		}

		var list = archive.querySelector('.lks-event-list');
		var controls = archive.querySelector('[data-past-events-controls]');
		var button = archive.querySelector('[data-past-events-more]');
		var status = archive.querySelector('[data-past-events-status]');
		var batchSize = 6;

		if (!list || !controls || !button || !status) {
			return;
		}

		var cards = Array.prototype.slice.call(list.querySelectorAll('.lks-event-card'));

		if (cards.length <= batchSize) {
			return;
		}

		list.id = 'lks-past-events-list';

		cards.forEach(function (card, index) {
			card.hidden = index >= batchSize;
		});

		function visibleCount() {
			return cards.filter(function (card) {
				return !card.hidden;
			}).length;
		}

		function updateStatus() {
			var count = visibleCount();
			status.textContent = 'Näytetään ' + count + '/' + cards.length + ' mennyttä tapahtumaa.';

			if (count === cards.length) {
				button.textContent = 'Kaikki menneet tapahtumat näytetty';
				button.disabled = true;
			}
		}

		button.addEventListener('click', function () {
			var hiddenCards = cards.filter(function (card) {
				return card.hidden;
			});

			hiddenCards.slice(0, batchSize).forEach(function (card) {
				card.hidden = false;
			});

			updateStatus();
		});

		controls.hidden = false;
		updateStatus();
	}

	initPastEventsArchive();

	function appendIdReference(element, attribute, id) {
		var values = (element.getAttribute(attribute) || '').split(/\s+/).filter(Boolean);

		if (values.indexOf(id) === -1) {
			values.push(id);
			element.setAttribute(attribute, values.join(' '));
		}
	}

	function removeIdReference(element, attribute, id) {
		var values = (element.getAttribute(attribute) || '').split(/\s+/).filter(function (value) {
			return value && value !== id;
		});

		if (values.length) {
			element.setAttribute(attribute, values.join(' '));
		} else {
			element.removeAttribute(attribute);
		}
	}

	function syncMembershipFormAccessibility() {
		document.querySelectorAll('.lks-membership-form-live form').forEach(function (form) {
			form.querySelectorAll('.wpforms-submit-spinner').forEach(function (spinner) {
				spinner.setAttribute('alt', '');
				spinner.setAttribute('aria-hidden', 'true');
			});

			form.querySelectorAll('[aria-errormessage]').forEach(function (control) {
				var errorId = control.getAttribute('aria-errormessage');
				var error = errorId ? document.getElementById(errorId) : null;
				var hasError = Boolean(error && error.textContent.trim() && !error.hidden);

				if (hasError) {
					control.setAttribute('aria-invalid', 'true');
					appendIdReference(control, 'aria-describedby', errorId);
				} else {
					control.removeAttribute('aria-invalid');
					if (errorId) {
						removeIdReference(control, 'aria-describedby', errorId);
					}
				}
			});

			form.querySelectorAll('.wpforms-error-container, .wpforms-error-alert').forEach(function (alert) {
				alert.setAttribute('role', 'alert');
				alert.setAttribute('aria-live', 'assertive');
			});
		});

		document.querySelectorAll('.lks-membership-form-section .wpforms-confirmation-container-full').forEach(function (confirmation) {
			confirmation.setAttribute('role', 'status');
			confirmation.setAttribute('aria-live', 'polite');
			confirmation.setAttribute('tabindex', '-1');

			if (!confirmation.hasAttribute('data-lks-announced')) {
				confirmation.setAttribute('data-lks-announced', 'true');
				confirmation.focus();
			}
		});
	}

	var membershipFormSection = document.querySelector('.lks-membership-form-section');

	if (membershipFormSection) {
		syncMembershipFormAccessibility();
		membershipFormSection.addEventListener('invalid', function (event) {
			event.target.setAttribute('aria-invalid', 'true');
		}, true);
		membershipFormSection.addEventListener('input', function (event) {
			if (event.target.matches('input, textarea, select') && event.target.validity && event.target.validity.valid) {
				event.target.removeAttribute('aria-invalid');
			}
		});

		new MutationObserver(syncMembershipFormAccessibility).observe(membershipFormSection, {
			childList: true,
			subtree: true,
			attributes: true,
			attributeFilter: ['class', 'hidden']
		});
	}

	var menu = document.querySelector('.lks-mobile-menu');

	if (!menu) {
		return;
	}

	var summary = menu.querySelector('summary');
	var background = [document.querySelector('#main'), document.querySelector('footer')].filter(Boolean);

	function setBackgroundInert(isOpen) {
		background.forEach(function (element) {
			if (isOpen) {
				element.setAttribute('inert', '');
			} else {
				element.removeAttribute('inert');
			}
		});
	}

	function syncMenuState() {
		var isOpen = menu.hasAttribute('open');
		document.body.classList.toggle('lks-menu-open', isOpen);
		setBackgroundInert(isOpen);

		if (summary) {
			summary.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
		}
	}

	function closeMenu(returnFocus) {
		if (!menu.hasAttribute('open')) {
			return;
		}

		menu.removeAttribute('open');
		syncMenuState();

		if (returnFocus && summary) {
			summary.focus();
		}
	}

	menu.addEventListener('toggle', syncMenuState);

	menu.addEventListener('click', function (event) {
		if (event.target.closest('a')) {
			closeMenu(false);
		}
	});

	document.addEventListener('keydown', function (event) {
		if (event.key === 'Escape' && menu.hasAttribute('open')) {
			event.preventDefault();
			closeMenu(true);
			return;
		}

		if (event.key === 'Tab' && menu.hasAttribute('open')) {
			var focusable = Array.prototype.slice.call(menu.querySelectorAll('summary, a[href], button:not([disabled]), [tabindex]:not([tabindex="-1"])')).filter(function (element) {
				return !element.hidden && element.getClientRects().length > 0;
			});
			var first = focusable[0];
			var last = focusable[focusable.length - 1];

			if (event.shiftKey && document.activeElement === first) {
				event.preventDefault();
				last.focus();
			} else if (!event.shiftKey && document.activeElement === last) {
				event.preventDefault();
				first.focus();
			}
		}
	});

	window.addEventListener('resize', function () {
		if (window.matchMedia('(min-width: 961px)').matches) {
			closeMenu(false);
		}
	});

	window.addEventListener('pageshow', syncMenuState);
	window.addEventListener('pagehide', function () {
		closeMenu(false);
		setBackgroundInert(false);
	});
})();
