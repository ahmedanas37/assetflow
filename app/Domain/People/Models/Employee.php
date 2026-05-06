<?php

namespace App\Domain\People\Models;

use App\Domain\Audits\Concerns\Auditable;
use App\Domain\People\Enums\UserStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model
{
    use Auditable;

    /** @use HasFactory<\Database\Factories\Domain\People\Models\EmployeeFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['employee_id',
        'name',
        'email',
        'department_id',
        'title',
        'phone',
        'status',
        'notes',
    ];

    protected $casts = [
        'status' => UserStatus::class,
    ];

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
