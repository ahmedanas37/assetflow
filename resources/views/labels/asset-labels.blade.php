<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Asset Labels</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 12mm;
        }
        :root {
            --label-width: 70mm;
            --label-height: 36mm;
            --label-gap: 6mm;
        }
        .toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8mm;
            margin-bottom: 8mm;
        }
        .toolbar-actions {
            display: flex;
            gap: 3mm;
            flex-wrap: wrap;
        }
        .btn {
            border: 1px solid #111;
            background: #111;
            color: #fff;
            padding: 2.5mm 5mm;
            border-radius: 999px;
            font-size: 9pt;
            cursor: pointer;
        }
        .btn.secondary {
            background: #fff;
            color: #111;
        }
        .toolbar-note {
            font-size: 8.5pt;
            color: #555;
            max-width: 120mm;
        }
        .label-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: var(--label-gap);
        }
        .label {
            border: 1px solid #ddd;
            padding: 6mm;
            min-height: var(--label-height);
            break-inside: avoid;
            display: grid;
            grid-template-columns: 1fr 24mm;
            gap: 3mm;
            align-items: start;
        }
        .label-info {
            min-width: 0;
        }
        .brand {
            font-size: 6.5pt;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: #111;
            line-height: 1.1;
            margin-bottom: 0.8mm;
        }
        .brand-sub {
            font-size: 6pt;
            color: #555;
            line-height: 1.1;
            margin-bottom: 2mm;
        }
        .tag {
            font-size: 11pt;
            font-weight: bold;
            margin-bottom: 2mm;
            font-family: "Courier New", Courier, monospace;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
            display: inline-block;
            padding: 1mm 2mm;
            border: 1px solid #111;
            border-radius: 2mm;
            background: #f8f8f8;
        }
        .meta {
            font-size: 8pt;
            color: #333;
            line-height: 1.4;
            word-break: break-word;
        }
        .custom {
            font-size: 7.5pt;
            color: #444;
            margin-top: 2mm;
            line-height: 1.35;
        }
        .verify {
            font-size: 8pt;
            color: #666;
            margin-top: 2mm;
        }
        .verify a {
            color: #111;
            text-decoration: underline;
        }
        .qr {
            margin-top: 2mm;
            width: 24mm;
            height: 24mm;
        }
        .qr svg {
            width: 100%;
            height: 100%;
        }
        .qr img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .screen-only {
            display: block;
        }
        .print-only {
            display: none;
        }
        @media print {
            body {
                margin: 8mm;
            }
            .toolbar,
            .screen-only {
                display: none;
            }
            .print-only {
                display: block;
            }
        }
    </style>
</head>
<body>
    @php
        $settings = app(\App\Services\PortalSettings::class);
        $product = $settings->productName();
        $company = $settings->companyName();
    @endphp
    <div class="toolbar">
        <div class="toolbar-actions">
            <button class="btn" type="button" onclick="window.print()">Print labels</button>
            @if (! empty($receiptUrl))
                <a class="btn secondary" href="{{ $receiptUrl }}" target="_blank" rel="noopener">Delivery receipt</a>
            @endif
            <a class="btn secondary" href="{{ \App\Filament\Resources\AssetResource::getUrl() }}">Back to Assets</a>
        </div>
        <div class="toolbar-note">
            Verify delivery by scanning the QR code to open the asset record and confirm the assignment details.
        </div>
    </div>
    <div class="label-grid">
        @foreach ($labels as $label)
            <div class="label">
                <div class="label-info">
                    <div class="brand">{{ $product }}</div>
                    <div class="brand-sub">Property of {{ $company }}</div>
                    <div class="tag">{{ $label['asset_tag'] }}</div>
                    <div class="meta">{{ $label['model'] ?? '-' }}</div>
                    <div class="meta">Serial: {{ $label['serial'] ?? '-' }}</div>
                    @if (! empty($label['custom_fields']))
                        <div class="custom">
                            @foreach (array_slice($label['custom_fields'], 0, 2) as $customField)
                                <div>{{ $customField }}</div>
                            @endforeach
                        </div>
                    @endif
                    <div class="verify screen-only">
                        Verify: <a href="{{ $label['url'] }}">Open asset record</a>
                    </div>
                    <div class="verify print-only">
                        Verify by scanning the QR code.
                    </div>
                </div>
                <div class="qr">
                    @if (str_starts_with($label['qr'] ?? '', 'data:'))
                        <img src="{{ $label['qr'] }}" alt="QR code for {{ $label['asset_tag'] }}">
                    @else
                        {!! $label['qr'] !!}
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</body>
</html>
