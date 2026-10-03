<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker;

use Closure;
use Illuminate\Contracts\Foundation\Application;
use Wobqqq\AegisIpBlocker\Support\IpAddress;

/**
 * The address of the administrator saving the settings, which the list must not cover.
 */
final class Administrator
{
    private bool $absent = false;

    public function __construct(private readonly Application $app)
    {
    }

    public function ip(): ?string
    {
        if ($this->absent) {
            return null;
        }

        $ip = $this->app->make('request')->ip();

        return is_string($ip) ? IpAddress::normalize($ip) : null;
    }

    /**
     * Runs the callback as a save no administrator makes, such as a recovery command.
     *
     * @template T
     *
     * @param Closure(): T $callback
     *
     * @return T
     */
    public function absent(Closure $callback): mixed
    {
        $this->absent = true;

        try {
            return $callback();
        } finally {
            $this->absent = false;
        }
    }
}
