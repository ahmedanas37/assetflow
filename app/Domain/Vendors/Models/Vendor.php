<?php

namespace App\Domain\Vendors\Models;

use App\Domain\Assets\Models\Asset;
use App\Domain\Audits\Concerns\Auditable;
use App\Domain\Maintenance\Models\MaintenanceLog;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vendor extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\Vendors\Models\VendorFactory> */
    use HasFactory;

    protected $fillable = ['name',
        'contact_name',
        'email',
        'phone',
        'website',
        'address',
        'notes',
    ];

    public function assets(): HasMany
    {
        return $this->hasMany(Asset::class);
    }

    public function maintenanceLogs(): HasMany
    {
        return $this->hasMany(MaintenanceLog::class);
    }
}
