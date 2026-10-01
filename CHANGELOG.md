# Changelog

All notable changes are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [semantic versioning](https://semver.org/).

## [Unreleased]

### Added

- The IP Blocker section on the Aegis settings page: on/off, the page shown to a blocked visitor and the list of blocked addresses and subnets (IPv4, IPv6, CIDR), off by default.
- The `BlockListedIps` middleware, first in the `web` group (the site and Nova) and aliased as `aegis.ip-blocker`, answering 403 with the chosen page, never cached by a shared cache.
- IPv6 addresses match however they are written, and IPv4-mapped addresses as their IPv4 address.
- A list that covers the address of the administrator saving it, by address or by subnet, is refused.
- The module's line on the Aegis dashboard and the *Visitor IP address* check, which warns when requests come through a proxy that is not trusted.
- `aegis:ip-blocker:remove-ip {ip}` and `aegis:ip-blocker:disable` console commands to recover a locked-out administrator.
