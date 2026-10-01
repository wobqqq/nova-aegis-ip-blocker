<?php

declare(strict_types=1);

use Illuminate\Http\Request;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\AegisIpBlocker\IpBlockerModule;
use Wobqqq\AegisIpBlocker\Tests\Fixtures\User;
use Wobqqq\AegisIpBlocker\Tests\TestCase;

use function Pest\Laravel\withServerVariables;

pest()->extend(TestCase::class)->in('Unit', 'Feature');

/**
 * The next requests come from this address.
 */
function from(string $ip): TestCase
{
    $test = withServerVariables(['REMOTE_ADDR' => $ip]);
    assert($test instanceof TestCase);

    return $test;
}

function admin(): User
{
    return User::query()->create(['email' => 'admin@example.com', 'is_admin' => true]);
}

function editor(): User
{
    return User::query()->create(['email' => 'editor@example.com', 'is_admin' => false]);
}

/**
 * Saves the module's section outside any visitor's request, as from 127.0.0.1.
 *
 * @param list<string> $ips
 * @param array<string, mixed> $values
 *
 * @return array<string, mixed>
 */
function block(array $ips, array $values = []): array
{
    app()->instance('request', Request::create('/'));
    $rows = array_map(static fn (string $ip): array => ['ip' => $ip, 'note' => null], $ips);

    return resolve(SettingsRepository::class)->save(IpBlockerModule::KEY, $values + ['enabled' => true, 'view' => 'aegis-ip-blocker::blocked', 'ips' => $rows]);
}
