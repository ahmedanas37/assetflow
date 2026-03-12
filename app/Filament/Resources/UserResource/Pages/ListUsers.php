<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Filament\Resources\UserResource\Pages;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('import_users')
                ->label('Import CSV')
                ->icon('heroicon-o-arrow-up-tray')
                ->url(fn () => Pages\ImportUsers::getUrl())
                ->visible(fn (): bool => auth()->user()?->can('import users') ?? false)
                ->authorize(fn (): bool => auth()->user()?->can('import users') ?? false),
        ];
    }
}
