<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $success ? 'Paiement reussi' : 'Paiement annule' }} - LexiCoach</title>
        <style>
            :root {
                --primary: #6547E8;
                --background: #F9F7FF;
                --surface: #FFFFFF;
                --text: #222033;
                --muted: #77718A;
            }
            * { box-sizing: border-box; }
            body {
                margin: 0;
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                background: var(--background);
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                color: var(--text);
                padding: 24px;
            }
            .card {
                background: var(--surface);
                border-radius: 24px;
                padding: 40px 32px;
                max-width: 420px;
                text-align: center;
                box-shadow: 0 18px 50px rgba(101, 71, 232, .10);
            }
            .badge {
                width: 56px;
                height: 56px;
                margin: 0 auto 16px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 28px;
                font-weight: 700;
                color: #fff;
                background: {{ $success ? '#2FAE60' : '#E85B5B' }};
            }
            h1 { font-size: 20px; margin: 0 0 8px; }
            p { color: var(--muted); margin: 0; line-height: 1.5; }
        </style>
    </head>
    <body>
        <div class="card">
            <div class="badge">{{ $success ? '✓' : '×' }}</div>
            <h1>{{ $success ? 'Paiement reussi' : 'Paiement annule' }}</h1>
            <p>Vous pouvez retourner a l'application LexiCoach.</p>
        </div>
    </body>
</html>
