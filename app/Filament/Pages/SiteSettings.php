<?php

namespace App\Filament\Pages;

use App\Concerns\ManagesSingletonUploads;
use App\Enums\SiteTheme;
use App\Models\SiteSetting;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * @property-read Schema $form
 */
class SiteSettings extends Page
{
    use ManagesSingletonUploads;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Site Settings';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?int $navigationSort = 20;

    protected string $view = 'filament.pages.singleton-form';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public SiteSetting $record;

    public function mount(): void
    {
        $this->record = SiteSetting::query()->first() ?? SiteSetting::query()->create($this->defaultData());

        $this->form->fill($this->record->refresh()->attributesToArray());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->model($this->record)
            ->operation('edit')
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Site')
                ->schema([
                    TextInput::make('site_name')
                        ->required()
                        ->maxLength(255),
                    Select::make('default_theme')
                        ->options([
                            SiteTheme::System->value => 'System',
                            SiteTheme::Light->value => 'Light',
                            SiteTheme::Dark->value => 'Dark',
                        ])
                        ->required(),
                    TextInput::make('accent_color')
                        ->required()
                        ->regex('/^#[0-9A-Fa-f]{6}$/')
                        ->maxLength(7),
                    TextInput::make('contact_recipient_email')
                        ->email()
                        ->maxLength(255),
                ])
                ->columns(2),
            Section::make('Metadata')
                ->schema([
                    TextInput::make('meta_title')
                        ->required()
                        ->maxLength(255),
                    Textarea::make('meta_description')
                        ->required()
                        ->rows(4),
                    TextInput::make('canonical_url')
                        ->url()
                        ->maxLength(255),
                    FileUpload::make('og_image_path')
                        ->label('OG image')
                        ->disk('public')
                        ->directory('site')
                        ->visibility('public')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->rules(['mimes:jpg,jpeg,png,webp'])
                        ->maxSize(2048)
                        ->preventFilePathTampering(false)
                        ->editableSvgs(false)
                        ->previewable(false)
                        ->openable()
                        ->mutateStateForValidationUsing(fn (mixed $state): array => Arr::wrap($state))
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => $this->storeManagedUpload($file, 'site'))
                        ->dehydrateStateUsing(fn (FileUpload $component): ?string => $this->normalizeManagedPath($component->getRawState(), 'site'))
                        ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => $this->safeUploadedFileName($file)),
                ])
                ->columns(2),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $oldOgImagePath = $this->record->og_image_path;

        $data['og_image_path'] = $this->normalizeManagedPath($data['og_image_path'] ?? null, 'site');

        DB::transaction(fn () => $this->record->update($data));

        $this->deleteUnreferencedManagedFile($oldOgImagePath, 'site');

        $this->form->fill($this->record->refresh()->attributesToArray());

        Notification::make()
            ->title('Site settings saved')
            ->success()
            ->send();
    }

    public function repairStorageLink(): void
    {
        try {
            $storagePath = config('filesystems.disks.public.root');
            $publicStoragePath = public_path('storage');

            if (! File::isDirectory($storagePath)) {
                File::makeDirectory($storagePath, 0755, true);
            }

            if (! File::exists($publicStoragePath)) {
                File::makeDirectory($publicStoragePath, 0755, true);
            }

            if (! File::isDirectory($publicStoragePath) || ! File::isWritable($storagePath)) {
                Notification::make()
                    ->title('Public storage needs hosting permission')
                    ->body('Check that public/storage exists and is writable by PHP.')
                    ->warning()
                    ->send();

                return;
            }

            Notification::make()
                ->title('Public storage ready')
                ->body('Uploads will be stored in public/storage.')
                ->success()
                ->send();
        } catch (Throwable $exception) {
            Notification::make()
                ->title('Storage repair failed')
                ->body($exception->getMessage())
                ->danger()
                ->send();
        }
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([
            Form::make([EmbeddedSchema::make('form')])
                ->id('form')
                ->livewireSubmitHandler('save')
                ->footer([
                    Actions::make($this->getFormActions())
                        ->alignment($this->getFormActionsAlignment())
                        ->key('form-actions'),
                ]),
        ]);
    }

    /**
     * @return array<Action>
     */
    public function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save changes')
                ->submit('save')
                ->keyBindings(['mod+s']),
            Action::make('repairStorageLink')
                ->label('Repair public storage')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Repair public storage?')
                ->modalDescription('This creates public/storage for hosts where symlink and terminal access are disabled.')
                ->action('repairStorageLink'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultData(): array
    {
        return [
            'site_name' => 'Parth Patel',
            'default_theme' => SiteTheme::Dark,
            'accent_color' => '#f59e0b',
            'meta_title' => 'Parth Patel',
            'meta_description' => 'Personal portfolio.',
        ];
    }
}
