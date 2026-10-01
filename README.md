# Aegis IP Blocker

[![CI](https://github.com/wobqqq/nova-aegis-ip-blocker/actions/workflows/ci.yml/badge.svg)](https://github.com/wobqqq/nova-aegis-ip-blocker/actions/workflows/ci.yml)
[![Packagist](https://img.shields.io/packagist/v/wobqqq/nova-aegis-ip-blocker)](https://packagist.org/packages/wobqqq/nova-aegis-ip-blocker)
[![PHP](https://img.shields.io/badge/PHP-8.4%2B-777bb4)](https://github.com/wobqqq/nova-aegis-ip-blocker/blob/main/composer.json)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%20max-brightgreen)](https://github.com/wobqqq/nova-aegis-ip-blocker/blob/main/phpstan.neon.dist)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://github.com/wobqqq/nova-aegis-ip-blocker/blob/main/LICENSE.md)

**IP Blocker** is a module of [Aegis](https://github.com/wobqqq/nova-aegis), the security suite for Laravel Nova. It refuses every request from the IP addresses and subnets you list, on the site and in Nova, and answers them with a 403 page. The list is managed on the Aegis page.

## 🚀 Features

- Blocks **IPv4 and IPv6 addresses** and **subnets in CIDR notation** (`198.51.100.0/24`, `2001:db8::/32`).
- Applies to the **whole application**: the `web` middleware group, which Nova's pages and API use too. Other routes can use the `aegis.ip-blocker` middleware.
- **IPv6 is normalized**: `2001:db8::1` and `2001:0db8:0:0:0:0:0:1` are the same address, and an IPv4-mapped address (`::ffff:203.0.113.7`) is its IPv4 address.
- **Lock-out protection**: a list that covers the address of the administrator saving it, by address or by subnet, is refused.
- A **403 page** of your choice (any Blade view), falling back to the module's own page. The answer is marked `no-store`, so a CDN never serves one visitor's refusal to another.
- **Cheap on every request**: it runs before the session starts and reads the cached Aegis settings, never the database.
- A line on the **Aegis dashboard** and the **Visitor IP address** check, which warns when requests come through a proxy Laravel does not trust.
- **Console recovery** for an administrator who locked themselves out.

## 📦 Requirements

- PHP 8.4 or higher
- Laravel 12 or 13
- Laravel Nova 5
- [Aegis](https://github.com/wobqqq/nova-aegis) 1.1 or newer (installed with the module)

## 📥 Installation

### 1. Install the package

```bash
composer require wobqqq/nova-aegis-ip-blocker
```

The service provider is discovered automatically.

### 2. Run the migrations

```bash
php artisan migrate
```

This creates the Aegis settings table if the core is new to the application; the module adds no table of its own.

### 3. Set up Aegis (once per application)

If Aegis is new to the application, register its tool and define the `viewAegis` gate as the [Aegis README](https://github.com/wobqqq/nova-aegis#-installation) describes. Skip this step if you already use another Aegis module.

### 4. Turn it on in Nova

Open **Aegis → Settings → IP Blocker** in Nova, list the addresses or subnets to block, switch **Enable IP Blocker** on and save. A list that would block your own address is refused.

## ⚙️ Configuration

Everything is set on the Aegis page:

| Setting | Default | |
|---|---|---|
| Enable IP Blocker | off | Nothing is blocked until it is switched on. |
| Page shown to a blocked visitor | `aegis-ip-blocker::blocked` | A Blade view name (`errors.403`, `vendor::view`). The default page is used when the view does not exist or fails. |
| Blocked addresses and subnets | none | Up to 500 rows of an address or a CIDR subnet and an optional note. |

To change the default page, publish it:

```bash
php artisan vendor:publish --tag=aegis-ip-blocker-views
```

The page is drawn before the session starts: a custom view must not need the session, the authenticated user or `$errors`.

To guard routes outside the `web` group, such as an API, add the middleware to them:

```php
Route::middleware('aegis.ip-blocker')->group(base_path('routes/api.php'));
```

## 🛟 Recovery commands

If you blocked yourself (your address changed, or a proxy's address was listed):

```bash
php artisan aegis:ip-blocker:remove-ip 203.0.113.7       # remove an address, however it is written
php artisan aegis:ip-blocker:remove-ip 198.51.100.0/24   # or a subnet
php artisan aegis:ip-blocker:disable                     # turn the module off, keeping the list
```

`remove-ip` names the subnets that still cover the address.

## ⚠️ Good to know

- **The address is `Request::ip()`.** Behind a load balancer, a reverse proxy or a CDN it is the proxy's address unless Laravel trusts the proxy: configure it in `bootstrap/app.php` with `$middleware->trustProxies(at: [...])`. Until then every visitor has the proxy's address, and the lock-out protection refuses a list that blocks it. The **Visitor IP address** check on the Aegis page warns about it.
- **Forwarding headers can be forged.** `X-Forwarded-For`, `Forwarded`, `X-Real-IP` and `CF-Connecting-IP` are only believed from a trusted proxy. Never trust every proxy (`at: '*'`) on a server that is reachable directly: anyone could then choose the address the module sees.
- **A block list is not a firewall.** An attacker can change address. It stops a known source; traffic you never want to reach PHP is best refused by the web server, the firewall or a WAF.
- With several servers or workers, the settings are read through the Aegis cache: use a shared cache store (`AEGIS_CACHE_STORE`) so a saved list applies everywhere at once.

## ⬆️ Upgrading

See [CHANGELOG.md](https://github.com/wobqqq/nova-aegis-ip-blocker/blob/main/CHANGELOG.md).

## 🔒 Security

Please report a vulnerability privately, as described in [SECURITY.md](https://github.com/wobqqq/nova-aegis-ip-blocker/blob/main/SECURITY.md).

## 🛠️ Development

The toolchain runs in Docker, the host needs nothing but `docker` and `make`. Until the core is on Packagist it is read from a sibling checkout, `../nova-aegis` (a Composer path repository), so the container mounts the parent directory. No Nova license is needed: development and CI run on a test double of Nova in `stubs/nova` (installed as `laravel/nova` from a path repository, never shipped). Applications still install the real Nova.

```bash
make install        # composer install
make code.fix       # composer normalize, Rector, PHP CS Fixer
make code.check     # composer validate/audit, php -l, PHP CS Fixer, Rector, PHPStan (level max)
make test.coverage  # Pest with coverage (90 % minimum)
make ready          # everything above
make test.nova      # optional: the PHP suite on the real Nova
```

`make test.nova` copies the repository to a temporary directory, installs the real `laravel/nova` from nova.laravel.com there and runs Pest; it needs your own Nova license in `auth.json` (gitignored), and `NOVA_VERSION=5.9.3 make test.nova` picks a release your license may download. The working copy, its `vendor/` and `composer.lock` are left untouched.

GitHub Actions runs the same checks on every pull request, with the core checked out next to the module. It needs no Nova license, only the repository secret `AEGIS_CORE_TOKEN` (a token that can read `wobqqq/nova-aegis`) while the core repository is private.
