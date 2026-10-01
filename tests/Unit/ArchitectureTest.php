<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Wobqqq\Aegis\Settings\AegisSetting;

arch('every file declares strict types')
    ->expect('Wobqqq\AegisIpBlocker')
    ->toUseStrictTypes();

arch('no debugging calls are left behind')
    ->expect(['dd', 'dump', 'var_dump', 'print_r', 'ray', 'die', 'exit'])
    ->not->toBeUsed();

arch('classes are final')
    ->expect('Wobqqq\AegisIpBlocker')
    ->classes()
    ->toBeFinal();

arch('value objects are immutable')
    ->expect([Wobqqq\AegisIpBlocker\BlockList::class, Wobqqq\AegisIpBlocker\IpBlockerSettings::class])
    ->toBeReadonly();

arch('the module uses the core through its public API only')
    ->expect('Wobqqq\AegisIpBlocker')
    ->not->toUse([AegisSetting::class, 'Wobqqq\Aegis\Support', 'Wobqqq\Aegis\Modules', 'Wobqqq\Aegis\Hardening', DB::class]);

arch('the visitor address is never read from a header by hand')
    ->expect('Wobqqq\AegisIpBlocker')
    ->not->toUse(['getallheaders'])
    ->and('Wobqqq\AegisIpBlocker\Http')
    ->not->toUse(Log::class);
