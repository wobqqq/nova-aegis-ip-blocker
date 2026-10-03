<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Wobqqq\Aegis\Aegis;
use Wobqqq\AegisIpBlocker\Support\IpAddress;

/**
 * The recovery commands' changes to the list, saved without an administrator to keep in.
 */
final readonly class BlockListWriter
{
    public function __construct(private IpBlocker $blocker, private Administrator $administrator)
    {
    }

    /**
     * Removes the rows listing this address or subnet, however it is written.
     *
     * @return int the number of rows removed
     */
    public function remove(string $entry): int
    {
        $entry = IpAddress::entry($entry);

        if ($entry === null) {
            return 0;
        }

        $rows = $this->rows();
        $kept = array_values(array_filter($rows, static fn (array $row): bool => IpAddress::entry($row['ip']) !== $entry));

        if ($kept !== $rows) {
            $this->save(['ips' => $kept]);
        }

        return count($rows) - count($kept);
    }

    public function disable(): void
    {
        $this->save(['enabled' => false]);
    }

    /**
     * The stored rows a save accepts again, so a broken row cannot stop the recovery commands.
     *
     * @return list<array{ip: string, note: string|null}>
     */
    private function rows(): array
    {
        $rows = Aegis::settings(IpBlockerModule::KEY)['ips'] ?? null;
        $valid = [];

        foreach (is_array($rows) ? $rows : [] as $row) {
            $ip = is_array($row) ? ($row['ip'] ?? null) : null;
            $note = is_array($row) ? ($row['note'] ?? null) : null;

            if (is_string($ip) && IpAddress::entry($ip) !== null) {
                $valid[] = ['ip' => trim($ip), 'note' => is_string($note) ? mb_substr($note, 0, 255) : null];
            }
        }

        return array_slice($valid, 0, IpBlockerSettings::MAX_ENTRIES);
    }

    /**
     * @param array<string, mixed> $changes
     */
    private function save(array $changes): void
    {
        $settings = $this->blocker->settings();
        $values = $changes + ['enabled' => $settings->enabled, 'view' => $settings->view, 'ips' => $this->rows()];

        $this->administrator->absent(static fn (): array => Aegis::save(IpBlockerModule::KEY, $values));
    }
}
