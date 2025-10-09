<?php
/**
 * Nomad rental booking form snippet v6.1.
 *
 * Provides a resilient WordPress shortcode with sanitised input handling,
 * nonce protection and accessible UI components. The snippet reuses the same
 * engine for the v5.1 layout variant (paired mobile columns) and the v6.1
 * layout variant (stacked locations on phones).
 */

if (! defined('NOMAD_RENTAL_FORM_V61_VERSION')) {
    define('NOMAD_RENTAL_FORM_V61_VERSION', '6.1.0');
}

/**
 * Register shared CSS/JS assets and localisation variables.
 *
 * @return void
 */
function nomad_rental_form_v61_register_assets() {
    $style_handle = 'nomad-rental-form-v61';
    $script_handle = 'nomad-rental-form-v61';

    if (! wp_style_is($style_handle, 'registered')) {
        wp_register_style($style_handle, false, [], NOMAD_RENTAL_FORM_V61_VERSION);
        $css_path = __DIR__ . '/assets/css/nomad-rental-form-v61.css';
        if (file_exists($css_path)) {
            $css_content = file_get_contents($css_path); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.fileGetContentsUnknown
            if (false !== $css_content) {
                wp_add_inline_style($style_handle, $css_content);
            }
        }
    }

    if (! wp_script_is($script_handle, 'registered')) {
        wp_register_script($script_handle, '', [], NOMAD_RENTAL_FORM_V61_VERSION, true);
        $js_path = __DIR__ . '/assets/js/nomad-rental-form-v61.js';
        if (file_exists($js_path)) {
            $js_content = file_get_contents($js_path); // phpcs:ignore WordPressVIPMinimum.Performance.FetchingRemoteData.fileGetContentsUnknown
            if (false !== $js_content) {
                wp_add_inline_script($script_handle, $js_content);
            }
        }

        $i18n = [
            'submitting'   => __('Invio in corso…', 'nomad-rental'),
            'networkError' => __('Si è verificato un problema di rete. Riprova.', 'nomad-rental'),
            'unknownError' => __('Si è verificato un errore inatteso. Riprova più tardi.', 'nomad-rental'),
            'success'      => __('Ricerca avviata, verrai reindirizzato tra pochi secondi.', 'nomad-rental'),
        ];

        wp_localize_script(
            $script_handle,
            'NomadRentalFormV61Settings',
            [
                'ajaxUrl'   => esc_url(admin_url('admin-ajax.php')),
                'maxNights' => 120,
                'i18n'      => $i18n,
            ]
        );
    }
}

/**
 * Retrieve the list of supported locations.
 *
 * @return array<int, array<string, string|bool>>
 */
function nomad_rental_form_v61_locations() {
    return [
        [
            'value'    => 'berat',
            'label'    => __('Berat City', 'nomad-rental'),
            'selected' => true,
        ],
        [
            'value' => 'shuttle',
            'label' => __('Airport Shuttle +€70', 'nomad-rental'),
        ],
        [
            'value' => 'aeroporto',
            'label' => __('Tirana Airport +€150', 'nomad-rental'),
        ],
        [
            'value' => 'teg',
            'label' => __('Tirana City TEG +€150', 'nomad-rental'),
        ],
        [
            'value' => 'durres',
            'label' => __('Durrës +€140', 'nomad-rental'),
        ],
        [
            'value' => 'saranda',
            'label' => __('Saranda +€220', 'nomad-rental'),
        ],
    ];
}

/**
 * Time slots from 07:00 to 22:00 every 30 minutes.
 *
 * @return array<int, array<string, string|bool>>
 */
function nomad_rental_form_v61_time_options() {
    $slots = [];
    $start = new DateTimeImmutable('07:00');
    $end   = new DateTimeImmutable('22:00');
    $current = $start;

    while ($current <= $end) {
        $value = $current->format('H:i');
        $label = $current->format('H:i');
        $slots[] = [
            'value'    => $value,
            'label'    => $label,
            'selected' => '10:00' === $value,
        ];
        $current = $current->modify('+30 minutes');
    }

    return $slots;
}

/**
 * Render a select element options list.
 *
 * @param array<int, array<string, string|bool>> $options Option definitions.
 * @param string                                 $selected Selected value.
 *
 * @return string
 */
function nomad_rental_form_v61_render_options($options, $selected) {
    $html = '';
    foreach ($options as $option) {
        $value    = isset($option['value']) ? (string) $option['value'] : '';
        $label    = isset($option['label']) ? (string) $option['label'] : '';
        $is_selected = ($selected && $value === $selected) || (! $selected && ! empty($option['selected']));
        $html .= sprintf(
            '<option value="%s"%s>%s</option>',
            esc_attr($value),
            $is_selected ? ' selected' : '',
            esc_html($label)
        );
    }

    return $html;
}

