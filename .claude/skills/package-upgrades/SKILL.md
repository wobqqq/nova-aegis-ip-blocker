---
name: package-upgrades
description: "How a change reaches the Laravel applications that already run the IP Blocker. Use before changing a stored setting (its key, type or meaning), a default value, the section key, what is memoized, a public name (middleware alias, command, view, publish tag, lang keys), the Aegis core API the module uses, composer.json constraints, or when preparing a release or a tag."
license: MIT
---

# Upgrading installed applications

Applications update the core and each module independently with Composer. Every change is written for an application that has been running the previous version for months, possibly with an older or newer core.

## Versions and releases

- Semantic versions: a fix is a patch, a new option a minor, a removed option or a changed public name a major.
- Every change adds a line under *Unreleased* in `CHANGELOG.md` saying what changes for the developer. A release moves them under the version and date.
- Release: merge the pull request, then `git tag -a v1.0.1 -m "..." && git push origin v1.0.1`. Packagist reads the tag.
- Before the first release: the core is on Packagist, the `nova-aegis` path repository is removed and the constraint is the released range (`^1.0`), with the lock file updated.

## Constraints

- `laravel/nova` stays `^5.0`, `laravel/framework` `^12.0` and `wobqqq/nova-aegis` `^1.0`: whole majors. Supporting a new major is a minor release with both ranges and tests against both.
- The lock file is for development only (export-ignored); the ranges are what applications resolve.

## Stored settings

The section is one `aegis_settings` row owned by the core: `section = 'ip-blocker'`, JSON `values`.

- The core merges the stored values over `defaults()` and drops keys the defaults no longer name, so adding a setting needs no migration: add it to `defaults()`, `rules()`, `fields()` and `IpBlockerSettings::fromArray()` together.
- Never rename the section key, a setting key (`enabled`, `view`, `ips`) or a row column (`ip`, `note`). Changing a type or a meaning is a **new key**.
- `IpBlockerSettings::fromArray()` keeps reading every shape a released version stored.

## Cached and memoized values

- The settings are cached by the core (arrays under its versioned key); the module adds no cache entry of its own.
- `IpBlocker` memoizes the parsed settings per process and forgets them on `SettingsSaved`. A new memo is forgotten the same way.

## The core's API

- Use only the contract the core's AGENTS.md lists, plus `SettingsRepository::save()` for the console commands.
- A newer core API is used behind `method_exists` / `class_exists` with a fallback, so the module works with every released core of the same major.

## Defaults

- A new protection ships disabled, or with a default that cannot block the current administrator.
- Changing a default changes the behaviour of every application that never saved the section: say so in the changelog, or keep the old default.
