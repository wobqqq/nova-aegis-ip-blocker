<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Wobqqq\Aegis\Enums\Status;
use Wobqqq\AegisIpBlocker\Checks\ClientIpCheck;

use function Pest\Laravel\actingAs;

afterEach(function (): void {
    Request::setTrustedProxies([], 0);
});

/**
 * @param array<string, string> $server
 */
function checkFor(array $server): Wobqqq\Aegis\Checks\CheckResult
{
    app()->instance('request', Request::create('/', 'GET', server: $server));

    return resolve(ClientIpCheck::class)->run();
}

it('passes when the address comes from the connection', function (): void {
    expect(checkFor(['REMOTE_ADDR' => '203.0.113.7'])->status)->toBe(Status::PASS);
});

it('warns when a proxy forwards the address but is not trusted', function (): void {
    $result = checkFor(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => '203.0.113.7']);

    expect($result->status)->toBe(Status::WARN)->and($result->message)->toContain('X-Forwarded-For');
});

it('passes when the proxy is trusted', function (): void {
    Request::setTrustedProxies(['10.0.0.0/8'], Request::HEADER_X_FORWARDED_FOR);

    expect(checkFor(['REMOTE_ADDR' => '10.0.0.2', 'HTTP_X_FORWARDED_FOR' => '203.0.113.7'])->status)->toBe(Status::PASS);
});

it('runs with the other Aegis checks', function (): void {
    actingAs(admin())->getJson('/nova-vendor/aegis/overview')
        ->assertOk()
        ->assertJsonFragment(['key' => ClientIpCheck::KEY, 'status' => 'pass']);
});
