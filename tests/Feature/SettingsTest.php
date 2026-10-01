<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use Wobqqq\Aegis\Aegis;
use Wobqqq\AegisIpBlocker\IpBlockerModule;
use Wobqqq\AegisIpBlocker\IpBlockerSettings;

use function Pest\Laravel\actingAs;

/**
 * @param list<array<string, mixed>> $rows
 *
 * @return array<string, mixed>
 */
function section(array $rows, bool $enabled = true): array
{
    return ['enabled' => $enabled, 'view' => IpBlockerSettings::DEFAULT_VIEW, 'ips' => $rows];
}

it('starts off with an empty list', function (): void {
    expect(Aegis::settings(IpBlockerModule::KEY))->toBe(['enabled' => false, 'view' => 'aegis-ip-blocker::blocked', 'ips' => []]);
});

it('describes its section on the Aegis page', function (): void {
    actingAs(admin())->getJson('/nova-vendor/aegis/settings')
        ->assertOk()
        ->assertJsonFragment(['key' => IpBlockerModule::KEY, 'label' => 'IP Blocker'])
        ->assertJsonFragment(['name' => 'ips', 'type' => 'table']);
});

it('saves a list from the Aegis page', function (): void {
    $values = section([['ip' => '203.0.113.7', 'note' => 'Scraper'], ['ip' => '2001:db8::/32', 'note' => null], ['ip' => '', 'note' => null]]);

    actingAs(admin())->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->putJson('/nova-vendor/aegis/settings/ip-blocker', ['values' => $values])
        ->assertOk()
        ->assertJsonPath('values.ips.0.ip', '203.0.113.7');

    expect(Aegis::settings(IpBlockerModule::KEY)['enabled'])->toBeTrue();
});

it('refuses a list that blocks the administrator saving it', function (string $entry): void {
    actingAs(admin())->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->putJson('/nova-vendor/aegis/settings/ip-blocker', ['values' => section([['ip' => '203.0.113.7'], ['ip' => $entry]])])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['ips' => 'The list blocks your own address (192.0.2.10).']);

    expect(Aegis::settings(IpBlockerModule::KEY)['ips'])->toBe([]);
})->with([
    'the address' => ['192.0.2.10'],
    'a subnet' => ['192.0.2.0/24'],
    'a wide subnet' => ['0.0.0.0/0'],
    'the IPv4-mapped address' => ['::ffff:192.0.2.10'],
]);

it('refuses a list that blocks an IPv6 administrator however it is written', function (): void {
    actingAs(admin())->withServerVariables(['REMOTE_ADDR' => '2001:db8::10'])
        ->putJson('/nova-vendor/aegis/settings/ip-blocker', ['values' => section([['ip' => '2001:0db8:0000:0000:0000:0000:0000:0010']])])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('ips');
});

it('keeps the administrator in even while the list is saved switched off', function (): void {
    actingAs(admin())->withServerVariables(['REMOTE_ADDR' => '192.0.2.10'])
        ->putJson('/nova-vendor/aegis/settings/ip-blocker', ['values' => section([['ip' => '192.0.2.10']], enabled: false)])
        ->assertUnprocessable();
});

it('refuses invalid values', function (array $values, string $error): void {
    /** @var array<string, mixed> $values */
    $errors = [];

    try {
        Aegis::save(IpBlockerModule::KEY, $values + section([]));
    } catch (ValidationException $e) {
        $errors = $e->errors();
    }

    expect($errors)->toHaveKey($error);
})->with([
    'not an address' => [['ips' => [['ip' => 'example.com']]], 'ips.0.ip'],
    'markup' => [['ips' => [['ip' => '<script>alert(1)</script>']]], 'ips.0.ip'],
    'bad prefix' => [['ips' => [['ip' => '198.51.100.0/40']]], 'ips.0.ip'],
    'not a string' => [['ips' => [['ip' => ['203.0.113.7']]]], 'ips.0.ip'],
    'an unknown column' => [['ips' => [['ip' => '203.0.113.7', 'evil' => 'x']]], 'ips.0'],
    'a long note' => [['ips' => [['ip' => '203.0.113.7', 'note' => str_repeat('a', 256)]]], 'ips.0.note'],
    'too many rows' => [['ips' => array_fill(0, IpBlockerSettings::MAX_ENTRIES + 1, ['ip' => '203.0.113.7'])], 'ips'],
    'not a list' => [['ips' => 'all'], 'ips'],
    'a path as the view' => [['view' => '../../secret'], 'view'],
    'an empty view' => [['view' => ''], 'view'],
    'not a boolean' => [['enabled' => 'sometimes'], 'enabled'],
]);

it('lists its status on the dashboard', function (): void {
    $module = resolve(IpBlockerModule::class);

    expect($module->status(section([]), )->message)->toBe('On, but no address is listed.')
        ->and($module->status(section([['ip' => '203.0.113.7']]))->message)->toBe('On: blocking 1 address or subnet.')
        ->and($module->status(section([['ip' => '203.0.113.7'], ['ip' => '198.51.100.0/24']]))->message)->toBe('On: blocking 2 addresses and subnets.')
        ->and($module->status(section([], enabled: false))->status->value)->toBe('warn');

    block(['203.0.113.7']);

    actingAs(admin())->getJson('/nova-vendor/aegis/overview')
        ->assertOk()
        ->assertJsonFragment(['key' => IpBlockerModule::KEY, 'status' => 'pass', 'message' => 'On: blocking 1 address or subnet.']);
});
