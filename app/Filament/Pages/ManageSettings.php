<?php

namespace App\Filament\Pages;

use App\Support\BusinessProfile;
use App\Support\Locales;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * One place to customise the whole store: info, money, theme, feature modules.
 *
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected string $view = 'filament.pages.manage-settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 10;

    /** @var array<string, mixed> */
    public ?array $data = [];

    public static function getNavigationLabel(): string
    {
        return __('Store settings');
    }

    public function getTitle(): string
    {
        return __('Store settings');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Settings');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('settings.view') ?? false;
    }

    public function mount(): void
    {
        $s = setting();

        $this->form->fill([
            'store' => [
                'name' => $s->get('store.name'),
                'tagline' => $s->get('store.tagline'),
                'email' => $s->get('store.email'),
                'phone' => $s->get('store.phone'),
                'currency' => $s->get('store.currency'),
                'currency_symbol' => $s->get('store.currency_symbol'),
                'timezone' => $s->get('store.timezone'),
                'default_locale' => $s->get('store.default_locale'),
                'locales' => $s->get('store.locales'),
                'tax_rate' => $s->get('store.tax_rate'),
                'tax_inclusive' => (bool) $s->get('store.tax_inclusive'),
            ],
            'theme' => [
                'primary' => $s->get('theme.primary'),
                'accent' => $s->get('theme.accent'),
                'mode' => $s->get('theme.mode'),
                'radius' => $s->get('theme.radius'),
            ],
            'modules' => collect($this->moduleOptions())
                ->mapWithKeys(fn ($label, $key) => [$key => BusinessProfile::hasModule($key)])
                ->all(),
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->statePath('data')
            ->components([
                Tabs::make('Settings')->persistTabInQueryString()->tabs([
                    Tab::make(__('Store'))->icon(Heroicon::OutlinedBuildingStorefront)->schema([
                        Section::make(__('Store information'))->columns(2)->schema([
                            TextInput::make('store.name')->label(__('Store name'))->required(),
                            TextInput::make('store.tagline')->label(__('Tagline')),
                            TextInput::make('store.email')->label(__('Contact email'))->email(),
                            TextInput::make('store.phone')->label(__('Contact phone'))->tel(),
                        ]),
                        Section::make(__('Languages'))->columns(2)->schema([
                            Select::make('store.default_locale')->label(__('Default language'))
                                ->options(collect(Locales::all())->map(fn ($m) => $m['label'])->all())->required(),
                            Select::make('store.locales')->label(__('Enabled languages'))->multiple()
                                ->options(collect(Locales::all())->map(fn ($m) => $m['label'])->all())->required(),
                        ]),
                        Section::make(__('Money & tax'))->columns(3)->schema([
                            TextInput::make('store.currency')->label(__('Currency code'))->length(3)->required(),
                            TextInput::make('store.currency_symbol')->label(__('Symbol'))->maxLength(5)->required(),
                            Select::make('store.timezone')->label(__('Timezone'))->searchable()
                                ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                                ->required(),
                            TextInput::make('store.tax_rate')->label(__('Tax rate (%)'))->numeric()->minValue(0)->maxValue(100),
                            Toggle::make('store.tax_inclusive')->label(__('Prices include tax'))->inline(false),
                        ]),
                    ]),
                    Tab::make(__('Appearance'))->icon(Heroicon::OutlinedSwatch)->schema([
                        Section::make(__('Theme'))->columns(2)->schema([
                            ColorPicker::make('theme.primary')->label(__('Primary colour'))->required(),
                            ColorPicker::make('theme.accent')->label(__('Accent colour'))->required(),
                            Select::make('theme.mode')->label(__('Default mode'))->options([
                                'dark' => __('Dark'), 'light' => __('Light'), 'auto' => __('Follow device'),
                            ])->required(),
                            Select::make('theme.radius')->label(__('Corner roundness'))->options([
                                '0.25rem' => __('Sharp'), '0.5rem' => __('Soft'), '1rem' => __('Rounded'), '1.5rem' => __('Very rounded'),
                            ])->required(),
                        ]),
                    ]),
                    Tab::make(__('Modules'))->icon(Heroicon::OutlinedPuzzlePiece)->schema([
                        Section::make(__('Feature modules'))
                            ->description('Switch features on or off for this business. Defaults come from the business profile chosen at setup.')
                            ->columns(2)
                            ->schema(collect($this->moduleOptions())->map(
                                fn ($label, $key) => Toggle::make("modules.{$key}")->label($label)
                            )->values()->all()),
                    ]),
                ]),
            ]);
    }

    /** @return array<string, string> */
    protected function moduleOptions(): array
    {
        return collect(BusinessProfile::all())
            ->pluck('modules')->flatten()->unique()->sort()
            ->mapWithKeys(fn ($m) => [$m => str($m)->replace('_', ' ')->title()->toString()])
            ->all();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label(__('Save settings'))->submit('save')
                ->visible(fn () => auth()->user()?->can('settings.update')),
        ];
    }

    public function save(): void
    {
        abort_unless(auth()->user()?->can('settings.update'), 403);

        $data = $this->form->getState();
        $flat = [];

        foreach (['store', 'theme', 'modules'] as $group) {
            foreach ($data[$group] ?? [] as $key => $value) {
                $flat["{$group}.{$key}"] = $value;
            }
        }

        setting()->setMany($flat);

        Notification::make()->title(__('Settings saved'))->success()->send();
    }
}
