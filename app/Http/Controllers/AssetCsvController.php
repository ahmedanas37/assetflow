<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Models\Asset;
use Illuminate\Support\Facades\Gate;
use League\Csv\Writer;
use SplTempFileObject;

class AssetCsvController extends Controller
{
    public function template()
    {
        Gate::authorize('import assets');

        $headers = [
            'asset_tag',
            'serial',
            'model',
            'model_number',
            'manufacturer',
            'category',
            'status',
            'location',
            'vendor',
            'induction_date',
            'purchase_cost',
            'warranty_end_date',
            'notes',
        ];

        $csv = Writer::createFromFileObject(new SplTempFileObject);
        $csv->insertOne($headers);
        $csv->insertOne([
            'LAP-000001',
            'SN-12345',
            'ThinkPad T14',
            'T14-Gen3',
            'Lenovo',
            'Laptop',
            'In Stock',
            'HQ',
            'Default Vendor',
            '2025-01-10',
            '1200.00',
            '2028-01-10',
            'Initial import',
        ]);

        return response()->streamDownload(function () use ($csv): void {
            echo $csv->toString();
        }, 'assetflow-assets-template.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function export(Asset $asset)
    {
        Gate::authorize('view', $asset);
        Gate::authorize('export assets');

        $csv = Writer::createFromFileObject(new SplTempFileObject);
        $csv->insertOne([
            'asset_tag',
            'serial',
            'model',
            'model_number',
            'manufacturer',
            'category',
            'status',
            'location',
            'vendor',
            'induction_date',
            'purchase_cost',
            'warranty_end_date',
            'notes',
        ]);

        $csv->insertOne([
            $asset->asset_tag,
            $asset->serial,
            $asset->assetModel?->name,
            $asset->assetModel?->model_number,
            $asset->assetModel?->manufacturer?->name,
            $asset->category?->name,
            $asset->statusLabel?->name,
            $asset->location?->name,
            $asset->vendor?->name,
            optional($asset->purchase_date)->format('Y-m-d'),
            $asset->purchase_cost,
            optional($asset->warranty_end_date)->format('Y-m-d'),
            $asset->notes,
        ]);

        return response()->streamDownload(function () use ($csv): void {
            echo $csv->toString();
        }, "asset-{$asset->asset_tag}.csv", [
            'Content-Type' => 'text/csv',
        ]);
    }
}
