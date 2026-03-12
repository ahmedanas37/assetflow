<?php

namespace App\Domain\Assets\Observers;

use App\Domain\Assets\Models\Asset;
use App\Domain\Audits\Services\AuditLogger;

class AssetObserver
{
    public function updated(Asset $asset): void
    {
        if ($asset->wasChanged('status_label_id')) {
            AuditLogger::log(
                $asset,
                'status_changed',
                ['status_label_id' => $asset->getOriginal('status_label_id')],
                ['status_label_id' => $asset->status_label_id],
            );
        }
    }
}
