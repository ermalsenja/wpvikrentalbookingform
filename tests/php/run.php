<?php
require __DIR__ . '/stubs.php';
require __DIR__ . '/../../snipetv6_1.php';

$tests = [];

$tests['parse_date_invalid'] = function () {
    $date = nomad_rental_form_v61_parse_date('2024-02-30');
    if (null !== $date) {
        throw new RuntimeException('Expected null for invalid date.');
    }
};

$tests['validate_checkout_before_checkin'] = function () {
    $today = new DateTimeImmutable('today');
    $payload = [
        'pickup-location' => 'berat',
        'dropoff-location' => 'berat',
        'pickup-date'      => $today->format('Y-m-d'),
        'dropoff-date'     => $today->modify('-1 day')->format('Y-m-d'),
        'pickup-time'      => '10:00',
        'dropoff-time'     => '12:00',
        'guests'           => '2',
    ];
    $result = nomad_rental_form_v61_validate($payload);
    if ($result['ok']) {
        throw new RuntimeException('Validation should fail when checkout precedes checkin.');
    }
    if (empty($result['errors']['checkout'])) {
        throw new RuntimeException('Expected checkout error message.');
    }
};

$tests['validate_sanitizes_input'] = function () {
    $today = new DateTimeImmutable('today');
    $payload = [
        'pickup-location' => 'berat',
        'dropoff-location' => '',
        'pickup-date'      => $today->modify('+1 day')->format('Y-m-d'),
        'dropoff-date'     => $today->modify('+3 day')->format('Y-m-d'),
        'pickup-time'      => '09:00',
        'dropoff-time'     => '10:00',
        'guests'           => '2',
        'email'            => 'test@example.com',
        'base-url'         => 'https://example.com/search',
        'querystring'      => 'utm_source=google&utm_campaign=<script>&foo=bar',
    ];
    $result = nomad_rental_form_v61_validate($payload);
    if (! $result['ok']) {
        throw new RuntimeException('Expected validation to succeed with sanitised inputs.');
    }
    if (strpos($result['url'], '<') !== false) {
        throw new RuntimeException('Sanitisation failed, dangerous characters present in URL.');
    }
    if (strpos($result['url'], 'foo=') !== false) {
        throw new RuntimeException('Unexpected non-UTM parameter preserved.');
    }
    if (strpos($result['url'], 'utm_source=google') === false) {
        throw new RuntimeException('UTM parameter should be preserved.');
    }
};

$tests['build_query_merges'] = function () {
    $query = nomad_rental_form_v61_build_query(
        [
            'pickup'   => 'berat',
            'checkin'  => '2024-09-01',
            'checkout' => '2024-09-05',
        ],
        'utm_source=google&utm_medium=cpc&irrelevant=value'
    );
    parse_str($query, $params);
    if (! isset($params['pickup']) || 'berat' !== $params['pickup']) {
        throw new RuntimeException('Pickup parameter missing after merge.');
    }
    if (isset($params['irrelevant'])) {
        throw new RuntimeException('Unknown parameter should have been removed.');
    }
    if (! isset($params['utm_source'])) {
        throw new RuntimeException('UTM parameter missing after merge.');
    }
};

foreach ($tests as $name => $callback) {
    try {
        $callback();
        echo "[PASS] {$name}" . PHP_EOL;
    } catch (Throwable $throwable) {
        echo "[FAIL] {$name}: " . $throwable->getMessage() . PHP_EOL;
        exit(1);
    }
}

echo "All PHP tests passed" . PHP_EOL;
