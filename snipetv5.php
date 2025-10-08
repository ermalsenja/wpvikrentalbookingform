<?php
/**
 * Enhanced shortcode variant with UIkit icons and refined responsive behaviour.
 *
 * Version 5 tweaks: keep pick-up/drop-off paired on tablet widths but stack them on
 * narrow mobile breakpoints.
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
            'litepicker', 'https://cdn.jsdelivr.net/npm/litepicker/dist/bundle.js', 
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
                    display: flex;
                    align-items: stretch;
                    width: 100%;
                    gap: 2px;
                }

                .nomad-search-field {
                    background: #fff;
                    position: relative;
                    border-radius: 6px;
                    flex: 1;
                    transition: background-color 0.15s ease;
                    overflow: hidden;
                }

                .nomad-search-field:hover {
                    background: #fafafa;
                }

                .nomad-search-btn {
                    background: #0073e6 !important;
                    color: #fff !important;
                    justify-content: center;
                    align-items: center;
                    display: flex;
                    flex: 0 0 120px;
                    font-weight: 600;
                    font-size: 16px;
                    line-height: 24px;
                    transition: transform 0.12s cubic-bezier(0.2, 0, 0.4, 0.8);
                    border: none;
                    border-radius: 6px;
                    cursor: pointer;
                }


                .nomad-field-trigger {
                    display: flex;
                    align-items: center;
                    gap: 12px;
                    padding: 6px 12px;
                    width: 100%;
                    height: 100%;
                    cursor: pointer;
                    min-height: 52px;
                }

                .nomad-field-icon {
                    opacity: 0.75;
                    flex-shrink: 0;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                }

                .nomad-field-icon svg {
                    width: 20px;
                    height: 20px;
                    display: block;
                    pointer-events: none;
                }

                .nomad-field-icon.uk-icon .nomad-icon-fallback,
                .nomad-field-icon[data-uk-icon] .nomad-icon-fallback {
                    display: none;
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
                    top: 0;
                    left: 0;
                    right: 0;
                    bottom: 0;
                    opacity: 0;
                }

                .nomad-search-fields.single-location .nomad-pickup-location {
                    flex: 2;
                }

                .nomad-search-fields:not(.single-location) .nomad-pickup-location,
                .nomad-search-fields:not(.single-location) .nomad-dropoff-location {
                    flex: 1;
                }

                .nomad-dropoff-location {
                    display: none;
                }

                .nomad-dropoff-location.show {
                    display: flex;
                }

                .nomad-date-field {
                    flex: 0.7;
                    min-width: 120px;
                }

                .nomad-time-field {
                    flex: 0.6;
                    min-width: 110px;
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
                        flex: 0 0 140px;
                    }

                    .nomad-time-field {
                        flex: 0 0 110px;
                    }

                    .nomad-pickup-location,
                    .nomad-dropoff-location {
                        min-width: 150px;
                    }

                    .nomad-search-fields.single-location .nomad-pickup-location {
                        flex: 2;
                    }
                }

                @media (max-width: 1020px) {
                    .nomad-search-fields {
                        flex-wrap: wrap;
                        gap: 2px;
                    }

                    .nomad-pickup-location {
                        flex: 1 1 calc(50% - 1px);
                        min-height: 60px;
                        order: 1;
                    }

                    .nomad-dropoff-location {
                        flex: 1 1 calc(50% - 1px);
                        min-height: 60px;
                        order: 2;
                    }

                    .nomad-dropoff-location.show {
                        flex: 1 1 calc(50% - 1px);
                    }

                    .nomad-search-fields.single-location .nomad-pickup-location {
                        flex: 1 1 100% !important;
                    }

                    .nomad-search-fields:not(.single-location) .nomad-pickup-location,
                    .nomad-search-fields:not(.single-location) .nomad-dropoff-location,
                    .nomad-search-fields:not(.single-location) .nomad-dropoff-location.show {
                        flex: 1 1 calc(50% - 1px) !important;
                    }

                    .nomad-search-field[id$="_pickup_date_field"] {
                        flex: 1 1 calc(50% - 1px);
                        min-height: 60px;
                        order: 3;
                    }

                    .nomad-search-field[id$="_pickup_time_field"] {
                        flex: 1 1 calc(50% - 1px);
                        min-height: 60px;
                        order: 4;
                    }

                    .nomad-search-field[id$="_dropoff_date_field"] {
                        flex: 1 1 calc(50% - 1px);
                        min-height: 60px;
                        order: 5;
                    }

                    .nomad-search-field[id$="_dropoff_time_field"] {
                        flex: 1 1 calc(50% - 1px);
                        min-height: 60px;
                        order: 6;
                    }

                    .nomad-search-btn {
                        flex: 1 1 100%;
                        min-height: 50px;
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
                    .nomad-dropoff-location,
                    .nomad-search-fields:not(.single-location) .nomad-pickup-location,
                    .nomad-search-fields:not(.single-location) .nomad-dropoff-location,
                    .nomad-search-fields:not(.single-location) .nomad-dropoff-location.show {
                        flex: 1 1 100% !important;
                        min-height: 56px;
                    }

                    .nomad-search-fields.single-location .nomad-pickup-location {
                        flex: 1 1 100% !important;
                    }

                    .nomad-search-field[id$="_pickup_date_field"],
                    .nomad-search-field[id$="_pickup_time_field"],
                    .nomad-search-field[id$="_dropoff_date_field"],
                    .nomad-search-field[id$="_dropoff_time_field"] {
                        flex: 1 1 calc(50% - 2px);
                        min-width: 0;
                    }

                    .nomad-field-label {
                        font-size: 11px;
                    }

                    .nomad-field-value {
                        font-size: 13px;
                    }

                    /* Stack Litepicker months vertically on smaller screens and keep within viewport */
                    .litepicker {
                        width: calc(100vw - 24px);
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
                    .nomad-date-field,
                    .nomad-time-field {
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
                /* 1) Colora di nero tutte le icone (UIkit + fallback) */
.nomad-field-icon,
.nomad-field-icon .uk-icon,
.nomad-field-icon [uk-icon],
.nomad-field-icon [data-uk-icon] {
  color: #000;   /* nero */
  opacity: 1;    /* niente grigio */
}

/* 2) Assicurati che lo stroke segua il color anche se il tema lo sovrascrive */
.nomad-field-icon svg {
  stroke: currentColor !important;
  fill: none; /* i tuoi SVG sono a traccia */
}

/* (opzionale) Dimensione leggermente più grande */
.nomad-field-icon svg {
  width: 22px;
  height: 22px;
}

/* (opzionale) Se vuoi effetto hover diverso, cambialo qui */
.nomad-field-trigger:hover .nomad-field-icon {
  color: #000; /* lascia nero oppure metti un #111/#333 per micro-contrasto */
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

                    function normalizePickerDate(value) {
                        if (! value) {
                            return null;
                        }

                        if (value instanceof Date) {
                            return value;
                        }

                        if (typeof value.toDate === 'function') {
                            const date = value.toDate();
                            if (date instanceof Date) {
                                return date;
                            }
                        }

                        if (typeof value.toJSDate === 'function') {
                            const date = value.toJSDate();
                            if (date instanceof Date) {
                                return date;
                            }
                        }

                        if (value && value.dateInstance instanceof Date) {
                            return value.dateInstance;
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
                        const normalized = normalizePickerDate(date);
                        if (! normalized) {
                            return '';
                        }

                        const day = pad(normalized.getDate());
                        const month = pad(normalized.getMonth() + 1);
                        const year = normalized.getFullYear();

                        return `${day}/${month}/${year}`;
                    }

                    function formatDisplayDate(date) {
                        const normalized = normalizePickerDate(date);
                        if (! normalized) {
                            return '';
                        }

                        return displayFormatter.format(normalized);
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
                        if (selectedOption) {
                            display.textContent = selectedOption.textContent;
                        }
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

                    function generateVikrentcarUrl(baseUrl, itemId, data) {
                        const pickupDate = normalizePickerDate(data.pickupDate);
                        const dropoffDate = normalizePickerDate(data.dropoffDate);

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

                        const params = new URLSearchParams({
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
                        });

                        return `${baseUrl}?${params.toString()}`;
                    }

                    function initForm(config) {
                        const { formId, baseUrl, itemId } = config;
                        const form = document.getElementById(`${formId}_form`);
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
                        const searchButton = document.getElementById(`${formId}_search_button`);

                        if (! form || typeof Litepicker !== 'function') {
                            return;
                        }

                        form.addEventListener('submit', function(event) {
                            event.preventDefault();
                        });

                        form.classList.add('single-location');

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

                        function syncDropoffState() {
                            const isDifferent = Boolean(differentLocationEl.checked);

                            if (isDifferent) {
                                dropoffField.classList.add('show');
                                form.classList.remove('single-location');
                            } else {
                                dropoffField.classList.remove('show');
                                form.classList.add('single-location');
                                dropoffLocationEl.value = pickupLocationEl.value;
                                updateLocationDisplay(formId, 'dropoff_location', dropoffLocationEl);
                            }
                        }

                        differentLocationEl.addEventListener('change', syncDropoffState);

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

                        syncDropoffState();

                        const today = new Date();
                        const future = new Date(today.getTime());
                        future.setDate(future.getDate() + 3);

                        const picker = new Litepicker({
                            element: pickupDateInput,
                            elementEnd: dropoffDateInput,
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

                        window[`${formId}_picker`] = picker;

                        function applySelectedDates(start, end) {
                            const startText = formatDisplayDate(start);
                            const endText = formatDisplayDate(end || start);
                            pickupDateDisplay.textContent = startText;
                            dropoffDateDisplay.textContent = endText;
                            pickupDateInput.value = startText;
                            dropoffDateInput.value = endText;
                        }

                        picker.on('selected', function(startDate, endDate) {
                            applySelectedDates(startDate, endDate);
                        });

                        applySelectedDates(picker.getStartDate(), picker.getEndDate());

                        if (searchButton) {
                            searchButton.addEventListener('click', function(event) {
                                event.preventDefault();
                                const url = generateVikrentcarUrl(baseUrl, itemId, {
                                    pickupLocation: pickupLocationEl.value,
                                    dropoffLocation: dropoffLocationEl.value,
                                    diffReturn: differentLocationEl.checked,
                                    pickupDate: picker.getStartDate(),
                                    dropoffDate: picker.getEndDate(),
                                    pickupTime: pickupTimeEl.value,
                                    dropoffTime: dropoffTimeEl.value
                                });

                                if (url) {
                                    window.open(url, '_self', 'noopener');
                                }
                            });
                        }
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
                    <div class="nomad-search-field nomad-pickup-location" id="<?php echo esc_attr($unique_id); ?>_pickup_location_field">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_pickup_location">
                            <span class="nomad-field-icon" uk-icon="icon: location; ratio: 0.85" data-uk-icon="icon: location; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <path d="M12 21s-6-4.5-6-10a6 6 0 0 1 12 0c0 5.5-6 10-6 10Z"></path>
                                    <circle cx="12" cy="11" r="3"></circle>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Pick-up location</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_pickup_location_display">Berat City</div>
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

                    <div class="nomad-search-field nomad-dropoff-location" id="<?php echo esc_attr($unique_id); ?>_dropoff_location_field">
                        <div class="nomad-field-trigger" data-target="<?php echo esc_attr($unique_id); ?>_dropoff_location">
                            <span class="nomad-field-icon" uk-icon="icon: location; ratio: 0.85" data-uk-icon="icon: location; ratio: 0.85" aria-hidden="true">
                                <svg class="nomad-icon-fallback" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" focusable="false">
                                    <path d="M12 21s-6-4.5-6-10a6 6 0 0 1 12 0c0 5.5-6 10-6 10Z"></path>
                                    <circle cx="12" cy="11" r="3"></circle>
                                </svg>
                            </span>
                            <div class="nomad-field-content">
                                <div class="nomad-field-label">Drop-off location</div>
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_dropoff_location_display">Berat City</div>
                                <select id="<?php echo esc_attr($unique_id); ?>_dropoff_location" class="nomad-field-input" name="dropoff-location">
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
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_pickup_date_display">Wed, Sep 17</div>
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
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_pickup_time_display">10:00 AM</div>
                                <select id="<?php echo esc_attr($unique_id); ?>_pickup_time" class="nomad-field-input" name="pickup-time"></select>
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
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_dropoff_date_display">Sat, Sep 20</div>
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
                                <div class="nomad-field-value" id="<?php echo esc_attr($unique_id); ?>_dropoff_time_display">10:00 AM</div>
                                <select id="<?php echo esc_attr($unique_id); ?>_dropoff_time" class="nomad-field-input" name="dropoff-time"></select>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="nomad-search-field nomad-search-btn" id="<?php echo esc_attr($unique_id); ?>_search_button">
                        SEARCH
                    </button>
                </form>
            </div>

            <div class="nomad-options-row">
                <label class="nomad-checkbox-option" for="<?php echo esc_attr($unique_id); ?>_different_location">
                    <input type="checkbox" id="<?php echo esc_attr($unique_id); ?>_different_location" />
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
