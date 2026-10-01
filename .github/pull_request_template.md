## What changes

<!-- What the change does and why. -->

## Upgrading applications

<!-- A changed setting, default or cache? A new Aegis core version required? Anything a developer has to do after `composer update`? Write "none" if nothing. -->

## Checklist

- [ ] `make ready` passes (fixers, static analysis, tests with coverage)
- [ ] Every new setting is validated in `rules()` and read again in `IpBlockerSettings::fromArray()`
- [ ] The administrator saving the settings still cannot block their own address
- [ ] The request path still reads cached settings only, never the database
- [ ] CHANGELOG.md and README updated if the behaviour or the settings changed
