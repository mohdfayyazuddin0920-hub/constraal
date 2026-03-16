<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Two-Factor Authentication — Constraal</title>
    <link rel="icon" type="image/png" href="{{ asset('images/constraal_favicon.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/constraal_favicon.png') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="auth-shell">
    <a class="auth-back" href="{{ route('home') }}">Back to site</a>

    <div class="auth-card">
        <div class="auth-brand">
            <img src="{{ asset('images/constraal_logo.png') }}" alt="Constraal">
            <div>
                <h1 class="auth-title">Two-Factor Authentication</h1>
                <p class="auth-subtitle">Enter the code from your authenticator app</p>
            </div>
        </div>

        @if(session('error'))
        <div class="auth-alert">
            {{ session('error') }}
        </div>
        @endif

        @if($errors->any())
        <div class="auth-alert">
            {{ $errors->first() }}
        </div>
        @endif

        <form class="auth-form" method="POST" action="{{ route('account.customer.2fa.verify.post') }}">
            @csrf

            <div>
                <label class="auth-label" for="code">Verification Code</label>
                <input
                    type="text"
                    class="auth-input"
                    id="code"
                    name="code"
                    placeholder="Enter 6-digit code or recovery code"
                    autocomplete="one-time-code"
                    inputmode="numeric"
                    required
                    autofocus>
            </div>

            <button type="submit" class="auth-button">Verify</button>
        </form>

        <p class="auth-meta" style="margin-top: 16px;">
            Lost your authenticator? Use a <strong>recovery code</strong> instead.
        </p>

        <p class="auth-meta">
            <a class="auth-link" href="{{ route('account.customer.login') }}">Back to login</a>
        </p>
    </div>
</body>

</html>