@php
    $settings = app(\App\Services\PortalSettings::class);
    $company = $settings->companyName();
    $product = $settings->productName();
    $accent = $settings->brandColor();
    $logoUrl = $settings->logoUrl();
@endphp
<tr>
    <td style="background:{{ $accent }}; color:#ffffff; padding:16px 20px; border-radius:12px 12px 0 0;">
        @if ($logoUrl)
            <div style="margin-bottom:10px;">
                <img src="{{ $logoUrl }}" alt="{{ $product }} logo" style="display:block; max-width:160px; height:auto;">
            </div>
        @endif
        <div style="font-size:18px; font-weight:700; margin-top:2px;">{{ $product }}</div>
        <div style="font-size:13px; margin-top:4px; opacity:0.9;">{{ $company }}</div>
        <div style="font-size:13px; margin-top:4px; opacity:0.9;">{{ $title }}</div>
    </td>
</tr>
