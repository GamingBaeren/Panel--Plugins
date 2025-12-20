<?php

namespace Boy132\Register;

use App\Contracts\Plugins\HasPluginSettings;
use App\Traits\EnvironmentWriterTrait;
use Boy132\Register\Filament\Pages\Auth\EmailVerificationPrompt;
use Boy132\Register\Filament\Pages\Auth\Register;
use Boy132\Register\Http\Middleware\EnsureEmailIsVerified;
use Filament\Contracts\Plugin;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Panel;

class RegisterPlugin implements HasPluginSettings, Plugin
{
    use EnvironmentWriterTrait;

    public function getId(): string
    {
        return 'register';
    }

    public function register(Panel $panel): void
    {
        $panel->registration(Register::class);

        if (config('register.enable_email_verification')) {
            $panel->emailVerification(EmailVerificationPrompt::class);
            $panel->emailVerifiedMiddlewareName(EnsureEmailIsVerified::class);
        }
    }

    public function boot(Panel $panel): void {}

    public function getSettingsForm(): array
    {
        return [
            Toggle::make('enable_email_verification')
                ->label('Enable E-mail verification?')
                ->inline(false)
                ->default(fn () => config('register.enable_email_verification')),
        ];
    }

    public function saveSettings(array $data): void
    {
        $this->writeToEnvironment([
            'REGISTER_ENABLE_EMAIL_VERIFICATION' => $data['enable_email_verification'] ? 'true' : 'false',
        ]);

        Notification::make()
            ->title('Settings saved')
            ->success()
            ->send();
    }
}
