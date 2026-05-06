@php
    $settings = app(\App\Services\PortalSettings::class);
    $accent = $settings->brandColor();
    $accessory = $assignment->accessory;
    $assignedTo = $assignment->assigned_to_name ?: 'Unspecified';
    if ($assignment->assigned_to_label) {
        $assignedTo = $assignedTo . ' (' . $assignment->assigned_to_label . ')';
    }
    $assignedAt = $assignment->assigned_at?->format('M d, Y h:i A');
    $dueAt = $assignment->due_at?->format('M d, Y h:i A');
    $ackLink = app(\App\Services\ReceiptAcceptanceService::class)->accessoryUrl($assignment);
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Accessory Issuance Receipt</title>
</head>
<body style="margin:0; padding:0; background:#eef2f7; color:#111827; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#eef2f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="640" style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                    @include('mail.partials.brand-header', ['title' => 'Accessory Issuance Receipt'])
                    <tr>
                        <td style="padding:20px;">
                            <div style="font-size:14px; margin-bottom:14px; color:#111827;">
                                This accessory has been issued to you. Please acknowledge this email for your records.
                            </div>
                            <table role="presentation" cellpadding="0" cellspacing="0" style="margin:0 0 18px;">
                                <tr>
                                    <td>
                                        <a href="{{ $ackLink }}" style="background:{{ $accent }}; color:#ffffff; text-decoration:none; padding:10px 16px; border-radius:6px; font-size:13px; font-weight:700; display:inline-block;">
                                            Accept Receipt
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            <div style="font-size:14px; margin-bottom:16px;">
                                <strong>Issued to:</strong> {{ $assignedTo }}
                            </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-size:14px;">
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280; width:40%;">Receipt ID</td>
                                    <td style="padding:6px 0;">ACR-{{ $assignment->id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Accessory</td>
                                    <td style="padding:6px 0;">{{ $accessory?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Category</td>
                                    <td style="padding:6px 0;">{{ $accessory?->category?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Model Number</td>
                                    <td style="padding:6px 0;">{{ $accessory?->model_number ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Quantity Issued</td>
                                    <td style="padding:6px 0;">{{ $assignment->quantity ?? 0 }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Location at issuance</td>
                                    <td style="padding:6px 0;">{{ $assignment->location_at_assignment ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Issued by</td>
                                    <td style="padding:6px 0;">{{ $assignment->assignedBy?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Issued at</td>
                                    <td style="padding:6px 0;">{{ $assignedAt ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Due at</td>
                                    <td style="padding:6px 0;">{{ $dueAt ?? '-' }}</td>
                                </tr>
                            </table>

                            @if (! empty($assignment->notes))
                                <div style="margin-top:16px; font-size:13px; color:#374151;">
                                    <strong>Notes:</strong> {{ $assignment->notes }}
                                </div>
                            @endif
                        </td>
                    </tr>
                    @include('mail.partials.brand-footer')
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
