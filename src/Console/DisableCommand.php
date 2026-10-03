<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Console;

use Illuminate\Console\Command;
use Wobqqq\AegisIpBlocker\BlockListWriter;

final class DisableCommand extends Command
{
    /** @var string */
    protected $signature = 'aegis:ip-blocker:disable';

    /** @var string */
    protected $description = 'Turn the IP Blocker off, for an administrator it locked out.';

    public function handle(BlockListWriter $writer): int
    {
        $writer->disable();

        $this->components->info('IP Blocker is off.');

        return self::SUCCESS;
    }
}
