<?php

namespace App\Filament\Resources;

use App\Enums\PortfolioStatus;
use App\Filament\Resources\PortfolioResource\Pages;
use App\Filament\Resources\PortfolioResource\RelationManagers;
use App\Models\Experience;
use App\Models\Portfolio;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use UnitEnum;

class PortfolioResource extends Resource
{
    protected static ?string $model = Portfolio::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Portfolio';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::RectangleGroup;

    protected static ?int $navigationSort = 70;

    protected static ?string $recordTitleAttribute = 'title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Project')
                ->schema([
                    TextInput::make('title')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (Set $set, ?string $state): mixed => filled($state) ? $set('slug', Str::slug($state)) : null),
                    TextInput::make('slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Textarea::make('short_description')
                        ->required()
                        ->rows(3)
                        ->columnSpanFull(),
                    Textarea::make('full_description')
                        ->rows(8)
                        ->columnSpanFull(),
                    Select::make('experience_id')
                        ->label('Experience')
                        ->relationship('experience', 'company_name')
                        ->getOptionLabelFromRecordUsing(fn (Experience $record): string => "{$record->company_name} - {$record->role_title}")
                        ->searchable()
                        ->preload()
                        ->nullable(),
                    TagsInput::make('technologies')
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('role')
                        ->maxLength(255),
                    Textarea::make('outcome')
                        ->rows(3)
                        ->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Links and dates')
                ->schema([
                    TextInput::make('project_url')
                        ->url()
                        ->maxLength(255),
                    TextInput::make('repository_url')
                        ->url()
                        ->maxLength(255),
                    DatePicker::make('started_at'),
                    DatePicker::make('completed_at')
                        ->afterOrEqual('started_at')
                        ->prohibited(fn (Get $get): bool => (bool) $get('is_ongoing')),
                    Toggle::make('is_ongoing')
                        ->live()
                        ->default(false),
                    TextInput::make('sort_order')
                        ->numeric()
                        ->integer()
                        ->minValue(0)
                        ->default(0)
                        ->required(),
                ])
                ->columns(2),
            Section::make('Publication')
                ->schema([
                    Select::make('status')
                        ->options([
                            PortfolioStatus::Draft->value => 'Draft',
                            PortfolioStatus::Published->value => 'Published',
                        ])
                        ->required()
                        ->default(PortfolioStatus::Draft->value)
                        ->live(),
                    DateTimePicker::make('published_at')
                        ->seconds(false)
                        ->required(fn (Get $get): bool => $get('status') === PortfolioStatus::Published->value),
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
                TextColumn::make('slug')->searchable()->toggleable(),
                TextColumn::make('experience.company_name')->label('Experience')->searchable()->toggleable(),
                TextColumn::make('status')->badge()->sortable(),
                IconColumn::make('is_ongoing')->label('Ongoing')->boolean()->sortable(),
                TextColumn::make('published_at')->dateTime()->sortable()->placeholder('Draft'),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    PortfolioStatus::Draft->value => 'Draft',
                    PortfolioStatus::Published->value => 'Published',
                ]),
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
            ->emptyStateHeading('No portfolio projects')
            ->emptyStateDescription('Create portfolio projects and attach media.');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\MediaRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPortfolios::route('/'),
            'create' => Pages\CreatePortfolio::route('/create'),
            'edit' => Pages\EditPortfolio::route('/{record}/edit'),
        ];
    }
}
