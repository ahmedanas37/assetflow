<?php

namespace App\Domain\Maintenance\Enums;

enum MaintenanceType: string
{
    case Repair = 'repair';
    case Upgrade = 'upgrade';
    case Inspection = 'inspection';
}
