<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\Asset;
use App\Filament\Resources\AssetResource;
use App\Services\ReceiptAcceptanceService;
use Illuminate\View\View;

class AssetScanController extends Controller
{
    public function show(Asset $asset, ReceiptAcceptanceService $acceptance): View
    {
        $asset->load([
            'assetModel',
            'category',
            'statusLabel',
            'location',
            'activeAssignment.assignedToUser',
            'activeAssignment.assignedToEmployee',
            'activeAssignment.assignedToLocation',
            'activeAssignment.assignedBy',
        ]);

        $user = auth()->user();
        $canManage = (bool) ($user?->can('view', $asset));
        $activeAssignment = $asset->activeAssignment;
        $acceptanceUrl = null;

        if ($canManage && $activeAssignment && ($user?->can('view assignments') ?? false)) {
            $acceptanceUrl = $acceptance->assetUrl($activeAssignment);
        }

        return view('assets.scan', [
            'asset' => $asset,
            'activeAssignment' => $activeAssignment,
            'canManage' => $canManage,
            'adminUrl' => $canManage ? AssetResource::getUrl('view', ['record' => $asset]) : null,
            'receiptUrl' => $canManage && ($user?->can('print labels') ?? false)
                ? route('assetflow.receipts.single', $asset)
                : null,
            'labelUrl' => $canManage && ($user?->can('print labels') ?? false)
                ? route('assetflow.labels.single', $asset)
                : null,
            'acceptanceUrl' => $acceptanceUrl,
        ]);
    }
}
