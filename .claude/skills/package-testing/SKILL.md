---
name: package-testing
description: "How the IP Blocker is tested. Use when writing or changing anything in tests/ (Pest files, tests/TestCase.php, tests/Pest.php, tests/Fixtures), phpunit.xml.dist, when a test needs a visitor address, a proxy, Nova, the console or a view, when a test fails only in the suite, or when PHPStan complains about a test."
license: MIT
---

# Testing the package

## The harness

- Pest 4 on Orchestra Testbench 10 (Laravel 12) with the real `laravel/nova` and the Aegis core (`../nova-aegis` through the path repository). SQLite in memory, array cache and session (`phpunit.xml.dist`).
- `tests/TestCase.php` loads Inertia, Nova, `AegisServiceProvider` and `IpBlockerServiceProvider`, runs the core's migrations, creates the `users` table for `tests/Fixtures/User.php`, registers `AegisTool`, defines `viewAegis` as `is_admin`, adds the `fixtures::` views and three routes: `/` (web group), `/outside` (no group) and `/api/ping` (the `aegis.ip-blocker` alias).
- `tests/Pest.php` gives global helpers: `from($ip)` (the next requests come from that address), `block([...], [...])` (saves the section through the core, from 127.0.0.1), `admin()` and `editor()`.
- `Feature/` exercises the module through HTTP, the Aegis API, the console and the container; `Unit/` holds the address parsing, the block list, the settings reader and the `arch()` rules.

## Rules

- Test what a visitor, an administrator or the console sees: the status code and page, the JSON and validation errors of the Aegis API, the exit code and output of a command.
- Every security rule is a test: an IPv6 spelling, an IPv4-mapped address, a subnet, the administrator's own address or subnet, an untrusted and a trusted proxy, an invalid or hostile entry, a broken page, unreadable settings.
- Trusted proxies go through `TrustProxies::at([...])` (the middleware resets them on every request), reset with `TrustProxies::flushState()` in `afterEach`.
- A setting is saved through the core (`block()` or the API), never written to the table by hand, unless the test is about a stored row the rules would refuse.
- After a request, `request()` is that request: `block()` resets it so the lock-out rule sees 127.0.0.1.
- Coverage stays at 90 % or more (`make test.coverage`).

## PHPStan max on tests, without ignores

- Use the global `Pest\Laravel\*` functions and the helpers, never `$this->` in a closure.
- Console: `expect(Artisan::call('aegis:ip-blocker:disable'))->toBe(0)` and `Artisan::output()`.
- Annotate mocks (`/** @var Mockery\MockInterface&ViewFactory $views */`) and narrow `mixed` with `is_array()` / `is_string()` before indexing.

## Workflow

1. Write the change and its tests; iterate with `docker compose run --rm php vendor/bin/pest --filter='...'`.
2. `make composer.test.coverage` for gaps; cover the uncovered decisions, not getters.
3. `make ready` before the commit.
