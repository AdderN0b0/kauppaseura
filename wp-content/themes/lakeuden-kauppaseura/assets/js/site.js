(function () {
	'use strict';

	function markCurrentNavigation() {
		var route = '/';
		var classes = document.body.classList;

		if (classes.contains('page-template-page-meista')) {
			route = '/meista/';
		} else if (classes.contains('page-template-page-tapahtumat') || classes.contains('single-lks_event')) {
			route = '/tapahtumat/';
		} else if (classes.contains('page-template-page-blogi') || classes.contains('single-post')) {
			route = '/blogi/';
		} else if (classes.contains('page-template-page-yhteystiedot')) {
			route = '/yhteystiedot/';
		}

		document.querySelectorAll('.lks-main-nav a, .lks-mobile-menu nav a').forEach(function (link) {
			var path = new URL(link.href, window.location.href).pathname;
			var isCurrent = route === '/' ? /\/$/.test(path) && !/\/(meista|tapahtumat|blogi|yhteystiedot)\/$/.test(path) : path.endsWith(route);

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
