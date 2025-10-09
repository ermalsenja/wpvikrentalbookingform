(function (root, factory) {
    if (typeof module === 'object' && module.exports) {
        module.exports = factory();
    } else {
        var api = factory(root);
        root.NomadRentalFormV61 = api;
        if (Array.isArray(root.NomadRentalFormV61Queue)) {
            root.NomadRentalFormV61Queue.forEach(function (config) {
                api.init(config);
            });
        }
        root.NomadRentalFormV61Queue = {
            push: function (config) {
                api.init(config);
            }
        };
    }
}(typeof self !== 'undefined' ? self : this, function (root) {
    'use strict';

    root = root || {};

    var documentRef = root.document;
    var locationRef = root.location;
    var historyRef = root.history;

    function debounce(fn, wait) {
        if (wait === void 0) {
            wait = 250;
        }
        var timer;
        return function () {
            var args = arguments;
            clearTimeout(timer);
            timer = setTimeout(function () {
                fn.apply(null, args);
            }, wait);
        };
    }

    function parseInputDate(value) {
        if (!value || typeof value !== 'string') {
            return null;
        }
        var parts = value.split('-');
        if (parts.length !== 3) {
            return null;
        }
        var year = Number(parts[0]);
        var month = Number(parts[1]);
        var day = Number(parts[2]);
        if (!Number.isInteger(year) || !Number.isInteger(month) || !Number.isInteger(day)) {
            return null;
        }
        if (month < 1 || month > 12 || day < 1 || day > 31) {
            return null;
        }
        var date = new Date(Date.UTC(year, month - 1, day));
        if (date.getUTCFullYear() !== year || date.getUTCMonth() !== month - 1 || date.getUTCDate() !== day) {
            return null;
        }
        return date;
    }

    function normalizeDate(date) {
        if (!(date instanceof Date)) {
            return null;
        }
        return new Date(Date.UTC(date.getUTCFullYear(), date.getUTCMonth(), date.getUTCDate()));
    }

    function ensureRange(checkin, checkout, options) {
        var settings = options || {};
        var errors = {};
        var checkinDate = normalizeDate(checkin);
        var checkoutDate = normalizeDate(checkout);
        var now = new Date();
        var today = normalizeDate(new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate())));

        if (!checkinDate) {
            errors.checkin = 'Data non valida';
        } else if (checkinDate < today) {
            errors.checkin = 'La data di ritiro non può essere nel passato';
        }

        if (!checkoutDate) {
            errors.checkout = 'Data non valida';
        }

        if (!errors.checkin && !errors.checkout && checkoutDate <= checkinDate) {
            errors.checkout = 'La riconsegna deve essere successiva al ritiro';
        }

        var maxNights = settings.maxNights;
        if (!errors.checkout && typeof maxNights === 'number' && maxNights > 0 && checkinDate && checkoutDate) {
            var diff = (checkoutDate.getTime() - checkinDate.getTime()) / (1000 * 60 * 60 * 24);
            if (diff > maxNights) {
                errors.checkout = 'La durata massima è di ' + maxNights + ' notti';
            }
        }

        return {
            ok: Object.keys(errors).length === 0,
            errors: errors,
            checkin: checkinDate,
            checkout: checkoutDate
        };
    }

    function buildQueryString(params, baseSearch) {
        var search = new URLSearchParams(baseSearch || '');
        Array.from(search.keys()).forEach(function (key) {
            if (!Object.prototype.hasOwnProperty.call(params, key) && key.indexOf('utm_') !== 0) {
                search.delete(key);
            }
        });
        Object.keys(params).forEach(function (key) {
            var value = params[key];
            if (value === undefined || value === null || value === '') {
                search.delete(key);
            } else {
                search.set(key, value);
            }
        });
        var entries = [];
        search.forEach(function (value, key) {
            entries.push([key, value]);
        });
        entries.sort(function (a, b) {
            return a[0] > b[0] ? 1 : (a[0] < b[0] ? -1 : 0);
        });
        var sorted = new URLSearchParams();
        entries.forEach(function (entry) {
            sorted.append(entry[0], entry[1]);
        });
        return sorted.toString();
    }

    function getSettings() {
        var defaults = {
            ajaxUrl: '',
            maxNights: 120,
            minLeadMinutes: 0,
            i18n: {
                submitting: 'Invio in corso…',
                networkError: 'Si è verificato un problema di rete. Riprova.',
                unknownError: 'Si è verificato un errore inatteso. Riprova più tardi.',
                success: 'Ricerca avviata, verrai reindirizzato tra pochi secondi.'
            }
        };
        if (typeof root !== 'undefined' && root.NomadRentalFormV61Settings) {
            var merged = Object.assign({}, defaults, root.NomadRentalFormV61Settings);
            merged.i18n = Object.assign({}, defaults.i18n, root.NomadRentalFormV61Settings.i18n || {});
            return merged;
        }
        return defaults;
    }

    function findField(form, name) {
        return form.querySelector('[name="' + name + '"]');
    }

    function showAlert(container, type, message) {
        var region = container.querySelector('[data-role="nomad-alert-region"]');
        if (!region) {
            return;
        }
        region.innerHTML = '';
        if (!message) {
            return;
        }
        var div = documentRef.createElement('div');
        div.className = 'uk-alert uk-alert-' + type + ' nomad-rental-alert';
        div.setAttribute('role', 'alert');
        div.setAttribute('tabindex', '-1');
        div.textContent = message;
        region.appendChild(div);
        div.focus();
    }

    function clearErrors(form) {
        var fields = form.querySelectorAll('.nomad-field');
        fields.forEach(function (field) {
            field.classList.remove('is-error');
            var errorEl = field.querySelector('.nomad-field__error');
            if (errorEl) {
                errorEl.textContent = '';
            }
            var input = field.querySelector('input, select');
            if (input) {
                input.removeAttribute('aria-invalid');
                input.removeAttribute('aria-describedby');
            }
        });
    }

    function applyErrors(form, errors) {
        var firstInvalid = null;
        Object.keys(errors).forEach(function (key) {
            var field = form.querySelector('[data-field="' + key + '"]');
            if (!field) {
                return;
            }
            field.classList.add('is-error');
            var message = errors[key];
            var errorEl = field.querySelector('.nomad-field__error');
            var input = field.querySelector('input, select');
            if (errorEl) {
                errorEl.textContent = message;
            }
            if (input) {
                input.setAttribute('aria-invalid', 'true');
                var describedBy = input.id + '_error';
                errorEl.id = describedBy;
                input.setAttribute('aria-describedby', describedBy);
                if (!firstInvalid) {
                    firstInvalid = input;
                }
            }
        });
        if (firstInvalid) {
            firstInvalid.focus();
        }
    }

    function fillSelect(select, options) {
        if (!select) {
            return;
        }
        select.innerHTML = '';
        options.forEach(function (option) {
            var opt = documentRef.createElement('option');
            opt.value = option.value;
            opt.textContent = option.label;
            if (option.selected) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });
    }

    function formatDisplayDate(date) {
        if (!(date instanceof Date)) {
            return '';
        }
        return date.toLocaleDateString(undefined, {
            weekday: 'short',
            month: 'short',
            day: 'numeric'
        });
    }

    function collectParams(form) {
        var params = {};
        var pickupLocation = findField(form, 'pickup-location');
        var dropoffLocation = findField(form, 'dropoff-location');
        var pickupDate = findField(form, 'pickup-date');
        var dropoffDate = findField(form, 'dropoff-date');
        var pickupTime = findField(form, 'pickup-time');
        var dropoffTime = findField(form, 'dropoff-time');
        var passengers = findField(form, 'guests');
        var email = findField(form, 'email');

        if (pickupLocation) {
            params.pickup = pickupLocation.value;
        }
        if (dropoffLocation && !dropoffLocation.disabled) {
            params.dropoff = dropoffLocation.value;
        }
        if (pickupDate) {
            params.checkin = pickupDate.value;
        }
        if (dropoffDate) {
            params.checkout = dropoffDate.value;
        }
        if (pickupTime) {
            params.pickup_time = pickupTime.value;
        }
        if (dropoffTime) {
            params.dropoff_time = dropoffTime.value;
        }
        if (passengers) {
            params.guests = passengers.value;
        }
        if (email && email.value) {
            params.email = email.value;
        }
        return params;
    }

    function applyParams(form, params) {
        Object.keys(params).forEach(function (key) {
            var fieldName;
            switch (key) {
                case 'pickup':
                    fieldName = 'pickup-location';
                    break;
                case 'dropoff':
                    fieldName = 'dropoff-location';
                    break;
                case 'checkin':
                    fieldName = 'pickup-date';
                    break;
                case 'checkout':
                    fieldName = 'dropoff-date';
                    break;
                case 'pickup_time':
                    fieldName = 'pickup-time';
                    break;
                case 'dropoff_time':
                    fieldName = 'dropoff-time';
                    break;
                case 'guests':
                    fieldName = 'guests';
                    break;
                case 'email':
                    fieldName = 'email';
                    break;
                default:
                    fieldName = null;
            }
            if (!fieldName) {
                return;
            }
            var field = findField(form, fieldName);
            if (!field) {
                return;
            }
            if (field.matches('select')) {
                var option = field.querySelector('option[value="' + params[key] + '"]');
                if (option) {
                    field.value = params[key];
                }
            } else {
                field.value = params[key];
            }
        });
    }

    function toggleDropoff(form, enabled) {
        var dropoffWrapper = form.querySelector('[data-role="dropoff-field"]');
        var dropoffSelect = findField(form, 'dropoff-location');
        var toggle = form.querySelector('[data-role="dropoff-toggle"]');
        if (dropoffWrapper) {
            dropoffWrapper.hidden = !enabled;
        }
        if (dropoffSelect) {
            dropoffSelect.disabled = !enabled;
        }
        if (toggle) {
            toggle.setAttribute('aria-checked', enabled ? 'true' : 'false');
        }
        if (!enabled && dropoffSelect) {
            var pickupSelect = findField(form, 'pickup-location');
            if (pickupSelect) {
                dropoffSelect.value = pickupSelect.value;
            }
        }
    }

    function enhanceFieldMirrors(form) {
        var pickupDate = findField(form, 'pickup-date');
        var pickupMirror = form.querySelector('[data-display="pickup-date"]');
        var dropoffDate = findField(form, 'dropoff-date');
        var dropoffMirror = form.querySelector('[data-display="dropoff-date"]');
        var pickupLocation = findField(form, 'pickup-location');
        var dropoffLocation = findField(form, 'dropoff-location');
        var pickupLocationMirror = form.querySelector('[data-display="pickup-location"]');
        var dropoffLocationMirror = form.querySelector('[data-display="dropoff-location"]');
        var pickupTime = findField(form, 'pickup-time');
        var dropoffTime = findField(form, 'dropoff-time');
        var pickupTimeMirror = form.querySelector('[data-display="pickup-time"]');
        var dropoffTimeMirror = form.querySelector('[data-display="dropoff-time"]');

        function syncLocation(select, mirror) {
            if (!select || !mirror) {
                return;
            }
            var label = select.options[select.selectedIndex] ? select.options[select.selectedIndex].textContent : '';
            mirror.textContent = label;
        }

        function syncTime(select, mirror) {
            if (!select || !mirror) {
                return;
            }
            var label = select.options[select.selectedIndex] ? select.options[select.selectedIndex].textContent : '';
            mirror.textContent = label;
        }

        function syncDate(input, mirror) {
            if (!input || !mirror) {
                return;
            }
            var parsed = parseInputDate(input.value);
            mirror.textContent = parsed ? formatDisplayDate(parsed) : '';
        }

        if (pickupLocation) {
            syncLocation(pickupLocation, pickupLocationMirror);
            pickupLocation.addEventListener('change', function () {
                syncLocation(pickupLocation, pickupLocationMirror);
            });
        }
        if (dropoffLocation) {
            syncLocation(dropoffLocation, dropoffLocationMirror);
            dropoffLocation.addEventListener('change', function () {
                syncLocation(dropoffLocation, dropoffLocationMirror);
            });
        }
        if (pickupTime) {
            syncTime(pickupTime, pickupTimeMirror);
            pickupTime.addEventListener('change', function () {
                syncTime(pickupTime, pickupTimeMirror);
            });
        }
        if (dropoffTime) {
            syncTime(dropoffTime, dropoffTimeMirror);
            dropoffTime.addEventListener('change', function () {
                syncTime(dropoffTime, dropoffTimeMirror);
            });
        }
        if (pickupDate) {
            syncDate(pickupDate, pickupDateMirror);
            pickupDate.addEventListener('change', function () {
                syncDate(pickupDate, pickupDateMirror);
            });
        }
        if (dropoffDate) {
            syncDate(dropoffDate, dropoffDateMirror);
            dropoffDate.addEventListener('change', function () {
                syncDate(dropoffDate, dropoffDateMirror);
            });
        }
    }

    function attachFormHandlers(form, config) {
        var settings = getSettings();
        var updateHistory = debounce(function () {
            if (!historyRef || !locationRef) {
                return;
            }
            var params = collectParams(form);
            var qs = buildQueryString(params, locationRef.search);
            var newUrl = locationRef.pathname + (qs ? '?' + qs : '');
            historyRef.replaceState(null, '', newUrl);
        }, 250);

        var dropoffToggle = form.querySelector('[data-role="dropoff-toggle"]');
        if (dropoffToggle) {
            dropoffToggle.addEventListener('change', function (event) {
                toggleDropoff(form, event.target.checked);
                updateHistory();
            });
        }

        var pickups = [
            findField(form, 'pickup-location'),
            findField(form, 'dropoff-location'),
            findField(form, 'pickup-date'),
            findField(form, 'dropoff-date'),
            findField(form, 'pickup-time'),
            findField(form, 'dropoff-time'),
            findField(form, 'guests'),
            findField(form, 'email')
        ];
        pickups.forEach(function (field) {
            if (!field) {
                return;
            }
            field.addEventListener('change', updateHistory);
        });

        var wrapper = form.closest('[data-role="nomad-wrapper"]');

        form.addEventListener('submit', function (event) {
            event.preventDefault();
            clearErrors(form);
            var submitButton = form.querySelector('[data-role="submit"]');
            var spinner;
            if (submitButton) {
                submitButton.disabled = true;
                spinner = documentRef.createElement('span');
                spinner.className = 'nomad-loading-indicator';
                submitButton.appendChild(spinner);
            }

            var params = collectParams(form);
            var result = ensureRange(parseInputDate(params.checkin), parseInputDate(params.checkout), {
                maxNights: settings.maxNights
            });
            if (!result.ok) {
                if (submitButton && spinner) {
                    submitButton.removeChild(spinner);
                    submitButton.disabled = false;
                }
                showAlert(wrapper, 'danger', 'Controlla i campi evidenziati');
                var filteredErrors = {};
                if (result.errors.checkin) {
                    filteredErrors.checkin = result.errors.checkin;
                }
                if (result.errors.checkout) {
                    filteredErrors.checkout = result.errors.checkout;
                }
                applyErrors(form, filteredErrors);
                return;
            }

            var formData = new root.FormData(form);
            formData.set('action', 'nomad_rental_form_v61_submit');
            formData.set('layout_variant', config.layout);
            var mergedParams = buildQueryString(params, locationRef ? locationRef.search : '');
            formData.set('querystring', mergedParams);

            if (!settings.ajaxUrl) {
                if (submitButton && spinner) {
                    submitButton.removeChild(spinner);
                    submitButton.disabled = false;
                }
                showAlert(wrapper, 'warning', settings.networkError);
                return;
            }

            fetch(settings.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json();
            }).then(function (json) {
                if (!json) {
                    throw new Error('invalid_json');
                }
                if (!json.ok) {
                    throw json;
                }
                showAlert(wrapper, 'success', settings.i18n.success);
                if (json.url) {
                    setTimeout(function () {
                        root.location.href = json.url;
                    }, 400);
                }
            }).catch(function (error) {
                if (error && error.errors && Object.keys(error.errors).length > 0) {
                    showAlert(wrapper, 'danger', 'Controlla i campi evidenziati');
                    applyErrors(form, error.errors);
                    return;
                }
                if (error && error.message) {
                    showAlert(wrapper, 'warning', error.message);
                    return;
                }
                if (error && error.message === 'invalid_json') {
                    showAlert(wrapper, 'warning', settings.i18n.networkError);
                } else {
                    showAlert(wrapper, 'warning', settings.i18n.networkError || settings.i18n.unknownError);
                }
            }).finally(function () {
                if (submitButton && spinner) {
                    submitButton.removeChild(spinner);
                    submitButton.disabled = false;
                }
            });
        });
    }

    function preloadFromUrl(form) {
        if (!locationRef) {
            return;
        }
        var params = new URLSearchParams(locationRef.search);
        var mapped = {};
        params.forEach(function (value, key) {
            switch (key) {
                case 'pickup':
                case 'pickup-location':
                    mapped.pickup = value;
                    break;
                case 'dropoff':
                case 'dropoff-location':
                    mapped.dropoff = value;
                    break;
                case 'checkin':
                case 'pickup-date':
                    mapped.checkin = value;
                    break;
                case 'checkout':
                case 'dropoff-date':
                    mapped.checkout = value;
                    break;
                case 'pickup_time':
                case 'pickup-time':
                    mapped.pickup_time = value;
                    break;
                case 'dropoff_time':
                case 'dropoff-time':
                    mapped.dropoff_time = value;
                    break;
                case 'guests':
                    mapped.guests = value;
                    break;
                case 'email':
                    mapped.email = value;
                    break;
                default:
                    if (key.indexOf('utm_') === 0) {
                        mapped[key] = value;
                    }
            }
        });
        applyParams(form, mapped);
        var dropoffToggle = form.querySelector('[data-role="dropoff-toggle"]');
        if (mapped.dropoff && mapped.dropoff !== mapped.pickup && dropoffToggle) {
            dropoffToggle.checked = true;
            toggleDropoff(form, true);
        }
    }

    function enhanceForm(config) {
        if (!documentRef) {
            return;
        }
        var wrapper = documentRef.getElementById(config.formId);
        if (!wrapper) {
            return;
        }
        var form = wrapper.querySelector('form');
        if (!form) {
            return;
        }
        enhanceFieldMirrors(form);
        preloadFromUrl(form);
        attachFormHandlers(form, config);
    }

    function init(config) {
        if (!config || !config.formId) {
            return;
        }
        if (documentRef && documentRef.readyState === 'loading') {
            documentRef.addEventListener('DOMContentLoaded', function onReady() {
                documentRef.removeEventListener('DOMContentLoaded', onReady);
                enhanceForm(config);
            });
        } else {
            enhanceForm(config);
        }
    }

    return {
        init: init,
        utils: {
            debounce: debounce,
            parseInputDate: parseInputDate,
            normalizeDate: normalizeDate,
            ensureRange: ensureRange,
            buildQueryString: buildQueryString
        }
    };
}));
