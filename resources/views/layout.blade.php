<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Laravel Log Monitor</title>
    <style>
        :root {
            --bg: #f5f6f8;
            --panel: #ffffff;
            --border: #e2e4e9;
            --text: #1f2430;
            --text-muted: #6b7280;
            --accent: #4f46e5;
            --emergency: #7f1d1d; --alert: #991b1b; --critical: #b91c1c;
            --error: #dc2626; --warning: #d97706; --notice: #2563eb;
            --info: #059669; --debug: #6b7280;
        }
        @media (prefers-color-scheme: dark) {
            :root {
                --bg: #0f1115;
                --panel: #171a21;
                --border: #262a34;
                --text: #e5e7eb;
                --text-muted: #9aa1ac;
            }
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
        }
        header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 20px;
            border-bottom: 1px solid var(--border);
            background: var(--panel);
        }
        header h1 { font-size: 16px; margin: 0; }
        .app {
            display: grid;
            grid-template-columns: 260px 1fr;
            min-height: calc(100vh - 49px);
        }
        @media (max-width: 768px) {
            .app { grid-template-columns: 1fr; }
        }
        aside {
            border-right: 1px solid var(--border);
            background: var(--panel);
            padding: 12px;
            overflow-y: auto;
        }
        main { padding: 16px; overflow-x: auto; }
        a { color: inherit; }
        button, select, input {
            font: inherit;
            color: inherit;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 6px;
            padding: 6px 10px;
        }
        button { cursor: pointer; }
        button:hover { border-color: var(--accent); }
        button.danger { color: var(--error); border-color: var(--error); }
        button.danger:hover { background: var(--error); color: #fff; }
        .file-item {
            display: block;
            padding: 8px 10px;
            border-radius: 6px;
            margin-bottom: 4px;
            text-decoration: none;
            font-size: 13px;
        }
        .file-item:hover { background: var(--bg); }
        .file-item.active { background: var(--accent); color: #fff; }
        .file-item small { display: block; color: var(--text-muted); }
        .file-item.active small { color: #e0e7ff; }
        .toolbar { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 12px; }
        .toolbar input[type="search"] { flex: 1; min-width: 200px; }
        .entry {
            background: var(--panel);
            border: 1px solid var(--border);
            border-left: 4px solid var(--debug);
            border-radius: 6px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .entry.level-emergency, .entry.level-alert, .entry.level-critical { border-left-color: var(--critical); }
        .entry.level-error { border-left-color: var(--error); }
        .entry.level-warning { border-left-color: var(--warning); }
        .entry.level-notice { border-left-color: var(--notice); }
        .entry.level-info { border-left-color: var(--info); }
        .entry.level-debug { border-left-color: var(--debug); }
        .entry-head { display: flex; justify-content: space-between; align-items: baseline; gap: 8px; }
        .badge {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            padding: 2px 6px;
            border-radius: 4px;
            color: #fff;
        }
        .badge.level-emergency, .badge.level-alert, .badge.level-critical, .badge.level-error { background: var(--error); }
        .badge.level-warning { background: var(--warning); }
        .badge.level-notice { background: var(--notice); }
        .badge.level-info { background: var(--info); }
        .badge.level-debug { background: var(--debug); }
        .entry-time { color: var(--text-muted); font-size: 12px; }
        .entry-message { white-space: pre-wrap; margin: 6px 0; font-family: ui-monospace, SFMono-Regular, monospace; font-size: 13px; }
        .entry-actions { display: flex; gap: 8px; margin-top: 6px; }
        .entry-actions button { font-size: 12px; padding: 4px 8px; }
        .stack-trace {
            display: none;
            white-space: pre-wrap;
            font-family: ui-monospace, SFMono-Regular, monospace;
            font-size: 12px;
            background: var(--bg);
            border-radius: 6px;
            padding: 8px;
            margin-top: 6px;
        }
        .stack-trace.open { display: block; }
        .stats {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            padding: 10px 16px;
            border: 1px solid var(--border);
            border-radius: 6px;
            background: var(--panel);
            font-size: 13px;
            margin-bottom: 12px;
        }
        .stat-filter {
            display: flex;
            align-items: center;
            gap: 6px;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 6px;
            padding: 4px 8px;
        }
        .stat-filter:hover { border-color: var(--border); background: var(--bg); }
        .stat-filter.active { border-color: var(--accent); background: var(--bg); }
        .pagination { display: flex; gap: 8px; align-items: center; margin-top: 12px; }
        .empty { color: var(--text-muted); padding: 24px; text-align: center; }
    </style>
</head>
<body>
    <header>
        <h1>Laravel Log Monitor</h1>
        <span style="display:flex;align-items:center;gap:12px;">
            @isset($packageVersion)
                <span class="entry-time" title="luiscamp/laravel-log-monitor">v{{ ltrim($packageVersion, 'v') }}</span>
            @endisset
            <span id="environment-badge" class="entry-time">{{ app()->environment() }}</span>
        </span>
    </header>
    @yield('content')
</body>
</html>
