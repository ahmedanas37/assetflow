<?php

namespace App\Domain\Assets\Enums;

enum AssetCondition: string
{
    case Good = 'good';
    case Fair = 'fair';
    case Damaged = 'damaged';
}
