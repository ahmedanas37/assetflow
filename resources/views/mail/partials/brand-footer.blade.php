@php
    $settings = app(\App\Services\PortalSettings::class);
    $product = $settings->productName();
    $customFooter = (string) $settings->get('branding.email_footer', '');
@endphp
<tr>
    <td style="padding:14px 20px; font-size:11px; color:#9ca3af; text-align:center;">
        @if (trim($customFooter) !== '')
            {{ $customFooter }}
        @else
            This message was generated automatically by {{ $product }}.
        @endif
    </td>
</tr>
