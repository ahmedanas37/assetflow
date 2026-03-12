<?php

namespace App\Domain\Maintenance\Models;

use App\Domain\Assets\Models\Asset;
use App\Domain\Attachments\Models\Attachment;
use App\Domain\Audits\Concerns\Auditable;
use App\Domain\Audits\Models\AuditLog;
use App\Domain\Maintenance\Enums\MaintenanceStatus;
use App\Domain\Maintenance\Enums\MaintenanceType;
use App\Domain\Vendors\Models\Vendor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class MaintenanceLog extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Maintenance\Models\MaintenanceLogFactory> */
    use HasFactory;

    protected $fillable = ['asset_id',
        'type',
        'start_date',
        'end_date',
        'cost',
        'vendor_id',
        'notes',
        'performed_by',
        'status',
    ];

    protected $casts = [
        'type' => MaintenanceType::class,
        'status' => MaintenanceStatus::class,
        'start_date' => 'date',
        'end_date' => 'date',
        'cost' => 'decimal:2',
    ];

    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class);
    }

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'entity');
    }
}
