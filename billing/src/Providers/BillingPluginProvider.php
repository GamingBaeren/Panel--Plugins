<?php

namespace Boy132\Billing\Providers;

use App\Models\Role;
use Boy132\Billing\Console\Commands\CheckOrdersCommand;
use Boy132\Billing\Services\PayPalService;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\ServiceProvider;
use Stripe\StripeClient;

class BillingPluginProvider extends ServiceProvider
{
    public function register(): void
    {
        // Only bind StripeClient if Stripe is enabled and has a secret
        $this->app->bind(StripeClient::class, function () {
            $secret = config('billing.stripe.secret');
            if (!$secret || !config('billing.stripe.enabled')) {
                return null;
            }
            return new StripeClient($secret);
        });
        $this->app->bind(PayPalService::class, fn () => new PayPalService());
    }

    public function boot(): void
    {
        Schedule::command(CheckOrdersCommand::class)->everyMinute()->withoutOverlapping();
    }
}
