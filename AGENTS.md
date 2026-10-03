# AGENTS.md

Guidance for coding agents working in this repository.

## What this is

**IP Blocker** (`wobqqq/nova-aegis-ip-blocker`) is a module of Aegis, the security suite for Laravel Nova (Laravel 12 or 13, PHP 8.4+). It refuses every request from the IP addresses and subnets (IPv4 and IPv6, CIDR notation) the administrator lists, on the site and in Nova, answering 403 with the page the administrator chose. It is the Laravel port of the October CMS module `Wobqqq.FortifyIpBlocker`.

It requires the core [`wobqqq/nova-aegis`](https://github.com/wobqqq/nova-aegis): the settings are a section of the Aegis page (`ip-blocker`), stored and cached by the core, and the module adds its line to the core's dashboard and one check.

This is a **security product installed on production applications**. A bug here locks administrators or visitors out, or silently leaves an application unprotected. Security and safe upgrades come before everything else.

## The self-check gate (run before every commit)

Everything runs in Docker; the host needs no PHP. The container mounts the parent directory, because the core comes from the Composer path repository `../nova-aegis` until it is on Packagist.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # validate --strict, normalize --dry-run, composer audit, php -l, cs, Rector, PHPStan max
make test           # Pest
make test.coverage  # Pest with pcov, failing below 90 %
make ready          # all of the above
```

`make ready` must pass. PHPStan runs at `level: max` with strict rules and **no baseline**: fix the type, never add an ignore. Advisories from `composer audit` are fixed by updating the package, never ignored. The module has no Vue of its own: the core draws its settings form from `fields()`.

No Nova license is needed: `laravel/nova` resolves to the test double in `stubs/nova` (see *Tests*). `make test.nova` runs the PHP suite on the real Nova and is the only command that needs a license, read from `auth.json` (gitignored and export-ignored). Never read, print or commit it.

## How the code is laid out

| Path | Holds |
|------|-------|
| `src/IpBlockerServiceProvider.php` | Wiring only: the module and the check with Aegis, the `SettingsSaved` listener, the middleware (alias and `web` group), views, translations, commands. |
| `src/IpBlockerModule.php` | The `Module` contract: key `ip-blocker`, defaults, the validation rules, the form fields, the dashboard status. |
| `src/IpBlockerSettings.php` | The section read again with safe fallbacks (`fromArray()`), into a `BlockList`. |
| `src/BlockList.php` | Normalized addresses (a hash lookup) and subnets (`IpUtils::checkIp()`). |
| `src/Support/IpAddress.php` | One spelling per address or subnet: IPv6 compressed and lowercase, IPv4-mapped as IPv4. |
| `src/IpBlocker.php` | The per-process settings memo and the request decision. |
| `src/Administrator.php` | The administrator's address for the lock-out rule; `absent()` runs a save no administrator makes. |
| `src/BlockListWriter.php` | The recovery commands' changes to the list. |
| `src/Rules/` | `IpOrSubnet` (an entry is an address or a CIDR subnet) and `DoesNotBlockAdministrator` (the lock-out protection). |
| `src/Http/Middleware/BlockListedIps.php` | The 403 answer, its page and its fallbacks. |
| `src/Checks/ClientIpCheck.php` | Warns when forwarding headers arrive from a proxy that is not trusted. |
| `src/Console/` | `aegis:ip-blocker:remove-ip` and `aegis:ip-blocker:disable`, the recovery path: they parse the input and call `BlockListWriter`. |
| `resources/lang/en/ip-blocker.php`, `resources/views/blocked.blade.php` | Every string (`aegis-ip-blocker::ip-blocker.*`) and the default 403 page. |
| `stubs/nova/` | The Nova test double the suite and PHPStan run on (export-ignored), a copy of the core's. |

### How the module uses the core

The core and the module are separate packages that applications update independently. Use only the core's public API, listed in the core's AGENTS.md:

- `Aegis::module(new IpBlockerModule(...))` and `Aegis::check(new ClientIpCheck(...))` in `boot()`;
- `Aegis::settings('ip-blocker')` to read the section, merged over `defaults()` and cached by the core: the request path never queries the database;
- `Aegis::save('ip-blocker', $values)` in the recovery commands, which validates with `rules()`, flushes the cache and dispatches `SettingsSaved`;
- `Wobqqq\Aegis\Support\Values` for the typed reads in `IpBlockerSettings::fromArray()`;
- `Wobqqq\Aegis\Events\SettingsSaved` to forget the memo when the section is saved;
- `Module`, `Check`, `CheckResult`, `Field`, `Status`.

The module requires core 1.1 (`Aegis::save()`, `Values`). An architecture test forbids the core's internals (`AegisSetting`, `SettingsRepository`, `Modules`, `Hardening`) and `DB`.

A new core API is used only behind a check (`method_exists`, `class_exists`) with a fallback, so the module keeps working with every released core of the same major.

## Architecture

The architecture skills in `.claude/skills/` are the rules for how code is shaped; read the one that matches the change before writing it:

- `application-layer`: entry points (middleware, controllers, console commands, the module's Nova pieces) only translate input and output; the work sits in classes named after what they do, with typed input.
- `dependency-injection`: collaborators and configuration arrive through the constructor; facades stay in entry points; interfaces only at I/O boundaries (HTTP, sockets, the clock, processes).
- `error-handling`, `validation`: failures are typed exceptions, never `null` or `false`; input shape is validated at the entry point, business rules where the work is done.
- `events`: reactions run after the commit, from events that say what happened.
- `testing-architecture`: unit tests for pure logic, feature tests for use cases, fakes only at boundaries.
- `domain-layer-cqrs`: when (rarely) a separate domain layer or read side pays off.
- `package-boundaries`: what is public API here and how it may change.

In this module: the middleware asks `IpBlocker` for a decision and nothing else; the recovery commands parse their input and call `BlockListWriter`; a save that has no administrator runs inside `Administrator::absent()` rather than behind a flag.

## Upgrading installed applications safely

Read the `package-upgrades` skill before changing anything that reaches an application that already runs the module. In short:

- The section key `ip-blocker`, its keys (`enabled`, `view`, `ips`) and the row columns (`ip`, `note`) are stored data: never rename them; add a new key instead, with a default.
- `IpBlockerSettings::fromArray()` keeps reading every shape a previous version stored.
- Defaults stay safe: off, an empty list, the module's own page.
- The middleware alias `aegis.ip-blocker`, the command names, the view `aegis-ip-blocker::blocked` and the publish tag `aegis-ip-blocker-views` are public too.
- Every change is a line under *Unreleased* in `CHANGELOG.md`.

## Security rules (always)

Read the `aegis-security` skill for the full checklist. For this module in particular:

- **The IP is `Request::ip()`**: behind a proxy or a CDN it is the proxy's unless the application trusts it (`TrustProxies`). Never read `X-Forwarded-For` or any other header yourself.
- **Addresses are compared normalized**, through `IpAddress` and `BlockList`, never as raw strings: an IPv6 address has many spellings and a subnet covers many addresses.
- **Validate every setting twice**: in `IpBlockerModule::rules()` and again in `IpBlockerSettings::fromArray()` (bounded count, view name pattern, entries that do not parse are skipped).
- **The administrator saving the list must not block themselves** (`DoesNotBlockAdministrator`, by address or by subnet, even while the module is off). Keep that true for every new way of listing addresses. Only the console commands skip it, because a console save has no administrator.
- **Every request is cheap**: the middleware runs first in `web`, reads the memoized settings, does a hash lookup and one `IpUtils` pass over the subnets. No database, no logging, no session.
- **Escape everything**: the 403 page prints with `{{ }}`, a custom view is drawn without data; the core's Vue form prints settings and validation messages as text.
- **The application keeps working when the module breaks**: unreadable settings let the request through (reported, not thrown); a broken custom page falls back to the default one, then to plain text.
- Never log or print secrets, `auth.json` or a request's headers and cookies.

Recovery from the console, for an administrator who locked themselves out:

- `php artisan aegis:ip-blocker:remove-ip {ip}` — removes an address or a subnet, however it is written, and names the subnets that still cover it.
- `php artisan aegis:ip-blocker:disable` — turns the module off and keeps the list.

## Tests

Pest 4 on Orchestra Testbench 10 with the Aegis core (SQLite in memory). Read the `package-testing` skill.

`laravel/nova` is the test double in `stubs/nova`: a path repository (`"versions": {"laravel/nova": "5.99.0"}`, symlinked) declared in `composer.json`, so `make install`, CI and PHPStan need no license; the `require` stays `laravel/nova: ^5.0`, and applications get the real Nova because a dependency's repositories are ignored. It is a verbatim copy of the core's `stubs/nova`: never change it here. When the module needs a Nova API the double lacks, add it in the core first (with the real signature) and copy the directory back unchanged. `make test.nova` checks the suite on the real Nova when you have a license.

## Git workflow

- `main` is protected: **never push to it and never force-push.** After the initial build every change goes through a pull request:
  1. branch off the latest `main`, named after the change (`fix/…`, `feat/…`, `chore/…`, `docs/…`);
  2. commit on the branch and `git push -u origin <branch>`;
  3. open a pull request with the template filled in (what changes, what it means for applications that upgrade);
  4. merge once `make ready` passed, then delete the branch.
- A release is a tag pushed on a merged commit of `main` (`git tag -a v1.0.0 -m "..." && git push origin v1.0.0`); Packagist reads the tag. Before the first release, replace the core's `dev-main` constraint and path repository with the released core.
- Code, comments, commit messages, pull requests, issues and documentation are written in **English**.

## Conventions

- `declare(strict_types=1);` in every PHP file; PSR-12 via PHP CS Fixer, the same rules as the core.
- Code documents itself: names over comments. A comment explains a non-obvious *why*, in one sentence.
- Every class is `final`; value objects are `final readonly`.
- Laravel patterns: container bindings, validation rule objects, `Request::ip()`, `IpUtils`, views and translations under the `aegis-ip-blocker` namespace.
- Commits: imperative subject saying what the change does for the application ("Refuse listed addresses on every web and Nova request"), a body with the why.
