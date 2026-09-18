<?php

namespace App\Filament\Pages;

use App\Models\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContactInbox extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string|\UnitEnum|null $navigationGroup = 'Messages';

    protected static ?string $navigationLabel = 'Contact Inbox';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Inbox;

    protected static ?int $navigationSort = 80;

    protected string $view = 'filament.pages.contact-inbox';

    public static function getNavigationBadge(): ?string
    {
        $count = ContactMessage::query()->unread()->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'warning';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn (): Builder => ContactMessage::query())
            ->columns([
                IconColumn::make('read_at')
                    ->label('Read')
                    ->boolean()
                    ->getStateUsing(fn (ContactMessage $record): bool => filled($record->read_at)),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight(fn (ContactMessage $record): string => blank($record->read_at) ? 'bold' : 'medium'),
                TextColumn::make('email')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('subject')
                    ->searchable()
                    ->limit(60)
                    ->weight(fn (ContactMessage $record): string => blank($record->read_at) ? 'bold' : 'medium'),
                TextColumn::make('created_at')
                    ->label('Received')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('read_at')
                    ->label('Read state')
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('read_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('read_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make()
                    ->schema([
                        Section::make('Message')
                            ->schema([
                                TextEntry::make('name'),
                                TextEntry::make('email')->copyable(),
                                TextEntry::make('subject'),
                                TextEntry::make('message')->prose()->columnSpanFull(),
                                TextEntry::make('created_at')->label('Received')->dateTime(),
                                TextEntry::make('read_at')->label('Read')->dateTime()->placeholder('Unread'),
                            ])
                            ->columns(2),
                    ]),
                Action::make('markRead')
                    ->label('Mark read')
                    ->icon(Heroicon::CheckCircle)
                    ->visible(fn (ContactMessage $record): bool => blank($record->read_at))
                    ->action(function (ContactMessage $record): void {
                        $record->forceFill(['read_at' => now()])->save();

                        Notification::make()->title('Message marked read')->success()->send();
                    }),
                Action::make('markUnread')
                    ->label('Mark unread')
                    ->icon(Heroicon::Envelope)
                    ->visible(fn (ContactMessage $record): bool => filled($record->read_at))
                    ->action(function (ContactMessage $record): void {
                        $record->forceFill(['read_at' => null])->save();

                        Notification::make()->title('Message marked unread')->success()->send();
                    }),
                DeleteAction::make()->requiresConfirmation(),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateIcon(Heroicon::Inbox)
            ->emptyStateHeading('No contact messages')
            ->emptyStateDescription('Valid public contact submissions appear here.');
    }
}
