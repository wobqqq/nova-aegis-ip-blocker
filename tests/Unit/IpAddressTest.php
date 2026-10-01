<?php

declare(strict_types=1);

use Wobqqq\AegisIpBlocker\Support\IpAddress;

it('spells every address one way', function (?string $ip, ?string $expected): void {
    expect(IpAddress::normalize($ip))->toBe($expected);
})->with([
    'IPv4' => [' 203.0.113.7 ', '203.0.113.7'],
    'IPv6 in full' => ['2001:0DB8:0000:0000:0000:0000:0000:0001', '2001:db8::1'],
    'IPv6 compressed' => ['2001:db8::1', '2001:db8::1'],
    'IPv4-mapped IPv6' => ['::ffff:203.0.113.7', '203.0.113.7'],
    'loopback' => ['::1', '::1'],
    'octal-looking IPv4' => ['010.0.0.1', null],
    'host name' => ['example.com', null],
    'zone index' => ['fe80::1%eth0', null],
    'empty' => ['', null],
    'null' => [null, null],
]);

it('reads an address or a subnet, and nothing else', function (string $value, ?string $expected): void {
    expect(IpAddress::entry($value))->toBe($expected);
})->with([
    'address' => ['198.51.100.10', '198.51.100.10'],
    'IPv4 subnet' => ['198.51.100.0/24', '198.51.100.0/24'],
    'IPv6 subnet in full' => ['2001:0db8:0000::/32', '2001:db8::/32'],
    'IPv4-mapped subnet' => ['::ffff:198.51.100.0/120', '198.51.100.0/24'],
    'IPv4-mapped subnet too wide' => ['::ffff:198.51.100.0/64', null],
    'IPv4 prefix too long' => ['198.51.100.0/33', null],
    'IPv6 prefix too long' => ['2001:db8::/129', null],
    'prefix not a number' => ['198.51.100.0/abc', null],
    'negative prefix' => ['198.51.100.0/-1', null],
    'no prefix' => ['198.51.100.0/', null],
    'two slashes' => ['198.51.100.0/24/8', null],
    'range' => ['198.51.100.1-198.51.100.9', null],
    'script' => ['<script>alert(1)</script>', null],
]);
