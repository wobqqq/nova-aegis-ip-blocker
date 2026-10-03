<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Throwable;
use Wobqqq\Aegis\Aegis;

final class IpBlocker
{
    private ?IpBlockerSettings $settings = null;

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
}
