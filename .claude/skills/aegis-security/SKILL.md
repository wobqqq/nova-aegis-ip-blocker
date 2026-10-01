---
name: aegis-security
description: "Security checklist for the Aegis IP Blocker. Use for any change to what an outside value can do or who may reach something: the BlockListedIps middleware or its place in the middleware groups, how the visitor's address is read (Request::ip(), trusted proxies), the address normalization and matching (IpAddress, BlockList), a setting and its validation rules, the lock-out rule, the 403 page or a view, the settings memo and its invalidation, a console command, or a security review of this package."
license: MIT
---

# IP Blocker security checklist

The module decides, on every request of every visitor, whether the application answers. Treat every rule below as a test to write, not a guideline to remember.

## 1. The visitor's address

- The address is `Request::ip()`, which honours the proxies the application trusts (`TrustProxies`). Never read `X-Forwarded-For`, `Forwarded`, `X-Real-IP`, `CF-Connecting-IP` or `REMOTE_ADDR` yourself: a header is whatever the client sent.
- `ClientIpCheck` warns when forwarding headers come from an untrusted proxy; keep its header list in step with what the README names.
- A missing or unparsable address is not blocked (there is nothing to compare) and never throws.

## 2. Matching

- Compare normalized values only: `IpAddress::normalize()` for addresses (IPv6 compressed and lowercase, IPv4-mapped as IPv4), `IpAddress::entry()` for list entries (prefix bounded to 0–32 / 0–128).
- Exact addresses are a hash lookup; subnets go through `IpUtils::checkIp()`. Never `str_starts_with()` or string equality on raw input.
- A new way of listing addresses (ranges, countries, ASNs) goes through `BlockList`, so the lock-out rule, the middleware and `remove-ip` see the same thing.

## 3. Settings: validate twice

- `IpBlockerModule::rules()` bounds every value: `boolean`, the view name pattern (`IpBlockerSettings::VIEW_PATTERN`, no `/` or `..`), `array|max:500` rows, `array:ip,note` columns, `IpOrSubnet` per entry, `max:255` notes.
- `IpBlockerSettings::fromArray()` reads the stored row again: a bad type falls back, a bad view becomes the default page, entries that do not parse are skipped, at most `MAX_ENTRIES` are read.

## 4. Lock-out protection

- `DoesNotBlockAdministrator` refuses a list that covers the address of the administrator saving it, by address or by subnet, whether the module is on or off.
- Only `IpBlocker`'s console saves skip it (`administratorIp()` is null while they run): a console save has no administrator. Never add another bypass.
- Every protection that can lock someone out ships its way back: `aegis:ip-blocker:remove-ip` and `aegis:ip-blocker:disable`. They must work on any stored row, including broken ones.

## 5. The request path

- The middleware is first in the `web` group: a refused request starts no session, reads no user, queries no database.
- It reads `Aegis::settings()` (cached by the core) through the per-process memo in `IpBlocker`, forgotten on `SettingsSaved` for `ip-blocker`. No query, no cache write, no log line per request.
- Unreadable settings let the request through and are reported: the module never turns a request into a 500.

## 6. Output

- The 403 page prints with `{{ }}` only; a custom view is drawn without data. Pin escaping in a test.
- The refusal carries `Cache-Control: no-store, private`: a shared cache must never serve it to other visitors.
- Validation messages and console output are text; the core's form prints them with `{{ }}`.

## 7. Secrets and data

- Never log or print `.env`, `auth.json`, cookies, `Authorization` headers or a request's headers.
- The module does not log blocked requests: an address is personal data and logging every refusal is a disk-filling vector.

## Review procedure

1. `git diff --stat` and list every changed setting, rule, matcher, middleware, view and command.
2. Walk each through sections 1 to 7 and name the test that pins it.
3. Run `make ready`.
4. Report each finding as: file:line, what an attacker sends, what happens, the fix.
