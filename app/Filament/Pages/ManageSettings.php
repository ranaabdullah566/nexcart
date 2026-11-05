<?php

namespace App\Filament\Pages;

use App\Support\BusinessProfile;
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

    protected static ?string $navigationLabel = 'Store settings';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static string|\UnitEnum|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Store settings';

    /** @var array<string, mixed> */
    public ?array $data = [];

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
                    Tab::make('Store')->icon(Heroicon::OutlinedBuildingStorefront)->schema([
                        Section::make('Store information')->columns(2)->schema([
                            TextInput::make('store.name')->label('Store name')->required(),
                            TextInput::make('store.tagline')->label('Tagline'),
                            TextInput::make('store.email')->label('Contact email')->email(),
                            TextInput::make('store.phone')->label('Contact phone')->tel(),
                        ]),
                        Section::make('Money & tax')->columns(3)->schema([
                            TextInput::make('store.currency')->label('Currency code')->length(3)->required(),
                            TextInput::make('store.currency_symbol')->label('Symbol')->maxLength(5)->required(),
                            Select::make('store.timezone')->label('Timezone')->searchable()
                                ->options(array_combine(timezone_identifiers_list(), timezone_identifiers_list()))
                                ->required(),
                            TextInput::make('store.tax_rate')->label('Tax rate (%)')->numeric()->minValue(0)->maxValue(100),
                            Toggle::make('store.tax_inclusive')->label('Prices include tax')->inline(false),
                        ]),
                    ]),
                    Tab::make('Appearance')->icon(Heroicon::OutlinedSwatch)->schema([
                        Section::make('Theme')->description('Design tokens used across the storefront and checkout.')->columns(2)->schema([
                            ColorPicker::make('theme.primary')->label('Primary colour')->required(),
                            ColorPicker::make('theme.accent')->label('Accent colour')->required(),
                            Select::make('theme.mode')->label('Default mode')->options([
                                'dark' => 'Dark', 'light' => 'Light', 'auto' => 'Follow device',
                            ])->required(),
                            Select::make('theme.radius')->label('Corner roundness')->options([
                                '0.25rem' => 'Sharp', '0.5rem' => 'Soft', '1rem' => 'Rounded', '1.5rem' => 'Very rounded',
                            ])->required(),
                        ]),
                    ]),
                    Tab::make('Modules')->icon(Heroicon::OutlinedPuzzlePiece)->schema([
                        Section::make('Feature modules')
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
            Action::make('save')->label('Save settings')->submit('save')
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

        Notification::make()->title('Settings saved')->success()->send();
    }
}
