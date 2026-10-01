<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\ServiceProvider;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\AegisIpBlocker\Checks\ClientIpCheck;

final class IpBlockerServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(IpBlocker::class);
    }

    public function boot(Dispatcher $events): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'aegis-ip-blocker');

        Aegis::module($this->app->make(IpBlockerModule::class));
        Aegis::check($this->app->make(ClientIpCheck::class));

        $events->listen(SettingsSaved::class, function (SettingsSaved $event): void {
            if ($event->section === IpBlockerModule::KEY) {
                $this->app->make(IpBlocker::class)->forget();
            }
        });
    }
}
