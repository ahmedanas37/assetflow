@php
    $settings = app(\App\Services\PortalSettings::class);
    $accessory = $assignment->accessory;
    $assignedTo = $assignment->assigned_to_name ?: 'Recipient';
    if ($assignment->assigned_to_label) {
        $assignedTo .= ' (' . $assignment->assigned_to_label . ')';
    }
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accept Accessory Receipt</title>
    <style>
        body { margin: 0; background: #eef2f7; color: #111827; font-family: Arial, sans-serif; }
        main { max-width: 720px; margin: 32px auto; padding: 0 16px; }
        .card { background: #fff; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; }
        .brand { color: #6b7280; font-size: 13px; margin-bottom: 6px; }
        h1 { font-size: 24px; margin: 0 0 16px; }
        .grid { display: grid; grid-template-columns: 160px 1fr; gap: 10px 16px; font-size: 14px; }
        .label { color: #6b7280; }
        .status { margin: 16px 0; padding: 12px; border-radius: 8px; background: #ecfdf5; color: #065f46; }
        .notice { margin: 16px 0; padding: 12px; border-radius: 8px; background: #eff6ff; color: #1e3a8a; }
        input { box-sizing: border-box; width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 10px 12px; font-size: 15px; }
        button { border: 0; border-radius: 8px; background: {{ $settings->brandColor() }}; color: #fff; padding: 11px 16px; font-weight: 700; cursor: pointer; }
        form { margin-top: 18px; }
        .error { color: #b91c1c; font-size: 13px; margin-top: 6px; }
        @media (max-width: 560px) { .grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
<main>
    <div class="card">
        <div class="brand">{{ $settings->companyName() }} · {{ $settings->productName() }}</div>
        <h1>Accessory Receipt Acceptance</h1>

        @if (session('status'))
            <div class="status">{{ session('status') }}</div>
        @endif

        @if ($assignment->accepted_at)
            <div class="status">
                Accepted by {{ $assignment->accepted_by_name }} on {{ $assignment->accepted_at->format('M d, Y h:i A') }}.
            </div>
        @else
            <div class="notice">
                Confirm that you received this accessory and accept responsibility according to your company policy.
            </div>
        @endif

        <div class="grid">
            <div class="label">Issued to</div><div>{{ $assignedTo }}</div>
            <div class="label">Accessory</div><div>{{ $accessory?->name ?? '-' }}</div>
            <div class="label">Model number</div><div>{{ $accessory?->model_number ?? '-' }}</div>
            <div class="label">Quantity</div><div>{{ $assignment->quantity }}</div>
            <div class="label">Issued by</div><div>{{ $assignment->assignedBy?->name ?? '-' }}</div>
            <div class="label">Issued at</div><div>{{ $assignment->assigned_at?->format('M d, Y h:i A') ?? '-' }}</div>
            <div class="label">Due at</div><div>{{ $assignment->due_at?->format('M d, Y h:i A') ?? '-' }}</div>
        </div>

        @if (! $assignment->accepted_at)
            <form method="post" action="{{ route('assetflow.acceptance.accessory.accept', [$assignment, $token]) }}">
                @csrf
                <label>
                    <span class="label">Your name</span>
                    <input name="accepted_by_name" value="{{ old('accepted_by_name', $assignment->assigned_to_name) }}" required maxlength="255">
                </label>
                @error('accepted_by_name')
                    <div class="error">{{ $message }}</div>
                @enderror
                <p>
                    <button type="submit">Accept Receipt</button>
                </p>
            </form>
        @endif
    </div>
</main>
</body>
</html>
