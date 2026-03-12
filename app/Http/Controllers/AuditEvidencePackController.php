<?php

namespace App\Http\Controllers;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Audits\Models\AuditLog;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Locations\Models\Location;
use App\Domain\People\Models\Department;
use App\Domain\People\Models\Employee;
use App\Domain\Vendors\Models\Vendor;
use App\Models\User;
use Illuminate\Http\Request;
use ZipArchive;

class AuditEvidencePackController extends Controller
{
    public function download(Request $request)
    {
        abort_unless(auth()->user()?->can('view audit logs') ?? false, 403);

        if (! class_exists(ZipArchive::class)) {
            abort(500, 'ZipArchive PHP extension is required to generate the evidence pack.');
        }

        $from = $request->input('from');
        $to = $request->input('to');

        $timestamp = now()->format('Ymd_His');
        $zipName = "assetflow_audit_evidence_{$timestamp}.zip";
        $tmpPath = storage_path('app/tmp/'.$zipName);

        if (! is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        $zip = new ZipArchive;
        $zip->open($tmpPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        $zip->addFromString('README.txt', $this->buildReadme($from, $to));

        $zip->addFromString('assets.csv', $this->csvString(
            ['asset_tag', 'serial', 'model', 'category', 'status', 'location', 'assigned_to', 'assigned_type', 'assigned_label', 'induction_date', 'warranty_end_date', 'notes', 'custom_fields'],
            Asset::query()
                ->withTrashed()
                ->with(['assetModel', 'category', 'statusLabel', 'location', 'activeAssignment'])
                ->get()
                ->map(function (Asset $asset): array {
                    $assignment = $asset->activeAssignment;

                    return [
                        $asset->asset_tag,
                        $asset->serial,
                        $asset->assetModel?->name,
                        $asset->category?->name,
                        $asset->statusLabel?->name,
                        $asset->location?->name,
                        $asset->assigned_to_display,
                        $assignment?->assigned_to_type?->value ?? $assignment?->assigned_to_type,
                        $assignment?->assigned_to_label,
                        $asset->purchase_date,
                        $asset->warranty_end_date,
                        $asset->notes,
                        $asset->custom_fields,
                    ];
                })
                ->all(),
        ));

        $zip->addFromString('asset_assignments.csv', $this->csvString(
            ['asset_tag', 'assigned_to_type', 'assigned_to_id', 'assigned_to_name', 'assigned_to_label', 'assigned_by', 'assigned_at', 'due_at', 'returned_at', 'return_condition', 'transferred_from_id', 'notes'],
            AssetAssignment::query()
                ->with(['asset', 'assignedBy', 'assignedToUser', 'assignedToEmployee', 'assignedToLocation'])
                ->get()
                ->map(function (AssetAssignment $assignment): array {
                    return [
                        $assignment->asset?->asset_tag,
                        $assignment->assigned_to_type?->value ?? $assignment->assigned_to_type,
                        $assignment->assigned_to_id,
                        $assignment->assigned_to_name,
                        $assignment->assigned_to_label,
                        $assignment->assignedBy?->name,
                        $assignment->assigned_at,
                        $assignment->due_at,
                        $assignment->returned_at,
                        $assignment->return_condition?->value ?? $assignment->return_condition,
                        $assignment->transferred_from_id,
                        $assignment->notes,
                    ];
                })
                ->all(),
        ));

        $zip->addFromString('accessories.csv', $this->csvString(
            ['name', 'category', 'location', 'model_number', 'quantity_total', 'quantity_available', 'reorder_threshold', 'notes'],
            Accessory::query()
                ->withTrashed()
                ->with(['category', 'location'])
                ->get()
                ->map(function (Accessory $accessory): array {
                    return [
                        $accessory->name,
                        $accessory->category?->name,
                        $accessory->location?->name,
                        $accessory->model_number,
                        $accessory->quantity_total,
                        $accessory->quantity_available,
                        $accessory->reorder_threshold,
                        $accessory->notes,
                    ];
                })
                ->all(),
        ));

        $zip->addFromString('accessory_assignments.csv', $this->csvString(
            ['accessory', 'assigned_to_type', 'assigned_to_id', 'assigned_to_name', 'assigned_to_label', 'assigned_by', 'assigned_at', 'due_at', 'returned_at', 'quantity', 'returned_quantity', 'notes'],
            AccessoryAssignment::query()
                ->with(['accessory', 'assignedBy', 'assignedToUser', 'assignedToEmployee', 'assignedToLocation'])
                ->get()
                ->map(function (AccessoryAssignment $assignment): array {
                    return [
                        $assignment->accessory?->name,
                        $assignment->assigned_to_type?->value ?? $assignment->assigned_to_type,
                        $assignment->assigned_to_id,
                        $assignment->assigned_to_name,
                        $assignment->assigned_to_label,
                        $assignment->assignedBy?->name,
                        $assignment->assigned_at,
                        $assignment->due_at,
                        $assignment->returned_at,
                        $assignment->quantity,
                        $assignment->returned_quantity,
                        $assignment->notes,
                    ];
                })
                ->all(),
        ));

        $zip->addFromString('employees.csv', $this->csvString(
            ['employee_id', 'name', 'email', 'department', 'status', 'title', 'phone', 'notes'],
            Employee::query()
                ->withTrashed()
                ->with('department')
                ->get()
                ->map(function (Employee $employee): array {
                    return [
                        $employee->employee_id,
                        $employee->name,
                        $employee->email,
                        $employee->department?->name,
                        $employee->status?->value ?? $employee->status,
                        $employee->title,
                        $employee->phone,
                        $employee->notes,
                    ];
                })
                ->all(),
        ));

        $zip->addFromString('users.csv', $this->csvString(
            ['name', 'email', 'username', 'department', 'status', 'roles'],
            User::query()
                ->with(['department', 'roles'])
                ->get()
                ->map(function (User $user): array {
                    return [
                        $user->name,
                        $user->email,
                        $user->username,
                        $user->department?->name,
                        $user->status?->value ?? $user->status,
                        $user->roles?->pluck('name')->implode(', '),
                    ];
                })
                ->all(),
        ));

        $auditQuery = AuditLog::query()->with('actor');
        if ($from) {
            $auditQuery->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $auditQuery->whereDate('created_at', '<=', $to);
        }

        $zip->addFromString('audit_logs.csv', $this->csvString(
            ['action', 'entity_type', 'entity_id', 'actor', 'old_values', 'new_values', 'ip', 'user_agent', 'created_at'],
            $auditQuery
                ->orderBy('created_at')
                ->get()
                ->map(function (AuditLog $log): array {
                    return [
                        $log->action,
                        $log->entity_type,
                        $log->entity_id,
                        $log->actor?->name,
                        $log->old_values,
                        $log->new_values,
                        $log->ip,
                        $log->user_agent,
                        $log->created_at,
                    ];
                })
                ->all(),
        ));

        $zip->addFromString('reference_categories.csv', $this->csvString(
            ['name', 'type', 'depreciation_months', 'prefix', 'notes'],
            Category::query()->get()->map(fn (Category $category) => [
                $category->name,
                $category->type?->value ?? $category->type,
                $category->depreciation_months,
                $category->prefix,
                $category->notes,
            ])->all()
        ));

        $zip->addFromString('reference_models.csv', $this->csvString(
            ['name', 'model_number', 'category', 'manufacturer', 'notes'],
            AssetModel::query()->withTrashed()->with(['category', 'manufacturer'])->get()->map(fn (AssetModel $model) => [
                $model->name,
                $model->model_number,
                $model->category?->name,
                $model->manufacturer?->name,
                $model->notes,
            ])->all()
        ));

        $zip->addFromString('reference_locations.csv', $this->csvString(
            ['name', 'parent', 'notes'],
            Location::query()->withTrashed()->with('parent')->get()->map(fn (Location $location) => [
                $location->name,
                $location->parent?->name,
                $location->notes,
            ])->all()
        ));

        $zip->addFromString('reference_departments.csv', $this->csvString(
            ['name', 'manager'],
            Department::query()->with('manager')->get()->map(fn (Department $department) => [
                $department->name,
                $department->manager?->name,
            ])->all()
        ));

        $zip->addFromString('reference_status_labels.csv', $this->csvString(
            ['name', 'deployable', 'default', 'sort_order'],
            StatusLabel::query()->orderBy('sort_order')->get()->map(fn (StatusLabel $label) => [
                $label->name,
                $label->deployable ? '1' : '0',
                $label->is_default ? '1' : '0',
                $label->sort_order,
            ])->all()
        ));

        $zip->addFromString('reference_vendors.csv', $this->csvString(
            ['name', 'email', 'phone', 'contact_name', 'notes'],
            Vendor::query()->get()->map(fn (Vendor $vendor) => [
                $vendor->name,
                $vendor->email,
                $vendor->phone,
                $vendor->contact_name,
                $vendor->notes,
            ])->all()
        ));

        $zip->close();

        return response()->download($tmpPath, $zipName)->deleteFileAfterSend(true);
    }

    private function buildReadme(?string $from, ?string $to): string
    {
        $productName = (string) config('assetflow.product_name', config('app.name', 'AssetFlow'));

        $lines = [
            $productName.' - Audit Evidence Pack',
            'Generated: '.now()->format('Y-m-d H:i:s'),
            $from || $to ? 'Audit log range: '.($from ?: 'beginning').' to '.($to ?: 'now') : 'Audit log range: all',
            '',
            'Contents:',
            '- assets.csv',
            '- asset_assignments.csv',
            '- accessories.csv',
            '- accessory_assignments.csv',
            '- employees.csv',
            '- users.csv',
            '- audit_logs.csv',
            '- reference_categories.csv',
            '- reference_models.csv',
            '- reference_locations.csv',
            '- reference_departments.csv',
            '- reference_status_labels.csv',
            '- reference_vendors.csv',
            '',
            'All timestamps are in system timezone: '.config('app.timezone'),
        ];

        return implode("\n", $lines);
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function csvString(array $headers, array $rows): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, $headers);

        foreach ($rows as $row) {
            fputcsv($handle, array_map(fn ($value) => $this->formatValue($value), $row));
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function formatValue(mixed $value): string
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE);
        }

        if ($value === null) {
            return '';
        }

        return (string) $value;
    }
}
