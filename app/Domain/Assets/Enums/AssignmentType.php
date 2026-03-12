<?php

namespace App\Domain\Assets\Enums;

enum AssignmentType: string
{
    case User = 'user';
    case Employee = 'employee';
    case Location = 'location';
}
