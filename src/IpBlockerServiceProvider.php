<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Override;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Events\SettingsSaved;
use Wobqqq\AegisIpBlocker\Checks\ClientIpCheck;
use Wobqqq\AegisIpBlocker\Console\DisableCommand;
use Wobqqq\AegisIpBlocker\Console\RemoveIpCommand;
use Wobqqq\AegisIpBlocker\Http\Middleware\BlockListedIps;

final class IpBlockerServiceProvider extends ServiceProvider
{
    #[Override]
    public function register(): void
    {
        $this->app->singleton(IpBlocker::class);
        $this->app->singleton(Administrator::class);
    }

    public function boot(Router $router, Dispatcher $events): void
    {
        $this->loadTranslationsFrom(__DIR__ . '/../resources/lang', 'aegis-ip-blocker');
        $this->loadViewsFrom(__DIR__ . '/../resources/views', 'aegis-ip-blocker');

        Aegis::module($this->app->make(IpBlockerModule::class));
        Aegis::check($this->app->make(ClientIpCheck::class));

        $events->listen(SettingsSaved::class, function (SettingsSaved $event): void {
            if ($event->section === IpBlockerModule::KEY) {
                $this->app->make(IpBlocker::class)->forget();
            }
        });

        $router->aliasMiddleware(BlockListedIps::ALIAS, BlockListedIps::class);
        // First in the group, so a refused request starts no session and reaches no database.
        $router->prependMiddlewareToGroup('web', BlockListedIps::class);

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__ . '/../resources/views' => resource_path('views/vendor/aegis-ip-blocker')], 'aegis-ip-blocker-views');
            $this->commands([RemoveIpCommand::class, DisableCommand::class]);
        }
    }
}
