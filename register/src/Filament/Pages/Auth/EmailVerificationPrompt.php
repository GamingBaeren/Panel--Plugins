<?php

namespace Boy132\Register\Filament\Pages\Auth;

use Boy132\Register\Models\UserVerifyEmail;
use Filament\Auth\Pages\EmailVerification\EmailVerificationPrompt as BaseEmailVerificationPrompt;
use Filament\Facades\Filament;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Schema;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\HtmlString;

class EmailVerificationPrompt extends BaseEmailVerificationPrompt
{
    protected function getVerifiable(): MustVerifyEmail
    {
        return new UserVerifyEmail(Filament::auth()->user());
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Text::make(__('filament-panels::auth/pages/email-verification/email-verification-prompt.messages.notification_sent', [
                    'email' => filament()->auth()->user()->email,
                ])),
                Text::make(new HtmlString(
                    __('filament-panels::auth/pages/email-verification/email-verification-prompt.messages.notification_not_received') .
                    ' ' .
                    $this->resendNotificationAction->toHtml(),
                )),
            ]);
    }
}
