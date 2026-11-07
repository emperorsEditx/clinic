<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title')</title>
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; background: #f8fafc; color: #0f172a; font: 14px/1.5 ui-sans-serif, system-ui, -apple-system, 'Segoe UI', sans-serif; }
        .box { max-width: 420px; padding: 32px; text-align: center; }
        .code { font-size: 13px; font-weight: 600; color: #1b7a73; letter-spacing: .08em; }
        h1 { font-size: 20px; margin: 8px 0; }
        p { color: #64748b; margin: 0 0 20px; }
        a { display: inline-block; padding: 8px 14px; border-radius: 8px; background: #1b7a73; color: #fff; text-decoration: none; font-weight: 500; font-size: 13px; }
        a.secondary { background: #fff; color: #334155; border: 1px solid #cbd5e1; margin-left: 6px; }
    </style>
</head>
<body>
<div class="box">
    <p class="code">ERROR @yield('code')</p>
    <h1>@yield('title')</h1>
    <p>@yield('message')</p>
    <a href="{{ url('/') }}">Go to dashboard</a><a href="javascript:history.back()" class="secondary">Go back</a>
</div>
</body>
</html>