/**
 * Render the booking form markup.
 *
 * @param array  $atts    Shortcode attributes.
 * @param string $variant Layout variant slug.
 *
 * @return string
 */
function nomad_rental_form_v61_render_form($atts, $variant) {
    nomad_rental_form_v61_register_assets();
    wp_enqueue_style('nomad-rental-form-v61');
    wp_enqueue_script('nomad-rental-form-v61');

    $atts = shortcode_atts(
        [
            'base_url' => 'https://nomadcamperhire.com/search-your-van/index.php',
            'itemid'   => '613',
        ],
        $atts,
        'nomad_rental_form_v61'
    );

    $base_url = esc_url_raw(rtrim($atts['base_url'], '/'));
    $itemid   = sanitize_text_field($atts['itemid']);

    $form_id = 'nomad_rental_form_' . wp_rand(1000, 9999);
    $layout  = 'paired-mobile' === $variant ? 'paired-mobile' : 'stacked-mobile';

    $locations   = nomad_rental_form_v61_locations();
    $time_slots  = nomad_rental_form_v61_time_options();
    $location_id = $form_id . '_pickup_location';

    ob_start();
    ?>
    <div class="nomad-rental-wrapper" id="<?php echo esc_attr($form_id); ?>" data-role="nomad-wrapper" data-layout="<?php echo esc_attr($layout); ?>">
        <div data-role="nomad-alert-region"></div>
        <div class="nomad-rental-card">
            <form class="nomad-rental-form" method="post" novalidate>
                <?php wp_nonce_field('nomad_rental_form_v61_action', 'nomad_rental_form_v61_nonce'); ?>
                <input type="hidden" name="base-url" value="<?php echo esc_attr($base_url); ?>" />
                <input type="hidden" name="itemid" value="<?php echo esc_attr($itemid); ?>" />

                <div class="nomad-field-group nomad-field-group--locations">
                    <div class="nomad-field nomad-field--location" data-field="pickup">
                        <label class="nomad-field__label" for="<?php echo esc_attr($location_id); ?>"><?php esc_html_e('Pick-up location', 'nomad-rental'); ?></label>
                        <div class="nomad-field__value" data-display="pickup-location" aria-live="polite"></div>
                        <select class="nomad-field__select" id="<?php echo esc_attr($location_id); ?>" name="pickup-location" required>
                            <?php echo nomad_rental_form_v61_render_options($locations, ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </select>
                        <p class="nomad-field__error" id="<?php echo esc_attr($location_id); ?>_error"></p>
                    </div>

                    <div class="nomad-field nomad-field--location nomad-field--dropoff" data-field="dropoff" data-role="dropoff-field" hidden>
                        <?php $dropoff_id = $form_id . '_dropoff_location'; ?>
                        <label class="nomad-field__label" for="<?php echo esc_attr($dropoff_id); ?>"><?php esc_html_e('Drop-off location', 'nomad-rental'); ?></label>
                        <div class="nomad-field__value" data-display="dropoff-location" aria-live="polite"></div>
                        <select class="nomad-field__select" id="<?php echo esc_attr($dropoff_id); ?>" name="dropoff-location" disabled>
                            <?php echo nomad_rental_form_v61_render_options($locations, ''); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </select>
                        <p class="nomad-field__error" id="<?php echo esc_attr($dropoff_id); ?>_error"></p>
                    </div>
                </div>

                <div class="nomad-field-group nomad-field-group--dates">
                    <?php $pickup_date_id = $form_id . '_pickup_date'; ?>
                    <div class="nomad-field nomad-field--date" data-field="checkin">
                        <label class="nomad-field__label" for="<?php echo esc_attr($pickup_date_id); ?>"><?php esc_html_e('Pick-up date', 'nomad-rental'); ?></label>
                        <div class="nomad-field__value" data-display="pickup-date" aria-live="polite"></div>
                        <input type="date" class="nomad-field__input" id="<?php echo esc_attr($pickup_date_id); ?>" name="pickup-date" required />
                        <p class="nomad-field__error" id="<?php echo esc_attr($pickup_date_id); ?>_error"></p>
                    </div>

                    <?php $pickup_time_id = $form_id . '_pickup_time'; ?>
                    <div class="nomad-field nomad-field--time" data-field="pickup_time">
                        <label class="nomad-field__label" for="<?php echo esc_attr($pickup_time_id); ?>"><?php esc_html_e('Pick-up time', 'nomad-rental'); ?></label>
                        <div class="nomad-field__value" data-display="pickup-time" aria-live="polite"></div>
                        <select class="nomad-field__select" id="<?php echo esc_attr($pickup_time_id); ?>" name="pickup-time" required>
                            <?php echo nomad_rental_form_v61_render_options($time_slots, '10:00'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </select>
                        <p class="nomad-field__error" id="<?php echo esc_attr($pickup_time_id); ?>_error"></p>
                    </div>

                    <?php $dropoff_date_id = $form_id . '_dropoff_date'; ?>
                    <div class="nomad-field nomad-field--date" data-field="checkout">
                        <label class="nomad-field__label" for="<?php echo esc_attr($dropoff_date_id); ?>"><?php esc_html_e('Drop-off date', 'nomad-rental'); ?></label>
                        <div class="nomad-field__value" data-display="dropoff-date" aria-live="polite"></div>
                        <input type="date" class="nomad-field__input" id="<?php echo esc_attr($dropoff_date_id); ?>" name="dropoff-date" required />
                        <p class="nomad-field__error" id="<?php echo esc_attr($dropoff_date_id); ?>_error"></p>
                    </div>

                    <?php $dropoff_time_id = $form_id . '_dropoff_time'; ?>
                    <div class="nomad-field nomad-field--time" data-field="dropoff_time">
                        <label class="nomad-field__label" for="<?php echo esc_attr($dropoff_time_id); ?>"><?php esc_html_e('Drop-off time', 'nomad-rental'); ?></label>
                        <div class="nomad-field__value" data-display="dropoff-time" aria-live="polite"></div>
                        <select class="nomad-field__select" id="<?php echo esc_attr($dropoff_time_id); ?>" name="dropoff-time" required>
                            <?php echo nomad_rental_form_v61_render_options($time_slots, '10:00'); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
                        </select>
                        <p class="nomad-field__error" id="<?php echo esc_attr($dropoff_time_id); ?>_error"></p>
                    </div>
                </div>

                <?php $guests_id = $form_id . '_guests'; ?>
                <div class="nomad-field" data-field="guests">
                    <label class="nomad-field__label" for="<?php echo esc_attr($guests_id); ?>"><?php esc_html_e('Guests', 'nomad-rental'); ?></label>
                    <select class="nomad-field__select" id="<?php echo esc_attr($guests_id); ?>" name="guests">
                        <?php for ($i = 1; $i <= 6; $i++) : ?>
                            <option value="<?php echo esc_attr((string) $i); ?>"<?php selected(2, $i); ?>><?php echo esc_html($i); ?></option>
                        <?php endfor; ?>
                    </select>
                    <p class="nomad-field__error" id="<?php echo esc_attr($guests_id); ?>_error"></p>
                </div>

                <?php $email_id = $form_id . '_email'; ?>
                <div class="nomad-field" data-field="email">
                    <label class="nomad-field__label" for="<?php echo esc_attr($email_id); ?>"><?php esc_html_e('Email (facoltativa)', 'nomad-rental'); ?></label>
                    <input type="email" class="nomad-field__input" id="<?php echo esc_attr($email_id); ?>" name="email" autocomplete="email" />
                    <p class="nomad-field__error" id="<?php echo esc_attr($email_id); ?>_error"></p>
                </div>

                <div class="nomad-toggle-row">
                    <?php $toggle_id = $form_id . '_different_location'; ?>
                    <label for="<?php echo esc_attr($toggle_id); ?>">
                        <input type="checkbox" id="<?php echo esc_attr($toggle_id); ?>" data-role="dropoff-toggle" role="switch" aria-checked="false" />
                        <?php esc_html_e('Riconsegna in una località diversa', 'nomad-rental'); ?>
                    </label>
                </div>

                <?php $submit_id = $form_id . '_submit'; ?>
                <button type="submit" class="nomad-search-submit" id="<?php echo esc_attr($submit_id); ?>" data-role="submit">
                    <?php esc_html_e('Cerca', 'nomad-rental'); ?>
                </button>
            </form>
        </div>
        <div class="nomad-live-region" aria-live="polite" aria-atomic="true"></div>
    </div>
    <script>
        (function(){
            if (window.NomadRentalFormV61 && typeof window.NomadRentalFormV61.init === 'function') {
                window.NomadRentalFormV61.init({
                    formId: '<?php echo esc_js($form_id); ?>',
                    layout: '<?php echo esc_js($layout); ?>',
                    baseUrl: '<?php echo esc_js($base_url); ?>',
                    itemId: '<?php echo esc_js($itemid); ?>'
                });
            } else {
                window.NomadRentalFormV61Queue = window.NomadRentalFormV61Queue || [];
                window.NomadRentalFormV61Queue.push({
                    formId: '<?php echo esc_js($form_id); ?>',
                    layout: '<?php echo esc_js($layout); ?>',
                    baseUrl: '<?php echo esc_js($base_url); ?>',
                    itemId: '<?php echo esc_js($itemid); ?>'
                });
            }
        })();
    </script>
    <?php

    return trim(ob_get_clean());
}

/**
 * Shortcode entry point for the v6.1 layout (stacked locations on phones).
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function nomad_rental_form_v61_shortcode($atts = []) {
    return nomad_rental_form_v61_render_form($atts, 'stacked-mobile');
}
add_shortcode('nomad_rental_form_enhanced_v61', 'nomad_rental_form_v61_shortcode');

/**
 * Shortcode entry point for the v5.1 layout (locations paired on medium screens).
 *
 * @param array $atts Shortcode attributes.
 *
 * @return string
 */
function nomad_rental_form_v51_shortcode($atts = []) {
    return nomad_rental_form_v61_render_form($atts, 'paired-mobile');
}
add_shortcode('nomad_rental_form_enhanced_v51', 'nomad_rental_form_v51_shortcode');

/**
 * Parse a submitted date value and normalise to UTC midnight.
 *
 * @param string $value Raw submitted value.
 *
 * @return DateTimeImmutable|null
 */
function nomad_rental_form_v61_parse_date($value) {
    if (empty($value)) {
        return null;
    }

    $value = sanitize_text_field(wp_unslash($value));

    $parts = explode('-', $value);
    if (3 !== count($parts)) {
        return null;
    }

    [$year, $month, $day] = array_map('intval', $parts);

    if ($year < 1970 || $month < 1 || $month > 12 || $day < 1 || $day > 31) {
        return null;
    }

    try {
        $date = new DateTimeImmutable(sprintf('%04d-%02d-%02d', $year, $month, $day));
        if ($date->format('Y-m-d') !== sprintf('%04d-%02d-%02d', $year, $month, $day)) {
            return null;
        }
        return $date;
    } catch (Exception $e) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
        return null;
    }
}

/**
 * Validate the posted payload and build the redirect URL.
 *
 * @param array $data Sanitised data array.
 *
 * @return array{ok:bool,errors:array<string,string>,url?:string}
 */
function nomad_rental_form_v61_validate(array $data) {
    $errors = [];

    $pickup_location  = isset($data['pickup-location']) ? sanitize_text_field(wp_unslash($data['pickup-location'])) : '';
    $dropoff_location = isset($data['dropoff-location']) ? sanitize_text_field(wp_unslash($data['dropoff-location'])) : '';
    $pickup_date      = nomad_rental_form_v61_parse_date($data['pickup-date'] ?? '');
    $dropoff_date     = nomad_rental_form_v61_parse_date($data['dropoff-date'] ?? '');
    $pickup_time      = isset($data['pickup-time']) ? sanitize_text_field(wp_unslash($data['pickup-time'])) : '';
    $dropoff_time     = isset($data['dropoff-time']) ? sanitize_text_field(wp_unslash($data['dropoff-time'])) : '';
    $guests           = isset($data['guests']) ? max(1, (int) $data['guests']) : 1;
    $email            = isset($data['email']) ? sanitize_email(wp_unslash($data['email'])) : '';

    if ('' === $pickup_location) {
        $errors['pickup'] = __('Seleziona una località di ritiro', 'nomad-rental');
    }

    if (! empty($dropoff_location) && ! in_array($dropoff_location, wp_list_pluck(nomad_rental_form_v61_locations(), 'value'), true)) {
        $errors['dropoff'] = __('Località di riconsegna non valida', 'nomad-rental');
    }

    if (empty($dropoff_location)) {
        $dropoff_location = $pickup_location;
    }

    if (! in_array($pickup_location, wp_list_pluck(nomad_rental_form_v61_locations(), 'value'), true)) {
        $errors['pickup'] = __('Località di ritiro non valida', 'nomad-rental');
    }

    if (! $pickup_date) {
        $errors['checkin'] = __('Data non valida', 'nomad-rental');
    }

    if (! $dropoff_date) {
        $errors['checkout'] = __('Data non valida', 'nomad-rental');
    }

    $valid_times = wp_list_pluck(nomad_rental_form_v61_time_options(), 'value');
    if ('' === $pickup_time || ! in_array($pickup_time, $valid_times, true)) {
        $errors['pickup_time'] = __('Orario di ritiro non valido', 'nomad-rental');
    }

    if ('' === $dropoff_time || ! in_array($dropoff_time, $valid_times, true)) {
        $errors['dropoff_time'] = __('Orario di riconsegna non valido', 'nomad-rental');
    }

    if ($pickup_date && $pickup_date < new DateTimeImmutable('today')) {
        $errors['checkin'] = __('La data di ritiro non può essere nel passato', 'nomad-rental');
    }

    if ($pickup_date && $dropoff_date && $dropoff_date <= $pickup_date) {
        $errors['checkout'] = __('La riconsegna deve essere successiva al ritiro', 'nomad-rental');
    }

    $max_nights = 120;
    if ($pickup_date && $dropoff_date) {
        $diff = (int) $dropoff_date->diff($pickup_date)->format('%a');
        if ($diff > $max_nights) {
            $errors['checkout'] = sprintf(
                /* translators: %d is the maximum number of nights allowed. */
                __('La durata massima è di %d notti', 'nomad-rental'),
                $max_nights
            );
        }
    }

    if ($email && ! is_email($email)) {
        $errors['email'] = __('Inserisci un indirizzo email valido', 'nomad-rental');
    }

    if ($guests < 1 || $guests > 8) {
        $errors['guests'] = __('Numero di ospiti non valido', 'nomad-rental');
    }

    if (! empty($errors)) {
        return [
            'ok'     => false,
            'errors' => $errors,
        ];
    }

    $params = [
        'pickup'        => $pickup_location,
        'dropoff'       => $dropoff_location,
        'checkin'       => $pickup_date ? $pickup_date->format('Y-m-d') : '',
        'checkout'      => $dropoff_date ? $dropoff_date->format('Y-m-d') : '',
        'pickup_time'   => $pickup_time,
        'dropoff_time'  => $dropoff_time,
        'guests'        => (string) $guests,
    ];

    if ($email) {
        $params['email'] = $email;
    }

    if (! empty($data['itemid'])) {
        $params['Itemid'] = sanitize_text_field(wp_unslash($data['itemid']));
    }

    $existing_query = isset($data['querystring']) ? sanitize_textarea_field(wp_unslash($data['querystring'])) : '';
    $query = nomad_rental_form_v61_build_query($params, $existing_query);

    $base_url = isset($data['base-url']) ? esc_url_raw($data['base-url']) : '';
    if (! $base_url) {
        $base_url = 'https://nomadcamperhire.com/search-your-van/index.php';
    }

    return [
        'ok'     => true,
        'errors' => [],
        'url'    => esc_url_raw($base_url . '?' . $query),
    ];
}

/**
 * Merge parameters preserving UTM tags.
 *
 * @param array  $params       Parameters to merge.
 * @param string $existing_qs  Existing query string.
 *
 * @return string
 */
function nomad_rental_form_v61_build_query(array $params, $existing_qs) {
    $query = [];
    if (! empty($existing_qs)) {
        parse_str($existing_qs, $query);
    }

    foreach ($query as $key => $value) {
        if (0 !== strpos((string) $key, 'utm_')) {
            unset($query[$key]);
        } else {
            if (is_array($value)) {
                $query[$key] = array_map('sanitize_text_field', wp_unslash($value));
            } else {
                $query[$key] = sanitize_text_field(wp_unslash($value));
            }
        }
    }

    foreach ($params as $key => $value) {
        if ('' === $value || null === $value) {
            unset($query[$key]);
            continue;
        }
        $query[$key] = $value;
    }

    ksort($query);

    return http_build_query($query, '', '&', PHP_QUERY_RFC3986);
}

/**
 * AJAX handler for secure submission.
 *
 * @return void
 */
function nomad_rental_form_v61_handle_submit() {
    if (empty($_POST['nomad_rental_form_v61_nonce']) || ! wp_verify_nonce(
        sanitize_text_field(wp_unslash($_POST['nomad_rental_form_v61_nonce'])),
        'nomad_rental_form_v61_action'
    )) {
        wp_send_json(
            [
                'ok'     => false,
                'errors' => [],
                'message'=> __('Sicurezza: sessione scaduta, ricarica la pagina.', 'nomad-rental'),
            ],
            400
        );
        return;
    }

    $result = nomad_rental_form_v61_validate($_POST);

    if (! $result['ok']) {
        wp_send_json([
            'ok'     => false,
            'errors' => $result['errors'],
        ], 422);
        return;
    }

    wp_send_json([
        'ok'  => true,
        'url' => $result['url'],
    ]);
}
add_action('wp_ajax_nomad_rental_form_v61_submit', 'nomad_rental_form_v61_handle_submit');
add_action('wp_ajax_nopriv_nomad_rental_form_v61_submit', 'nomad_rental_form_v61_handle_submit');
