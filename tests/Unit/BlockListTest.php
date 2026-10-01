<?php

declare(strict_types=1);

use Wobqqq\AegisIpBlocker\BlockList;
use Wobqqq\AegisIpBlocker\IpBlockerSettings;

it('finds listed addresses and the addresses of listed subnets', function (): void {
    $list = BlockList::fromEntries(['203.0.113.7', '198.51.100.0/24', '2001:0db8::/32', '2001:db8::/32', 'nonsense', 42]);

    expect($list->count())->toBe(3)
        ->and($list->contains('203.0.113.7'))->toBeTrue()
        ->and($list->contains('::ffff:203.0.113.7'))->toBeTrue()
        ->and($list->contains('198.51.100.200'))->toBeTrue()
        ->and($list->contains('2001:db8:0:0:0:0:0:abcd'))->toBeTrue()
        ->and($list->contains('203.0.113.8'))->toBeFalse()
        ->and($list->contains('2001:db9::1'))->toBeFalse()
        ->and($list->contains(null))->toBeFalse()
        ->and($list->contains('not an ip'))->toBeFalse();
});

it('names the subnets that cover an address', function (): void {
    $list = BlockList::fromEntries(['198.51.100.0/24', '198.51.0.0/16', '203.0.113.0/24']);

    expect($list->subnetsCovering('198.51.100.9'))->toBe(['198.51.100.0/24', '198.51.0.0/16'])
        ->and($list->subnetsCovering('nope'))->toBe([]);
});

it('reads a stored section of any shape with safe fallbacks', function (): void {
    $settings = IpBlockerSettings::fromArray([
        'enabled' => 'yes',
        'view' => '../../etc/passwd',
        'ips' => [['ip' => ' 203.0.113.7 '], ['ip' => ''], ['ip' => ['nested']], 'string row', ['note' => 'no ip']],
    ]);

    expect($settings->enabled)->toBeTrue()
        ->and($settings->view)->toBe(IpBlockerSettings::DEFAULT_VIEW)
        ->and($settings->blockList->addresses)->toBe(['203.0.113.7' => true]);

    expect(IpBlockerSettings::fromArray(['enabled' => 'maybe', 'ips' => 'all'])->enabled)->toBeFalse()
        ->and(IpBlockerSettings::fromArray(['view' => 'errors.403'])->view)->toBe('errors.403');
});

it('reads no more entries than a save accepts', function (): void {
    $rows = array_map(static fn (int $i): array => ['ip' => '10.0.' . intdiv($i, 256) . '.' . ($i % 256)], range(0, IpBlockerSettings::MAX_ENTRIES + 9));

    expect(IpBlockerSettings::fromArray(['ips' => $rows])->blockList->count())->toBe(IpBlockerSettings::MAX_ENTRIES);
});
