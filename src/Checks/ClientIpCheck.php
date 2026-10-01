<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Checks;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Http\Request;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Check;

/**
 * Behind a proxy that is not trusted every visitor has the proxy's address, so the list blocks nobody or everybody.
 */
final readonly class ClientIpCheck implements Check
{
    public const KEY = 'ip-blocker-client-ip';

    private const FORWARDING_HEADERS = ['X-Forwarded-For', 'Forwarded', 'X-Real-IP', 'CF-Connecting-IP', 'True-Client-IP'];

    public function __construct(private Application $app)
    {
    }

    public function run(): CheckResult
    {
        /** @var Request $request */
        $request = $this->app->make('request');
        $label = (string)__('aegis-ip-blocker::ip-blocker.checks.client_ip.label');

        $forwarded = array_filter(self::FORWARDING_HEADERS, $request->headers->has(...));

        return $forwarded !== [] && !$request->isFromTrustedProxy()
            ? CheckResult::warn(self::KEY, $label, (string)__('aegis-ip-blocker::ip-blocker.checks.client_ip.untrusted', ['headers' => implode(', ', $forwarded)]))
            : CheckResult::pass(self::KEY, $label, (string)__('aegis-ip-blocker::ip-blocker.checks.client_ip.pass'));
    }
}
