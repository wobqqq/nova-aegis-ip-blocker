<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Console;

use Illuminate\Console\Command;
use Wobqqq\AegisIpBlocker\IpBlocker;
use Wobqqq\AegisIpBlocker\Support\IpAddress;

final class RemoveIpCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:ip-blocker:remove-ip {ip : The address or subnet (CIDR) to remove}';

    /** @var string */
    protected $description = 'Remove an address or a subnet from the IP Blocker list, for an administrator it locked out.';

    public function handle(IpBlocker $blocker): int
    {
        $argument = $this->argument('ip');
        $entry = IpAddress::entry(is_string($argument) ? $argument : null);

        if ($entry === null) {
            $this->components->error('That is not an IP address or a subnet in CIDR notation.');

            return self::FAILURE;
        }

        $removed = $blocker->remove($entry);

        $removed > 0
            ? $this->components->info(sprintf('%s is no longer on the IP Blocker list.', $entry))
            : $this->components->warn(sprintf('%s is not on the IP Blocker list.', $entry));

        $covering = str_contains($entry, '/') ? [] : $blocker->settings()->blockList->subnetsCovering($entry);

        if ($covering !== []) {
            $this->components->warn(sprintf('It is still blocked by %s: remove the subnet or run aegis:ip-blocker:disable.', implode(', ', $covering)));
        }

        return self::SUCCESS;
    }
}
