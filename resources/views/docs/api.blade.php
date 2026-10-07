<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $documentation['title'] }}</title>
    <style>
        :root {
            color-scheme: light;
            --bg: #f6f8fb;
            --panel: #ffffff;
            --panel-soft: #f8fafc;
            --ink: #202638;
            --muted: #697386;
            --line: #e0e7ef;
            --line-strong: #cbd5e1;
            --accent: #707a95;
            --accent-dark: #525d76;
            --accent-soft: #eef1f7;
            --mint: #edf6f0;
            --mint-ink: #597565;
            --sky: #edf5fa;
            --sky-ink: #5c7485;
            --rose: #f8edf1;
            --rose-ink: #82616d;
            --amber: #f8f1dd;
            --amber-ink: #806f48;
            --code-bg: #263044;
            --code-ink: #eef2f7;
            --shadow: 0 14px 32px rgba(54, 65, 82, .09);
            --shadow-soft: 0 8px 20px rgba(54, 65, 82, .06);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: var(--bg);
            color: var(--ink);
            font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            line-height: 1.55;
        }

        header {
            border-bottom: 1px solid var(--line);
            background: #eef3f7;
        }

        .header-inner,
        main {
            width: min(1120px, calc(100% - 32px));
            margin: 0 auto;
        }

        .header-inner {
            padding: 34px 0 30px;
        }

        h1,
        h2,
        h3 {
            margin: 0;
            line-height: 1.2;
        }

        h1 {
            font-size: clamp(2.1rem, 5vw, 3.35rem);
            letter-spacing: 0;
            max-width: 760px;
        }

        h2 {
            align-items: center;
            display: flex;
            font-size: 1.45rem;
            gap: 10px;
            margin-bottom: 12px;
        }

        h3 {
            font-size: 1.05rem;
            letter-spacing: 0;
        }

        p {
            margin: 8px 0 0;
            color: var(--muted);
        }

        button {
            align-items: center;
            border: 1px solid var(--accent-dark);
            border-radius: 8px;
            background: var(--accent);
            box-shadow: var(--shadow-soft);
            color: white;
            cursor: pointer;
            display: inline-flex;
            gap: 8px;
            font: inherit;
            font-weight: 800;
            min-height: 42px;
            padding: 9px 14px;
        }

        button:hover {
            background: var(--accent-dark);
        }

        main {
            padding: 30px 0 64px;
            display: grid;
            grid-template-columns: 268px minmax(0, 1fr);
            gap: 24px;
            align-items: start;
        }

        nav,
        article {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
        }

        nav {
            box-shadow: var(--shadow-soft);
            position: sticky;
            top: 20px;
            padding: 16px;
        }

        nav strong {
            align-items: center;
            color: var(--muted);
            display: flex;
            font-size: .78rem;
            gap: 8px;
            letter-spacing: .08em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        nav a {
            align-items: center;
            border-radius: 7px;
            color: var(--ink);
            display: flex;
            gap: 10px;
            text-decoration: none;
            padding: 10px;
            font-weight: 650;
        }

        nav a:hover {
            background: var(--accent-soft);
            color: var(--accent-dark);
        }

        .content {
            display: grid;
            gap: 26px;
        }

        section {
            scroll-margin-top: 24px;
        }

        article {
            box-shadow: var(--shadow-soft);
            margin-top: 14px;
            overflow: hidden;
            padding: 18px;
        }

        .meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 18px;
        }

        .header-top {
            display: flex;
            align-items: start;
            justify-content: space-between;
            gap: 16px;
        }

        .copy-actions {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .copy-status {
            color: var(--accent);
            font-size: .9rem;
            font-weight: 800;
            min-height: 22px;
        }

        .markdown-source {
            position: absolute;
            left: -9999px;
            width: 1px;
            height: 1px;
            opacity: 0;
        }

        .pill {
            border: 1px solid var(--line);
            display: inline-flex;
            align-items: center;
            gap: 7px;
            min-height: 30px;
            border-radius: 8px;
            padding: 5px 10px;
            background: var(--accent-soft);
            color: var(--accent-dark);
            font-size: .88rem;
            font-weight: 700;
        }

        .pill.neutral {
            background: var(--sky);
            color: var(--sky-ink);
        }

        .pill.warning {
            background: var(--amber);
            color: var(--amber-ink);
        }

        .endpoint-title {
            display: flex;
            gap: 11px;
            flex-wrap: wrap;
            align-items: center;
        }

        .method {
            min-width: 64px;
            text-align: center;
            border-radius: 6px;
            padding: 5px 8px;
            background: var(--ink);
            box-shadow: none;
            color: white;
            font-size: .82rem;
            font-weight: 800;
        }

        .method-get {
            background: #6f8f7c;
        }

        .method-post {
            background: #817a9f;
        }

        .path {
            background: var(--panel-soft);
            border: 1px solid var(--line);
            border-radius: 7px;
            color: #354158;
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
            font-size: .92rem;
            overflow-wrap: anywhere;
            padding: 5px 8px;
        }

        .block {
            margin-top: 16px;
        }

        .block-title {
            align-items: center;
            display: flex;
            gap: 8px;
            margin: 0 0 8px;
            color: var(--ink);
            font-weight: 800;
        }

        pre {
            margin: 0;
            padding: 15px;
            overflow: auto;
            background: var(--code-bg);
            color: var(--code-ink);
            border-radius: 8px;
            font-size: .9rem;
            line-height: 1.55;
        }

        code {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace;
        }

        ol {
            counter-reset: steps;
            display: grid;
            gap: 10px;
            list-style: none;
            margin: 14px 0 0;
            padding: 0;
        }

        ol li {
            align-items: start;
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            color: #354158;
            display: grid;
            gap: 10px;
            grid-template-columns: 30px minmax(0, 1fr);
            padding: 11px 12px;
        }

        ol li::before {
            align-items: center;
            background: var(--mint);
            border-radius: 7px;
            color: var(--mint-ink);
            content: counter(steps);
            counter-increment: steps;
            display: inline-flex;
            font-size: .82rem;
            font-weight: 900;
            height: 30px;
            justify-content: center;
            width: 30px;
        }

        .language {
            background: var(--rose);
            border-radius: 7px;
            color: var(--rose-ink);
            font-size: .85rem;
            font-weight: 700;
            padding: 4px 8px;
            text-transform: uppercase;
        }

        .eyebrow {
            align-items: center;
            color: var(--accent-dark);
            display: inline-flex;
            font-size: .82rem;
            font-weight: 850;
            gap: 8px;
            letter-spacing: .08em;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .icon {
            display: inline-block;
            flex: 0 0 auto;
            height: 18px;
            width: 18px;
        }

        .icon-box {
            align-items: center;
            background: var(--accent-soft);
            border: 1px solid var(--line);
            border-radius: 8px;
            color: var(--accent-dark);
            display: inline-flex;
            flex: 0 0 auto;
            height: 36px;
            justify-content: center;
            width: 36px;
        }

        .icon-box.mint {
            background: var(--mint);
            color: var(--mint-ink);
        }

        .icon-box.sky {
            background: var(--sky);
            color: var(--sky-ink);
        }

        .icon-box.rose {
            background: var(--rose);
            color: var(--rose-ink);
        }

        .icon-box.amber {
            background: var(--amber);
            color: var(--amber-ink);
        }

        .nav-dot {
            background: var(--mint);
            border: 1px solid var(--line);
            border-radius: 6px;
            height: 11px;
            width: 11px;
        }

        .section-heading {
            align-items: center;
            display: flex;
            gap: 12px;
            margin-bottom: 6px;
        }

        .section-heading h2 {
            margin-bottom: 0;
        }

        .section-description {
            max-width: 740px;
        }

        .intro-panel {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow);
            padding: 18px;
        }

        .endpoint-card {
            border-top: 5px solid var(--accent);
        }

        .endpoint-card.is-protected {
            border-top-color: #d7c384;
        }

        .endpoint-card.is-public {
            border-top-color: #a8ccb7;
        }

        .guide-grid {
            display: grid;
            gap: 16px;
            margin-top: 16px;
        }

        .guide-card {
            background: var(--panel);
            border: 1px solid var(--line);
            border-radius: 8px;
            box-shadow: var(--shadow-soft);
            overflow: hidden;
        }

        .guide-card-header {
            background: var(--panel-soft);
            border-bottom: 1px solid var(--line);
            padding: 16px 18px;
        }

        .guide-card-header p {
            max-width: 820px;
        }

        .guide-tag {
            align-items: center;
            background: var(--mint);
            border: 1px solid var(--line);
            border-radius: 7px;
            color: var(--mint-ink);
            display: inline-flex;
            font-size: .78rem;
            font-weight: 850;
            gap: 7px;
            margin-bottom: 9px;
            padding: 5px 8px;
            text-transform: uppercase;
        }

        .guide-body {
            display: grid;
            gap: 14px;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            padding: 16px 18px 18px;
        }

        .guide-body.compact {
            grid-template-columns: minmax(180px, .8fr) minmax(0, 2fr);
        }

        .guide-list {
            background: var(--panel-soft);
            border: 1px solid var(--line);
            border-radius: 8px;
            padding: 14px;
        }

        .guide-list.wide {
            min-width: 0;
        }

        .guide-list h4 {
            align-items: center;
            color: var(--ink);
            display: flex;
            font-size: .92rem;
            gap: 8px;
            margin: 0 0 10px;
        }

        .guide-list ul {
            display: grid;
            gap: 8px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .guide-list li {
            color: #3d485f;
            font-size: .93rem;
            padding-left: 18px;
            position: relative;
        }

        .guide-list li::before {
            background: var(--accent);
            border-radius: 999px;
            content: "";
            height: 6px;
            left: 2px;
            position: absolute;
            top: .66em;
            width: 6px;
        }

        @media (max-width: 820px) {
            .header-top {
                display: grid;
            }

            .copy-actions {
                justify-content: flex-start;
            }

            main {
                grid-template-columns: 1fr;
            }

            nav {
                position: static;
            }

            .header-inner,
            main {
                width: min(100% - 24px, 1120px);
            }

            .guide-body {
                grid-template-columns: 1fr;
            }

            .guide-body.compact {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-inner">
            <div class="header-top">
                <div>
                    <span class="eyebrow">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M6 3h8l4 4v14H6V3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M14 3v5h5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 13h6M9 17h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                        LexiCoach Backend
                    </span>
                    <h1>{{ $documentation['title'] }}</h1>
                    <p>Base URL: <code>{{ $documentation['base_url'] }}</code></p>
                </div>

                <div class="copy-actions">
                    <button type="button" id="copy-markdown-button">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 8V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-3" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M4 8h9a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H4V8Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        </svg>
                        Copy docs as .md
                    </button>
                    <span class="copy-status" id="copy-markdown-status" aria-live="polite"></span>
                </div>
            </div>

            <div class="meta">
                <span class="pill">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 6v6l4 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    Version {{ $documentation['version'] }}
                </span>
                <span class="pill neutral">
                    <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3 5 6v5c0 4.5 2.9 8.6 7 10 4.1-1.4 7-5.5 7-10V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                        <path d="m9 12 2 2 4-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    {{ $documentation['authentication_type'] }}
                </span>
            </div>
        </div>
    </header>

    <textarea class="markdown-source" id="markdown-documentation" aria-hidden="true" tabindex="-1">{{ $markdownDocumentation }}</textarea>

    <main>
        <nav aria-label="Documentation modules">
            <strong>
                <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                Modules
            </strong>
            @foreach ($documentation['modules'] as $module)
                <a href="#{{ $module['slug'] }}"><span class="nav-dot"></span>{{ $module['name'] }}</a>
            @endforeach
            <a href="#roles"><span class="nav-dot"></span>User roles</a>
            <a href="#scenarios"><span class="nav-dot"></span>Frontend scenarios</a>
            <a href="#real-cases"><span class="nav-dot"></span>Real cases</a>
            <a href="#flutter"><span class="nav-dot"></span>Flutter examples</a>
        </nav>

        <div class="content">
            <section class="intro-panel">
                <div class="section-heading">
                    <span class="icon-box sky">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M12 3 5 6v5c0 4.5 2.9 8.6 7 10 4.1-1.4 7-5.5 7-10V6l-7-3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            <path d="M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h2>Important Headers</h2>
                </div>
                <pre><code>{{ json_encode($documentation['important_headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </section>

            <section>
                <div class="section-heading">
                    <span class="icon-box mint">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M4 5h16M4 12h16M4 19h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="m8 8 4 4-4 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <h2>Frontend Steps</h2>
                </div>
                <ol>
                    @foreach ($documentation['frontend_steps'] as $step)
                        <li>{{ $step }}</li>
                    @endforeach
                </ol>
            </section>

            <section id="roles">
                <div class="section-heading">
                    <span class="icon-box">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M10 11a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.9" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M16 3.1a4 4 0 0 1 0 7.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h2>User Roles</h2>
                </div>
                <p class="section-description">The backend manages the <code>learner</code>, <code>tutor</code>, and <code>admin</code> roles. The <code>visitor</code> role represents a guest user and is not stored in the database.</p>

                <div class="guide-grid">
                    @foreach ($documentation['roles'] as $role)
                        <article class="guide-card">
                            <div class="guide-card-header">
                                <span class="guide-tag">{{ $role['stored_in_database'] ? 'Signed-in account' : 'Public' }}</span>
                                <h3>{{ $role['name'] }}</h3>
                                <p>{{ $role['description'] }}</p>
                            </div>

                            <div class="guide-body compact">
                                <div class="guide-list">
                                    <h4>Storage</h4>
                                    <ul>
                                        <li>{{ $role['stored_in_database'] ? 'This role is stored in the users.role column.' : 'This role is not stored in the database.' }}</li>
                                    </ul>
                                </div>

                                <div class="guide-list wide">
                                    <h4>Permissions principales</h4>
                                    <ul>
                                        @foreach ($role['permissions'] as $permission)
                                            <li>{{ $permission }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="scenarios">
                <div class="section-heading">
                    <span class="icon-box sky">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M5 6h14M5 12h9M5 18h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="m16 10 3 2-3 2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </span>
                    <h2>Scenarios and Frontend Role</h2>
                </div>
                <p class="section-description">These scenarios explain the expected mobile app flow and the API calls needed for each feature.</p>

                <div class="guide-grid">
                    @foreach ($documentation['feature_guides'] as $guide)
                        <article class="guide-card">
                            <div class="guide-card-header">
                                <span class="guide-tag">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M9 18h6M10 22h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        <path d="M8 14a6 6 0 1 1 8 0c-.8.7-1 1.4-1 2H9c0-.6-.2-1.3-1-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                    </svg>
                                    Scenario
                                </span>
                                <h3>{{ $guide['title'] }}</h3>
                                <p>{{ $guide['goal'] }}</p>
                                <p><strong>User:</strong> {{ $guide['user_story'] }}</p>
                            </div>

                            <div class="guide-body">
                                <div class="guide-list">
                                    <h4>
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M9 11 12 14 22 4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                        Frontend
                                    </h4>
                                    <ul>
                                        @foreach ($guide['frontend_tasks'] as $task)
                                            <li>{{ $task }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="guide-list">
                                    <h4>
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M4 7h16M4 12h16M4 17h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                        API
                                    </h4>
                                    <ul>
                                        @foreach ($guide['api_flow'] as $step)
                                            <li>{{ $step }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="guide-list">
                                    <h4>
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="M12 9v4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            <path d="M12 17h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                                            <path d="M10.3 4.4 2.5 18a2 2 0 0 0 1.7 3h15.6a2 2 0 0 0 1.7-3L13.7 4.4a2 2 0 0 0-3.4 0Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        </svg>
                                        Affichage
                                    </h4>
                                    <ul>
                                        @foreach ($guide['display_rules'] as $rule)
                                            <li>{{ $rule }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            <section id="real-cases">
                <div class="section-heading">
                    <span class="icon-box amber">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M8 6h13M8 12h13M8 18h13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                            <path d="M3 6h.01M3 12h.01M3 18h.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h2>Important Real Cases</h2>
                </div>
                <p class="section-description">These cases show how the features behave in real LexiCoach usage.</p>

                <div class="guide-grid">
                    @foreach ($documentation['real_cases'] as $case)
                        <article class="guide-card">
                            <div class="guide-card-header">
                                <span class="guide-tag">{{ $case['actor'] }}</span>
                                <h3>{{ $case['title'] }}</h3>
                                <p>{{ $case['situation'] }}</p>
                            </div>

                            <div class="guide-body">
                                <div class="guide-list">
                                    <h4>Frontend Actions</h4>
                                    <ul>
                                        @foreach ($case['frontend_actions'] as $action)
                                            <li>{{ $action }}</li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="guide-list">
                                    <h4>Routes Used</h4>
                                    <ul>
                                        @foreach ($case['routes'] as $route)
                                            <li><code>{{ $route }}</code></li>
                                        @endforeach
                                    </ul>
                                </div>

                                <div class="guide-list">
                                    <h4>Backend garantit</h4>
                                    <ul>
                                        @foreach ($case['backend_guarantees'] as $guarantee)
                                            <li>{{ $guarantee }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>

            @foreach ($documentation['modules'] as $module)
                <section id="{{ $module['slug'] }}">
                    <div class="section-heading">
                        <span class="icon-box rose">
                            <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <path d="M5 5h6v6H5V5ZM13 5h6v6h-6V5ZM5 13h6v6H5v-6ZM13 13h6v6h-6v-6Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <h2>{{ $module['name'] }}</h2>
                    </div>
                    <p class="section-description">{{ $module['description'] }}</p>

                    @foreach ($module['endpoints'] as $endpoint)
                        <article class="endpoint-card {{ $endpoint['protected'] ? 'is-protected' : 'is-public' }}">
                            <div class="endpoint-title">
                                <span class="method method-{{ strtolower($endpoint['method']) }}">{{ $endpoint['method'] }}</span>
                                <h3>{{ $endpoint['name'] }}</h3>
                                <span class="path">{{ $endpoint['path'] }}</span>
                                <span class="pill {{ $endpoint['protected'] ? 'warning' : 'neutral' }}">
                                    {{ $endpoint['protected'] ? 'Token required' : 'Public' }}
                                </span>
                            </div>

                            <p>{{ $endpoint['description'] }}</p>

                            <div class="block">
                                <div class="block-title">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M4 7h16M4 12h16M4 17h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Headers
                                </div>
                                <pre><code>{{ json_encode($endpoint['headers'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>

                            <div class="block">
                                <div class="block-title">
                                    <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                        <path d="M7 4h10a2 2 0 0 1 2 2v14H5V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
                                        <path d="M8 9h8M8 13h8M8 17h5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    </svg>
                                    Request Body
                                </div>
                                <pre><code>{{ $endpoint['request_body'] === null ? 'No body' : json_encode($endpoint['request_body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                            </div>

                            @foreach ($endpoint['responses'] as $response)
                                <div class="block">
                                    <div class="block-title">
                                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                            <path d="m5 12 4 4L19 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                        </svg>
                                        Response {{ $response['status'] }} - {{ $response['title'] }}
                                    </div>
                                    <pre><code>{{ json_encode($response['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                                </div>
                            @endforeach
                        </article>
                    @endforeach
                </section>
            @endforeach

            <section id="flutter">
                <div class="section-heading">
                    <span class="icon-box">
                        <svg class="icon" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M7 4h10a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Z" stroke="currentColor" stroke-width="2"/>
                            <path d="M10 18h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <h2>Flutter Examples</h2>
                </div>
                <p class="section-description">These examples show how a Flutter app can call the backend, retrieve the Sanctum token, and use protected routes.</p>

                @foreach ($documentation['flutter_examples'] as $example)
                    <article class="endpoint-card">
                        <div class="endpoint-title">
                            <h3>{{ $example['title'] }}</h3>
                            <span class="language">{{ $example['language'] }}</span>
                        </div>
                        <div class="block">
                            <pre><code>{{ $example['code'] }}</code></pre>
                        </div>
                    </article>
                @endforeach
            </section>
        </div>
    </main>

    <script>
        const copyButton = document.getElementById('copy-markdown-button');
        const copyStatus = document.getElementById('copy-markdown-status');
        const markdownDocumentation = document.getElementById('markdown-documentation');

        copyButton?.addEventListener('click', async () => {
            const markdown = markdownDocumentation.value;

            try {
                await navigator.clipboard.writeText(markdown);
                copyStatus.textContent = 'Doc .md copiee';
            } catch (error) {
                markdownDocumentation.focus();
                markdownDocumentation.select();
                document.execCommand('copy');
                copyStatus.textContent = 'Doc .md copiee';
            }

            window.setTimeout(() => {
                copyStatus.textContent = '';
            }, 2500);
        });
    </script>
</body>
</html>
