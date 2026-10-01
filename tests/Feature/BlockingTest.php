<?php

declare(strict_types=1);

use Illuminate\Contracts\View\Factory as ViewFactory;
use Illuminate\Http\Middleware\TrustProxies;
use Wobqqq\Aegis\Aegis;
use Wobqqq\Aegis\Checks\CheckResult;
use Wobqqq\Aegis\Contracts\Module;
use Wobqqq\AegisIpBlocker\IpBlockerModule;

afterEach(function (): void {
    TrustProxies::flushState();
});

it('lets every request through while it is off', function (): void {
    block(['203.0.113.7'], ['enabled' => false]);

    from('203.0.113.7')->get('/')->assertOk()->assertSee('Welcome');
});

it('refuses a listed address with the 403 page, never cached', function (): void {
    block(['203.0.113.7']);

    $response = from('203.0.113.7')->get('/');

    $response->assertForbidden()->assertSee('Access denied')->assertDontSee('Welcome');
    expect($response->headers->get('Cache-Control'))->toContain('no-store')->toContain('private');

    from('203.0.113.8')->get('/')->assertOk();
});

it('refuses every address of a listed subnet', function (string $listed, string $visitor): void {
    block([$listed]);

    from($visitor)->get('/')->assertForbidden();
})->with([
    'IPv4 subnet' => ['198.51.100.0/24', '198.51.100.77'],
    'IPv6 subnet' => ['2001:db8::/32', '2001:db8:ffff::1'],
    'IPv6 written in full' => ['2001:0db8:0000:0000:0000:0000:0000:0001', '2001:db8::1'],
    'IPv6 visitor written in full' => ['2001:db8::1', '2001:0db8:0:0:0:0:0:1'],
    'IPv4-mapped visitor' => ['203.0.113.7', '::ffff:203.0.113.7'],
    'IPv4-mapped subnet' => ['::ffff:198.51.100.0/120', '198.51.100.1'],
]);

it('refuses Nova and its API too', function (): void {
    block(['203.0.113.7']);

    from('203.0.113.7')->getJson('/nova-vendor/aegis/overview')->assertForbidden()->assertSee('Access denied');
    from('203.0.113.8')->getJson('/nova-vendor/aegis/overview')->assertUnauthorized();
});

it('guards the routes outside the web group that use its middleware', function (): void {
    block(['203.0.113.7']);

    from('203.0.113.7')->get('/outside')->assertOk();
    from('203.0.113.7')->get('/api/ping')->assertForbidden();
});

it('shows the page the administrator chose, escaped, or the default one', function (): void {
    block(['203.0.113.7'], ['view' => 'fixtures::custom']);
    from('203.0.113.7')->get('/')->assertForbidden()->assertSee('Custom refusal for &lt;b&gt;', false);

    block(['203.0.113.7'], ['view' => 'fixtures::missing']);
    from('203.0.113.7')->get('/')->assertForbidden()->assertSee('Access denied');

    block(['203.0.113.7'], ['view' => 'fixtures::broken']);
    from('203.0.113.7')->get('/')->assertForbidden()->assertSee('Access denied');
});

it('still refuses when no page can be drawn', function (): void {
    block(['203.0.113.7']);

    /** @var Mockery\MockInterface&ViewFactory $views */
    $views = Mockery::mock(ViewFactory::class);
    $views->allows('exists')->andReturnFalse();
    app()->instance(ViewFactory::class, $views);

    from('203.0.113.7')->get('/')->assertForbidden()->assertContent('Forbidden');
});

it('applies a saved list to the next request at once', function (): void {
    from('203.0.113.7')->get('/')->assertOk();

    block(['203.0.113.7']);

    from('203.0.113.7')->get('/')->assertForbidden();
});

it('checks the visitor behind a trusted proxy, not the proxy', function (): void {
    block(['203.0.113.7']);

    from('10.0.0.2')->withHeader('X-Forwarded-For', '203.0.113.7')->get('/')->assertOk();

    TrustProxies::at(['10.0.0.0/8']);

    from('10.0.0.2')->withHeader('X-Forwarded-For', '203.0.113.7')->get('/')->assertForbidden();
    from('10.0.0.2')->withHeader('X-Forwarded-For', '203.0.113.8')->get('/')->assertOk();
});

it('lets the request through when its settings cannot be read', function (): void {
    block(['203.0.113.7']);

    Aegis::module(new class () implements Module {
        public function key(): string
        {
            return IpBlockerModule::KEY;
        }

        public function label(): string
        {
            return 'Broken';
        }

        public function description(): string
        {
            return '';
        }

        public function defaults(): array
        {
            throw new RuntimeException('Broken module');
        }

        public function rules(): array
        {
            return [];
        }

        public function fields(): array
        {
            return [];
        }

        public function status(array $values): ?CheckResult
        {
            return null;
        }
    });
    resolve(Wobqqq\AegisIpBlocker\IpBlocker::class)->forget();

    from('203.0.113.7')->get('/')->assertOk();
});
