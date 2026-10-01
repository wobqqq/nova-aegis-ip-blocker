<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Laravel\Nova\Nova;
use Laravel\Nova\NovaCoreServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;
use ReflectionClass;
use Wobqqq\Aegis\AegisServiceProvider;
use Wobqqq\Aegis\Nova\AegisTool;
use Wobqqq\AegisIpBlocker\IpBlockerServiceProvider;
use Wobqqq\AegisIpBlocker\Tests\Fixtures\User;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('users', static function (Blueprint $table): void {
            $table->id();
            $table->string('name')->default('Admin');
            $table->string('email')->unique();
            $table->string('password')->default('');
            $table->boolean('is_admin')->default(false);
            $table->rememberToken();
            $table->timestamps();
        });

        Nova::$tools = [];
        Nova::tools([new AegisTool()]);

        Gate::define(AegisTool::GATE, static fn (User $user): bool => $user->is_admin);
        View::addNamespace('fixtures', __DIR__ . '/Fixtures/views');
    }

    /**
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [\Inertia\ServiceProvider::class, NovaCoreServiceProvider::class, AegisServiceProvider::class, IpBlockerServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('auth.providers.users.model', User::class);
        $app['config']->set('aegis.users.model', User::class);
        $app['config']->set('aegis.audit.schedule', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(dirname((string)(new ReflectionClass(AegisServiceProvider::class))->getFileName(), 2) . '/database/migrations');
    }

    protected function defineRoutes($router): void
    {
        Route::middleware('web')->get('/', static fn (): string => 'Welcome');
        Route::get('/outside', static fn (): string => 'Outside the web group');
        Route::middleware('aegis.ip-blocker')->get('/api/ping', static fn (): string => 'Pong');
    }
}
