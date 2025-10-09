# Nomad Rental Booking Form Snippets

This repository hosts WordPress shortcode snippets used to render the Nomad Camper Hire booking form in multiple layout variants. Version **5.1** keeps the pick-up and drop-off fields paired on medium viewports, while **6.1** stacks them on narrow phones. Both share the same hardened PHP/JS engine.

## Architecture

- **Shortcodes**: `nomad_rental_form_enhanced_v51` and `nomad_rental_form_enhanced_v61` are declared inside `snipetv6_1.php`. The v5.1 wrapper (`snipetv5_1.php`) simply loads the shared implementation and selects the paired layout.
- **Assets**: CSS (`assets/css/nomad-rental-form-v61.css`) controls the responsive grid. JavaScript (`assets/js/nomad-rental-form-v61.js`) handles Litepicker-less date inputs, URL deep-linking, validation mirroring, and AJAX submission.
- **Server handler**: The AJAX endpoint `nomad_rental_form_v61_submit` validates payloads, enforces nonce checks, sanitises every field, and builds the VikRentCar redirect URL while preserving UTM parameters.
- **Tests**: Node’s built-in `node:test` suite covers the utility functions (`parseInputDate`, `normalizeDate`, `ensureRange`, `buildQueryString`, `debounce`). A lightweight PHP harness checks sanitisation, validation failures, and URL generation edge cases (invalid ranges, malicious input, UTM preservation).

## Supported parameters

The form recognises (client & server side) the following parameters:

| Parameter        | Description                          |
| ---------------- | ------------------------------------ |
| `pickup`         | Location slug for pick-up            |
| `dropoff`        | Location slug for drop-off           |
| `checkin`        | Pick-up date (`YYYY-MM-DD`)          |
| `checkout`       | Drop-off date (`YYYY-MM-DD`)         |
| `pickup_time`    | Pick-up time (24h, `HH:MM`)          |
| `dropoff_time`   | Drop-off time (24h, `HH:MM`)         |
| `guests`         | Number of passengers                 |
| `email`          | Optional contact email               |
| `Itemid`         | Joomla/VikRentCar item ID passthrough|
| `utm_*`          | Any UTM marketing tag (preserved)    |

Unknown parameters are ignored when the query string is rebuilt.

## Adding new fields

1. **Sanitise on input** in `nomad_rental_form_v61_validate()` using the relevant WordPress helper (e.g. `sanitize_text_field`, `sanitize_email`, `absint`).
2. **Escape on output** via `esc_attr`/`esc_html` inside the form renderer.
3. **Expose client logic**: add change listeners in `assets/js/nomad-rental-form-v61.js` and append to `collectParams` / `applyParams`.
4. **Extend tests** in both `tests/js/form-utils.test.cjs` and `tests/php/run.php` to cover success & failure paths.

## Running tests

```bash
npm install # no dependencies, but initialises lockfile if needed
npm test
```

This runs the JavaScript unit tests followed by the PHP validation harness. Both must pass before shipping changes.

## Deployment notes

- The shortcode injects inline assets exactly once per page load, so multiple form instances stay in sync.
- Nonce failures surface a UIkit warning and prevent submission without reloading.
- The history updater is debounced (250 ms) to avoid excessive `replaceState` calls and to minimise layout shifts.
- Respect `prefers-reduced-motion`: CSS transitions are disabled automatically.
