---
name: nova-development
description: >-
  Use whenever you change how the IP Blocker meets Nova and Laravel's HTTP
  layer: the section the Aegis page draws from IpBlockerModule::fields(), the
  dashboard status, the BlockListedIps middleware and the groups it joins,
  the 403 view, the service provider's wiring, or anything Nova-version
  specific. Use it with aegis-security for anything about access.
metadata:
  author: project
---

# Nova and Laravel integration (this module)

The module has no Nova tool or Vue of its own: the Aegis core draws its section, and the module protects the routes. Check the sources in `vendor/laravel/nova` and `vendor/wobqqq/nova-aegis` for version-specific APIs before using one.

## The settings section

- `IpBlockerModule::fields()` returns core `Field`s: `toggle('enabled')`, `text('view')`, `table('ips', [text('ip'), text('note')])`. The core's page renders them; a field type the core does not have is a core change first.
- Labels and help come from `resources/lang/en/ip-blocker.php` (`aegis-ip-blocker::ip-blocker.*`).
- The table sends every row, empty ones included: `ips.*.ip` is `nullable` and the reader skips empty rows.
- The section is saved by the core's `PUT /nova-vendor/aegis/settings/ip-blocker`, behind `viewAegis`; the module adds no route.

## The middleware

- `BlockListedIps` is prepended to the `web` group, which Nova's `nova` group (`config('nova.middleware')`) includes, so it covers the site, Nova's pages and its API. Prepending keeps a refused request from starting the session.
- It is aliased as `aegis.ip-blocker` for routes outside `web`. Do not push it to the global middleware stack: health checks and the console's internal requests must not depend on the list.
- The groups are read when the HTTP kernel is resolved; the provider changes them in `boot()`, as the core does for `TransportSecurity`.

## The page

- `resources/views/blocked.blade.php` is standalone HTML with inline styles: no assets, no session, no user, no data. A custom view is any Blade view name; the middleware falls back to the default page when it is missing or throws.

## Checklist

- [ ] The section still validates every field in `rules()` and reads it again in `IpBlockerSettings::fromArray()`.
- [ ] Nova's pages and API are still covered (`BlockingTest`).
- [ ] New strings in `resources/lang/en/ip-blocker.php`.
- [ ] `make ready` passes.
