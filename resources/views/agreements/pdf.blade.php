<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <title>{{ \App\Models\Agreement::typeLabel($agreement->type) }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 11px; color: #1a1a1a; }
        .letterhead { text-align: center; padding: 6px 0; }
        .letterhead img { height: 50px; }
        .header { background-color: #0e57a4; color: #fff; text-align: center; padding: 9px; font-weight: bold; font-size: 15px; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; }
        .fields td { padding: 5px 8px; vertical-align: top; }
        .fields .label { text-align: right; font-style: italic; font-weight: bold; color: #0e57a4; width: 45%; }
        .fields .value { font-weight: bold; border-bottom: 1px solid #d6e3f0; }
        .auth-text { border-top: 2px solid #ec1c24; border-bottom: 1px solid #f3cdcf; background-color: #fdecec; padding: 8px; color: #d4151c; font-weight: bold; font-style: italic; }
        .items th { background-color: #0e57a4; color: #fff; padding: 7px; text-align: left; font-style: italic; border: 1px solid #0a3f7a; }
        .items td { padding: 7px; border: 1px solid #d6e3f0; }
        .items .item-name { font-weight: bold; color: #0e57a4; }
        .items .item-desc { font-style: italic; color: #555; font-size: 9px; }
        .items .num { text-align: center; }
        .total-row td { text-align: right; font-weight: bold; font-style: italic; color: #0e57a4; }
        .total-amount { text-align: center; font-size: 13px; background-color: #eef5fc; }
        .signature-box { margin-top: 16px; border: 1px dashed #b9c9d9; padding: 8px; }
        .signature-box img { height: 80px; }
    </style>
</head>
<body>
    <div class="letterhead">
        <img src="{{ public_path('img/logo.png') }}" alt="Lunex Telecom">
    </div>
    <div class="header">{{ strtoupper(\App\Models\Agreement::typeLabel($agreement->type)) }}</div>

    <table class="fields">
        <tr>
            <td class="label">ACCOUNT ID:</td>
            <td class="value">{{ $agreement->account_id }}</td>
        </tr>
        <tr>
            <td class="label">OWNER'S NAME:</td>
            <td class="value">{{ $agreement->owner_name }}</td>
        </tr>
        <tr>
            <td class="label">PRIMARY PHONE NUMBER:</td>
            <td class="value">{{ $agreement->phone }}</td>
        </tr>
        <tr>
            <td class="label">BUSINESS NAME:</td>
            <td class="value">{{ $agreement->business_name }}</td>
        </tr>
        <tr>
            <td class="label">LOCATION ADDRESS:</td>
            <td class="value">{{ $agreement->address }}</td>
        </tr>
        <tr>
            <td class="label">LAST 4 DIGITS OF BANK ACCT:</td>
            <td class="value">{{ $agreement->last_4_bank }}</td>
        </tr>
        <tr>
            <td class="label">ACCOUNT OPEN DATE:</td>
            <td class="value">{{ $agreement->form_date->format('m/d/Y') }}</td>
        </tr>
        <tr>
            <td class="label">TRAINING DATE:</td>
            <td class="value">{{ optional($agreement->training_date)->format('m/d/Y') }}</td>
        </tr>
    </table>

    <div class="auth-text">
        I authorize Lunex Telecom, Inc. to initiate debit ACH to the account ending with {{ $agreement->authorization_digits }} (xxxx).
    </div>

    <table class="items" style="margin-top: 10px;">
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Rate</th>
                <th class="num">Quantity</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($agreement->items ?? [] as $item)
                <tr>
                    <td class="item-name">{{ $item['name'] }}</td>
                    <td class="num">{{ $item['rate'] > 0 ? '$' . number_format($item['rate'], 2) : 'Free' }}</td>
                    <td class="num">{{ $item['qty'] }}</td>
                    <td class="num">${{ number_format($item['total'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="3">TOTAL AMOUNT</td>
                <td class="total-amount">${{ number_format($agreement->total_amount, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <div class="signature-box">
        <div style="font-weight: bold; color: #0e57a4; text-transform: uppercase; margin-bottom: 4px;">{{ __('Signature') }}</div>
        @if ($signatureDataUri)
            <img src="{{ $signatureDataUri }}" alt="{{ __('Signature') }}">
        @endif
        <div style="font-size: 9px; color: #555; margin-top: 4px;">{{ __('Signed on :date', ['date' => $agreement->signed_at->format('m/d/Y H:i')]) }}@if ($agreement->signed_ip) &nbsp;{{ __('from IP :ip', ['ip' => $agreement->signed_ip]) }}@endif</div>
    </div>

    <div style="font-size: 8px; color: #999; margin-top: 10px; text-align: center;">
        {{ __('Link generated by :name on :date', ['name' => $agreement->creator?->name ?? '—', 'date' => $agreement->created_at->format('m/d/Y H:i')]) }}
    </div>
</body>
</html>
