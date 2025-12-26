<?php

namespace Boy132\Billing\Providers;

use App\Providers\RouteServiceProvider;
use Boy132\Billing\Http\Controllers\Api\CheckoutController;
use Illuminate\Support\Facades\Route;

class BillingRoutesProvider extends RouteServiceProvider
{
    public function boot(): void
    {
        $this->routes(function () {
            Route::middleware(['web'])
                ->namespace('Boy132\Billing\Http\Controllers')
                ->group(function () {
                    // Checkout page
                    Route::get('checkout/{order}', [CheckoutController::class, 'page'])->name('billing.checkout.page')->middleware(['auth']);

                    // Checkout process routes
                    Route::post('checkout/stripe', [CheckoutController::class, 'checkoutStripe'])->name('billing.checkout.stripe')->middleware(['auth']);
                    Route::post('checkout/paypal', [CheckoutController::class, 'checkoutPaypal'])->name('billing.checkout.paypal')->middleware(['auth']);

                    // Stripe callback routes
                    Route::get('checkout/success', [CheckoutController::class, 'success'])->name('billing.checkout.success')->withoutMiddleware(['auth']);
                    Route::get('checkout/cancel', [CheckoutController::class, 'cancel'])->name('billing.checkout.cancel')->withoutMiddleware(['auth']);

                    // PayPal callback routes
                    Route::get('paypal/success', [CheckoutController::class, 'paypalSuccess'])->name('billing.paypal.success')->withoutMiddleware(['auth']);
                    Route::get('paypal/cancel', [CheckoutController::class, 'paypalCancel'])->name('billing.paypal.cancel')->withoutMiddleware(['auth']);
                    Route::post('paypal/webhook', [CheckoutController::class, 'paypalWebhook'])->name('billing.paypal.webhook')->withoutMiddleware(['auth', 'csrf']);
                });
        });
    }
}