<?php

namespace App\Filament\Resources;

use App\Enums\SkillCategory;
use App\Filament\Resources\SkillResource\Pages;
use App\Models\Skill;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class SkillResource extends Resource
{
    protected static ?string $model = Skill::class;

    protected static string|UnitEnum|null $navigationGroup = 'Content';

    protected static ?string $navigationLabel = 'Skills';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Sparkles;

    protected static ?int $navigationSort = 40;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Skill')
                ->schema([
                    TextInput::make('name')->required()->maxLength(255),
                    Select::make('category')->options(SkillCategory::class)->required(),
                    TextInput::make('icon_key')->maxLength(255),
                    TextInput::make('proficiency')->numeric()->integer()->minValue(0)->maxValue(100),
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
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('category')->badge()->sortable(),
                TextColumn::make('icon_key')->searchable()->toggleable(),
                TextColumn::make('proficiency')->sortable()->placeholder('Not supplied'),
                ToggleColumn::make('is_visible')->label('Visible')->sortable(),
            ])
            ->filters([
                SelectFilter::make('category')->options(SkillCategory::class),
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
            ->emptyStateIcon(Heroicon::Sparkles)
            ->emptyStateHeading('No skills')
            ->emptyStateDescription('Create skills grouped by category.');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSkills::route('/'),
            'create' => Pages\CreateSkill::route('/create'),
            'edit' => Pages\EditSkill::route('/{record}/edit'),
        ];
    }
}
