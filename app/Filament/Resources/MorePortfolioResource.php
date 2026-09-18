<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MorePortfolioResource\Pages;
use App\Models\MorePortfolio;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class MorePortfolioResource extends Resource
{
    protected static ?string $model = MorePortfolio::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'More Portfolio';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::RectangleGroup;

    protected static ?int $navigationSort = 80;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('project_url')
                        ->label('Project link')
                        ->url()
                        ->maxLength(255),
                    TagsInput::make('technologies')
                        ->label('Skill tags / technologies')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                    Toggle::make('is_visible')
                        ->label('Visible')
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('title')->searchable()->sortable(),
                TextColumn::make('project_url')
                    ->label('Project link')
                    ->searchable()
                    ->url(fn (MorePortfolio $record): ?string => $record->project_url, shouldOpenInNewTab: true)
                    ->toggleable(),
                TextColumn::make('technologies')
                    ->label('Technologies')
                    ->formatStateUsing(fn (mixed $state): string => implode(', ', (array) $state))
                    ->wrap(),
                ToggleColumn::make('is_visible')->label('Visible')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_visible')->label('Visible'),
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
            ->reorderable('sort_order')
            ->emptyStateIcon(Heroicon::RectangleGroup)
            ->emptyStateHeading('No more portfolio items')
            ->emptyStateDescription('Create extra commercial project links.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMorePortfolios::route('/'),
            'create' => Pages\CreateMorePortfolio::route('/create'),
            'edit' => Pages\EditMorePortfolio::route('/{record}/edit'),
        ];
    }
}
