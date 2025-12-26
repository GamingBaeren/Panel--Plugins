<?php

namespace Boy132\Billing\Filament\App\Resources\Orders\Pages;

use Boy132\Billing\Enums\PaymentMethod;
use Boy132\Billing\Models\Order;
use Boy132\Billing\Services\PayPalService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;
use Stripe\StripeClient;

class CheckoutPage extends Page
{
    protected string $view = 'billing::checkout';

    public Order $order;

    public string $selectedMethod = 'stripe';

    public function mount(Order $order): void
    {
        $this->order = $order;

        $enabledMethods = PaymentMethod::getEnabledMethods();
        if (empty($enabledMethods)) {
            Notification::make()
                ->title('No payment methods available')
                ->danger()
                ->send();
            redirect(route('filament.app.resources.orders.index'));
        }

        // Set default to first enabled method
        $this->selectedMethod = current($enabledMethods)->value;

        $order->update(['payment_method' => $this->selectedMethod]);
    }

    public function getTitle(): string | Htmlable
    {
        return 'Checkout - Order #' . $this->order->id;
    }

    public function updatePaymentMethod(string $method): void
    {
        if (!PaymentMethod::tryFrom($method)?->isEnabled()) {
            Notification::make()
                ->title('Invalid payment method')
                ->danger()
                ->send();
            return;
        }

        $this->selectedMethod = $method;
        $this->order->update(['payment_method' => $method]);
    }

    public function proceedToStripe(): void
    {
        try {
            /** @var StripeClient $stripeClient */
            $stripeClient = app(StripeClient::class);

            $session = $this->order->getCheckoutSession();
            redirect($session->url);
        } catch (Exception $e) {
            Notification::make()
                ->title('Payment Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function proceedToPayPal(): void
    {
        try {
            /** @var PayPalService $paypalService */
            $paypalService = app(PayPalService::class);

            $paypalOrderId = $paypalService->createOrder($this->order);
            $approvalLink = null;

            $orderDetails = $paypalService->getOrderDetails($paypalOrderId);
            foreach ($orderDetails['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approvalLink = $link['href'];
                    break;
                }
            }

            if (!$approvalLink) {
                throw new Exception('PayPal approval link not found');
            }

            redirect($approvalLink);
        } catch (Exception $e) {
            Notification::make()
                ->title('Payment Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}

