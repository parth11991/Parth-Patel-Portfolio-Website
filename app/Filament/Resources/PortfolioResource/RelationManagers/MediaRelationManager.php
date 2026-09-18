<?php

namespace App\Filament\Resources\PortfolioResource\RelationManagers;

use App\Concerns\ManagesSingletonUploads;
use App\Enums\PortfolioMediaFit;
use App\Enums\PortfolioMediaType;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class MediaRelationManager extends RelationManager
{
    use ManagesSingletonUploads;

    protected static string $relationship = 'media';

    protected static ?string $title = 'Media';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('path')
                    ->label('Image')
                    ->disk('public')
                    ->directory(fn (): string => 'portfolio/'.$this->getOwnerRecord()->getKey())
                    ->visibility('public')
                    ->image()
                    ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                    ->rules(['mimes:jpg,jpeg,png,webp'])
                    ->maxSize(5120)
                    ->preventFilePathTampering(false)
                    ->editableSvgs(false)
                    ->previewable()
                    ->openable()
                    ->required()
                    ->mutateStateForValidationUsing(fn (mixed $state): array => Arr::wrap($state))
                    ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => $this->storeManagedUpload($file, 'portfolio/'.$this->getOwnerRecord()->getKey()))
                    ->dehydrateStateUsing(fn (FileUpload $component): ?string => $this->normalizeManagedPath($component->getRawState(), 'portfolio'))
                    ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => $this->safeUploadedFileName($file)),
                Select::make('media_type')
                    ->label('Type')
                    ->options([
                        PortfolioMediaType::Logo->value => 'Logo',
                        PortfolioMediaType::Screenshot->value => 'Screenshot',
                    ])
                    ->default(PortfolioMediaType::Screenshot->value)
                    ->required()
                    ->live()
                    ->afterStateUpdated(fn (Set $set, ?string $state): mixed => $set('fit', $state === PortfolioMediaType::Logo->value ? PortfolioMediaFit::Contain->value : PortfolioMediaFit::Cover->value)),
                Select::make('fit')
                    ->options([
                        PortfolioMediaFit::Contain->value => 'Contain',
                        PortfolioMediaFit::Cover->value => 'Cover',
                    ])
                    ->default(PortfolioMediaFit::Cover->value)
                    ->required(),
                Textarea::make('alt_text')
                    ->required()
                    ->rows(2)
                    ->columnSpanFull(),
                Toggle::make('is_primary')
                    ->label('Primary')
                    ->default(false),
                TextInput::make('sort_order')
                    ->numeric()
                    ->integer()
                    ->minValue(0)
                    ->default(0)
                    ->required(),
            ])
            ->columns(2);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('path')->disk('public')->label('Preview')->square(),
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('media_type')->badge()->sortable(),
                TextColumn::make('fit')->badge()->sortable(),
                TextColumn::make('alt_text')->searchable()->wrap(),
                ToggleColumn::make('is_primary')->label('Primary')->sortable(),
            ])
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order');
    }
}
