# Changelog

All notable changes are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [Unreleased]

### Added

- Laravel 13 support; CI runs the suite on Laravel 12 and 13.

### Changed

- PHP 8.4 or later is required.
- Native types throughout: typed constants and properties, `#[\Override]` on every overriding method, readonly value objects; no behaviour change.

## [1.0.1] - 2026-10-01

### Changed

- Development and CI run on a test double of Nova (`stubs/nova`, not shipped) and need no Nova license; `make test.nova` runs the PHP suite on the real Nova. Nothing changes for applications.
- The README splits the installation into numbered steps.
- The Dependabot config no longer reads the Nova registry.

## [1.0.0] - 2026-10-01

### Added

- The IP Blocker section on the Aegis settings page: on/off, the page shown to a blocked visitor and the list of blocked addresses and subnets (IPv4, IPv6, CIDR), off by default.
- The `BlockListedIps` middleware, first in the `web` group (the site and Nova) and aliased as `aegis.ip-blocker`, answering 403 with the chosen page, never cached by a shared cache.
- IPv6 addresses match however they are written, and IPv4-mapped addresses as their IPv4 address.
- A list that covers the address of the administrator saving it, by address or by subnet, is refused.
- The module's line on the Aegis dashboard and the *Visitor IP address* check, which warns when requests come through a proxy that is not trusted.
- `aegis:ip-blocker:remove-ip {ip}` and `aegis:ip-blocker:disable` console commands to recover a locked-out administrator.
- Requires Aegis 1.1 or newer.

[Unreleased]: https://github.com/wobqqq/nova-aegis-ip-blocker/compare/v1.0.1...HEAD
[1.0.1]: https://github.com/wobqqq/nova-aegis-ip-blocker/compare/v1.0.0...v1.0.1
[1.0.0]: https://github.com/wobqqq/nova-aegis-ip-blocker/releases/tag/v1.0.0
