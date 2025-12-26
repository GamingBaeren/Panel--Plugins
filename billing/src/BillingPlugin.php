<?php

namespace Boy132\Billing;

use App\Contracts\Plugins\HasPluginSettings;
use App\Enums\CustomizationKey;
use App\Filament\App\Resources\Servers\ServerResource;
use App\Filament\Pages\Auth\EditProfile;
use App\Traits\EnvironmentWriterTrait;
use Boy132\Billing\Enums\PaymentMethod;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Navigation\NavigationItem;
use Filament\Notifications\Notification;
use Filament\Panel;

class BillingPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'billing';
    }

    public function register(Panel $panel): void
    {
        $id = str($panel->getId())->title();

        if ($panel->getId() === 'app') {
            ServerResource::embedServerList();

            $panel->navigation(true);
            $panel->topbar(function () {
                $navigationType = user()?->getCustomization(CustomizationKey::TopNavigation);

                return $navigationType === 'topbar' || $navigationType === 'mixed' || $navigationType === true;
            });

            $panel->navigationItems([
                NavigationItem::make(fn () => trans('filament-panels::auth/pages/edit-profile.label'))
                    ->icon('tabler-user-circle')
                    ->url(fn () => EditProfile::getUrl(panel: 'app'))
                    ->isActiveWhen(fn () => request()->routeIs(EditProfile::getRouteName()))
                    ->sort(99),
            ]);

            $panel->clearCachedComponents();
        }

        $panel->discoverResources(plugin_path($this->getId(), "src/Filament/$id/Resources"), "Boy132\\Billing\\Filament\\$id\\Resources");
        $panel->discoverPages(plugin_path($this->getId(), "src/Filament/$id/Pages"), "Boy132\\Billing\\Filament\\$id\\Pages");
        $panel->discoverWidgets(plugin_path($this->getId(), "src/Filament/$id/Widgets"), "Boy132\\Billing\\Filament\\$id\\Widgets");
    }

    public function boot(Panel $panel): void {}

    public function getSettingsForm(): array
    {
        return [
            // Stripe Settings
            Toggle::make('stripe_enabled')
                ->label('Enable Stripe')
                ->default(fn () => config('billing.stripe.enabled'))
                ->inline(),
            TextInput::make('stripe_key')
                ->label('Stripe Key')
                ->default(fn () => config('billing.stripe.key'))
                ->visible(fn () => config('billing.stripe.enabled')),
            TextInput::make('stripe_secret')
                ->label('Stripe Secret')
                ->default(fn () => config('billing.stripe.secret'))
                ->visible(fn () => config('billing.stripe.enabled')),

            // PayPal Settings
            Toggle::make('paypal_enabled')
                ->label('Enable PayPal')
                ->default(fn () => config('billing.paypal.enabled'))
                ->inline(),
            Select::make('paypal_mode')
                ->label('PayPal Mode')
                ->options([
                    'sandbox' => 'Sandbox (Testing)',
                    'live' => 'Live (Production)',
                ])
                ->default(fn () => config('billing.paypal.mode', 'sandbox'))
                ->visible(fn () => config('billing.paypal.enabled')),
            TextInput::make('paypal_client_id')
                ->label('PayPal Client ID')
                ->default(fn () => config('billing.paypal.client_id'))
                ->visible(fn () => config('billing.paypal.enabled')),
            TextInput::make('paypal_secret')
                ->label('PayPal Secret')
                ->default(fn () => config('billing.paypal.secret'))
                ->visible(fn () => config('billing.paypal.enabled')),
            TextInput::make('paypal_webhook_id')
                ->label('PayPal Webhook ID (optional)')
                ->helperText('For webhook signature verification')
                ->default(fn () => config('billing.paypal.webhook_id'))
                ->visible(fn () => config('billing.paypal.enabled')),

            // General Settings
            Select::make('currency')
                ->label('Currency')
                ->required()
                ->default(fn () => config('billing.currency'))
                ->options([
                    'USD' => 'US Dollar',
                    'EUR' => 'Euro',
                    'GBP' => 'British Pound',
                ]),
            TagsInput::make('deployment_tags')
                ->label('Default node tags for deployment'),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'STRIPE_ENABLED' => $data['stripe_enabled'] ? 'true' : 'false',
            'STRIPE_KEY' => $data['stripe_key'] ?? '',
            'STRIPE_SECRET' => $data['stripe_secret'] ?? '',
            'PAYPAL_ENABLED' => $data['paypal_enabled'] ? 'true' : 'false',
            'PAYPAL_MODE' => $data['paypal_mode'] ?? 'sandbox',
            'PAYPAL_CLIENT_ID' => $data['paypal_client_id'] ?? '',
            'PAYPAL_SECRET' => $data['paypal_secret'] ?? '',
            'PAYPAL_WEBHOOK_ID' => $data['paypal_webhook_id'] ?? '',
            'BILLING_CURRENCY' => $data['currency'],
            'BILLING_DEPLOYMENT_TAGS' => implode(',', $data['deployment_tags'] ?? []),
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
