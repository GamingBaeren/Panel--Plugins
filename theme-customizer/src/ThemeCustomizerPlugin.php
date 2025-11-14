<?php

namespace Boy132\ThemeCustomizer;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Filament\Contracts\Plugin;
use Filament\FontProviders\LocalFontProvider;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Panel;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Colors\Color;
use Filament\Support\Facades\FilamentColor;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString;

class ThemeCustomizerPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public const COLOR_NAMES = [
        'gray',
        'primary',
        'info',
        'success',
        'warning',
        'danger',
    ];

    public function getId(): string
    {
        return 'theme-customizer';
    }

    public function register(Panel $panel): void
    {
        $font = config('theme-customizer.font');
        if ($font) {
            $panel->font($font, provider: LocalFontProvider::class);

            $fontUrl = asset("storage/fonts/$font.ttf");
            $panel->renderHook(
                PanelsRenderHook::STYLES_BEFORE,
                fn () => Blade::render("<style>@font-face { font-family: $font; src: url(\"$fontUrl\"); }</style>")
            );
        }

        $colors = [];

        foreach (ThemeCustomizerPlugin::COLOR_NAMES as $color) {
            $value = config('theme-customizer.colors.' . $color);
            if ($value) {
                $colors[$color] = $value;
            }
        }

        $panel->colors($colors);
    }

    public function boot(Panel $panel): void {}

    public function getSettingsForm(): array
    {
        $schema = [
            Select::make('font')
                ->live()
                ->selectablePlaceholder()
                ->placeholder('Default Font')
                ->options(function () {
                    $fonts = [];

                    foreach (Storage::disk('public')->allFiles('fonts') as $file) {
                        $fileInfo = pathinfo($file);

                        if ($fileInfo['extension'] === 'ttf') {
                            $fonts[$fileInfo['filename']] = $fileInfo['filename'];
                        }
                    }

                    return $fonts;
                })
                ->default(fn () => config('theme-customizer.font')),
            TextEntry::make('font_preview')
                ->label('Preview')
                ->state(function (Get $get) {
                    $fontName = $get('font');

                    if (!$fontName) {
                        return 'The quick brown fox jumps over the lazy dog';
                    }

                    $fontUrl = asset("storage/fonts/$fontName.ttf");
                    $style = <<<CSS
                        @font-face {
                            font-family: $fontName;
                            src: url("$fontUrl");
                        }
                        .preview-text {
                            font-family: $fontName;
                            font-size: 10px;
                            margin-top: 10px;
                            display: block;
                        }
                    CSS;

                    return new HtmlString(<<<HTML
                        <style>
                        {$style}
                        </style>
                        <span class="preview-text">The quick brown fox jumps over the lazy dog</span>
                    HTML);
                }),
        ];

        foreach (static::COLOR_NAMES as $color) {
            $defaultColor = FilamentColor::getColor($color);

            $schema[] = ColorPicker::make($color)
                ->live()
                ->rgb()
                ->placeholder(Color::convertToRgb($defaultColor[600]))
                ->default(fn () => config('theme-customizer.colors.' . $color));

            $schema[] = TextEntry::make($color . '_preview')
                ->label('Preview')
                ->state($color)
                ->badge()
                ->color(fn (Get $get) => $get($color) ? Color::rgb($get($color)) : $defaultColor);
        }

        return [
            Group::make($schema)
                ->columns(),
        ];
    }

    public function saveSettings(array $data): void
    {
        $data = array_filter(Arr::mapWithKeys($data, fn ($value, $key) => $key === 'font' ? [$key => $value] : ['THEME_CUSTOMIZER_COLORS_' . strtoupper($key) => $value]));
        $this->writeToEnvironment($data);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
