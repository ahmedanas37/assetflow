<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $productName }} Setup</title>
    <style>
        :root {
            --bg: #f3f6fb;
            --panel: #ffffff;
            --text: #0f172a;
            --muted: #475569;
            --primary: #1459d9;
            --border: #dbe3f0;
            --danger-bg: #fff4f4;
            --danger-text: #9f1239;
            --success-bg: #f0fdf4;
            --success-text: #166534;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(180deg, #eef4ff 0%, var(--bg) 100%);
            color: var(--text);
        }

        .wrapper {
            max-width: 900px;
            margin: 0 auto;
            padding: 48px 20px;
        }

        .panel {
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 18px;
            box-shadow: 0 18px 40px rgba(15, 23, 42, 0.08);
            padding: 28px;
        }

        h1 {
            margin: 0 0 8px;
            font-size: 1.9rem;
        }

        .subtitle {
            margin: 0 0 24px;
            color: var(--muted);
            line-height: 1.5;
        }

        .status,
        .error-box {
            border-radius: 10px;
            padding: 12px 14px;
            margin-bottom: 16px;
        }

        .status {
            background: var(--success-bg);
            color: var(--success-text);
            border: 1px solid #bbf7d0;
        }

        .error-box {
            background: var(--danger-bg);
            color: var(--danger-text);
            border: 1px solid #fecdd3;
        }

        .error-box ul {
            margin: 0;
            padding-left: 18px;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .field.full {
            grid-column: span 2;
        }

        label {
            font-weight: 600;
            font-size: 0.92rem;
        }

        input {
            width: 100%;
            border: 1px solid var(--border);
            border-radius: 10px;
            padding: 11px 12px;
            font-size: 0.95rem;
            color: var(--text);
            background: #fff;
        }

        input:focus {
            outline: 2px solid #bfdbfe;
            border-color: #93c5fd;
        }

        .hint {
            margin-top: 4px;
            color: var(--muted);
            font-size: 0.85rem;
        }

        .actions {
            margin-top: 22px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        button {
            border: 0;
            border-radius: 10px;
            background: var(--primary);
            color: #fff;
            font-size: 0.94rem;
            font-weight: 600;
            padding: 11px 16px;
            cursor: pointer;
        }

        button.secondary {
            background: #1f2937;
        }

        pre {
            margin: 0;
            background: #0f172a;
            color: #e2e8f0;
            border-radius: 10px;
            padding: 12px;
            white-space: pre-wrap;
            font-family: Consolas, Monaco, monospace;
            font-size: 0.8rem;
        }

        @media (max-width: 760px) {
            .grid {
                grid-template-columns: 1fr;
            }

            .field.full {
                grid-column: span 1;
            }
        }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="panel">
        <h1>First-Run Setup</h1>
        <p class="subtitle">
            This instance is not configured yet. Complete the setup once, then future logins will use
            <strong>/admin</strong>.
        </p>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="error-box">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $databaseReady)
            <p class="subtitle">
                Database schema is missing or unreachable. Click initialize to run migrations from the browser.
            </p>

            <form method="POST" action="{{ route('setup.initialize-database') }}">
                @csrf
                <div class="actions">
                    <button type="submit" class="secondary">Initialize Database</button>
                </div>
            </form>

            @if (session('setup_output'))
                <div style="margin-top: 16px;">
                    <pre>{{ session('setup_output') }}</pre>
                </div>
            @endif
        @else
            <form method="POST" action="{{ route('setup.store') }}">
                @csrf

                <div class="grid">
                    <div class="field full">
                        <label for="company_name">Company Name</label>
                        <input id="company_name" name="company_name" value="{{ old('company_name') }}" required>
                    </div>

                    <div class="field">
                        <label for="brand_color">Portal Accent Color</label>
                        <input id="brand_color" name="brand_color" type="color" value="{{ old('brand_color', $defaultBrandColor) }}">
                        <div class="hint">Used for portal and email accent color.</div>
                    </div>

                    <div class="field"></div>

                    <div class="field">
                        <label for="admin_name">Admin Name</label>
                        <input id="admin_name" name="admin_name" value="{{ old('admin_name') }}" required>
                    </div>

                    <div class="field">
                        <label for="admin_email">Admin Email</label>
                        <input id="admin_email" name="admin_email" type="email" value="{{ old('admin_email') }}" required>
                    </div>

                    <div class="field">
                        <label for="admin_username">Admin Username (Optional)</label>
                        <input id="admin_username" name="admin_username" value="{{ old('admin_username') }}">
                    </div>

                    <div class="field">
                        <label for="admin_password">Admin Password</label>
                        <input id="admin_password" name="admin_password" type="password" required>
                        <div class="hint">Minimum 12 characters.</div>
                    </div>

                    <div class="field full">
                        <label for="admin_password_confirmation">Confirm Password</label>
                        <input id="admin_password_confirmation" name="admin_password_confirmation" type="password" required>
                    </div>
                </div>

                <div class="actions">
                    <button type="submit">Complete Setup</button>
                </div>
            </form>
        @endif
    </div>
</div>
</body>
</html>
