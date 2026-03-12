<?php

namespace App\Domain\Maintenance\Enums;

enum MaintenanceStatus: string
{
    case Open = 'open';
    case Closed = 'closed';
}
