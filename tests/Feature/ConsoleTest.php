<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Settings\AegisSetting;
use Wobqqq\Aegis\Settings\SettingsRepository;
use Wobqqq\AegisIpBlocker\IpBlocker;
use Wobqqq\AegisIpBlocker\IpBlockerModule;

/**
 * @return list<string>
 */
function listed(): array
{
    $rows = Aegis::settings(IpBlockerModule::KEY)['ips'] ?? null;

    return array_values(array_map(static fn (mixed $row): string => is_array($row) && is_string($row['ip'] ?? null) ? $row['ip'] : '', is_array($rows) ? $rows : []));
}

it('removes an address however it is written', function (): void {
    block(['2001:0db8:0000:0000:0000:0000:0000:0001', '203.0.113.7']);

    expect(Artisan::call('aegis:ip-blocker:remove-ip', ['ip' => '2001:db8::1']))->toBe(0)
        ->and(Artisan::output())->toContain('2001:db8::1 is no longer on the IP Blocker list.')
        ->and(listed())->toBe(['203.0.113.7']);

    from('2001:db8::1')->get('/')->assertOk();
});

it('removes a subnet', function (): void {
    block(['198.51.100.0/24']);

    expect(Artisan::call('aegis:ip-blocker:remove-ip', ['ip' => '198.51.100.0/24']))->toBe(0)
        ->and(listed())->toBe([]);
});

it('says when the address is not listed or still blocked by a subnet', function (): void {
    block(['198.51.100.0/24']);

    expect(Artisan::call('aegis:ip-blocker:remove-ip', ['ip' => '198.51.100.7']))->toBe(0)
        ->and(Artisan::output())->toContain('198.51.100.7 is not on the IP Blocker list.')->toContain('still blocked by 198.51.100.0/24');
});

it('refuses what is not an address', function (string $ip): void {
    expect(Artisan::call('aegis:ip-blocker:remove-ip', ['ip' => $ip]))->toBe(1)
        ->and(Artisan::output())->toContain('not an IP address');
})->with(['example.com', '1.2.3.4/99', '']);

it('recovers a list that blocks the console address and drops broken rows', function (): void {
    AegisSetting::query()->create(['section' => IpBlockerModule::KEY, 'values' => [
        'enabled' => true,
        'view' => 'aegis-ip-blocker::blocked',
        'ips' => [['ip' => '127.0.0.1'], ['ip' => '127.0.0.0/8', 'note' => 'Local'], ['ip' => 'garbage'], 'row', ['ip' => '203.0.113.7', 'evil' => 'x']],
    ]]);
    resolve(SettingsRepository::class)->flush();

    expect(Artisan::call('aegis:ip-blocker:remove-ip', ['ip' => '127.0.0.1']))->toBe(0)
        ->and(Aegis::settings(IpBlockerModule::KEY)['ips'] ?? null)->toBe([['ip' => '127.0.0.0/8', 'note' => 'Local'], ['ip' => '203.0.113.7', 'note' => null]]);
});

it('turns the module off and keeps the list', function (): void {
    block(['203.0.113.7'], ['view' => 'errors.403']);

    expect(Artisan::call('aegis:ip-blocker:disable'))->toBe(0)
        ->and(Artisan::output())->toContain('IP Blocker is off.')
        ->and(Aegis::settings(IpBlockerModule::KEY))->toMatchArray(['enabled' => false, 'view' => 'errors.403'])
        ->and(listed())->toBe(['203.0.113.7']);

    from('203.0.113.7')->get('/')->assertOk();
});

it('keeps the lock-out rule for the next save from a request', function (): void {
    Artisan::call('aegis:ip-blocker:disable');

    block(['127.0.0.1']);
})->throws(ValidationException::class);

it('removes nothing for what is not an address', function (): void {
    block(['203.0.113.7']);

    expect(resolve(IpBlocker::class)->remove('nonsense'))->toBe(0)
        ->and(listed())->toBe(['203.0.113.7']);
});
