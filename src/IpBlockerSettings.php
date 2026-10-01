<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

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
        $enabled = $values['enabled'] ?? false;
        $view = $values['view'] ?? null;
        $view = is_string($view) && strlen($view) <= 100 && preg_match(self::VIEW_PATTERN, $view) === 1 ? $view : self::DEFAULT_VIEW;

        return new self(
            is_bool($enabled) ? $enabled : filter_var($enabled, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            $view,
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
        $entries = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $ip = is_array($row) ? ($row['ip'] ?? null) : null;

            if (is_string($ip) && trim($ip) !== '') {
                $entries[] = trim($ip);
            }
        }

        return $entries;
    }
}
