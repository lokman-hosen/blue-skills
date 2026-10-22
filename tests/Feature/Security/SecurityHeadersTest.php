<?php

use App\Domain\Identity\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Vite;

it('sends security headers with a script nonce the page uses', function () {
    $response = $this->get(route('home'))->assertOk();

    $policy = $response->headers->get('Content-Security-Policy-Report-Only');
    $nonce = Vite::cspNonce();

    expect($policy)->toContain("script-src 'self' 'nonce-{$nonce}'", "frame-ancestors 'none'", "object-src 'none'")
        ->and($response->getContent())->toContain("nonce=\"{$nonce}\"");

    $response->assertHeader('X-Content-Type-Options', 'nosniff')
        ->assertHeader('X-Frame-Options', 'DENY')
        ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->assertHeader('Permissions-Policy')
        ->assertHeaderMissing('Content-Security-Policy')
        ->assertHeaderMissing('Strict-Transport-Security');
});

it('enforces the policy and adds HSTS over HTTPS when configured for production', function () {
    config([
        'blueskills.security.csp_enforce' => true,
        'blueskills.security.media_sources' => 'https://files.example.test',
        'broadcasting.default' => 'reverb',
        'blueskills.security.reverb' => ['host' => 'ws.example.test', 'port' => 443, 'scheme' => 'https'],
    ]);

    $response = $this->get('https://localhost/jobs')->assertOk();

    expect($response->headers->get('Content-Security-Policy'))
        ->toContain('media-src \'self\' blob: https://files.example.test', 'wss://ws.example.test');
    $response->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains')
        ->assertHeaderMissing('Content-Security-Policy-Report-Only');
});

it('adds the headers to error pages too', function () {
    $this->get('/no-such-page')->assertNotFound()->assertHeader('X-Frame-Options', 'DENY');
});

it('limits writes per signed-in user', function () {
    config(['blueskills.security.writes_per_minute' => 2]);
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();
    $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();
    $this->actingAs($user)->post(route('notifications.read-all'))->assertTooManyRequests();

    // Reads are never counted.
    $this->actingAs($user)->get(route('notifications.index'))->assertOk();
});

it('limits worker sign-in per device rather than per network', function () {
    $limitsFor = function (string $sessionId): array {
        $request = Request::create('/join/phone', 'POST', server: ['REMOTE_ADDR' => '203.0.113.7']);
        $session = app('session')->driver();
        $session->setId($sessionId);
        $request->setLaravelSession($session);

        return RateLimiter::limiter('worker-join')($request);
    };

    [$device, $network] = $limitsFor(str_repeat('a', 40));
    [$otherDevice, $sameNetwork] = $limitsFor(str_repeat('b', 40));

    // Two phones on one job-fair Wi-Fi each get their own allowance and share a large one.
    expect($device->maxAttempts)->toBe(10)
        ->and($device->key)->not->toBe($otherDevice->key)
        ->and($network->key)->toBe($sameNetwork->key)
        ->and($network->maxAttempts)->toBe(300);
});
