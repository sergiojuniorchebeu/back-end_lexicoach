<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Dashboard - LexiCoach</title>
        @fonts
        <style>
            :root {
                --primary: #6547E8;
                --primary-soft: #F0EDFF;
                --primary-pale: #F9F7FF;
                --primary-border: #E2DDF2;
                --text: #222033;
                --muted: #77718A;
                --placeholder: #AAA5B7;
                --surface: #FFFFFF;
                --blue-soft: #E1F5FE;
                --orange-soft: #FFF3E0;
                --green-soft: #E0F2F1;
                --red-soft: #FFEBEE;
                --shadow: 0 18px 50px rgba(101, 71, 232, .08);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: var(--primary-pale);
                color: var(--text);
                font-family: "Instrument Sans", -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", sans-serif;
            }

            button,
            input,
            select {
                font: inherit;
            }

            .page {
                min-height: 100vh;
                padding: 18px;
            }

            .shell {
                width: min(1220px, 100%);
                margin: 0 auto;
                display: grid;
                gap: 18px;
            }

            .topbar,
            .panel,
            .metric {
                border: 1px solid var(--primary-border);
                background: rgba(255, 255, 255, .94);
                box-shadow: var(--shadow);
            }

            .topbar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                gap: 16px;
                border-radius: 28px;
                padding: 18px;
            }

            .panel {
                border-radius: 26px;
                overflow: hidden;
            }

            .metric {
                min-height: 138px;
                border-radius: 26px;
                padding: 18px;
            }

            h1,
            h2,
            p {
                margin: 0;
            }

            h1 {
                font-size: clamp(28px, 3vw, 38px);
                line-height: 1.08;
                letter-spacing: 0;
            }

            h2 {
                font-size: 18px;
                line-height: 1.2;
            }

            .eyebrow {
                margin-bottom: 6px;
                color: var(--primary);
                font-size: 12px;
                font-weight: 800;
                text-transform: uppercase;
            }

            .muted {
                color: var(--muted);
                font-size: 14px;
                line-height: 1.55;
            }

            .actions,
            .filters,
            .metric-top,
            .attempt-row {
                display: flex;
                align-items: center;
                gap: 10px;
            }

            .actions {
                flex-wrap: wrap;
                justify-content: flex-end;
            }

            .button {
                min-height: 46px;
                border: 1px solid var(--primary-border);
                border-radius: 18px;
                background: var(--surface);
                color: var(--primary);
                padding: 0 16px;
                font-weight: 800;
                cursor: pointer;
                transition: transform .18s ease, box-shadow .18s ease, opacity .18s ease;
            }

            .button:hover {
                transform: translateY(-1px);
                box-shadow: 0 12px 24px rgba(101, 71, 232, .10);
            }

            .button-primary {
                border-color: var(--primary);
                background: var(--primary);
                color: #FFFFFF;
                box-shadow: 0 12px 24px rgba(101, 71, 232, .16);
            }

            .button:disabled {
                cursor: not-allowed;
                opacity: .55;
                transform: none;
                box-shadow: none;
            }

            .pill {
                min-height: 38px;
                display: inline-flex;
                align-items: center;
                border: 1px solid var(--primary-border);
                border-radius: 999px;
                background: var(--primary-soft);
                color: var(--primary);
                padding: 0 12px;
                font-size: 14px;
                font-weight: 800;
            }

            .notice {
                border: 1px solid var(--primary-border);
                border-radius: 20px;
                background: var(--primary-soft);
                color: var(--text);
                padding: 13px 15px;
                font-size: 14px;
            }

            .metrics {
                display: grid;
                grid-template-columns: repeat(4, minmax(0, 1fr));
                gap: 14px;
            }

            .metric-top {
                justify-content: space-between;
            }

            .metric-value {
                margin-top: 10px;
                font-size: 34px;
                line-height: 1;
                font-weight: 850;
                letter-spacing: 0;
            }

            .icon-box {
                width: 48px;
                height: 48px;
                display: grid;
                place-items: center;
                border-radius: 18px;
                background: var(--primary-soft);
                color: var(--primary);
                font-weight: 850;
            }

            .content-grid {
                display: grid;
                grid-template-columns: minmax(0, 1.25fr) minmax(320px, .75fr);
                gap: 18px;
            }

            .section-head {
                display: flex;
                justify-content: space-between;
                align-items: end;
                gap: 14px;
                padding: 16px;
                border-bottom: 1px solid var(--primary-border);
                background: rgba(249, 247, 255, .68);
            }

            .filters {
                align-items: end;
                flex-wrap: wrap;
            }

            label {
                display: grid;
                gap: 7px;
                color: var(--text);
                font-size: 13px;
                font-weight: 800;
            }

            input,
            select {
                min-height: 46px;
                border: 1px solid var(--primary-border);
                border-radius: 18px;
                background: var(--surface);
                color: var(--text);
                padding: 0 14px;
                outline: none;
                transition: border-color .18s ease, box-shadow .18s ease;
            }

            input {
                width: min(250px, 100%);
            }

            input::placeholder {
                color: var(--placeholder);
            }

            input:focus,
            select:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 5px rgba(101, 71, 232, .12);
            }

            .table-wrap {
                overflow-x: auto;
                background: var(--surface);
            }

            table {
                width: 100%;
                min-width: 980px;
                border-collapse: collapse;
                text-align: left;
                font-size: 14px;
            }

            th {
                background: var(--surface);
                color: var(--muted);
                font-size: 12px;
                font-weight: 850;
                padding: 13px 16px;
                text-transform: uppercase;
            }

            td {
                border-top: 1px solid var(--primary-border);
                padding: 15px 16px;
                vertical-align: top;
            }

            .name {
                font-weight: 850;
            }

            .role {
                display: inline-flex;
                border: 1px solid;
                border-radius: 999px;
                padding: 6px 11px;
                font-size: 12px;
                font-weight: 850;
                text-transform: uppercase;
            }

            .role-learner {
                border-color: #E8E1FF;
                background: var(--primary-soft);
                color: var(--primary);
            }

            .role-tutor {
                border-color: #D1EEF8;
                background: var(--blue-soft);
                color: #04779C;
            }

            .role-admin {
                border-color: #FFE0B2;
                background: var(--orange-soft);
                color: #9A5B00;
            }

            .activity {
                padding: 16px;
            }

            .side-list {
                display: grid;
                gap: 12px;
                margin-top: 14px;
            }

            .attempt {
                border: 1px solid var(--primary-border);
                border-radius: 20px;
                background: var(--primary-pale);
                padding: 13px;
            }

            .attempt-row {
                justify-content: space-between;
            }

            .bar-row {
                display: grid;
                gap: 8px;
            }

            .bar-label {
                display: flex;
                justify-content: space-between;
                color: var(--text);
                font-size: 14px;
                font-weight: 800;
            }

            .bar {
                height: 9px;
                overflow: hidden;
                border-radius: 999px;
                background: var(--primary-border);
            }

            .bar span {
                display: block;
                height: 100%;
                border-radius: 999px;
                background: var(--primary);
            }

            .limit-controls {
                display: grid;
                grid-template-columns: minmax(88px, 1fr) minmax(88px, 1fr);
                gap: 8px;
                min-width: 230px;
            }

            .limit-controls label {
                font-size: 11px;
            }

            .limit-controls input {
                width: 100%;
                min-height: 40px;
                border-radius: 14px;
                padding: 0 10px;
            }

            .limit-save {
                grid-column: 1 / -1;
                min-height: 40px;
                border-radius: 14px;
            }

            .empty {
                padding: 30px 16px;
                color: var(--muted);
                text-align: center;
            }

            @media (max-width: 940px) {
                .metrics,
                .content-grid {
                    grid-template-columns: 1fr;
                }

                .topbar,
                .section-head {
                    align-items: stretch;
                    flex-direction: column;
                }

                .actions,
                .filters {
                    justify-content: stretch;
                }

                input,
                select,
                .button {
                    width: 100%;
                }
            }
        </style>
    </head>
    <body>
        <main class="page">
            <div class="shell">
                <header class="topbar">
                    <div>
                        <p class="eyebrow">LexiCoach</p>
                        <h1>Admin Dashboard</h1>
                        <p class="muted">Gestion des comptes, roles et statistiques globales.</p>
                    </div>
                    <div class="actions">
                        <span class="pill" id="admin-name">Admin</span>
                        <button class="button" id="refresh-dashboard">Actualiser</button>
                        <button class="button button-primary" id="logout">Deconnexion</button>
                    </div>
                </header>

                <div class="notice" id="notice" hidden></div>

                <section class="metrics" id="metrics"></section>

                <section class="content-grid">
                    <div class="panel">
                        <div class="section-head">
                            <div>
                                <h2>Gestion des comptes</h2>
                                <p class="muted">Recherche, filtre et modification des roles.</p>
                            </div>
                            <div class="filters">
                                <label>
                                    Recherche
                                    <input id="search" type="search" placeholder="Nom ou email">
                                </label>
                                <label>
                                    Role
                                    <select id="role-filter">
                                        <option value="all">Tous</option>
                                        <option value="learner">Learner</option>
                                        <option value="tutor">Tutor</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </label>
                                <button class="button" id="filter-users">Filtrer</button>
                            </div>
                        </div>
                        <div class="table-wrap">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Compte</th>
                                        <th>Role</th>
                                        <th>Activite</th>
                                        <th>Conversation IA</th>
                                        <th>Modifier</th>
                                    </tr>
                                </thead>
                                <tbody id="users-body"></tbody>
                            </table>
                        </div>
                        <div class="empty" id="users-empty" hidden>Aucun utilisateur ne correspond au filtre.</div>
                    </div>

                    <aside style="display: grid; gap: 18px;">
                        <section class="panel activity">
                            <h2>Repartition</h2>
                            <div class="side-list" id="roles-breakdown"></div>
                        </section>

                        <section class="panel activity">
                            <h2>Dernieres tentatives</h2>
                            <div class="side-list" id="recent-attempts"></div>
                        </section>
                    </aside>
                </section>
            </div>
        </main>

        <script>
            const tokenKey = 'lexicoach_admin_token';
            const baseUrl = `${window.location.origin}/api`;
            let token = window.localStorage.getItem(tokenKey);
            let dashboard = null;

            const elements = {
                notice: document.getElementById('notice'),
                adminName: document.getElementById('admin-name'),
                metrics: document.getElementById('metrics'),
                usersBody: document.getElementById('users-body'),
                usersEmpty: document.getElementById('users-empty'),
                rolesBreakdown: document.getElementById('roles-breakdown'),
                recentAttempts: document.getElementById('recent-attempts'),
            };

            document.getElementById('logout').addEventListener('click', () => {
                window.localStorage.removeItem(tokenKey);
                window.location.href = '/admin/login';
            });

            document.getElementById('refresh-dashboard').addEventListener('click', () => {
                loadAdminArea();
            });

            document.getElementById('filter-users').addEventListener('click', () => {
                loadUsers();
            });

            if (!token) {
                window.location.href = '/admin/login';
            } else {
                loadAdminArea();
            }

            async function loadAdminArea() {
                setLoading(true);
                showNotice('');

                try {
                    const profile = await apiRequest('/auth/me', { token });

                    if (profile.data.user.role !== 'admin') {
                        window.localStorage.removeItem(tokenKey);
                        window.location.href = '/admin/login';
                        return;
                    }

                    elements.adminName.textContent = profile.data.user.full_name;

                    const response = await apiRequest('/admin/dashboard', { token });
                    dashboard = response.data.dashboard;
                    renderDashboard();
                    await loadUsers();
                } catch (error) {
                    if (error.status === 401 || error.status === 403) {
                        redirectToLogin();
                        return;
                    }

                    showNotice(error.message || 'Erreur API.');
                } finally {
                    setLoading(false);
                }
            }

            async function loadUsers() {
                try {
                    const params = new URLSearchParams();
                    const search = document.getElementById('search').value.trim();
                    const role = document.getElementById('role-filter').value;

                    if (search !== '') {
                        params.set('search', search);
                    }

                    if (role !== 'all') {
                        params.set('role', role);
                    }

                    const query = params.toString();
                    const path = query === '' ? '/admin/users' : `/admin/users?${query}`;
                    const response = await apiRequest(path, { token });
                    renderUsers(response.data.users);
                } catch (error) {
                    if (error.status === 401 || error.status === 403) {
                        redirectToLogin();
                        return;
                    }

                    showNotice(error.message || 'Erreur API.');
                }
            }

            function renderDashboard() {
                elements.metrics.innerHTML = [
                    metricCard('Utilisateurs', dashboard.users.total, `${dashboard.users.learners} learners, ${dashboard.users.tutors} tutors`, 'U'),
                    metricCard('Reading attempts', dashboard.learning.reading_attempts, `${dashboard.learning.average_reading_score}% de moyenne`, 'R'),
                    metricCard('Writing attempts', dashboard.learning.writing_attempts || 0, `${dashboard.learning.average_writing_score || 0}% de moyenne`, 'W'),
                    metricCard('Smart abstract', dashboard.learning.smart_abstract_attempts || 0, `${dashboard.learning.smart_abstract_exercises || 0} documents disponibles`, 'A'),
                    metricCard('Associations', dashboard.tutor_view.linked_pairs, `${dashboard.tutor_view.tutors_with_learners} tutors actifs`, 'L'),
                ].join('');

                elements.rolesBreakdown.innerHTML = [
                    barRow('Learners', dashboard.users.learners, dashboard.users.total),
                    barRow('Tutors', dashboard.users.tutors, dashboard.users.total),
                    barRow('Admins', dashboard.users.admins, dashboard.users.total),
                ].join('');

                if (dashboard.recent_attempts.length === 0) {
                    elements.recentAttempts.innerHTML = '<p class="muted">Aucune tentative recente.</p>';
                    return;
                }

                elements.recentAttempts.innerHTML = dashboard.recent_attempts
                    .map((attempt) => `
                        <div class="attempt">
                            <div class="attempt-row">
                                <div>
                                    <div class="name">${escapeHtml(attempt.learner.full_name)}</div>
                                    <div class="muted">${escapeHtml(attempt.exercise.title)}</div>
                                </div>
                                <span class="pill">${attempt.score}%</span>
                            </div>
                        </div>
                    `)
                    .join('');
            }

            function renderUsers(users) {
                elements.usersEmpty.hidden = users.length !== 0;
                elements.usersBody.innerHTML = users
                    .map((user) => {
                        const limits = user.conversation_limits || {};
                        const sessionMinutes = limits.session_limit_minutes || 3;
                        const dailyLimit = limits.daily_session_limit ?? 3;

                        return `
                        <tr>
                            <td>
                                <div class="name">${escapeHtml(user.full_name)}</div>
                                <div class="muted">${escapeHtml(user.email)}</div>
                            </td>
                            <td><span class="role role-${user.role}">${user.role}</span></td>
                            <td class="muted">
                                <div>${user.reading_attempts_count || 0} attempts</div>
                                <div>${user.learners_count || 0} learners, ${user.tutors_count || 0} tutors</div>
                            </td>
                            <td>
                                <div class="limit-controls">
                                    <label>
                                        Min/session
                                        <input type="number" min="1" max="60" value="${sessionMinutes}" data-user-id="${user.id}" data-limit-field="session-minutes">
                                    </label>
                                    <label>
                                        Sessions/jour
                                        <input type="number" min="0" max="100" value="${dailyLimit}" data-user-id="${user.id}" data-limit-field="daily-limit">
                                    </label>
                                    <button class="button limit-save" data-user-id="${user.id}">Enregistrer</button>
                                </div>
                            </td>
                            <td>
                                <select data-user-id="${user.id}" data-current-role="${user.role}" class="role-select">
                                    <option value="learner" ${user.role === 'learner' ? 'selected' : ''}>Learner</option>
                                    <option value="tutor" ${user.role === 'tutor' ? 'selected' : ''}>Tutor</option>
                                    <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                                </select>
                            </td>
                        </tr>
                    `;
                    })
                    .join('');

                document.querySelectorAll('.role-select').forEach((select) => {
                    select.addEventListener('change', async (event) => {
                        const field = event.target;
                        await updateUserRole(field.dataset.userId, field.value, field);
                    });
                });

                document.querySelectorAll('.limit-save').forEach((button) => {
                    button.addEventListener('click', async (event) => {
                        await updateConversationLimits(event.target.dataset.userId, event.target);
                    });
                });
            }

            async function updateUserRole(userId, role, field) {
                setLoading(true);
                showNotice('');

                try {
                    await apiRequest(`/admin/users/${userId}/role`, {
                        method: 'PATCH',
                        token,
                        body: JSON.stringify({ role }),
                    });
                    await loadAdminArea();
                } catch (error) {
                    field.value = field.dataset.currentRole;
                    showNotice(error.message || 'Impossible de changer le role.');
                } finally {
                    setLoading(false);
                }
            }

            async function updateConversationLimits(userId, button) {
                const sessionInput = document.querySelector(`[data-user-id="${userId}"][data-limit-field="session-minutes"]`);
                const dailyInput = document.querySelector(`[data-user-id="${userId}"][data-limit-field="daily-limit"]`);
                const sessionMinutes = Number.parseInt(sessionInput.value, 10);
                const dailyLimit = Number.parseInt(dailyInput.value, 10);

                if (!Number.isFinite(sessionMinutes) || !Number.isFinite(dailyLimit)) {
                    showNotice('Les limites doivent etre des nombres.');
                    return;
                }

                setLoading(true);
                button.disabled = true;
                showNotice('');

                try {
                    await apiRequest(`/admin/users/${userId}/conversation-limits`, {
                        method: 'PATCH',
                        token,
                        body: JSON.stringify({
                            ai_conversation_session_limit_seconds: sessionMinutes * 60,
                            ai_conversation_daily_session_limit: dailyLimit,
                        }),
                    });
                    showNotice('Limites conversation IA mises a jour.');
                    await loadUsers();
                } catch (error) {
                    showNotice(error.message || 'Impossible de modifier les limites.');
                } finally {
                    button.disabled = false;
                    setLoading(false);
                }
            }

            async function apiRequest(path, options = {}) {
                const headers = new Headers(options.headers || {});
                headers.set('Accept', 'application/json');

                if (options.body) {
                    headers.set('Content-Type', 'application/json');
                }

                if (options.token) {
                    headers.set('Authorization', `Bearer ${options.token}`);
                }

                const response = await fetch(`${baseUrl}${path}`, {
                    ...options,
                    headers,
                });
                const contentType = response.headers.get('content-type') || '';
                const payload = contentType.includes('application/json')
                    ? await response.json()
                    : { message: await response.text() };

                if (!response.ok) {
                    const firstError = payload.errors
                        ? Object.values(payload.errors).flat()[0]
                        : null;
                    const error = new Error(firstError || payload.message || 'Erreur API');
                    error.status = response.status;
                    throw error;
                }

                return payload;
            }

            function redirectToLogin() {
                window.localStorage.removeItem(tokenKey);
                window.location.href = '/admin/login';
            }

            function metricCard(label, value, detail, icon) {
                return `
                    <article class="metric">
                        <div class="metric-top">
                            <div>
                                <p class="muted">${label}</p>
                                <div class="metric-value">${value}</div>
                            </div>
                            <div class="icon-box">${icon}</div>
                        </div>
                        <p class="muted" style="margin-top: 10px;">${detail}</p>
                    </article>
                `;
            }

            function barRow(label, value, total) {
                const percent = total > 0 ? Math.round((value / total) * 100) : 0;

                return `
                    <div class="bar-row">
                        <div class="bar-label">
                            <span>${label}</span>
                            <span>${value}</span>
                        </div>
                        <div class="bar"><span style="width: ${percent}%"></span></div>
                    </div>
                `;
            }

            function setLoading(isLoading) {
                document.querySelectorAll('button, select').forEach((element) => {
                    if (element.id !== 'logout') {
                        element.disabled = isLoading;
                    }
                });
            }

            function showNotice(message) {
                elements.notice.textContent = message;
                elements.notice.hidden = message === '';
            }

            function escapeHtml(value) {
                return String(value)
                    .replaceAll('&', '&amp;')
                    .replaceAll('<', '&lt;')
                    .replaceAll('>', '&gt;')
                    .replaceAll('"', '&quot;')
                    .replaceAll("'", '&#039;');
            }
        </script>
    </body>
</html>
