<?php

namespace Boy132\Billing\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentMethod: string implements HasLabel
{
    case Stripe = 'stripe';
    case PayPal = 'paypal';

    public function getLabel(): string
    {
        return match ($this) {
            self::Stripe => 'Stripe',
            self::PayPal => 'PayPal',
        };
    }

    public function isEnabled(): bool
    {
        return match ($this) {
            self::Stripe => !empty(config('billing.stripe.enabled')) && !empty(config('billing.stripe.key')),
            self::PayPal => !empty(config('billing.paypal.enabled')) && !empty(config('billing.paypal.client_id')),
        };
    }

    public static function getEnabledMethods(): array
    {
        return array_filter(self::cases(), fn (self $method) => $method->isEnabled());
    }
}
