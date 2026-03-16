@extends('customer.layouts.app')

@section('title', 'Security')
@section('page-title', 'Security Settings')
@section('icon', 'bi-shield-lock')

@section('content')
<div class="row">
    <div class="col-lg-8 offset-lg-2">
        <!-- Security Features -->
        <div class="card mb-4">
            <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px;">
                <h5 class="card-title mb-0">Security Features</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <div class="p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Two-Factor Authentication</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                                        {{ $twoFactorEnabled ? 'Enabled' : 'Disabled' }}
                                    </p>
                                </div>
                                @if(!$twoFactorEnabled)
                                <a href="{{ route('account.customer.security.2fa-setup') }}" class="btn btn-sm btn-success">
                                    <i class="bi bi-shield-check"></i> Setup
                                </a>
                                @else
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#disable2faModal">
                                    Disable
                                </button>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-3">
                        <div class="p-3 border rounded">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Password Status</h6>
                                    <p class="text-muted mb-0" style="font-size: 0.9rem;">
                                        Last changed {{ $user->updated_at->diffForHumans() }}
                                    </p>
                                </div>
                                <a href="{{ route('account.customer.security.change-password') }}" class="btn btn-sm btn-primary">Change</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="divider"></div>

                <form method="POST" action="{{ route('account.customer.security.logout-other-sessions') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger">
                        <i class="bi bi-box-arrow-left"></i> Log Out All Other Sessions
                    </button>
                </form>
            </div>
        </div>

        <!-- Login Activity -->
        <div class="card">
            <div class="card-header" style="background-color: #f8f9fa; border-bottom: 1px solid #dee2e6; padding: 15px;">
                <h5 class="card-title mb-0">Login Activity</h5>
            </div>
            <div class="card-body p-0">
                @if($loginActivity->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead style="background-color: #f8f9fa;">
                            <tr>
                                <th>Date & Time</th>
                                <th>Device Info</th>
                                <th>IP Address</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($loginActivity as $activity)
                            <tr>
                                <td>{{ $activity->created_at->format('M d, Y H:i') }}</td>
                                <td><small class="text-muted">{{ \Illuminate\Support\Str::limit($activity->user_agent, 50) }}</small></td>
                                <td><code>{{ $activity->ip_address }}</code></td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox" style="font-size: 2rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No login activity</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Disable 2FA Modal -->
@if($twoFactorEnabled)
<div class="modal fade" id="disable2faModal" tabindex="-1" aria-labelledby="disable2faModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('account.customer.security.disable-2fa') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="disable2faModalLabel">Disable Two-Factor Authentication</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Enter your password to confirm disabling two-factor authentication.</p>
                    <div class="mb-3">
                        <label class="form-label" for="disable-2fa-password">Password</label>
                        <input type="password" class="form-control" id="disable-2fa-password" name="password" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Disable 2FA</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection