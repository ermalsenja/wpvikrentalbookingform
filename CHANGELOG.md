# Changelog

## [6.1.0] - 2024-09-18
- Added hardened v5.1 and v6.1 shortcodes with nonce-protected forms and consistent escaping.
- Implemented shared asset pipeline with responsive CSS and debounced JavaScript controller.
- Added server-side validation that mirrors the client rules (date parsing, range checks, guest/email sanitisation).
- Introduced AJAX responses using `{ ok, errors }` payloads plus UIkit alert mapping.
- Preserved marketing `utm_*` parameters when rebuilding deep-link URLs.
- Added automated tests (`node:test` + PHP harness) and developer documentation.
