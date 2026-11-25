<?php

use Illuminate\Http\Middleware\TrustHosts;

test('forwarded https is treated as a secure request', function () {
    $this->withHeader('X-Forwarded-Proto', 'https')
        ->get(route('login'));

    expect(request()->isSecure())->toBeTrue();
});

test('forwarded headers from an untrusted address are ignored', function () {
    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
        ->withHeaders([
            'X-Forwarded-For' => '198.51.100.1',
            'X-Forwarded-Host' => 'evil.example',
            'X-Forwarded-Proto' => 'https',
        ])
        ->get(route('login'));

    expect(request()->ip())->toBe('203.0.113.10')
        ->and(request()->getHost())->not->toBe('evil.example')
        ->and(request()->isSecure())->toBeFalse();
});

test('forwarded client ip from a trusted proxy is used', function () {
    $this->withHeader('X-Forwarded-For', '198.51.100.1')
        ->get(route('login'));

    expect(request()->ip())->toBe('198.51.100.1');
});

test('only the application host is trusted', function () {
    $patterns = app(TrustHosts::class)->hosts();
    $host = parse_url(config('app.url'), PHP_URL_HOST);

    $matches = fn (string $candidate): bool => collect($patterns)
        ->filter()
        ->contains(fn (string $pattern): bool => preg_match('{'.$pattern.'}i', $candidate) === 1);

    expect($matches($host))->toBeTrue()
        ->and($matches('evil.example'))->toBeFalse();
});
