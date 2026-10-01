<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ __('aegis-ip-blocker::ip-blocker.blocked.title') }}</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: #f8fafc; color: #1e293b; }
        main { max-width: 32rem; padding: 2rem; text-align: center; }
        h1 { margin: 0 0 .5rem; font-size: 1.5rem; }
        p { margin: 0; color: #475569; }
        @media (prefers-color-scheme: dark) { body { background: #0f172a; color: #e2e8f0; } p { color: #94a3b8; } }
    </style>
</head>
<body>
    <main>
        <h1>403 · {{ __('aegis-ip-blocker::ip-blocker.blocked.title') }}</h1>
        <p>{{ __('aegis-ip-blocker::ip-blocker.blocked.message') }}</p>
    </main>
</body>
</html>
