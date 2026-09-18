<?php

namespace App\Filament\Pages;

use App\Concerns\ManagesSingletonUploads;
use App\Models\SiteProfile as SiteProfileModel;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TagsInput;
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
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * @property-read Schema $form
 */
class SiteProfile extends Page
{
    use ManagesSingletonUploads;

    protected static string|\UnitEnum|null $navigationGroup = 'Website';

    protected static ?string $navigationLabel = 'Site Profile';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::UserCircle;

    protected static ?int $navigationSort = 10;

    protected string $view = 'filament.pages.singleton-form';

    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public SiteProfileModel $record;

    public function mount(): void
    {
        $this->record = SiteProfileModel::query()->first() ?? SiteProfileModel::query()->create($this->defaultData());
        $this->importSourceAssets();

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
            Section::make('Identity')
                ->schema([
                    TextInput::make('full_name')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('professional_title')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('location')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('public_email')
                        ->email()
                        ->required()
                        ->maxLength(255),
                    TextInput::make('public_phone')
                        ->tel()
                        ->maxLength(255),
                    TextInput::make('availability_text')
                        ->maxLength(255),
                    TextInput::make('years_experience')
                        ->numeric()
                        ->minValue(0)
                        ->maxValue(80),
                ])
                ->columns(2),
            Section::make('Content')
                ->schema([
                    Textarea::make('hero_intro')
                        ->required()
                        ->rows(4),
                    Textarea::make('about_text')
                        ->required()
                        ->rows(6),
                    TagsInput::make('hero_code_lines'),
                    TextInput::make('about_question')
                        ->maxLength(255),
                    Textarea::make('about_answer')
                        ->rows(4),
                ]),
            Section::make('Files')
                ->schema([
                    FileUpload::make('profile_image_path')
                        ->label('Profile image')
                        ->disk('public')
                        ->directory('profile')
                        ->visibility('public')
                        ->image()
                        ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                        ->rules(['mimes:jpg,jpeg,png,webp'])
                        ->maxSize(4096)
                        ->preventFilePathTampering(false)
                        ->editableSvgs(false)
                        ->previewable(false)
                        ->openable()
                        ->mutateStateForValidationUsing(fn (mixed $state): array => Arr::wrap($state))
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => $this->storeManagedUpload($file, 'profile'))
                        ->dehydrateStateUsing(fn (FileUpload $component): ?string => $this->normalizeManagedPath($component->getRawState(), 'profile'))
                        ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => $this->safeUploadedFileName($file)),
                    FileUpload::make('cv_path')
                        ->label('CV PDF')
                        ->disk('public')
                        ->directory('cv')
                        ->visibility('public')
                        ->acceptedFileTypes(['application/pdf'])
                        ->rules(['mimes:pdf'])
                        ->maxSize(5120)
                        ->preventFilePathTampering(false)
                        ->downloadable()
                        ->openable()
                        ->mutateStateForValidationUsing(fn (mixed $state): array => Arr::wrap($state))
                        ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file): string => $this->storeManagedUpload($file, 'cv'))
                        ->dehydrateStateUsing(fn (FileUpload $component): ?string => $this->normalizeManagedPath($component->getRawState(), 'cv'))
                        ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file): string => $this->safeUploadedFileName($file)),
                ])
                ->columns(2),
        ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $oldProfileImagePath = $this->record->profile_image_path;
        $oldCvPath = $this->record->cv_path;

        $data['profile_image_path'] = $this->normalizeManagedPath($data['profile_image_path'] ?? null, 'profile');
        $data['cv_path'] = $this->normalizeManagedPath($data['cv_path'] ?? null, 'cv');

        DB::transaction(fn () => $this->record->update($data));

        $this->deleteUnreferencedManagedFile($oldProfileImagePath, 'profile');
        $this->deleteUnreferencedManagedFile($oldCvPath, 'cv');

        $this->form->fill($this->record->refresh()->attributesToArray());

        Notification::make()
            ->title('Site profile saved')
            ->success()
            ->send();
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
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function defaultData(): array
    {
        return [
            'full_name' => 'Parth Patel',
            'professional_title' => 'Software Developer',
            'hero_intro' => 'Portfolio introduction.',
            'about_text' => 'About text.',
            'location' => 'United Kingdom',
            'public_email' => 'hello@example.com',
        ];
    }

    private function importSourceAssets(): void
    {
        if (blank($this->record->profile_image_path)) {
            $profileImagePath = $this->importSingleSourceAsset(
                base_path('source-assets/profile'),
                'profile',
                ['image/jpeg', 'image/png', 'image/webp'],
            );

            if ($profileImagePath !== null) {
                $this->record->profile_image_path = $profileImagePath;
            }
        }

        if (blank($this->record->cv_path)) {
            $cvPath = $this->importSingleSourceAsset(
                base_path('source-assets/cv'),
                'cv',
                ['application/pdf'],
            );

            if ($cvPath !== null) {
                $this->record->cv_path = $cvPath;
            }
        }

        if ($this->record->isDirty()) {
            $this->record->save();
        }
    }
}
