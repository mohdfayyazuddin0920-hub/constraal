<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 13px;
            color: #1a1a2e;
            line-height: 1.5;
        }

        .invoice-container {
            padding: 40px;
        }

        .header {
            display: table;
            width: 100%;
            margin-bottom: 40px;
        }

        .header-left,
        .header-right {
            display: table-cell;
            vertical-align: top;
        }

        .header-right {
            text-align: right;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #1a1a2e;
        }

        .company-tagline {
            font-size: 11px;
            color: #666;
        }

        .invoice-title {
            font-size: 28px;
            font-weight: bold;
            color: #1a1a2e;
            margin-bottom: 5px;
        }

        .invoice-number {
            font-size: 14px;
            color: #666;
        }

        .meta-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }

        .meta-left,
        .meta-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }

        .meta-right {
            text-align: right;
        }

        .meta-label {
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin-bottom: 3px;
        }

        .meta-value {
            font-size: 13px;
            color: #333;
            margin-bottom: 12px;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .items-table thead th {
            background: #1a1a2e;
            color: #fff;
            padding: 10px 15px;
            text-align: left;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .items-table thead th:last-child {
            text-align: right;
        }

        .items-table tbody td {
            padding: 12px 15px;
            border-bottom: 1px solid #eee;
        }

        .items-table tbody td:last-child {
            text-align: right;
        }

        .items-table tfoot td {
            padding: 12px 15px;
            font-weight: bold;
        }

        .items-table tfoot td:last-child {
            text-align: right;
            font-size: 16px;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-paid {
            background: #d4edda;
            color: #155724;
        }

        .status-unpaid {
            background: #fff3cd;
            color: #856404;
        }

        .status-overdue {
            background: #f8d7da;
            color: #721c24;
        }

        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            font-size: 11px;
            color: #999;
        }
    </style>
</head>

<body>
    <div class="invoice-container">
        <div class="header">
            <div class="header-left">
                <div class="company-name">Constraal</div>
                <div class="company-tagline">Technology & Innovation</div>
            </div>
            <div class="header-right">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $invoice->invoice_number ?? 'INV-' . $invoice->id }}</div>
            </div>
        </div>

        <div class="meta-section">
            <div class="meta-left">
                <div class="meta-label">Bill To</div>
                <div class="meta-value">
                    {{ $user->name }}<br>
                    {{ $user->email }}
                    @if($user->phone)<br>{{ $user->phone }}@endif
                </div>
            </div>
            <div class="meta-right">
                <div class="meta-label">Invoice Date</div>
                <div class="meta-value">{{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('M d, Y') : $invoice->created_at->format('M d, Y') }}</div>

                <div class="meta-label">Due Date</div>
                <div class="meta-value">{{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('M d, Y') : 'N/A' }}</div>

                <div class="meta-label">Status</div>
                <div class="meta-value">
                    <span class="status-badge status-{{ $invoice->status ?? 'unpaid' }}">
                        {{ ucfirst($invoice->status ?? 'pending') }}
                    </span>
                </div>
            </div>
        </div>

        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $invoice->description ?? 'Subscription / Service' }}</td>
                    <td>${{ number_format($invoice->amount ?? 0, 2) }}</td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td><strong>Total</strong></td>
                    <td><strong>${{ number_format($invoice->amount ?? 0, 2) }}</strong></td>
                </tr>
            </tfoot>
        </table>

        <div class="footer">
            <p>Thank you for your business.</p>
            <p>Constraal &mdash; constraal.com</p>
        </div>
    </div>
</body>

</html>