<?php

namespace App\Domain\People\Enums;

enum UserStatus: string
{
    case Active = 'active';
    case Inactive = 'inactive';
}
