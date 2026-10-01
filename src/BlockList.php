<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Symfony\Component\HttpFoundation\IpUtils;
use Wobqqq\AegisIpBlocker\Support\IpAddress;

final readonly class BlockList
{
    /**
     * @param array<string, true> $addresses normalized addresses, as keys for a constant-time lookup
     * @param list<string> $subnets normalized subnets in CIDR notation
     */
    public function __construct(public array $addresses = [], public array $subnets = [])
    {
    }

    /**
     * @param iterable<mixed> $entries addresses and subnets as written; anything else is skipped
     */
    public static function fromEntries(iterable $entries): self
    {
        $addresses = [];
        $subnets = [];

        foreach ($entries as $entry) {
            $entry = is_string($entry) ? IpAddress::entry($entry) : null;

            if ($entry === null) {
                continue;
            }

            if (str_contains($entry, '/')) {
                $subnets[$entry] = $entry;
            } else {
                $addresses[$entry] = true;
            }
        }

        return new self($addresses, array_values($subnets));
    }

    public function contains(?string $ip): bool
    {
        $ip = IpAddress::normalize($ip);

        if ($ip === null) {
            return false;
        }

        return isset($this->addresses[$ip]) || ($this->subnets !== [] && IpUtils::checkIp($ip, $this->subnets));
    }

    /**
     * @return list<string>
     */
    public function subnetsCovering(string $ip): array
    {
        $ip = IpAddress::normalize($ip);

        if ($ip === null) {
            return [];
        }

        return array_values(array_filter($this->subnets, static fn (string $subnet): bool => IpUtils::checkIp($ip, $subnet)));
    }

    public function count(): int
    {
        return count($this->addresses) + count($this->subnets);
    }
}
