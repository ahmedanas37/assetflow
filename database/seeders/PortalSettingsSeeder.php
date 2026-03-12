<?php

namespace Database\Seeders;

use App\Services\PortalSettings;
use Illuminate\Database\Seeder;

class PortalSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $bcc = config('assetflow.receipts.bcc');
        $bccValue = is_array($bcc) ? implode(', ', $bcc) : '';

        app(PortalSettings::class)->setMany([
            'branding.company_name' => (string) config('assetflow.company_name', 'Your Company'),
            'branding.brand_color' => (string) config('assetflow.brand_color', '#1459D9'),
            'branding.logo_path' => '',
            'branding.email_footer' => '',
            'emails.enabled' => (bool) config('assetflow.receipts.email_enabled'),
            'emails.receipts.enabled' => true,
            'emails.returns.enabled' => (bool) config('assetflow.receipts.return_email_enabled'),
            'emails.cc_actor' => (bool) config('assetflow.receipts.cc_actor'),
            'emails.fallback_to_actor' => (bool) config('assetflow.receipts.fallback_to_actor'),
            'emails.cc' => '',
            'emails.bcc' => $bccValue,
            'features.asset_transfers' => true,
            'features.evidence_pack' => true,
            'performance.mode' => false,
        ]);
    }
}
