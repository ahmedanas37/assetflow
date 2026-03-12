<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Asset Delivery Receipt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 12mm;
            color: #111;
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
            text-decoration: none;
        }
        .btn.secondary {
            background: #fff;
            color: #111;
        }
        .receipt {
            border: 1px solid #ddd;
            padding: 8mm;
            margin-bottom: 10mm;
        }
        .receipt-header {
            display: flex;
            justify-content: space-between;
            gap: 6mm;
            align-items: flex-start;
        }
        .title {
            font-size: 16pt;
            font-weight: bold;
        }
        .subtitle {
            font-size: 9pt;
            color: #555;
        }
        .tag {
            font-size: 14pt;
            font-weight: bold;
            margin-top: 3mm;
            font-family: "Courier New", Courier, monospace;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            white-space: nowrap;
            display: inline-block;
            padding: 1.5mm 3mm;
            border: 1px solid #111;
            border-radius: 2mm;
            background: #f8f8f8;
        }
        .section {
            margin-top: 6mm;
        }
        .section-title {
            font-size: 9pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: #444;
            margin-bottom: 3mm;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 4mm;
        }
        .custom-grid {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
        .field {
            font-size: 9pt;
            line-height: 1.5;
        }
        .field-label {
            font-size: 7.5pt;
            color: #666;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }
        .field-value {
            font-weight: 600;
        }
        .qr {
            width: 30mm;
            height: 30mm;
        }
        .qr svg,
        .qr img {
            width: 100%;
            height: 100%;
            display: block;
        }
        .checklist {
            display: flex;
            gap: 6mm;
            flex-wrap: wrap;
            font-size: 9pt;
        }
        .checkbox {
            display: inline-block;
            width: 4mm;
            height: 4mm;
            border: 1px solid #111;
            margin-right: 2mm;
            vertical-align: middle;
        }
        .signature-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8mm;
            margin-top: 8mm;
        }
        .signature-line {
            border-top: 1px solid #333;
            margin-top: 10mm;
        }
        .signature-label {
            font-size: 8pt;
            color: #666;
            margin-top: 2mm;
        }
        .acknowledge {
            font-size: 8.5pt;
            color: #555;
            margin-top: 4mm;
        }
        .page-break {
            page-break-after: always;
        }
        @media print {
            body {
                margin: 8mm;
            }
            .toolbar {
                display: none;
            }
            .receipt {
                page-break-after: always;
            }
            .receipt:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    @php
        $settings = app(\App\Services\PortalSettings::class);
        $company = $settings->companyName();
        $product = $settings->productName();
    @endphp
    <div class="toolbar">
        <div class="toolbar-actions">
            <button class="btn" type="button" onclick="window.print()">Print receipt</button>
            <a class="btn secondary" href="{{ \App\Filament\Resources\AssetResource::getUrl() }}">Back to Assets</a>
        </div>
    </div>

    @foreach ($receipts as $receipt)
        <div class="receipt {{ $loop->last ? '' : 'page-break' }}">
            <div class="receipt-header">
                <div>
                    <div class="title">Asset Delivery Receipt</div>
                    <div class="subtitle">{{ $company }} · {{ $product }}</div>
                    <div class="tag">{{ $receipt['asset_tag'] }}</div>
                </div>
                <div class="qr">
                    @if (str_starts_with($receipt['qr'] ?? '', 'data:'))
                        <img src="{{ $receipt['qr'] }}" alt="QR code for {{ $receipt['asset_tag'] }}">
                    @else
                        {!! $receipt['qr'] !!}
                    @endif
                </div>
            </div>

            <div class="section">
                <div class="section-title">Asset Details</div>
                <div class="info-grid">
                    <div class="field">
                        <div class="field-label">Model</div>
                        <div class="field-value">{{ $receipt['model'] ?? '-' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Category</div>
                        <div class="field-value">{{ $receipt['category'] ?? '-' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Serial</div>
                        <div class="field-value">{{ $receipt['serial'] ?? '-' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Home Location</div>
                        <div class="field-value">{{ $receipt['location'] ?? '-' }}</div>
                    </div>
                </div>
            </div>

            @if (! empty($receipt['custom_fields']))
                <div class="section">
                    <div class="section-title">Custom Fields</div>
                    <div class="info-grid custom-grid">
                        @foreach ($receipt['custom_fields'] as $field)
                            <div class="field">
                                <div class="field-label">{{ $field['label'] }}</div>
                                <div class="field-value">{{ $field['value'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="section">
                <div class="section-title">Delivery Details</div>
                <div class="info-grid">
                    <div class="field">
                        <div class="field-label">Assigned To</div>
                        <div class="field-value">{{ $receipt['assigned_to'] ?? '-' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Assignment Type</div>
                        <div class="field-value">{{ $receipt['assigned_type'] ?? '-' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">System / Cubicle</div>
                        <div class="field-value">{{ $receipt['assigned_label'] ?? '-' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Assigned By</div>
                        <div class="field-value">{{ $receipt['assigned_by'] ?? '-' }}</div>
                    </div>
                    <div class="field">
                        <div class="field-label">Assigned At</div>
                        <div class="field-value">
                            {{ $receipt['assigned_at'] ? $receipt['assigned_at']->format('d M Y') : '-' }}
                        </div>
                    </div>
                    <div class="field">
                        <div class="field-label">Due At</div>
                        <div class="field-value">
                            {{ $receipt['due_at'] ? $receipt['due_at']->format('d M Y') : '-' }}
                        </div>
                    </div>
                </div>
                <div class="acknowledge">
                    Verify delivery by scanning the QR code or opening: {{ $receipt['url'] ?? '' }}
                </div>
            </div>

            <div class="section">
                <div class="section-title">Condition Checklist</div>
                <div class="checklist">
                    <div><span class="checkbox"></span>Good</div>
                    <div><span class="checkbox"></span>Fair</div>
                    <div><span class="checkbox"></span>Damaged</div>
                    <div><span class="checkbox"></span>Accessories included</div>
                </div>
            </div>

            <div class="section">
                <div class="section-title">Acknowledgement</div>
                <div class="acknowledge">
                    I confirm receipt of the asset listed above and agree to return it upon request or by the due date.
                </div>
                <div class="signature-grid">
                    <div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Received By (Name & Signature)</div>
                    </div>
                    <div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Delivered By (Name & Signature)</div>
                    </div>
                    <div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Date</div>
                    </div>
                    <div>
                        <div class="signature-line"></div>
                        <div class="signature-label">Department / Cost Center</div>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</body>
</html>
