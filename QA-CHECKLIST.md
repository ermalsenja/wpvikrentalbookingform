# QA Checklist

- [ ] Submit with nonce removed → receives warning alert and no redirect.
- [ ] Load page with `?pickup=berat&dropoff=saranda&utm_source=google&utm_medium=cpc` → fields pre-populated and UTM tags persist after edits.
- [ ] Set check-in to today and check-out to yesterday → inline errors + focus on check-in.
- [ ] Submit empty form → required fields flagged and alert rendered.
- [ ] Enable `prefers-reduced-motion` in OS/browser → no animated button transitions.
- [ ] Verify browser consoles (Safari, Firefox, Chrome) → no warnings/errors.
- [ ] Compare v5.1 vs v6.1 layouts → no regressions from previous versions.
- [ ] Confirm success flow shows `uk-alert-success` then redirects to VikRentCar.
