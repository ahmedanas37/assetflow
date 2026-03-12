<?php

namespace App\Providers;

use App\Domain\Accessories\Models\Accessory;
use App\Domain\Accessories\Models\AccessoryAssignment;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetAssignment;
use App\Domain\Assets\Models\StatusLabel;
use App\Domain\Attachments\Models\Attachment;
use App\Domain\Audits\Models\AuditLog;
use App\Domain\Inventory\Models\AssetModel;
use App\Domain\Inventory\Models\Category;
use App\Domain\Inventory\Models\Manufacturer;
use App\Domain\Locations\Models\Location;
use App\Domain\Maintenance\Models\MaintenanceLog;
use App\Domain\People\Models\Department;
use App\Domain\People\Models\Employee;
use App\Domain\Vendors\Models\Vendor;
use App\Models\User;
use App\Policies\AccessoryAssignmentPolicy;
use App\Policies\AccessoryPolicy;
use App\Policies\AssetAssignmentPolicy;
use App\Policies\AssetModelPolicy;
use App\Policies\AssetPolicy;
use App\Policies\AttachmentPolicy;
use App\Policies\AuditLogPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\EmployeePolicy;
use App\Policies\LocationPolicy;
use App\Policies\MaintenanceLogPolicy;
use App\Policies\ManufacturerPolicy;
use App\Policies\StatusLabelPolicy;
use App\Policies\UserPolicy;
use App\Policies\VendorPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Asset::class => AssetPolicy::class,
        AssetAssignment::class => AssetAssignmentPolicy::class,
        Accessory::class => AccessoryPolicy::class,
        AccessoryAssignment::class => AccessoryAssignmentPolicy::class,
        StatusLabel::class => StatusLabelPolicy::class,
        Category::class => CategoryPolicy::class,
        AssetModel::class => AssetModelPolicy::class,
        Manufacturer::class => ManufacturerPolicy::class,
        Vendor::class => VendorPolicy::class,
        Location::class => LocationPolicy::class,
        Department::class => DepartmentPolicy::class,
        Employee::class => EmployeePolicy::class,
        MaintenanceLog::class => MaintenanceLogPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
        Attachment::class => AttachmentPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
