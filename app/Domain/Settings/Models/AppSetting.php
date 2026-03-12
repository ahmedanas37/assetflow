<?php

namespace App\Domain\Settings\Models;

use Illuminate\Database\Eloquent\Model;

class AppSetting extends Model
{
    protected $fillable = ['key',
        'value',
    ];
}
