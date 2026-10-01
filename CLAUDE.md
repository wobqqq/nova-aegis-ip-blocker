# CLAUDE.md

@AGENTS.md

## Skills

- Skills in `.claude/skills/`: `aegis-security` (read it for any change to input, output, access, the middleware or the lock-out rule), `package-upgrades` (anything that reaches an installed application), `package-testing`, `nova-development`, `testing-best-practices`, `laravel-best-practices`.
- A changed PHP file is formatted by the `PostToolUse` hook in `.claude/settings.json`; still run `make ready` before you say a change is done, and report its result.
- The Aegis core lives in the sibling repository `../nova-aegis`. Never change it from here; a need for a new core API is a pull request there first.
- Never push to `main`: work on a branch and open a pull request (see *Git workflow* in AGENTS.md). Write everything in English.
