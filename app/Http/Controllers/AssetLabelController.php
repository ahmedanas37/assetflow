<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Services\QrCodeService;
use App\Filament\Resources\AssetResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AssetLabelController extends Controller
{
    public function single(Asset $asset, QrCodeService $qrCodeService)
    {
        Gate::authorize('view', $asset);
        Gate::authorize('print labels');

        return view('labels.asset-labels', [
            'labels' => $this->buildLabels([$asset], $qrCodeService),
            'receiptUrl' => route('assetflow.receipts.single', $asset),
        ]);
    }

    public function batch(Request $request, QrCodeService $qrCodeService)
    {
        Gate::authorize('view assets');
        Gate::authorize('print labels');

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter()
            ->map(fn (string $id) => (int) $id)
            ->values();

        $assets = Asset::query()->whereIn('id', $ids)->get();

        return view('labels.asset-labels', [
            'labels' => $this->buildLabels($assets->all(), $qrCodeService),
            'receiptUrl' => route('assetflow.receipts.batch', ['ids' => $ids->implode(',')]),
        ]);
    }

    public function receiptSingle(Asset $asset, QrCodeService $qrCodeService)
    {
        Gate::authorize('view', $asset);
        Gate::authorize('print labels');

        $asset->load([
            'assetModel',
            'category',
            'location',
            'activeAssignment.assignedToUser',
            'activeAssignment.assignedToEmployee',
            'activeAssignment.assignedToLocation',
            'activeAssignment.assignedBy',
        ]);

        return view('labels.asset-receipt', [
            'receipts' => $this->buildReceipts([$asset], $qrCodeService),
        ]);
    }

    public function receiptBatch(Request $request, QrCodeService $qrCodeService)
    {
        Gate::authorize('view assets');
        Gate::authorize('print labels');

        $ids = collect(explode(',', (string) $request->query('ids')))
            ->filter()
            ->map(fn (string $id) => (int) $id)
            ->values();

        $assets = Asset::query()
            ->with([
                'assetModel',
                'category',
                'location',
                'activeAssignment.assignedToUser',
                'activeAssignment.assignedToEmployee',
                'activeAssignment.assignedToLocation',
                'activeAssignment.assignedBy',
            ])
            ->whereIn('id', $ids)
            ->get();

        return view('labels.asset-receipt', [
            'receipts' => $this->buildReceipts($assets->all(), $qrCodeService),
        ]);
    }

    /**
     * @param  array<int, Asset>  $assets
     * @return array<int, array<string, string|null>>
     */
    private function buildLabels(array $assets, QrCodeService $qrCodeService): array
    {
        return collect($assets)->map(function (Asset $asset) use ($qrCodeService): array {
            $url = AssetResource::getUrl('view', ['record' => $asset]);
            $customPairs = $this->normalizeCustomFieldPairs($asset->custom_fields ?? null);

            return [
                'asset_tag' => $asset->asset_tag,
                'model' => $asset->assetModel?->name,
                'serial' => $asset->serial,
                'qr' => $qrCodeService->svg($url),
                'url' => $url,
                'custom_fields' => collect($customPairs)
                    ->map(fn (array $pair) => trim($pair['label'].': '.$pair['value']))
                    ->filter()
                    ->values()
                    ->all(),
            ];
        })->all();
    }

    /**
     * @param  array<int, Asset>  $assets
     * @return array<int, array<string, mixed>>
     */
    private function buildReceipts(array $assets, QrCodeService $qrCodeService): array
    {
        return collect($assets)->map(function (Asset $asset) use ($qrCodeService): array {
            $url = AssetResource::getUrl('view', ['record' => $asset]);
            $assignment = $asset->activeAssignment;
            $customPairs = $this->normalizeCustomFieldPairs($asset->custom_fields ?? null);

            return [
                'asset_tag' => $asset->asset_tag,
                'model' => $asset->assetModel?->name,
                'category' => $asset->category?->name,
                'serial' => $asset->serial,
                'location' => $asset->location?->name,
                'assigned_to' => $assignment?->assignedToUser?->name
                    ?? $assignment?->assignedToEmployee?->name
                    ?? $assignment?->assignedToLocation?->name,
                'assigned_type' => $assignment?->assigned_to_type
                    ? ucfirst((string) $assignment->assigned_to_type->value)
                    : null,
                'assigned_label' => $assignment?->assigned_to_label,
                'assigned_by' => $assignment?->assignedBy?->name,
                'assigned_at' => $assignment?->assigned_at,
                'due_at' => $assignment?->due_at,
                'qr' => $qrCodeService->svg($url),
                'url' => $url,
                'custom_fields' => $customPairs,
            ];
        })->all();
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    private function normalizeCustomFieldPairs(?array $fields): array
    {
        if (empty($fields)) {
            return [];
        }

        return collect($fields)
            ->map(function ($value, $key): array {
                $label = is_int($key) ? 'Field '.($key + 1) : (string) $key;
                $stringValue = match (true) {
                    is_null($value) => '',
                    is_bool($value) => $value ? 'Yes' : 'No',
                    is_scalar($value) => trim((string) $value),
                    default => trim((string) json_encode($value)),
                };

                return [
                    'label' => $label,
                    'value' => $stringValue,
                ];
            })
            ->filter(fn (array $pair) => $pair['label'] !== '' || $pair['value'] !== '')
            ->values()
            ->all();
    }
}
