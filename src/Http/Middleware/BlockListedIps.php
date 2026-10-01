<?php

declare(strict_types=1);

namespace Wobqqq\AegisIpBlocker\Http\Middleware;

use Closure;
use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;
use Throwable;
use Wobqqq\AegisIpBlocker\IpBlocker;
use Wobqqq\AegisIpBlocker\IpBlockerSettings;

final readonly class BlockListedIps
{
    public const string ALIAS = 'aegis.ip-blocker';

    public function __construct(private IpBlocker $blocker, private ViewFactory $views)
    {
    }

    /**
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!$this->blocker->blocks($request->ip())) {
            return $next($request);
        }

        $response = new HttpResponse($this->page(), Response::HTTP_FORBIDDEN);
        // A shared cache must never serve one visitor's refusal to another.
        $response->headers->set('Cache-Control', 'no-store, private');

        return $response;
    }

    private function page(): string
    {
        $view = $this->blocker->settings()->view;

        foreach (array_unique([$view, IpBlockerSettings::DEFAULT_VIEW]) as $name) {
            try {
                if ($this->views->exists($name)) {
                    return $this->views->make($name)->render();
                }
            } catch (Throwable $e) {
                report($e);
            }
        }

        return 'Forbidden';
    }
}
