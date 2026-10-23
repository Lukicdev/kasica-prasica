<?php

test('forwarded https is treated as a secure request', function () {
    $this->withHeader('X-Forwarded-Proto', 'https')
        ->get(route('login'));

    expect(request()->isSecure())->toBeTrue();
});
