<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Subscription;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillingController extends Controller
{
    /**
     * Display billing dashboard
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $subscriptions = $user->subscriptions()->get();
        $totalSpent = $user->subscriptions()->sum('total_amount') ?? 0;
        $activeSubscriptions = $subscriptions->where('status', 'active')->count();

        return view('customer.billing.index', [
            'subscriptions' => $subscriptions,
            'totalSpent' => $totalSpent,
            'activeSubscriptions' => $activeSubscriptions,
        ]);
    }

    /**
     * Show subscriptions
     */
    public function subscriptions()
    {
        /** @var User $user */
        $user = Auth::user();
        $subscriptions = $user->subscriptions()->paginate(10);

        return view('customer.billing.subscriptions', ['subscriptions' => $subscriptions]);
    }

    /**
     * Upgrade or downgrade subscription
     */
    public function changePlan(Request $request, Subscription $subscription)
    {
        $request->validate(['plan_id' => 'required|exists:billing_plans,id']);

        // Check if subscription belongs to user
        if ($subscription->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized');
        }

        $subscription->update(['plan_id' => $request->plan_id]);

        /** @var User $user */
        $user = Auth::user();
        $user->activities()->create([
            'action' => 'subscription_changed',
            'description' => 'Subscription plan changed',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Subscription plan updated');
    }

    /**
     * Cancel subscription
     */
    public function cancelSubscription(Request $request, Subscription $subscription)
    {
        // Check if subscription belongs to user
        if ($subscription->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized');
        }

        $subscription->update(['status' => 'cancelled']);

        /** @var User $user */
        $user = Auth::user();
        $user->activities()->create([
            'action' => 'subscription_cancelled',
            'description' => 'Subscription cancelled',
            'ip_address' => $request->ip(),
        ]);

        return back()->with('success', 'Subscription cancelled');
    }

    /**
     * Show payment methods
     */
    public function paymentMethods()
    {
        /** @var User $user */
        $user = Auth::user();
        $paymentMethods = $user->paymentMethods()->get();

        return view('customer.billing.payment-methods', ['paymentMethods' => $paymentMethods]);
    }

    /**
     * Add payment method
     */
    public function addPaymentMethod(Request $request)
    {
        $request->validate([
            'card_number' => 'required|string',
            'card_holder' => 'required|string',
            'expiry' => 'required|string',
            'cvv' => 'required|string',
        ]);

        /** @var User $user */
        $user = Auth::user();
        $paymentMethod = $user->paymentMethods()->create([
            'type' => 'credit_card',
            'card_last_four' => substr($request->card_number, -4),
            'card_holder' => $request->card_holder,
            'expiry' => $request->expiry,
            'is_default' => $request->has('set_default'),
        ]);

        return back()->with('success', 'Payment method added');
    }

    /**
     * Remove payment method
     */
    public function removePaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        // Check if payment method belongs to user
        if ($paymentMethod->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized');
        }

        $paymentMethod->delete();

        return back()->with('success', 'Payment method removed');
    }

    /**
     * Set default payment method
     */
    public function setDefaultPaymentMethod(Request $request, PaymentMethod $paymentMethod)
    {
        // Check if payment method belongs to user
        if ($paymentMethod->user_id !== Auth::id()) {
            return back()->with('error', 'Unauthorized');
        }

        /** @var User $user */
        $user = Auth::user();
        $user->paymentMethods()->update(['is_default' => false]);
        $paymentMethod->update(['is_default' => true]);

        return back()->with('success', 'Default payment method updated');
    }

    /**
     * Show invoices
     */
    public function invoices()
    {
        /** @var User $user */
        $user = Auth::user();
        $invoices = $user->invoices()->paginate(10);

        return view('customer.billing.invoices', ['invoices' => $invoices]);
    }

    /**
     * Download invoice as PDF
     */
    public function downloadInvoice(Invoice $invoice)
    {
        // Check if invoice belongs to user
        if ($invoice->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        /** @var User $user */
        $user = Auth::user();

        $pdf = Pdf::loadView('customer.billing.invoice-pdf', [
            'invoice' => $invoice,
            'user' => $user,
        ]);

        $filename = ($invoice->invoice_number ?? 'INV-' . $invoice->id) . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * View invoice details
     */
    public function viewInvoice(Invoice $invoice)
    {
        // Check if invoice belongs to user
        if ($invoice->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        return view('customer.billing.invoice-detail', ['invoice' => $invoice]);
    }
}
