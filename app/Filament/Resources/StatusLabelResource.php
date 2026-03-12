<?php

namespace App\Filament\Resources;

use App\Domain\Assets\Models\StatusLabel;
use App\Filament\Resources\StatusLabelResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\Exceptions\Halt;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Collection;

class StatusLabelResource extends Resource
{
    protected static ?string $model = StatusLabel::class;

    protected static ?string $navigationIcon = 'heroicon-o-swatch';

    protected static ?string $navigationGroup = 'Inventory';

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->unique(ignoreRecord: true),
                ColorPicker::make('color')
                    ->label('Color'),
                Toggle::make('deployable')
                    ->default(true)
                    ->label('Deployable'),
                Toggle::make('is_default')
                    ->label('Default'),
                TextInput::make('sort_order')
                    ->numeric()
                    ->default(0),
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
                IconColumn::make('deployable')
                    ->boolean()
                    ->label('Deployable'),
                IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default'),
                TextColumn::make('sort_order')
                    ->label('Order')
                    ->toggleable(),
            ])
            ->filters([
                TernaryFilter::make('deployable')
                    ->label('Deployable')
                    ->queries(
                        true: fn ($query) => $query->where('deployable', true),
                        false: fn ($query) => $query->where('deployable', false),
                    ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->before(function (StatusLabel $record): void {
                        if ($record->assets()->exists()) {
                            Notification::make()
                                ->title('Cannot delete status label')
                                ->body('This status label is assigned to one or more assets. Reassign those assets first.')
                                ->danger()
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function (Collection $records): void {
                        $blockedCount = $records
                            ->filter(fn (StatusLabel $record): bool => $record->assets()->exists())
                            ->count();

                        if ($blockedCount > 0) {
                            Notification::make()
                                ->title('Cannot delete status labels')
                                ->body('One or more selected status labels are assigned to assets. Reassign those assets first.')
                                ->danger()
                                ->send();

                            throw new Halt;
                        }
                    }),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStatusLabels::route('/'),
            'create' => Pages\CreateStatusLabel::route('/create'),
            'edit' => Pages\EditStatusLabel::route('/{record}/edit'),
        ];
    }
}
