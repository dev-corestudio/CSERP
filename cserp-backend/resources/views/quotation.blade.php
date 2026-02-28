<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Wycena</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif; /* DejaVu supports Polish characters */
            font-size: 12px;
            color: #333;
        }
        .header {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }
        .company-info {
            float: left;
            width: 50%;
        }
        .customer-info {
            float: right;
            width: 40%;
            text-align: right;
        }
        .title-block {
            clear: both;
            text-align: center;
            margin-bottom: 30px;
            padding-top: 20px;
        }
        h1 {
            font-size: 18px;
            margin: 0;
            color: #2c3e50;
        }
        .meta {
            font-size: 11px;
            color: #666;
            margin-top: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th {
            background-color: #f8f9fa;
            border-bottom: 2px solid #ddd;
            padding: 8px;
            text-align: left;
            font-weight: bold;
            font-size: 11px;
            text-transform: uppercase;
        }
        td {
            border-bottom: 1px solid #eee;
            padding: 8px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-bold { font-weight: bold; }

        .totals {
            width: 40%;
            float: right;
            margin-top: 10px;
        }
        .totals-row {
            padding: 5px 0;
            border-bottom: 1px solid #eee;
        }
        .totals-row.grand-total {
            border-top: 2px solid #333;
            border-bottom: none;
            font-size: 14px;
            font-weight: bold;
            margin-top: 10px;
            padding-top: 10px;
        }
        .clear { clear: both; }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 30px;
            text-align: center;
            font-size: 10px;
            color: #999;
            border-top: 1px solid #eee;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-info">
            <strong>SMARTPOS sp. z o.o.</strong><br>
            ul.  Fabryczna 2<br>
            84-200 Wejherowo<br>
            NIP: 5882490752<br>
            wwww.smart-pos.pl
        </div>
        <div class="customer-info">
            <strong>Nabywca:</strong><br>
            {{ $customer->name }}<br>
            @if($customer->nip) NIP: {{ $customer->nip }}<br> @endif
            @if($customer->address) {{ $customer->address }}<br> @endif
            {{ $customer->email }}
        </div>
        <div class="clear"></div>
    </div>

    <div class="title-block">
        <h1>OFERTA CENOWA</h1>
        <div class="meta">
            Numer zamówienia: {{ $order->full_order_number }}<br>
            Wariant: {{ $variant->name }} (Wersja wyceny: {{ $quotation->version_number }})<br>
            Data: {{ $quotation->created_at->format('Y-m-d') }}
        </div>
    </div>

    @foreach($quotation->items as $item)
        @if($item->materials->count() > 0)
            <h3>Materiały</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">Lp</th>
                        <th style="width: 45%">Nazwa</th>
                        <th style="width: 15%" class="text-right">Ilość</th>
                        <th style="width: 10%" class="text-center">J.m.</th>
                        <th style="width: 25%" class="text-right">Wartość</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->materials as $index => $mat)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                {{ $mat->assortmentItem->name ?? 'Materiał' }}
                                @if($mat->assortmentItem->category)<br><small style="color:#777">{{ $mat->assortmentItem->category }}</small>@endif
                            </td>
                            <td class="text-right">{{ $mat->quantity }}</td>
                            <td class="text-center">{{ $mat->unit }}</td>
                            <td class="text-right">{{ number_format($mat->total_cost, 2, ',', ' ') }} PLN</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        @if($item->services->count() > 0)
            <h3>Usługi</h3>
            <table>
                <thead>
                    <tr>
                        <th style="width: 5%">Lp</th>
                        <th style="width: 45%">Nazwa</th>
                        <th style="width: 15%" class="text-right">Ilość (h)</th>
                        <th style="width: 10%" class="text-center">J.m.</th>
                        <th style="width: 25%" class="text-right">Wartość</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($item->services as $index => $srv)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $srv->assortmentItem->name ?? 'Usługa' }}</td>
                            <td class="text-right">{{ $srv->estimated_time_hours }}</td>
                            <td class="text-center">{{ $srv->unit }}</td>
                            <td class="text-right">{{ number_format($srv->total_cost, 2, ',', ' ') }} PLN</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @endforeach

    <div class="totals">
        <div class="totals-row">
            <span style="float:left">Suma Netto:</span>
            <span style="float:right">{{ number_format($quotation->total_net, 2, ',', ' ') }} PLN</span>
            <div class="clear"></div>
        </div>
        <div class="totals-row">
            <span style="float:left">VAT (23%):</span>
            <span style="float:right">{{ number_format($quotation->total_gross - $quotation->total_net, 2, ',', ' ') }} PLN</span>
            <div class="clear"></div>
        </div>
        <div class="totals-row grand-total">
            <span style="float:left">Do zapłaty (Brutto):</span>
            <span style="float:right">{{ number_format($quotation->total_gross, 2, ',', ' ') }} PLN</span>
            <div class="clear"></div>
        </div>
    </div>

    <div class="clear"></div>

    @if($quotation->notes)
        <div style="margin-top: 40px; padding: 10px; background-color: #f9f9f9; border: 1px solid #eee;">
            <strong>Uwagi:</strong><br>
            {!! nl2br(e($quotation->notes)) !!}
        </div>
    @endif

    <div class="footer">
        Dokument wygenerowany elektronicznie z systemu CSERP dnia {{ date('Y-m-d H:i') }}
    </div>
</body>
</html>
