<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Wobqqq\Aegis\Support\Values;

/**
 * The stored section read again with safe fallbacks: the row may predate the rules or be written by hand.
 */
final readonly class IpBlockerSettings
{
    public const DEFAULT_VIEW = 'aegis-ip-blocker::blocked';

    public const MAX_ENTRIES = 500;

    public const VIEW_PATTERN = '/^[A-Za-z0-9_.-]{1,100}(?:::[A-Za-z0-9_.-]{1,100})?$/';

    public function __construct(public bool $enabled, public string $view, public BlockList $blockList)
    {
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        $view = Values::string($values, 'view');

        return new self(
            Values::bool($values, 'enabled'),
            strlen($view) <= 100 && preg_match(self::VIEW_PATTERN, $view) === 1 ? $view : self::DEFAULT_VIEW,
            BlockList::fromEntries(array_slice(self::entries($values['ips'] ?? null), 0, self::MAX_ENTRIES)),
        );
    }

    /**
     * The `ip` column of the table setting, skipping rows of any other shape.
     *
     * @return list<string>
     */
    public static function entries(mixed $rows): array
    {
        return Values::column(['ips' => $rows], 'ips', 'ip');
    }
}
