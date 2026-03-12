<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Domain\Inventory\Models\Category;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCategory extends EditRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->using(function (Category $record): bool {
                    if (! CategoryResource::canDeleteCategory($record)) {
                        return false;
                    }

                    return (bool) $record->delete();
                })
                ->failureNotification(fn (Notification $notification) => $notification
                    ->title('Cannot delete category')
                    ->body('This category is in use by asset models or assets. Reassign or delete them first.')),
        ];
    }
}
