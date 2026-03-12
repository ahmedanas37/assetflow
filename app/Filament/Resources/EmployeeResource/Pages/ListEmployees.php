<?php

namespace App\Filament\Resources\EmployeeResource\Pages;

use App\Filament\Resources\EmployeeResource;
use App\Filament\Resources\EmployeeResource\Pages;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEmployees extends ListRecords
{
    protected static string $resource = EmployeeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import_employees')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn () => Pages\ImportEmployees::getUrl())
                ->visible(fn (): bool => auth()->user()?->can('import employees') ?? false)
                ->authorize(fn (): bool => auth()->user()?->can('import employees') ?? false),
        ];
    }
}
