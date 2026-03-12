<?php

namespace App\Domain\Inventory\Enums;

enum CategoryType: string
{
    case Asset = 'asset';
    case Accessory = 'accessory';
    case Consumable = 'consumable';
}
