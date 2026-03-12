<?php

namespace App\Filament\Resources;

use App\Domain\People\Enums\UserStatus;
use App\Domain\People\Models\Department;
use App\Domain\People\Models\Employee;
use App\Filament\Resources\EmployeeResource\Pages;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ForceDeleteAction;
use Filament\Tables\Actions\RestoreAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'People';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'email', 'employee_id'];
    }

    public static function getGlobalSearchResultDetails($record): array
    {
        return [
            'Department' => $record->department?->name,
            'Status' => ucfirst($record->status?->value ?? 'unknown'),
        ];
    }

    public static function form(Forms\Form $form): Forms\Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                TextInput::make('employee_id')
                    ->label('Employee ID')
                    ->unique(ignoreRecord: true)
                    ->maxLength(50),
                TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Select::make('department_id')
                    ->label('Department')
                    ->options(fn () => Department::query()->orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                TextInput::make('title')
                    ->label('Title')
                    ->maxLength(150),
                TextInput::make('phone')
                    ->label('Phone')
                    ->maxLength(50),
                Select::make('status')
                    ->options([
                        UserStatus::Active->value => 'Active',
                        UserStatus::Inactive->value => 'Inactive',
                    ])
                    ->default(UserStatus::Active->value)
                    ->required(),
                Textarea::make('notes')
                    ->rows(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('employee_id')
                    ->label('Employee ID')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('email')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('department.name')
                    ->label('Department')
                    ->toggleable(),
                TextColumn::make('title')
                    ->label('Title')
                    ->toggleable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (Employee $record) => $record->status === UserStatus::Active ? 'success' : 'gray'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        UserStatus::Active->value => 'Active',
                        UserStatus::Inactive->value => 'Inactive',
                    ]),
                Tables\Filters\SelectFilter::make('department_id')
                    ->label('Department')
                    ->relationship('department', 'name'),
                TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                RestoreAction::make(),
                ForceDeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                Tables\Actions\RestoreBulkAction::make(),
                Tables\Actions\ForceDeleteBulkAction::make(),
            ])
            ->persistFiltersInSession()
            ->persistSearchInSession();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
            'import' => Pages\ImportEmployees::route('/import'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['department'])
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
