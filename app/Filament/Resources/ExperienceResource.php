<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExperienceResource\Pages;
use App\Models\Experience;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class ExperienceResource extends Resource
{
    protected static ?string $model = Experience::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Experience';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Briefcase;

    protected static ?int $navigationSort = 50;

    protected static ?string $recordTitleAttribute = 'role_title';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Role')
                ->schema([
                    TextInput::make('company_name')->required()->maxLength(255),
                    TextInput::make('role_title')->required()->maxLength(255),
                    TextInput::make('employment_type')->maxLength(255),
                    TextInput::make('location')->maxLength(255),
                    TextInput::make('company_url')->url()->maxLength(255),
                    Textarea::make('summary')->required()->rows(5)->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Dates and visibility')
                ->schema([
                    DatePicker::make('started_at')->required(),
                    DatePicker::make('ended_at')
                        ->required(fn (Get $get): bool => ! $get('is_current'))
                        ->afterOrEqual('started_at'),
                    Toggle::make('is_current')->live()->default(false),
                    TextInput::make('sort_order')->numeric()->integer()->minValue(0)->default(0)->required(),
                    Toggle::make('is_visible')->label('Visible')->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sort_order')->label('Order')->sortable(),
                TextColumn::make('company_name')->searchable()->sortable(),
                TextColumn::make('role_title')->searchable()->sortable(),
                TextColumn::make('employment_type')->searchable()->toggleable(),
                TextColumn::make('started_at')->date()->sortable(),
                TextColumn::make('ended_at')->date()->placeholder('Current')->sortable(),
                IconColumn::make('is_current')->label('Current')->boolean()->sortable(),
                ToggleColumn::make('is_visible')->label('Visible')->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_current')->label('Current role'),
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
            ->emptyStateIcon(Heroicon::Briefcase)
            ->emptyStateHeading('No experience')
            ->emptyStateDescription('Create roles and timeline entries.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListExperiences::route('/'),
            'create' => Pages\CreateExperience::route('/create'),
            'edit' => Pages\EditExperience::route('/{record}/edit'),
        ];
    }
}
