<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EducationResource\Pages;
use App\Models\Education;
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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class EducationResource extends Resource
{
    protected static ?string $model = Education::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Education';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::AcademicCap;

    protected static ?int $navigationSort = 60;

    protected static ?string $recordTitleAttribute = 'institution';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Education')
                ->schema([
                    TextInput::make('institution')->required()->maxLength(255),
                    TextInput::make('qualification')->required()->maxLength(255),
                    TextInput::make('field_of_study')->maxLength(255),
                    DatePicker::make('started_at'),
                    DatePicker::make('ended_at')
                        ->afterOrEqual(fn (Get $get): ?string => filled($get('started_at')) ? 'started_at' : null),
                    Textarea::make('description')->rows(4)->columnSpanFull(),
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
                TextColumn::make('institution')->searchable()->sortable(),
                TextColumn::make('qualification')->searchable()->sortable(),
                TextColumn::make('field_of_study')->searchable()->toggleable(),
                TextColumn::make('started_at')->date()->sortable(),
                TextColumn::make('ended_at')->date()->sortable(),
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
            ->emptyStateIcon(Heroicon::AcademicCap)
            ->emptyStateHeading('No education')
            ->emptyStateDescription('Create education timeline entries.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEducation::route('/'),
            'create' => Pages\CreateEducation::route('/create'),
            'edit' => Pages\EditEducation::route('/{record}/edit'),
        ];
    }
}
