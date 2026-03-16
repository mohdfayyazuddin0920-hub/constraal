@extends('customer.layouts.app')

@section('title', 'Setup Two-Factor Authentication')
@section('page-title', 'Two-Factor Authentication Setup')
@section('icon', 'bi-shield-check')

@section('content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        @endif

        <!-- Step 1: Scan QR Code -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px;">
                <h5 class="card-title mb-0">Step 1: Scan QR Code</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Scan this QR code with your authenticator app (Google Authenticator, Authy, Microsoft Authenticator, etc.)
                </p>

                <div class="text-center mb-4">
                    <div class="d-inline-block p-3 bg-white border rounded">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data={{ urlencode($qrUri) }}"
                            alt="2FA QR Code"
                            width="200"
                            height="200"
                            style="image-rendering: pixelated;">
                    </div>
                </div>

                <p class="text-muted mb-2" style="font-size: 0.9rem;">Can't scan the QR code? Enter this key manually:</p>
                <div class="p-3 bg-light border rounded text-center mb-0">
                    <code style="font-size: 1.1rem; letter-spacing: 2px; word-break: break-all;">{{ $secret }}</code>
                </div>
            </div>
        </div>

        <!-- Step 2: Save Recovery Codes -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px;">
                <h5 class="card-title mb-0">Step 2: Save Recovery Codes</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Save these recovery codes in a safe place. You can use them to access your account if you lose your authenticator device.
                </p>
                <div class="p-3 bg-light border rounded mb-3">
                    <div class="row">
                        @foreach($recoveryCodes as $code)
                        <div class="col-md-6 mb-1">
                            <code style="font-size: 0.95rem;">{{ $code }}</code>
                        </div>
                        @endforeach
                    </div>
                </div>
                <p class="text-danger mb-0" style="font-size: 0.85rem;">
                    <i class="bi bi-exclamation-triangle"></i>
                    These codes will not be shown again. Each code can only be used once.
                </p>
            </div>
        </div>

        <!-- Step 3: Verify -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px;">
                <h5 class="card-title mb-0">Step 3: Verify</h5>
            </div>
            <div class="card-body">
                <p class="text-muted mb-3">
                    Enter the 6-digit code from your authenticator app to confirm setup.
                </p>
                <form method="POST" action="{{ route('account.customer.security.enable-2fa') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label" for="code">Verification Code</label>
                        <input type="text"
                            class="form-control"
                            id="code"
                            name="code"
                            placeholder="000000"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            inputmode="numeric"
                            autocomplete="one-time-code"
                            required
                            style="max-width: 200px; font-size: 1.2rem; letter-spacing: 4px; text-align: center;">
                        @error('code')
                        <div class="text-danger mt-1" style="font-size: 0.85rem;">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Enable Two-Factor Authentication
                        </button>
                        <a href="{{ route('account.customer.security.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection