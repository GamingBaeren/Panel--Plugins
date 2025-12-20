<?php

namespace Boy132\Register\Models;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;

class UserVerifyEmail implements MustVerifyEmailContract
{
    public function __construct(protected User $user) {}

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->user->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        return $this->user->forceFill([
            'email_verified_at' => $this->user->freshTimestamp(),
        ])->save();
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmail());
    }

    public function getEmailForVerification(): string
    {
        return $this->user->email;
    }

    public function notify(mixed $instance): void
    {
        $this->user->notify($instance);
    }

    public function getKey(): mixed
    {
        return $this->user->getKey();
    }
}
