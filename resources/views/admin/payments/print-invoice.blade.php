<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - {{ $payment->payment_number }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            font-size: 14px;
            line-height: 1.6;
            color: #333;
            background: #fff;
            padding: 20px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
        }

        /* Invoice Header */
        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 3px solid #333;
        }

        .company-info h1 {
            font-size: 28px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 5px;
        }

        .company-info p {
            color: #64748b;
            font-size: 14px;
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-title {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }

        .invoice-number {
            font-size: 16px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .invoice-date {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 10px;
        }

        .invoice-date strong {
            font-weight: 600;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            border: 2px solid #333;
        }

        .status-paid {
            background: #e8f5e9;
            color: #1b5e20;
        }

        .status-pending {
            background: #fff9e6;
            color: #663c00;
        }

        .status-cancelled {
            background: #ffebee;
            color: #b71c1c;
        }

        /* Invoice Parties */
        .invoice-parties {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }

        .party-section h3 {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #1e293b;
            margin-bottom: 12px;
            letter-spacing: 0.5px;
        }

        .party-name {
            font-size: 16px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .party-info {
            color: #64748b;
            margin-bottom: 4px;
            font-size: 14px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 6px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 600;
            color: #64748b;
        }

        .info-value {
            color: #1e293b;
        }

        /* Items Table */
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
        }

        .items-table thead {
            background: #f5f5f5;
        }

        .items-table th {
            padding: 12px;
            text-align: left;
            font-weight: 700;
            font-size: 11px;
            text-transform: uppercase;
            color: #333;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #333;
        }

        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            color: #1e293b;
            font-size: 14px;
        }

        .items-table tbody tr:last-child td {
            border-bottom: 2px solid #333;
        }

        .items-table th.text-center,
        .items-table td.text-center {
            text-align: center;
        }

        .items-table th.text-right,
        .items-table td.text-right {
            text-align: right;
        }

        .item-description {
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 4px;
        }

        .item-type {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 500;
        }

        /* Totals */
        .invoice-totals {
            display: flex;
            justify-content: flex-end;
            margin-bottom: 35px;
        }

        .totals-rows {
            width: 350px;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid #ddd;
            font-size: 14px;
        }

        .total-label {
            font-weight: 600;
        }

        .total-value {
            font-weight: 700;
        }

        .grand-total {
            background: #f5f5f5;
            color: #1e293b;
            padding: 14px;
            border-radius: 4px;
            margin-top: 8px;
            border: 2px solid #333 !important;
            font-size: 18px;
            font-weight: 700;
        }

        /* Notes */
        .invoice-notes {
            margin-bottom: 35px;
            padding: 15px;
            background: #f9f9f9;
            border-radius: 4px;
            border-left: 4px solid #333;
        }

        .notes-title {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            color: #333;
            margin-bottom: 8px;
        }

        .notes-content {
            color: #64748b;
            line-height: 1.6;
            font-size: 14px;
        }

        /* Footer */
        .invoice-footer {
            text-align: center;
            padding-top: 30px;
            border-top: 2px solid #333;
        }

        .footer-text {
            font-size: 14px;
            color: #64748b;
            margin-bottom: 30px;
        }

        .signature-section {
            display: inline-block;
            text-align: center;
        }

        .signature-line {
            display: inline-block;
            min-width: 200px;
            border-bottom: 2px solid #333;
            margin-bottom: 8px;
        }

        .signature-label {
            font-size: 11px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        /* Print Styles */
        @media print {
            body {
                padding: 0;
            }

            .invoice-container {
                max-width: 100%;
            }

            @page {
                margin: 1.5cm;
                size: A4;
            }
        }

    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Invoice Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>Educational Center</h1>
                <p>Student Management System</p>
            </div>
            <div class="invoice-meta">
                <div class="invoice-title">INVOICE</div>
                <div class="invoice-number">{{ $payment->payment_number }}</div>
                <div class="invoice-date">
                    <strong>Date:</strong> {{ ($payment->paid_at ?? $payment->created_at)->format('F d, Y') }}
                </div>
                <div class="invoice-status">
                    <span class="status-badge status-{{ $payment->status }}">
                        {{ ucfirst($payment->status) }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Invoice Parties -->
        <div class="invoice-parties">
            <div class="party-section">
                <h3>Bill To:</h3>
                <div class="party-details">
                    <div class="party-name">{{ $payment->student->full_name }}</div>
                    <div class="party-info">Admission #: {{ $payment->student->admission_number }}</div>
                    @if($payment->student->phone)
                        <div class="party-info">Phone: {{ $payment->student->phone }}</div>
                    @endif
                    @if($payment->student->email)
                        <div class="party-info">Email: {{ $payment->student->email }}</div>
                    @endif
                </div>
            </div>

            <div class="party-section">
                <h3>Payment Information:</h3>
                <div class="party-details">
                    <div class="info-row">
                        <span class="info-label">Payment Method:</span>
                        <span class="info-value">{{ $payment->payment_method ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Received By:</span>
                        <span class="info-value">{{ $payment->admin->name ?? '—' }}</span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Date:</span>
                        <span class="info-value">{{ ($payment->paid_at ?? $payment->created_at)->format('F d, Y') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payment Items Table -->
        <table class="items-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-center">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Discount</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment->items as $item)
                    <tr>
                        <td>
                            <div class="item-description">{{ $item->description }}</div>
                            <div class="item-type">{{ $item->item_type === 'subscription' ? 'Subscription' : 'Product' }}</div>
                        </td>
                        <td class="text-center">{{ $item->quantity }}</td>
                        <td class="text-right">${{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-right">${{ number_format($item->discount_value, 2) }}</td>
                        <td class="text-right">${{ number_format($item->total_price, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totals -->
        <div class="invoice-totals">
            <div class="totals-rows">
                <div class="total-row">
                    <span class="total-label">Subtotal:</span>
                    <span class="total-value">${{ number_format($subtotal, 2) }}</span>
                </div>
                <div class="total-row">
                    <span class="total-label">Total Discount:</span>
                    <span class="total-value">${{ number_format($totalDiscount, 2) }}</span>
                </div>
                <div class="total-row grand-total">
                    <span class="total-label">Grand Total:</span>
                    <span class="total-value">${{ number_format($payment->amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Notes -->
        @if($payment->note)
            <div class="invoice-notes">
                <div class="notes-title">Notes:</div>
                <div class="notes-content">{{ $payment->note }}</div>
            </div>
        @endif

        <!-- Footer -->
        <div class="invoice-footer">
            <p class="footer-text">Thank you for your payment!</p>
            <div class="signature-section">
                <div class="signature-line"></div>
                <div class="signature-label">Authorized Signature</div>
            </div>
        </div>
    </div>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
