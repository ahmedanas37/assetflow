<?php

namespace App\Filament\Resources;

use App\Domain\Inventory\Enums\CategoryType;
use App\Domain\Inventory\Models\Category;
use App\Filament\Resources\CategoryResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Collection;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                Select::make('type')
                    ->options([
                        CategoryType::Asset->value => 'Asset',
                        CategoryType::Accessory->value => 'Accessory',
                        CategoryType::Consumable->value => 'Consumable',
                    ])
                    ->placeholder('Optional'),
                TextInput::make('depreciation_months')
                    ->label('Depreciation Months')
                    ->numeric()
                    ->minValue(1),
                TextInput::make('prefix')
                    ->label('Tag Prefix')
                    ->maxLength(10),
                Textarea::make('notes')
                    ->rows(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst($state?->value ?? (string) $state)),
                TextColumn::make('depreciation_months')
                    ->label('Depreciation')
                    ->suffix(' mo')
                    ->toggleable(),
                TextColumn::make('prefix')
                    ->label('Prefix')
                    ->toggleable(),
                TextColumn::make('asset_models_count')
                    ->label('Models')
                    ->counts('assetModels')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        CategoryType::Asset->value => 'Asset',
                        CategoryType::Accessory->value => 'Accessory',
                        CategoryType::Consumable->value => 'Consumable',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->using(function (Category $record): bool {
                        if (! static::canDeleteCategory($record)) {
                            return false;
                        }

                        return (bool) $record->delete();
                    })
                    ->failureNotification(fn (Notification $notification) => $notification
                        ->title('Cannot delete category')
                        ->body('This category is in use by asset models or assets. Reassign or delete them first.')),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->action(function (Collection $records): void {
                        $deleted = 0;
                        $blocked = 0;

                        foreach ($records as $record) {
                            if (! static::canDeleteCategory($record)) {
                                $blocked++;

                                continue;
                            }

                            if ($record->delete()) {
                                $deleted++;
                            }
                        }

                        Notification::make()
                            ->title('Bulk delete completed')
                            ->body("Deleted {$deleted} categories. Skipped {$blocked} in use.")
                            ->success()
                            ->send();
                    }),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }

    public static function canDeleteCategory(Category $record): bool
    {
        $hasModels = $record->assetModels()->withTrashed()->exists();
        $hasAssets = $record->assets()->withTrashed()->exists();

        return ! ($hasModels || $hasAssets);
    }
}
