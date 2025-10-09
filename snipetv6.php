<?php
/**
 * Enhanced shortcode variant with UIkit icons and refined responsive behaviour.
 *
 * Version 6 focuses on resilience: improved accessibility semantics, safer
 * toggling logic for the drop-off field, Litepicker fallbacks, and a cleaner
 * responsive layout that keeps tablet parity while stacking gracefully on
 * narrow phones.
 *
 * Usage:
 *   [nomad_rental_form_enhanced base_url="https://example.com/search" itemid="613"]
 */

// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals -- snippet meant for WPCodeBox/functions.php use.

if (! function_exists('nomad_rental_form_prevent_litespeed_defer')) {
    /**
     * Prevent LiteSpeed (or similar optimisers) from deferring critical scripts.
     *
     * @param string $tag    Script tag HTML.
     * @param string $handle Script handle.
     * @param string $src    Script source URL.
     *
     * @return string
     */
    function nomad_rental_form_prevent_litespeed_defer($tag, $handle, $src) {
        $critical_handles = [
            'litepicker',
            'nomad-rental-form-enhanced',
        ];

        if (! in_array($handle, $critical_handles, true)) {
            return $tag;
        }

        // Remove defer attribute if present.
        $tag = preg_replace('/\sdefer(=("|\')defer("|\'))?/', '', $tag);

        // Add LiteSpeed opt-out attribute so the script won't be deferred.
        if (false === strpos($tag, 'data-litespeed-noopt')) {
            $tag = str_replace('<script ', '<script data-litespeed-noopt="1" ', $tag);
        }

        return $tag;
    }

    add_filter('script_loader_tag', 'nomad_rental_form_prevent_litespeed_defer', 10, 3);
}

