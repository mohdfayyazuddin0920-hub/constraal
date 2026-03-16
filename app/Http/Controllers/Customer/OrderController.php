<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Order;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display customer orders
     */
    public function index()
    {
        /** @var User $user */
        $user = Auth::user();
        $orders = $user->orders()->paginate(10);

        return view('customer.orders.index', ['orders' => $orders]);
    }

    /**
     * Show order details
     */
    public function show(Order $order)
    {
        // Check if order belongs to user
        if ($order->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        return view('customer.orders.show', ['order' => $order]);
    }

    /**
     * Download order invoice as PDF
     */
    public function downloadInvoice(Order $order)
    {
        // Check if order belongs to user
        if ($order->user_id !== Auth::id()) {
            return redirect()->back()->with('error', 'Unauthorized');
        }

        /** @var User $user */
        $user = Auth::user();

        // Try to find an associated invoice, otherwise build one from order data
        $invoice = Invoice::where('user_id', $user->id)->first();

        if ($invoice) {
            $pdf = Pdf::loadView('customer.billing.invoice-pdf', [
                'invoice' => $invoice,
                'user' => $user,
            ]);
            return $pdf->download('Order-' . $order->order_number . '.pdf');
        }

        // Build a virtual invoice object from order data
        $invoiceData = (object) [
            'id' => $order->id,
            'invoice_number' => 'ORD-' . $order->order_number,
            'invoice_date' => $order->created_at,
            'due_date' => null,
            'amount' => $order->total_amount,
            'status' => $order->status === 'completed' ? 'paid' : 'unpaid',
            'description' => 'Order #' . $order->order_number,
            'created_at' => $order->created_at,
        ];

        $pdf = Pdf::loadView('customer.billing.invoice-pdf', [
            'invoice' => $invoiceData,
            'user' => $user,
        ]);

        return $pdf->download('Order-' . $order->order_number . '.pdf');
    }
}
