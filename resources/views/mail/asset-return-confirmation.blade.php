@php
    $asset = $assignment->asset;
    $assignedTo = $assignment->assigned_to_name ?: 'Unspecified';
    if ($assignment->assigned_to_label) {
        $assignedTo = $assignedTo . ' (' . $assignment->assigned_to_label . ')';
    }
    $returnedAt = $assignment->returned_at?->format('M d, Y h:i A');
    $condition = $assignment->return_condition ? ucfirst((string) $assignment->return_condition) : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Asset Return Confirmation</title>
</head>
<body style="margin:0; padding:0; background:#eef2f7; color:#111827; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="background:#eef2f7; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" cellpadding="0" cellspacing="0" width="640" style="background:#ffffff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden;">
                    @include('mail.partials.brand-header', ['title' => 'Asset Return Confirmation'])
                    <tr>
                        <td style="padding:20px;">
                            <div style="font-size:14px; margin-bottom:16px;">
                                <strong>Returned by:</strong> {{ $assignedTo }}
                            </div>

                            <table role="presentation" cellpadding="0" cellspacing="0" width="100%" style="font-size:14px;">
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280; width:40%;">Receipt ID</td>
                                    <td style="padding:6px 0;">RR-{{ $assignment->id }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Asset Tag</td>
                                    <td style="padding:6px 0;">{{ $asset?->asset_tag ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Model</td>
                                    <td style="padding:6px 0;">{{ $asset?->assetModel?->name ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Serial</td>
                                    <td style="padding:6px 0;">{{ $asset?->serial ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Returned at</td>
                                    <td style="padding:6px 0;">{{ $returnedAt ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Condition</td>
                                    <td style="padding:6px 0;">{{ $condition ?? '-' }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0; color:#6b7280;">Returned to</td>
                                    <td style="padding:6px 0;">{{ $assignment->assignedBy?->name ?? '-' }}</td>
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
