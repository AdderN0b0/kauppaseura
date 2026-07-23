(function () {
	'use strict';

	function initializeRegistrationFields() {
		var toggle = document.getElementById('lks-event-registration-required');
		var fields = document.getElementById('lks-event-registration-fields');

		if (!toggle || !fields) {
			return;
		}

		var controls = fields.querySelectorAll('input, textarea, select');
		var error = fields.querySelector('.lks-event-registration-error');

		function updateRegistrationFields() {
			var enabled = toggle.checked;

			fields.hidden = !enabled;
			toggle.setAttribute('aria-expanded', enabled ? 'true' : 'false');

			controls.forEach(function (control) {
				control.disabled = !enabled;
				control.required = false;

				if (!enabled) {
					control.setCustomValidity('');
					control.removeAttribute('aria-invalid');
					control.removeAttribute('aria-errormessage');
				}
			});

			if (!enabled && error) {
				error.textContent = '';
				error.hidden = true;
			}
		}

		toggle.addEventListener('change', updateRegistrationFields);
		updateRegistrationFields();
	}

	if ('loading' === document.readyState) {
		document.addEventListener('DOMContentLoaded', initializeRegistrationFields);
	} else {
		initializeRegistrationFields();
	}
}());
