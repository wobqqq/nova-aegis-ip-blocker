<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Illuminate\Contracts\Foundation\Application;
use Throwable;
use Wobqqq\Aegis\Aegis;
use Wobqqq\AegisIpBlocker\Support\IpAddress;

final class IpBlocker
{
    private ?IpBlockerSettings $settings = null;

    private bool $recovering = false;

    public function __construct(private readonly Application $app)
    {
    }

    public function settings(): IpBlockerSettings
    {
        return $this->settings ??= IpBlockerSettings::fromArray(Aegis::settings(IpBlockerModule::KEY));
    }

    public function forget(): void
    {
        $this->settings = null;
    }

    /**
     * Whether a request from this address is refused; an unreadable setting lets it through.
     */
    public function blocks(?string $ip): bool
    {
        try {
            $settings = $this->settings();
        } catch (Throwable $throwable) {
            report($throwable);

            return false;
        }

        return $settings->enabled && $settings->blockList->contains($ip);
    }

    /**
     * The address of the administrator saving the settings, which the list must not cover; none from the console.
     */
    public function administratorIp(): ?string
    {
        if ($this->recovering) {
            return null;
        }

        $ip = $this->app->make('request')->ip();

        return is_string($ip) ? IpAddress::normalize($ip) : null;
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
     * A console save has no administrator to keep in.
     *
     * @param array<string, mixed> $changes
     */
    private function save(array $changes): void
    {
        $settings = $this->settings();
        $values = $changes + ['enabled' => $settings->enabled, 'view' => $settings->view, 'ips' => $this->rows()];

        $this->recovering = true;

        try {
            Aegis::save(IpBlockerModule::KEY, $values);
        } finally {
            $this->recovering = false;
        }
    }
}
