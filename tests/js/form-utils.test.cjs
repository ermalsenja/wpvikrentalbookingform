const test = require('node:test');
const assert = require('node:assert/strict');
const formModule = require('../../assets/js/nomad-rental-form-v61.js');

const { parseInputDate, normalizeDate, ensureRange, buildQueryString, debounce } = formModule.utils;

test('parseInputDate parses valid ISO dates', () => {
  const date = parseInputDate('2024-09-18');
  assert.ok(date instanceof Date);
  assert.equal(date.getUTCFullYear(), 2024);
  assert.equal(date.getUTCMonth(), 8);
  assert.equal(date.getUTCDate(), 18);
});

test('parseInputDate returns null for invalid inputs', () => {
  assert.equal(parseInputDate(''), null);
  assert.equal(parseInputDate('2024-13-01'), null);
  assert.equal(parseInputDate('2024-02-31'), null);
  assert.equal(parseInputDate('not-a-date'), null);
});

test('normalizeDate returns midnight UTC for valid date', () => {
  const original = new Date(Date.UTC(2024, 7, 12, 15, 30));
  const normalized = normalizeDate(original);
  assert.ok(normalized instanceof Date);
  assert.equal(normalized.getUTCHours(), 0);
  assert.equal(normalized.getUTCMinutes(), 0);
  assert.equal(normalized.getUTCDate(), 12);
});

test('ensureRange validates chronological order and max nights', () => {
  const today = new Date();
  const checkin = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate() + 1));
  const checkout = new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate() + 5));
  const result = ensureRange(checkin, checkout, { maxNights: 30 });
  assert.equal(result.ok, true);
  assert.deepEqual(result.errors, {});

  const invalid = ensureRange(checkout, checkin, { maxNights: 30 });
  assert.equal(invalid.ok, false);
  assert.equal(invalid.errors.checkout.includes('successiva'), true);

  const tooLong = ensureRange(checkin, new Date(Date.UTC(today.getUTCFullYear(), today.getUTCMonth(), today.getUTCDate() + 200)), { maxNights: 30 });
  assert.equal(tooLong.ok, false);
  assert.equal(tooLong.errors.checkout.includes('massima'), true);
});

test('ensureRange rejects past dates', () => {
  const now = new Date();
  const yesterday = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() - 1));
  const tomorrow = new Date(Date.UTC(now.getUTCFullYear(), now.getUTCMonth(), now.getUTCDate() + 1));
  const result = ensureRange(yesterday, tomorrow, { maxNights: 30 });
  assert.equal(result.ok, false);
  assert.equal(result.errors.checkin.includes('passato'), true);
});

test('buildQueryString merges params and keeps utm tags', () => {
  const qs = buildQueryString({ pickup: 'berat', checkin: '2024-09-01' }, 'utm_source=google&utm_campaign=brand&foo=bar');
  const params = new URLSearchParams(qs);
  assert.equal(params.get('pickup'), 'berat');
  assert.equal(params.get('checkin'), '2024-09-01');
  assert.equal(params.get('utm_source'), 'google');
  assert.equal(params.get('utm_campaign'), 'brand');
  assert.equal(params.has('foo'), false);
});

test('debounce waits the provided delay', async () => {
  let counter = 0;
  const fn = debounce(() => {
    counter += 1;
  }, 100);
  fn();
  fn();
  fn();
  await new Promise((resolve) => setTimeout(resolve, 150));
  assert.equal(counter, 1);
});
