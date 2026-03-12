<?php

namespace App\Filament\Resources\StatusLabelResource\Pages;

use App\Filament\Resources\StatusLabelResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStatusLabels extends ListRecords
{
    protected static string $resource = StatusLabelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
