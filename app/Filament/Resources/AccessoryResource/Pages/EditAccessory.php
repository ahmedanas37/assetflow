<?php

namespace App\Filament\Resources\AccessoryResource\Pages;

use App\Filament\Resources\AccessoryResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;

class EditAccessory extends EditRecord
{
    protected static string $resource = AccessoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $record = $this->record;
        $assignedOut = max(($record->quantity_total ?? 0) - ($record->quantity_available ?? 0), 0);
        $newTotal = (int) ($data['quantity_total'] ?? 0);

        if ($newTotal < $assignedOut) {
            throw ValidationException::withMessages([
                'quantity_total' => 'Total quantity cannot be less than the quantity currently checked out.',
            ]);
        }

        $data['quantity_available'] = max($newTotal - $assignedOut, 0);

        return $data;
    }
}