if (! function_exists('nomad_rental_form_enhanced_shortcode')) {
    /**
     * Render the enhanced booking form.
     *
     * @param array $atts Shortcode attributes.
     *
     * @return string
     */
    function nomad_rental_form_enhanced_shortcode($atts = []) {
        $atts = shortcode_atts(
            [
                'base_url' => 'https://nomadcamperhire.com/search-your-van/index.php',
                'itemid'   => '613',
            ],
            $atts,
            'nomad_rental_form_enhanced'
        );

        $base_url = esc_url_raw(rtrim($atts['base_url'], '/'));
        $itemid   = sanitize_text_field($atts['itemid']);

        wp_enqueue_style(
            'litepicker',
            'https://cdn.jsdelivr.net/npm/litepicker/dist/css/litepicker.css',
            [],
            '2.0.11'
        );
        wp_enqueue_script(
            'litepicker',
            'https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js',
            [],
            '2.0.11',
            true
        );

        $unique_id = 'nomad_form_' . wp_rand(1000, 9999);

        ob_start();

        if (! defined('NOMAD_RENTAL_FORM_SHARED_ASSETS')) {
            define('NOMAD_RENTAL_FORM_SHARED_ASSETS', true);
            ?>
            <style id="nomad-rental-form-shared-styles">
                .nomad-rental-container * {
                    box-sizing: border-box;
                }

                .nomad-search-bar {
                    background: rgb(255, 183, 0);
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: rgba(26, 26, 26, 0.16) 0 2px 8px;
                    margin-bottom: 16px;
                    display: flex;
                    padding: 2px;
                    font-family: "system-ui", -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                }

                .nomad-search-fields {
                    --nomad-gap: 2px;
                    --nomad-radius: 6px;
                    display: flex;
                    flex-wrap: wrap;
                    align-items: stretch;
                    width: 100%;
                    gap: var(--nomad-gap);
                }

                .nomad-search-field {
                    background: #fff;
                    position: relative;
                    border-radius: var(--nomad-radius);
                    flex: 1 1 160px;
                    transition: background-color 0.15s ease;
                    overflow: hidden;
                    display: flex;
                }

                .nomad-search-field:hover {
                    background: #f6f6f6;
                }

                .nomad-search-fields.single-location .nomad-pickup-location {
                    flex: 1.7 1 320px;
                }

                .nomad-search-fields:not(.single-location) .nomad-pickup-location,
                .nomad-search-fields:not(.single-location) .nomad-dropoff-location.is-visible {
                    flex: 1.25 1 240px;
                }

                .nomad-dropoff-location {
                    display: none;
                }

                .nomad-dropoff-location.is-visible {
                    display: flex;
                }

                .nomad-search-field[hidden] {
                    display: none !important;
                }

                .nomad-date-field {
                    flex: 1 1 180px;
                    min-width: 140px;
                }

                .nomad-time-field {
                    flex: 0.85 1 140px;
                    min-width: 120px;
                }

                .nomad-search-btn {
                    background: #0073e6 !important;
                    color: #fff !important;
                    justify-content: center;
                    align-items: center;
                    display: flex;
                    flex: 0 0 clamp(120px, 14vw, 160px);
                    font-weight: 600;
                    font-size: 16px;
                    line-height: 24px;
                    transition: transform 0.12s cubic-bezier(0.2, 0, 0.4, 0.8);
                    border: none;
                    border-radius: var(--nomad-radius);
                    cursor: pointer;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                }

                .nomad-search-btn:focus-visible {
                    outline: 3px solid rgba(0, 115, 230, 0.3);
                    outline-offset: 2px;
                }

                .nomad-search-btn:disabled {
                    opacity: 0.65;
                    cursor: not-allowed;
                }

                @media (prefers-reduced-motion: reduce) {
                    .nomad-search-btn {
                        transition: none;
                        transform: none !important;
                    }
                }

                .nomad-field-trigger {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 6px 12px;
                    width: 100%;
                    min-height: 58px;
                    cursor: pointer;
                }

                .nomad-field-icon {
                    opacity: 0.85;
                    flex-shrink: 0;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .nomad-field-icon svg {
                    width: 22px;
                    height: 22px;
                    display: block;
                    pointer-events: none;
                    stroke: currentColor !important;
                    fill: none;
                }

                .nomad-rental-container .nomad-field-icon,
                .nomad-rental-container .nomad-field-icon .uk-icon,
                .nomad-rental-container .nomad-field-icon [uk-icon],
                .nomad-rental-container .nomad-field-icon [data-uk-icon] {
                    color: #000;
                    opacity: 1;
                }

                .nomad-field-trigger:hover .nomad-field-icon {
                    color: #111;
                }

                .nomad-field-content {
                    flex: 1;
                    min-width: 0;
                    overflow: hidden;
                }

                .nomad-field-label {
                    font-size: 12px;
                    color: rgb(89, 89, 89);
                    font-weight: 500;
                    line-height: 18px;
                    margin-bottom: 2px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .nomad-field-value {
                    font-size: 14px;
                    color: rgb(26, 26, 26);
                    font-weight: 600;
                    line-height: 20px;
                    overflow: hidden;
                    text-overflow: ellipsis;
                    white-space: nowrap;
                }

                .nomad-field-input {
                    border: none;
                    background: transparent;
                    font-size: 14px;
                    font-weight: 600;
                    color: rgb(26, 26, 26);
                    width: 100%;
                    padding: 0;
                    outline: none;
                    position: absolute;
                    inset: 0;
                    opacity: 0;
                }

                .nomad-options-row {
                    display: flex;
                    align-items: center;
                    gap: 24px;
                    padding: 0 6px;
                    margin-top: 8px;
                    font-family: "system-ui", -apple-system, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                }

                .nomad-checkbox-option {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    font-size: 14px;
                    cursor: pointer;
                }

                .nomad-checkbox-option input[type="checkbox"] {
                    width: 18px;
                    height: 18px;
                    border-radius: 4px;
                    cursor: pointer;
                    margin: 0;
                }

                @media (max-width: 1200px) {
                    .nomad-date-field {
                        flex: 0.95 1 150px;
                    }

                    .nomad-time-field {
                        flex: 0.8 1 120px;
                    }
                }

                @media (max-width: 1020px) {
                    .nomad-search-fields {
                        gap: 6px;
                    }

                    .nomad-search-field {
                        min-height: 60px;
                    }

                    .nomad-pickup-location {
                        flex: 1 1 calc(50% - 3px);
                        order: 1;
                    }

                    .nomad-dropoff-location {
                        order: 2;
                    }

                    .nomad-dropoff-location.is-visible {
                        flex: 1 1 calc(50% - 3px);
                    }

                    .nomad-search-fields.single-location .nomad-pickup-location {
                        flex: 1 1 100%;
                    }

                    .nomad-date-field {
                        flex: 1 1 calc(50% - 3px);
                        order: 3;
                    }

                    .nomad-time-field {
                        flex: 1 1 calc(50% - 3px);
                        order: 4;
                    }

                    .nomad-search-field[id$="_dropoff_date_field"] {
                        order: 5;
                    }

                    .nomad-search-field[id$="_dropoff_time_field"] {
                        order: 6;
                    }

                    .nomad-search-btn {
                        flex: 1 1 100%;
                        min-height: 54px;
                        order: 7;
                    }

                    .nomad-field-trigger {
                        padding: 6px 10px;
                    }

                    .nomad-options-row {
                        flex-direction: column;
                        align-items: flex-start;
                        gap: 12px;
                        padding: 16px 20px;
                    }
                }

                @media (max-width: 680px) {
                    .nomad-pickup-location,
                    .nomad-dropoff-location.is-visible {
                        flex: 1 1 100%;
                        order: initial;
                    }

                    .nomad-search-field[id$="_pickup_date_field"],
                    .nomad-search-field[id$="_pickup_time_field"],
                    .nomad-search-field[id$="_dropoff_date_field"],
                    .nomad-search-field[id$="_dropoff_time_field"] {
                        flex: 1 1 calc(50% - 3px);
                    }

                    .nomad-field-label {
                        font-size: 11px;
                    }

                    .nomad-field-value {
                        font-size: 13px;
                    }

                    .litepicker {
                        width: min(100vw - 24px, 420px);
                        max-width: 100%;
                        margin: 0 auto;
                    }

                    .litepicker .container__months {
                        flex-direction: column;
                        gap: 12px;
                    }

                    .litepicker .month-item {
                        width: 100% !important;
                        max-width: none;
                    }

                    .litepicker .container__footer {
                        flex-direction: column;
                        align-items: stretch;
                        gap: 8px;
                    }
                }

                @media (max-width: 480px) {
                    .nomad-search-field[id$="_pickup_date_field"],
                    .nomad-search-field[id$="_pickup_time_field"],
                    .nomad-search-field[id$="_dropoff_date_field"],
                    .nomad-search-field[id$="_dropoff_time_field"] {
                        flex: 1 1 100%;
                    }
                }

                .litepicker {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
                }

                .litepicker .container__days .day-item.is-today {
                    color: #0073e6;
                    font-weight: 600;
                }

                .litepicker .container__days .day-item.is-start-date,
                .litepicker .container__days .day-item.is-end-date {
                    background-color: #0073e6 !important;
                    color: #fff !important;
                }

                .litepicker .container__days .day-item.is-in-range {
                    background-color: rgba(0, 115, 230, 0.1) !important;
                    color: #0073e6 !important;
                }
            </style>

            <script id="nomad-rental-form-shared-script" data-litespeed-noopt="1">
                (function() {
                    const locationIdMap = {
                        teg: '4',
                        berat: '3',
                        saranda: '6',
                        durres: '5',
                        shuttle: '2',
                        aeroporto: '1'
                    };

                    const displayFormatter = new Intl.DateTimeFormat('en-US', {
                        weekday: 'short',
                        month: 'short',
                        day: '2-digit'
                    });

                    function onReady(callback) {
                        if (document.readyState === 'loading') {
                            document.addEventListener('DOMContentLoaded', callback, { once: true });
                        } else {
                            callback();
                        }
                    }

                    function pad(value) {
                        return String(value).padStart(2, '0');
                    }

                    function normalizeDate(value) {
                        if (! value) {
                            return null;
                        }

                        if (value instanceof Date && ! Number.isNaN(value.getTime())) {
                            return value;
                        }

                        if (value && typeof value.toDate === 'function') {
                            const coerced = value.toDate();
                            if (coerced instanceof Date && ! Number.isNaN(coerced.getTime())) {
                                return coerced;
                            }
                        }

                        if (value && typeof value.toJSDate === 'function') {
                            const coerced = value.toJSDate();
                            if (coerced instanceof Date && ! Number.isNaN(coerced.getTime())) {
                                return coerced;
                            }
                        }

                        if (value && typeof value.getTime === 'function') {
                            const time = value.getTime();
                            if (! Number.isNaN(time)) {
                                return new Date(time);
                            }
                        }

                        if (typeof value === 'string') {
                            const parsed = new Date(value);
                            return Number.isNaN(parsed.getTime()) ? null : parsed;
                        }

                        return null;
                    }

                    function formatDMY(date) {
                        const normalized = normalizeDate(date);
                        if (! normalized) {
                            return '';
                        }

                        const day = pad(normalized.getDate());
                        const month = pad(normalized.getMonth() + 1);
                        const year = normalized.getFullYear();

                        return `${day}/${month}/${year}`;
                    }

                    function formatDisplayDate(date) {
                        const normalized = normalizeDate(date);
                        if (! normalized) {
                            return '';
                        }

                        return displayFormatter.format(normalized);
                    }

                    function buildQueryString(params) {
                        if (typeof URLSearchParams === 'function') {
                            return new URLSearchParams(params).toString();
                        }

                        return Object.keys(params)
                            .map(function(key) {
                                return encodeURIComponent(key) + '=' + encodeURIComponent(params[key]);
                            })
                            .join('&');
                    }

                    function formatInputDate(date) {
                        const normalized = normalizeDate(date);
                        if (! normalized) {
                            return '';
                        }

                        const year = normalized.getFullYear();
                        const month = pad(normalized.getMonth() + 1);
                        const day = pad(normalized.getDate());

                        return `${year}-${month}-${day}`;
                    }

                    function parseInputDate(value) {
                        if (! value) {
                            return null;
                        }

                        const parts = value.split('-').map(Number);
                        if (parts.length !== 3 || parts.some(function(part) { return Number.isNaN(part); })) {
                            return null;
                        }

                        const [year, month, day] = parts;
                        const parsed = new Date();
                        parsed.setHours(0, 0, 0, 0);
                        parsed.setFullYear(year, month - 1, day);

                        return Number.isNaN(parsed.getTime()) ? null : parsed;
                    }

                    function populateTimes(select) {
                        if (! select) {
                            return;
                        }

                        select.innerHTML = '';

                        for (let hour = 7; hour <= 21; hour += 1) {
                            for (let minute = 0; minute < 60; minute += 15) {
                                const option = document.createElement('option');
                                option.value = `${pad(hour)}:${pad(minute)}`;
                                option.textContent = `${pad(hour)}:${pad(minute)}`;
                                if (hour === 10 && minute === 0) {
                                    option.selected = true;
                                }
                                select.append(option);
                            }
                        }

                        const lastOption = document.createElement('option');
                        lastOption.value = '22:00';
                        lastOption.textContent = '22:00';
                        select.append(lastOption);
                    }

                    function updateLocationDisplay(formId, field, selectElement) {
                        const display = document.getElementById(`${formId}_${field}_display`);
                        if (! display || ! selectElement) {
                            return;
                        }

                        const selectedOption = selectElement.selectedOptions[0];
                        display.textContent = selectedOption ? selectedOption.textContent : '';
                    }

                    function updateTimeDisplay(formId, field, selectElement) {
                        const display = document.getElementById(`${formId}_${field}_display`);
                        if (! display || ! selectElement) {
                            return;
                        }

                        const value = selectElement.value;
                        if (! value) {
                            display.textContent = '';
                            return;
                        }

                        const [hours, minutes] = value.split(':');
                        const hour = parseInt(hours, 10);
                        const isPM = hour >= 12;
                        const twelveHour = hour === 0 ? 12 : (hour > 12 ? hour - 12 : hour);
                        display.textContent = `${twelveHour}:${minutes} ${isPM ? 'PM' : 'AM'}`;
                    }

                    function initDateAdapter(pickupInput, dropoffInput, onChange) {
                        if (typeof Litepicker === 'function') {
                            const today = new Date();
                            const future = new Date(today.getTime());
                            future.setDate(future.getDate() + 3);

                            const picker = new Litepicker({
                                element: pickupInput,
                                elementEnd: dropoffInput,
                                singleMode: false,
                                numberOfMonths: 2,
                                numberOfColumns: 2,
                                minDate: today,
                                startDate: today,
                                endDate: future,
                                format: 'ddd, MMM DD',
                                showWeekNumbers: false,
                                showTooltip: false
                            });

                            picker.on('selected', function(startDate, endDate) {
                                onChange(startDate, endDate);
                            });

                            onChange(picker.getStartDate(), picker.getEndDate());

                            return {
                                getStartDate() {
                                    return picker.getStartDate();
                                },
                                getEndDate() {
                                    return picker.getEndDate();
                                }
                            };
                        }

                        pickupInput.removeAttribute('readonly');
                        dropoffInput.removeAttribute('readonly');
                        pickupInput.type = 'date';
                        dropoffInput.type = 'date';

                        const today = new Date();
                        today.setHours(0, 0, 0, 0);
                        const defaultEnd = new Date(today.getTime());
                        defaultEnd.setDate(defaultEnd.getDate() + 3);

                        pickupInput.value = pickupInput.value || formatInputDate(today);
                        dropoffInput.value = dropoffInput.value || formatInputDate(defaultEnd);

                        function ensureRange() {
                            let start = parseInputDate(pickupInput.value);
                            let end = parseInputDate(dropoffInput.value);

                            if (! start) {
                                start = new Date();
                                start.setHours(0, 0, 0, 0);
                                pickupInput.value = formatInputDate(start);
                            }

                            if (! end || end < start) {
                                end = new Date(start.getTime());
                                end.setDate(end.getDate() + 3);
                                dropoffInput.value = formatInputDate(end);
                            }

                            onChange(start, end);
                        }

                        pickupInput.addEventListener('change', ensureRange);
                        dropoffInput.addEventListener('change', ensureRange);

                        ensureRange();

                        return {
                            getStartDate() {
                                return parseInputDate(pickupInput.value);
                            },
                            getEndDate() {
                                return parseInputDate(dropoffInput.value);
                            }
                        };
                    }

                    function generateVikrentcarUrl(baseUrl, itemId, data) {
                        const pickupDate = normalizeDate(data.pickupDate);
                        const dropoffDate = normalizeDate(data.dropoffDate);

                        if (! baseUrl || ! pickupDate || ! dropoffDate || ! data.pickupTime || ! data.dropoffTime) {
                            return null;
                        }

                        const [pickupHour, pickupMinute] = data.pickupTime.split(':');
                        const [dropoffHour, dropoffMinute] = data.dropoffTime.split(':');

                        const pickupLocation = (data.pickupLocation || '').toLowerCase();
                        const dropoffLocation = (data.dropoffLocation || '').toLowerCase();

                        const place = locationIdMap[pickupLocation] || data.pickupLocation;
                        const returnPlace = data.diffReturn
                            ? (locationIdMap[dropoffLocation] || data.dropoffLocation || data.pickupLocation)
                            : place;

                        const params = {
                            option: 'com_vikrentcar',
                            task: 'search',
                            place,
                            returnplace: returnPlace,
                            pickupdate: formatDMY(pickupDate),
                            pickuph: String(parseInt(pickupHour, 10)),
                            pickupm: String(parseInt(pickupMinute, 10)),
                            releasedate: formatDMY(dropoffDate),
                            releaseh: String(parseInt(dropoffHour, 10)),
                            releasem: String(parseInt(dropoffMinute, 10)),
                            search: 'Search',
                            Itemid: itemId
                        };

                        const query = buildQueryString(params);
                        const separator = baseUrl.indexOf('?') === -1 ? '?' : '&';

                        return `${baseUrl}${separator}${query}`;
                    }

                    function initForm(config) {
                        const { formId, baseUrl, itemId } = config;
                        const form = document.getElementById(`${formId}_form`);

                        if (! form) {
                            return;
                        }

                        const pickupLocationEl = document.getElementById(`${formId}_pickup_location`);
                        const dropoffLocationEl = document.getElementById(`${formId}_dropoff_location`);
                        const differentLocationEl = document.getElementById(`${formId}_different_location`);
                        const pickupDateInput = document.getElementById(`${formId}_pickup_date`);
                        const dropoffDateInput = document.getElementById(`${formId}_dropoff_date`);
                        const pickupDateDisplay = document.getElementById(`${formId}_pickup_date_display`);
                        const dropoffDateDisplay = document.getElementById(`${formId}_dropoff_date_display`);
                        const pickupTimeEl = document.getElementById(`${formId}_pickup_time`);
                        const dropoffTimeEl = document.getElementById(`${formId}_dropoff_time`);
                        const dropoffField = document.getElementById(`${formId}_dropoff_location_field`);
                        if (! pickupLocationEl || ! dropoffLocationEl || ! differentLocationEl || ! pickupDateInput || ! dropoffDateInput) {
                            return;
                        }

                        form.classList.add('single-location');
                        form.setAttribute('role', 'search');
                        form.setAttribute('novalidate', 'novalidate');

                        if (dropoffField) {
                            differentLocationEl.setAttribute('aria-controls', dropoffField.id);
                            dropoffField.setAttribute('role', 'group');
                            dropoffField.setAttribute('aria-label', 'Drop-off location');
                        }

                        form.querySelectorAll('.nomad-field-trigger').forEach(function(trigger) {
                            const targetId = trigger.getAttribute('data-target');
                            if (! targetId) {
                                return;
                            }

                            trigger.addEventListener('click', function() {
                                const element = document.getElementById(targetId);
                                if (element) {
                                    element.focus({ preventScroll: false });
                                }
                            });
                        });

                        populateTimes(pickupTimeEl);
                        populateTimes(dropoffTimeEl);

                        updateLocationDisplay(formId, 'pickup_location', pickupLocationEl);
                        updateLocationDisplay(formId, 'dropoff_location', dropoffLocationEl);
                        updateTimeDisplay(formId, 'pickup_time', pickupTimeEl);
                        updateTimeDisplay(formId, 'dropoff_time', dropoffTimeEl);

                        function applySelectedDates(start, end) {
                            const startText = formatDisplayDate(start) || 'Select date';
                            const endText = formatDisplayDate(end || start) || 'Select date';
                            if (pickupDateDisplay) {
                                pickupDateDisplay.textContent = startText;
                            }
                            if (dropoffDateDisplay) {
                                dropoffDateDisplay.textContent = endText;
                            }
                        }

                        const dateAdapter = initDateAdapter(pickupDateInput, dropoffDateInput, applySelectedDates);

                        if (pickupDateDisplay && dropoffDateDisplay) {
                            applySelectedDates(dateAdapter.getStartDate(), dateAdapter.getEndDate());
                        }

                        function syncDropoffState(options) {
                            const shouldFocus = options && options.shouldFocus;
                            const isDifferent = Boolean(differentLocationEl.checked);

                            if (dropoffField) {
                                dropoffField.classList.toggle('is-visible', isDifferent);
                                dropoffField.hidden = ! isDifferent;
                                dropoffField.setAttribute('aria-hidden', String(! isDifferent));
                            }

                            form.classList.toggle('single-location', ! isDifferent);
                            dropoffLocationEl.disabled = ! isDifferent;
                            dropoffLocationEl.required = isDifferent;
                            dropoffLocationEl.setAttribute('aria-disabled', String(! isDifferent));
                            dropoffLocationEl.tabIndex = isDifferent ? 0 : -1;
                            differentLocationEl.setAttribute('aria-expanded', String(isDifferent));
                            differentLocationEl.setAttribute('aria-checked', String(isDifferent));

                            if (! isDifferent) {
                                dropoffLocationEl.value = pickupLocationEl.value;
                                updateLocationDisplay(formId, 'dropoff_location', dropoffLocationEl);
                            } else if (shouldFocus && dropoffLocationEl) {
                                dropoffLocationEl.focus({ preventScroll: false });
                            }
                        }

                        differentLocationEl.addEventListener('change', function() {
                            syncDropoffState({ shouldFocus: differentLocationEl.checked });
                        });

                        syncDropoffState();

                        pickupLocationEl.addEventListener('change', function() {
                            updateLocationDisplay(formId, 'pickup_location', pickupLocationEl);
                            if (! differentLocationEl.checked) {
                                dropoffLocationEl.value = pickupLocationEl.value;
                                updateLocationDisplay(formId, 'dropoff_location', dropoffLocationEl);
                            }
                        });

                        dropoffLocationEl.addEventListener('change', function() {
                            updateLocationDisplay(formId, 'dropoff_location', dropoffLocationEl);
                        });

                        pickupTimeEl.addEventListener('change', function() {
                            updateTimeDisplay(formId, 'pickup_time', pickupTimeEl);
                        });

                        dropoffTimeEl.addEventListener('change', function() {
                            updateTimeDisplay(formId, 'dropoff_time', dropoffTimeEl);
                        });

                        form.addEventListener('submit', function(event) {
                            event.preventDefault();

                            const url = generateVikrentcarUrl(baseUrl, itemId, {
                                pickupLocation: pickupLocationEl.value,
                                dropoffLocation: dropoffLocationEl.value,
                                diffReturn: differentLocationEl.checked,
                                pickupDate: dateAdapter.getStartDate(),
                                dropoffDate: dateAdapter.getEndDate(),
                                pickupTime: pickupTimeEl.value,
                                dropoffTime: dropoffTimeEl.value
                            });

                            if (! url) {
                                form.reportValidity();
                                return;
                            }

                            window.location.href = url;
                        });
                    }

                    const NomadRentalForm = {
                        init(config) {
                            onReady(function() {
                                initForm(config);
                            });
                        }
                    };

                    const queuedConfigs = Array.isArray(window.NomadRentalFormQueue) ? window.NomadRentalFormQueue : [];
                    window.NomadRentalForm = NomadRentalForm;
                    window.NomadRentalFormQueue = {
                        push(config) {
                            NomadRentalForm.init(config);
                        }
                    };

                    queuedConfigs.forEach(function(config) {
                        NomadRentalForm.init(config);
                    });
                })();
            </script>
            <?php
        }
        ?>
        <div class="nomad-rental-container" id="<?php echo esc_attr($unique_id); ?>">
            <div class="nomad-search-bar">
                <form class="nomad-search-fields" id="<?php echo esc_attr($unique_id); ?>_form" autocomplete="off">
                    <div class="nomad-search-field nomad-pickup-location" id="<?php echo esc_attr($unique_id); ?>_pickup_location_field" role="group" aria-label="Pick-up location">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_pickup_location">
                            <span class="nomad-field-icon" uk-icon="icon: location; ratio: 0.85" data-uk-icon="icon: location; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <path d="M12 21s-6-4.5-6-10a6 6 0 0 1 12 0c0 5.5-6 10-6 10Z"></path>
                                    <circle cx="12" cy="11" r="3"></circle>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Pick-up location</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_pickup_location_display" aria-live="polite" aria-atomic="true">Berat City</div>
                                <select id="<?php echo esc_attr($unique_id); ?>_pickup_location" class="nomad-field-input" name="pickup-location" required>
                                    <option value="berat" selected>Berat City</option>
                                    <option value="shuttle">Airport Shuttle +€70</option>
                                    <option value="aeroporto">Tirana Airport +€150</option>
                                    <option value="teg">Tirana City TEG +€150</option>
                                    <option value="durres">Durrës +€140</option>
                                    <option value="saranda">Saranda +€220</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="nomad-search-field nomad-dropoff-location" id="<?php echo esc_attr($unique_id); ?>_dropoff_location_field" hidden aria-hidden="true">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_dropoff_location">
                            <span class="nomad-field-icon" uk-icon="icon: location; ratio: 0.85" data-uk-icon="icon: location; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <path d="M12 21s-6-4.5-6-10a6 6 0 0 1 12 0c0 5.5-6 10-6 10Z"></path>
                                    <circle cx="12" cy="11" r="3"></circle>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Drop-off location</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_dropoff_location_display" aria-live="polite" aria-atomic="true">Berat City</div>
                                <select id="<?php echo esc_attr($unique_id); ?>_dropoff_location" class="nomad-field-input" name="dropoff-location" disabled>
                                    <option value="berat" selected>Berat City</option>
                                    <option value="shuttle">Airport Shuttle +€70</option>
                                    <option value="aeroporto">Tirana Airport +€150</option>
                                    <option value="teg">Tirana City TEG +€150</option>
                                    <option value="durres">Durrës +€140</option>
                                    <option value="saranda">Saranda +€220</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="nomad-search-field nomad-date-field" id="<?php echo esc_attr($unique_id); ?>_pickup_date_field">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_pickup_date">
                            <span class="nomad-field-icon" uk-icon="icon: calendar; ratio: 0.85" data-uk-icon="icon: calendar; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Pick-up date</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_pickup_date_display" aria-live="polite" aria-atomic="true">Wed, Sep 17</div>
                                <input type="text" id="<?php echo esc_attr($unique_id); ?>_pickup_date" class="nomad-field-input" name="pickup-date" required readonly />
                            </div>
                        </div>
                    </div>

                    <div class="nomad-search-field nomad-time-field" id="<?php echo esc_attr($unique_id); ?>_pickup_time_field">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_pickup_time">
                            <span class="nomad-field-icon" uk-icon="icon: clock; ratio: 0.85" data-uk-icon="icon: clock; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <polyline points="12 7 12 12 16 14"></polyline>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Time</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_pickup_time_display" aria-live="polite" aria-atomic="true">10:00 AM</div>
                                <select id="<?php echo esc_attr($unique_id); ?>_pickup_time" class="nomad-field-input" name="pickup-time" required></select>
                            </div>
                        </div>
                    </div>

                    <div class="nomad-search-field nomad-date-field" id="<?php echo esc_attr($unique_id); ?>_dropoff_date_field">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_dropoff_date">
                            <span class="nomad-field-icon" uk-icon="icon: calendar; ratio: 0.85" data-uk-icon="icon: calendar; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                                    <line x1="16" y1="2" x2="16" y2="6"></line>
                                    <line x1="8" y1="2" x2="8" y2="6"></line>
                                    <line x1="3" y1="10" x2="21" y2="10"></line>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Drop-off date</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_dropoff_date_display" aria-live="polite" aria-atomic="true">Sat, Sep 20</div>
                                <input type="text" id="<?php echo esc_attr($unique_id); ?>_dropoff_date" class="nomad-field-input" name="dropoff-date" required readonly />
                            </div>
                        </div>
                    </div>

                    <div class="nomad-search-field nomad-time-field" id="<?php echo esc_attr($unique_id); ?>_dropoff_time_field">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_dropoff_time">
                            <span class="nomad-field-icon" uk-icon="icon: clock; ratio: 0.85" data-uk-icon="icon: clock; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <circle cx="12" cy="12" r="9"></circle>
                                    <polyline points="12 7 12 12 16 14"></polyline>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Time</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_dropoff_time_display" aria-live="polite" aria-atomic="true">10:00 AM</div>
                                <select id="<?php echo esc_attr($unique_id); ?>_dropoff_time" class="nomad-field-input" name="dropoff-time" required></select>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="nomad-search-field nomad-search-btn" id="<?php echo esc_attr($unique_id); ?>_search_button" aria-label="Search vehicles">
                        Search
                    </button>
                </form>
            </div>

            <div class="nomad-options-row">
                <label class="nomad-checkbox-option" for="<?php echo esc_attr($unique_id); ?>_different_location">
                    <input type="checkbox" id="<?php echo esc_attr($unique_id); ?>_different_location" role="switch" aria-checked="false" />
                    <span>Drop car off at different location</span>
                </label>
            </div>
        </div>
        <script data-litespeed-noopt="1">
            (function() {
                const config = {
                    formId: '<?php echo esc_js($unique_id); ?>',
                    baseUrl: '<?php echo esc_js($base_url); ?>',
                    itemId: '<?php echo esc_js($itemid); ?>'
                };

                if (window.NomadRentalForm && typeof window.NomadRentalForm.init === 'function') {
                    window.NomadRentalForm.init(config);
                } else {
                    window.NomadRentalFormQueue = window.NomadRentalFormQueue || [];
                    window.NomadRentalFormQueue.push(config);
                }
            })();
        </script>
        <?php

        return trim(ob_get_clean());
    }

    add_shortcode('nomad_rental_form_enhanced', 'nomad_rental_form_enhanced_shortcode');
}
