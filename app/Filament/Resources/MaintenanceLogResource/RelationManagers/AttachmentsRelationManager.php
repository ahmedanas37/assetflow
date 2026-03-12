<?php

namespace App\Filament\Resources\MaintenanceLogResource\RelationManagers;

use App\Domain\Attachments\Models\Attachment;
use Filament\Forms;
use Filament\Forms\Components\FileUpload;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

class AttachmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'attachments';

    protected static ?string $title = 'Attachments';

    public function form(Forms\Form $form): Forms\Form
    {
        return $form->schema([
            FileUpload::make('path')
                ->label('File')
                ->disk('private')
                ->directory('attachments/maintenance')
                ->visibility('private')
                ->preserveFilenames()
                ->acceptedFileTypes([
                    'application/pdf',
                    'image/jpeg',
                    'image/png',
                    'image/webp',
                    'text/plain',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->maxSize(8192)
                ->required(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('original_name')
                    ->label('File')
                    ->searchable(),
                TextColumn::make('mime')
                    ->toggleable(),
                TextColumn::make('size')
                    ->label('Size')
                    ->formatStateUsing(fn ($state) => $state ? number_format($state / 1024, 1).' KB' : null),
                TextColumn::make('uploadedBy.name')
                    ->label('Uploaded By')
                    ->toggleable(),
                TextColumn::make('uploaded_at')
                    ->dateTime()
                    ->toggleable(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Upload Attachment'),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Attachment $record) => route('assetflow.attachments.download', $record))
                    ->openUrlInNewTab()
                    ->authorize(fn (Attachment $record) => auth()->user()?->can('download', $record) ?? false),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->modifyQueryUsing(fn ($query) => $query->latest('uploaded_at'));
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $disk = 'private';
        $path = $data['path'];

        $data['disk'] = $disk;
        $data['original_name'] = basename($path);
        $data['mime'] = Storage::disk($disk)->mimeType($path) ?? 'application/octet-stream';
        $data['size'] = Storage::disk($disk)->size($path) ?? 0;
        $data['hash'] = hash_file('sha256', Storage::disk($disk)->path($path));
        $data['uploaded_by'] = auth()->id();
        $data['uploaded_at'] = now();

        return $data;
    }
}
