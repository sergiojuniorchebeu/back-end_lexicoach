<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Admin Login - LexiCoach</title>
        @fonts
        <style>
            :root {
                --primary: #6547E8;
                --primary-soft: #F0EDFF;
                --primary-border: #E2DDF2;
                --background: #F9F7FF;
                --surface: #FFFFFF;
                --text: #222033;
                --muted: #77718A;
                --placeholder: #AAA5B7;
                --shadow: 0 18px 50px rgba(101, 71, 232, .10);
            }

            * {
                box-sizing: border-box;
            }

            body {
                margin: 0;
                min-height: 100vh;
                background: var(--background);
                color: var(--text);
                font-family: "Instrument Sans", -apple-system, BlinkMacSystemFont, "SF Pro Display", "Segoe UI", sans-serif;
            }

            button,
            input {
                font: inherit;
            }

            .page {
                min-height: 100vh;
                display: grid;
                place-items: center;
                padding: 24px;
            }

            .auth-shell {
                width: min(100%, 440px);
            }

            .brand {
                margin-bottom: 22px;
                text-align: center;
            }

            .brand-mark {
                width: 64px;
                height: 64px;
                display: grid;
                place-items: center;
                margin: 0 auto 16px;
                border: 1px solid var(--primary-border);
                border-radius: 24px;
                background: var(--surface);
                color: var(--primary);
                box-shadow: var(--shadow);
                font-size: 24px;
                font-weight: 800;
            }

            h1 {
                margin: 0;
                font-size: 30px;
                line-height: 1.1;
            }

            p {
                margin: 8px 0 0;
                color: var(--muted);
                font-size: 15px;
                line-height: 1.6;
            }

            .card {
                border: 1px solid var(--primary-border);
                border-radius: 28px;
                background: rgba(255, 255, 255, .92);
                padding: 22px;
                box-shadow: var(--shadow);
            }

            .field {
                display: grid;
                gap: 8px;
                margin-bottom: 16px;
                color: var(--text);
                font-size: 14px;
                font-weight: 700;
            }

            input {
                width: 100%;
                min-height: 52px;
                border: 1px solid var(--primary-border);
                border-radius: 18px;
                background: #FFFFFF;
                color: var(--text);
                padding: 0 16px;
                outline: none;
                transition: border-color .18s ease, box-shadow .18s ease;
            }

            input::placeholder {
                color: var(--placeholder);
            }

            input:focus {
                border-color: var(--primary);
                box-shadow: 0 0 0 5px rgba(101, 71, 232, .12);
            }

            .button {
                width: 100%;
                min-height: 54px;
                border: 0;
                border-radius: 20px;
                background: var(--primary);
                color: #FFFFFF;
                font-weight: 800;
                cursor: pointer;
                box-shadow: 0 12px 24px rgba(101, 71, 232, .18);
                transition: transform .18s ease, opacity .18s ease;
            }

            .button:hover {
                transform: translateY(-1px);
            }

            .button:disabled {
                cursor: not-allowed;
                opacity: .65;
                transform: none;
            }

            .notice {
                margin-bottom: 14px;
                border: 1px solid var(--primary-border);
                border-radius: 18px;
                background: var(--primary-soft);
                color: var(--text);
                padding: 12px 14px;
                font-size: 14px;
            }

            .hint {
                margin-top: 16px;
                padding: 12px 14px;
                border-radius: 18px;
                background: var(--primary-soft);
                color: var(--muted);
                font-size: 13px;
                line-height: 1.5;
            }
        </style>
    </head>
    <body>
        <main class="page">
            <section class="auth-shell">
                <div class="brand">
                    <div class="brand-mark">L</div>
                    <h1>Admin LexiCoach</h1>
                    <p>Connecte-toi pour gerer les comptes, les roles et les statistiques.</p>
                </div>

                <form class="card" id="login-form">
                    <div class="notice" id="notice" hidden></div>

                    <label class="field">
                        Email
                        <input id="email" type="email" placeholder="admin@example.com" required>
                    </label>

                    <label class="field">
                        Mot de passe
                        <input id="password" type="password" placeholder="password123" required>
                    </label>

                    <button class="button" id="login-button" type="submit">Se connecter</button>

                    <div class="hint">
                        Apres le seed, le compte de demo est admin@example.com avec le mot de passe password123.
                    </div>
                </form>
            </section>
        </main>

        <script>
            const tokenKey = 'lexicoach_admin_token';
            const baseUrl = `${window.location.origin}/api`;
            const form = document.getElementById('login-form');
            const notice = document.getElementById('notice');
            const button = document.getElementById('login-button');

            form.addEventListener('submit', async (event) => {
                event.preventDefault();
                button.disabled = true;
                button.textContent = 'Connexion...';
                showNotice('');

                try {
                    const response = await apiRequest('/auth/login', {
                        method: 'POST',
                        body: JSON.stringify({
                            email: document.getElementById('email').value,
                            password: document.getElementById('password').value,
                            device_name: 'admin-dashboard',
                        }),
                    });

                    if (response.data.user.role !== 'admin') {
                        showNotice('Ce compte existe, mais il n a pas le role admin.');
                        return;
                    }

                    window.localStorage.setItem(tokenKey, response.data.token.access_token);
                    window.location.href = '/admin/dashboard';
                } catch (error) {
                    showNotice(error.message || 'Erreur de connexion.');
                } finally {
                    button.disabled = false;
                    button.textContent = 'Se connecter';
                }
            });

            async function apiRequest(path, options = {}) {
                const headers = new Headers(options.headers || {});
                headers.set('Accept', 'application/json');

                if (options.body) {
                    headers.set('Content-Type', 'application/json');
                }

                const response = await fetch(`${baseUrl}${path}`, {
                    ...options,
                    headers,
                });
                const payload = await response.json();

                if (!response.ok) {
                    const firstError = payload.errors
                        ? Object.values(payload.errors).flat()[0]
                        : null;
                    throw new Error(firstError || payload.message || 'Erreur API');
                }

                return payload;
            }

            function showNotice(message) {
                notice.textContent = message;
                notice.hidden = message === '';
            }
        </script>
    </body>
</html>
