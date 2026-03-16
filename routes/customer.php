<?php

use App\Http\Controllers\Customer\{
    ActivityController,
    AuthController,
    BillingController,
    DashboardController,
    NotificationController,
    OrderController,
    PrivacyController,
    ProfileController,
    SecurityController,
    ServiceController,
    SettingsController,
    SupportController
};
use Illuminate\Support\Facades\Route;

// Guest routes (no authentication required)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('customer.login');
    Route::post('/login', [AuthController::class, 'login'])->name('customer.login.post')->middleware('throttle:5,1');
    Route::get('/signup', [AuthController::class, 'showSignup'])->name('customer.signup');
    Route::post('/signup', [AuthController::class, 'signup'])->name('customer.signup.post')->middleware('throttle:5,1');
    Route::get('/reset-password', [AuthController::class, 'showResetPassword'])->name('customer.reset-password');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('customer.reset-password.post')->middleware('throttle:3,1');
    Route::get('/reset-password/confirm', [AuthController::class, 'showNewPassword'])->name('customer.reset-password.confirm');
    Route::post('/reset-password/confirm', [AuthController::class, 'setNewPassword'])->name('customer.reset-password.confirm.post')->middleware('throttle:5,1');

    // 2FA verification (user is authenticated but needs to complete 2FA)
    Route::get('/2fa/verify', [AuthController::class, 'showTwoFactor'])->name('customer.2fa.verify')->withoutMiddleware('guest');
    Route::post('/2fa/verify', [AuthController::class, 'verifyTwoFactor'])->name('customer.2fa.verify.post')->middleware('throttle:5,1')->withoutMiddleware('guest');
});

// Protected routes (authentication required)
Route::middleware('customer.auth')->group(function () {
    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('customer.dashboard');

    // Profile Management
    Route::get('/profile', [ProfileController::class, 'show'])->name('customer.profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('customer.profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('customer.profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'showChangePassword'])->name('customer.profile.change-password');
    Route::post('/profile/change-password', [ProfileController::class, 'updatePassword'])->name('customer.profile.update-password');

    // Security
    Route::get('/security', [SecurityController::class, 'index'])->name('customer.security.index');
    Route::get('/security/change-password', [SecurityController::class, 'showChangePassword'])->name('customer.security.change-password');
    Route::post('/security/change-password', [SecurityController::class, 'updatePassword'])->name('customer.security.update-password');
    Route::get('/security/2fa/setup', [SecurityController::class, 'showTwoFactorSetup'])->name('customer.security.2fa-setup');
    Route::post('/security/2fa/enable', [SecurityController::class, 'enableTwoFactor'])->name('customer.security.enable-2fa');
    Route::post('/security/2fa/disable', [SecurityController::class, 'disableTwoFactor'])->name('customer.security.disable-2fa');
    Route::post('/security/logout-other-sessions', [SecurityController::class, 'logoutOtherSessions'])->name('customer.security.logout-other-sessions');

    // Billing
    Route::prefix('billing')->group(function () {
        Route::get('/', [BillingController::class, 'index'])->name('customer.billing.index');
        Route::get('/subscriptions', [BillingController::class, 'subscriptions'])->name('customer.billing.subscriptions');
        Route::post('/subscriptions/{subscription}/change-plan', [BillingController::class, 'changePlan'])->name('customer.billing.change-plan');
        Route::post('/subscriptions/{subscription}/cancel', [BillingController::class, 'cancelSubscription'])->name('customer.billing.cancel');

        Route::get('/payment-methods', [BillingController::class, 'paymentMethods'])->name('customer.billing.payment-methods');
        Route::post('/payment-methods', [BillingController::class, 'addPaymentMethod'])->name('customer.billing.add-payment-method');
        Route::delete('/payment-methods/{paymentMethod}', [BillingController::class, 'removePaymentMethod'])->name('customer.billing.remove-payment-method');
        Route::post('/payment-methods/{paymentMethod}/set-default', [BillingController::class, 'setDefaultPaymentMethod'])->name('customer.billing.set-default-payment-method');

        Route::get('/invoices', [BillingController::class, 'invoices'])->name('customer.billing.invoices');
        Route::get('/invoices/{invoice}', [BillingController::class, 'viewInvoice'])->name('customer.billing.invoice-detail');
        Route::get('/invoices/{invoice}/download', [BillingController::class, 'downloadInvoice'])->name('customer.billing.download-invoice');
    });

    // Orders
    Route::get('/orders', [OrderController::class, 'index'])->name('customer.orders.index');
    Route::get('/orders/{order}', [OrderController::class, 'show'])->name('customer.orders.show');
    Route::get('/orders/{order}/invoice', [OrderController::class, 'downloadInvoice'])->name('customer.orders.download-invoice');

    // Services
    Route::get('/services', [ServiceController::class, 'index'])->name('customer.services.index');

    // Notifications
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('customer.notifications.index');
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead'])->name('customer.notifications.mark-read');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead'])->name('customer.notifications.mark-all-read');
        Route::delete('/{id}', [NotificationController::class, 'destroy'])->name('customer.notifications.destroy');
        Route::get('/preferences', [NotificationController::class, 'preferences'])->name('customer.notifications.preferences');
        Route::post('/preferences', [NotificationController::class, 'updatePreferences'])->name('customer.notifications.update-preferences');
    });

    // Support
    Route::prefix('support')->group(function () {
        Route::get('/', [SupportController::class, 'index'])->name('customer.support.index');
        Route::get('/create', [SupportController::class, 'create'])->name('customer.support.create');
        Route::post('/', [SupportController::class, 'store'])->name('customer.support.store');
        Route::get('/{ticket}', [SupportController::class, 'show'])->name('customer.support.show');
        Route::post('/{ticket}/reply', [SupportController::class, 'reply'])->name('customer.support.reply');
        Route::post('/{ticket}/close', [SupportController::class, 'close'])->name('customer.support.close');
    });

    // Activity
    Route::get('/activity', [ActivityController::class, 'index'])->name('customer.activity.index');

    // Privacy
    Route::prefix('privacy')->group(function () {
        Route::get('/', [PrivacyController::class, 'index'])->name('customer.privacy.index');
        Route::get('/download-data', [PrivacyController::class, 'downloadData'])->name('customer.privacy.download-data');
        Route::get('/delete-account', [PrivacyController::class, 'showDeleteAccount'])->name('customer.privacy.delete-account');
        Route::post('/delete-account', [PrivacyController::class, 'deleteAccount'])->name('customer.privacy.delete-account.post');
        Route::post('/update-preferences', [PrivacyController::class, 'updatePrivacy'])->name('customer.privacy.update-preferences');
    });

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])->name('customer.settings.index');
        Route::put('/', [SettingsController::class, 'update'])->name('customer.settings.update');
        Route::get('/email-preferences', [SettingsController::class, 'emailPreferences'])->name('customer.settings.email-preferences');
        Route::post('/email-preferences', [SettingsController::class, 'updateEmailPreferences'])->name('customer.settings.update-email-preferences');
    });

    // Logout
    Route::post('/logout', [AuthController::class, 'logout'])->name('customer.logout');
});
