<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Support;

/**
 * One spelling per address: an IPv6 address has many, and an IPv4-mapped IPv6 address is its IPv4 address.
 */
final class IpAddress
{
    private const MAPPED_PREFIX = "\0\0\0\0\0\0\0\0\0\0\xff\xff";

    public static function normalize(?string $ip): ?string
    {
        $ip = trim((string)$ip);

        if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
            return null;
        }

        $packed = (string)inet_pton($ip);

        if (strlen($packed) === 16 && str_starts_with($packed, self::MAPPED_PREFIX)) {
            $packed = substr($packed, 12);
        }

        $normalized = inet_ntop($packed);

        return $normalized === false ? null : $normalized;
    }

    /**
     * An address or a subnet in CIDR notation, normalized, or null when it is neither.
     */
    public static function entry(?string $value): ?string
    {
        $value = trim((string)$value);

        if (!str_contains($value, '/')) {
            return self::normalize($value);
        }

        [$address, $prefix] = explode('/', $value, 2);
        $ip = self::normalize($address);

        if ($ip === null || preg_match('/^\d{1,3}$/', $prefix) !== 1) {
            return null;
        }

        $prefix = (int)$prefix;

        if (str_contains(trim($address), ':') && !str_contains($ip, ':')) {
            // An IPv4-mapped subnet keeps the bits of its IPv4 part.
            $prefix -= 96;
        }

        $max = str_contains($ip, ':') ? 128 : 32;

        return $prefix >= 0 && $prefix <= $max ? $ip . '/' . $prefix : null;
    }
}
