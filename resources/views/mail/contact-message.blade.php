<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>New contact message</title>
    <style>
        :root {
            --primary: oklch(51.34% 0.1395 264.75);
            --primary-foreground: #ffffff;
            --background: oklch(97.15% 0.0064 262.51);
            --foreground: oklch(14.08% 0.0044 285.82);
            --muted: oklch(63.39% 0.0234 255.77);
            --muted-foreground: oklch(55.14% 0.0208 257.77);
            --border: oklch(92.57% 0.0056 261.22);
        }

        body {
            font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: var(--background);
            color: var(--foreground);
            margin: 0;
            padding: 0;
            width: 100%;
            line-height: 1.6;
        }

        .wrapper-layout {
            background-color: #f8fafc;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            min-width: 360px;
        }

        .main-card {
            background-color: var(--background);
            border-radius: 12px;
            padding: 32px;
            max-width: 560px;
            width: 100%;
            border: 1px solid var(--border);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08), 0 1px 2px rgba(0, 0, 0, 0.06);
        }

        h1 {
            color: var(--primary);
            font-size: 24px;
            font-weight: 700;
            margin-top: 0;
            margin-bottom: 8px;
            letter-spacing: -0.025em;
        }

        p {
            font-size: 15px;
            line-height: 1.7;
            color: var(--muted);
            margin: 0 0 12px 0;
        }

        .details {
            margin: 20px 0;
            border-collapse: collapse;
            width: 100%;
        }

        .details th,
        .details td {
            text-align: start;
            vertical-align: top;
            padding: 8px 0;
            border-top: 1px solid var(--border);
        }

        .details th {
            width: 96px;
            font-size: 13px;
            font-weight: 600;
            color: var(--muted-foreground);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .details td {
            font-size: 15px;
            color: var(--foreground);
            overflow-wrap: anywhere;
        }

        /* The message itself, kept as it was written. */
        .message {
            background-color: #ffffff;
            border: 1px solid var(--border);
            border-radius: 8px;
            padding: 16px;
            margin: 20px 0 0;
            font-size: 15px;
            line-height: 1.7;
            color: var(--foreground);
            white-space: pre-wrap;
        }

        /* Footer */
        .footer-section {
            border-top: 1px solid var(--border);
            padding-top: 20px;
            margin-top: 24px;
            font-size: 13px;
            color: var(--muted-foreground);
            line-height: 1.6;
        }
    </style>
</head>
<body>
    <div class="wrapper-layout">
        <main class="main-card">
            <header>
                <h1>New contact message</h1>
            </header>

            <table class="details">
                <tr>
                    <th>Name</th>
                    <td>{{ $contactMessage->name }}</td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>{{ $contactMessage->email }}</td>
                </tr>
                <tr>
                    <th>Subject</th>
                    <td>{{ $contactMessage->subject }}</td>
                </tr>
            </table>

            <div class="message">{{ $contactMessage->message }}</div>

            <footer class="footer-section">
                <p><strong>{{ config('app.name') }}</strong></p>
                <p>
                    Sent on {{ $contactMessage->created_at->translatedFormat('j F Y, H:i') }}.
                    Replying to this email answers {{ $contactMessage->name }}.
                </p>
            </footer>
        </main>
    </div>
</body>
</html>
