@php
    $settings = app(\App\Services\PortalSettings::class);
    $assignedTo = $activeAssignment?->assigned_to_name;
    if ($assignedTo && $activeAssignment?->assigned_to_label) {
        $assignedTo .= ' (' . $activeAssignment->assigned_to_label . ')';
    }
    $warrantyStatus = match (true) {
        ! $asset->warranty_end_date => 'Not recorded',
        $asset->warranty_end_date->isPast() => 'Expired ' . $asset->warranty_end_date->format('M d, Y'),
        $asset->warranty_end_date->lte(now()->addDays(30)) => 'Expires soon - ' . $asset->warranty_end_date->format('M d, Y'),
        default => 'Valid until ' . $asset->warranty_end_date->format('M d, Y'),
    };
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $asset->asset_tag }} · Asset Scan</title>
    <style>
        :root { --accent: {{ $settings->brandColor() }}; }
        * { box-sizing: border-box; }
        body { margin: 0; background: #eef2f7; color: #111827; font-family: Arial, sans-serif; }
        main { max-width: 760px; margin: 0 auto; padding: 18px 14px 28px; }
        .hero { background: #fff; border: 1px solid #e5e7eb; border-radius: 16px; padding: 20px; box-shadow: 0 16px 40px rgba(15, 23, 42, .08); }
        .brand { color: #6b7280; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; }
        h1 { margin: 10px 0 8px; font-size: clamp(26px, 8vw, 42px); line-height: 1; overflow-wrap: anywhere; }
        .subtitle { color: #4b5563; font-size: 15px; line-height: 1.5; }
        .status { display: inline-flex; align-items: center; margin-top: 14px; border-radius: 999px; padding: 7px 11px; background: #f3f4f6; font-size: 13px; font-weight: 700; }
        .status.deployable { background: #ecfdf5; color: #065f46; }
        .section { margin-top: 14px; background: #fff; border: 1px solid #e5e7eb; border-radius: 14px; padding: 16px; }
        .section h2 { margin: 0 0 12px; font-size: 16px; }
        .grid { display: grid; grid-template-columns: minmax(0, 1fr); gap: 12px; }
        .field { min-width: 0; }
        .label { color: #6b7280; font-size: 12px; margin-bottom: 3px; }
        .value { font-weight: 700; overflow-wrap: anywhere; }
        .muted { color: #6b7280; font-size: 13px; line-height: 1.45; }
        .actions { display: grid; gap: 10px; margin-top: 14px; }
        .button { display: block; text-align: center; text-decoration: none; border-radius: 10px; padding: 12px 14px; font-weight: 700; border: 1px solid #d1d5db; color: #111827; background: #fff; }
        .button.primary { color: #fff; border-color: var(--accent); background: var(--accent); }
        .copy-row { display: flex; gap: 8px; margin-top: 10px; }
        input { width: 100%; border: 1px solid #d1d5db; border-radius: 10px; padding: 10px; font-size: 13px; }
        button { border: 0; border-radius: 10px; background: #111827; color: #fff; padding: 0 14px; font-weight: 700; }
        @media (min-width: 640px) {
            main { padding-top: 28px; }
            .grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .actions { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        }
    </style>
</head>
<body>
<main>
    <section class="hero">
        <div class="brand">{{ $settings->companyName() }} · {{ $settings->productName() }}</div>
        <h1>{{ $asset->asset_tag }}</h1>
        <div class="subtitle">{{ $asset->assetModel?->name ?? 'Unknown model' }}</div>
        <div class="status {{ $asset->statusLabel?->deployable ? 'deployable' : '' }}">
            {{ $asset->statusLabel?->name ?? 'Unknown Status' }}
        </div>
    </section>

    <section class="section">
        <h2>Asset Details</h2>
        <div class="grid">
            <div class="field"><div class="label">Category</div><div class="value">{{ $asset->category?->name ?? '-' }}</div></div>
            @if ($canManage)
                <div class="field"><div class="label">Serial</div><div class="value">{{ $asset->serial ?? '-' }}</div></div>
                <div class="field"><div class="label">Home Location</div><div class="value">{{ $asset->location?->name ?? '-' }}</div></div>
                <div class="field"><div class="label">Warranty</div><div class="value">{{ $warrantyStatus }}</div></div>
                <div class="field"><div class="label">Last Updated</div><div class="value">{{ $asset->updated_at?->format('M d, Y h:i A') ?? '-' }}</div></div>
            @endif
        </div>
    </section>

    <section class="section">
        <h2>Assignment</h2>
        @if ($activeAssignment && $canManage)
            <div class="grid">
                <div class="field"><div class="label">Assigned To</div><div class="value">{{ $assignedTo ?? '-' }}</div></div>
                <div class="field"><div class="label">Assigned By</div><div class="value">{{ $activeAssignment->assignedBy?->name ?? '-' }}</div></div>
                <div class="field"><div class="label">Assigned At</div><div class="value">{{ $activeAssignment->assigned_at?->format('M d, Y') ?? '-' }}</div></div>
                <div class="field"><div class="label">Due At</div><div class="value">{{ $activeAssignment->due_at?->format('M d, Y') ?? '-' }}</div></div>
                <div class="field"><div class="label">Receipt Accepted</div><div class="value">{{ $activeAssignment->accepted_at ? $activeAssignment->accepted_at->format('M d, Y h:i A') : 'Not accepted' }}</div></div>
            </div>
        @elseif ($activeAssignment)
            <div class="muted">This asset is currently assigned. Login to view assignment details.</div>
        @else
            <div class="muted">This asset is not currently assigned.</div>
        @endif
    </section>

    <section class="section">
        <h2>Actions</h2>
        @if ($canManage)
            <div class="actions">
                @if ($adminUrl)
                    <a class="button primary" href="{{ $adminUrl }}">Open Admin Record</a>
                @endif
                @if ($receiptUrl)
                    <a class="button" href="{{ $receiptUrl }}">Print Receipt</a>
                @endif
                @if ($labelUrl)
                    <a class="button" href="{{ $labelUrl }}">Print Label</a>
                @endif
            </div>

            @if ($acceptanceUrl)
                <div class="copy-row">
                    <input id="acceptanceUrl" readonly value="{{ $acceptanceUrl }}">
                    <button
                        type="button"
                        onclick="navigator.clipboard.writeText(document.getElementById('acceptanceUrl').value); this.textContent = 'Copied'; setTimeout(() => this.textContent = 'Copy', 1500);"
                    >Copy</button>
                </div>
                <div class="muted" style="margin-top: 8px;">Copy the acceptance link and share it with the assigned recipient.</div>
            @endif
        @else
            <div class="muted">Login to manage this asset, view restricted details, or perform inventory actions.</div>
            <div class="actions">
                <a class="button primary" href="{{ url('/admin/login') }}">Login</a>
            </div>
        @endif
    </section>
</main>
</body>
</html>
