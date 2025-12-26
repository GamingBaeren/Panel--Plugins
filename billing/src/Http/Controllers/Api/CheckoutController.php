<?php

namespace Boy132\Billing\Http\Controllers\Api;

use App\Filament\Server\Pages\Console;
use App\Http\Controllers\Controller;
use Boy132\Billing\Filament\App\Resources\Orders\Pages\ListOrders;
use Boy132\Billing\Models\Order;
use Boy132\Billing\Services\PayPalService;
use Exception;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use Stripe\StripeClient;

class CheckoutController extends Controller
{
    public function __construct(
        private ?StripeClient $stripeClient,
        private PayPalService $paypalService
    ) {}

    public function page(Request $request)
    {
        $orderParam = $request->route('order');

        // Route model binding may provide the Order model or just the id.
        if ($orderParam instanceof Order) {
            $order = $orderParam;
        } else {
            $order = Order::find($orderParam);
        }

        if (!$order) {
            return redirect(Filament::getPanel('app')->getUrl());
        }

        $selected = $request->get('method', $order->payment_method ?? 'stripe');

        return view('billing::checkout', [
            'order' => $order,
            'selectedMethod' => $selected,
        ]);
    }

    public function checkoutStripe(Request $request): RedirectResponse
    {
        try {
            $orderId = $request->get('order_id');
            
            /** @var ?Order $order */
            $order = Order::find($orderId);

            if (!$order) {
                return response()->redirectTo(Filament::getPanel('app')->getUrl());
            }
            
            $order->update(['payment_method' => 'stripe']);

            if (!$this->stripeClient) {
                return response()->redirectTo(Filament::getPanel('app')->getUrl());
            }

            $session = $order->getCheckoutSession();
            
            // If session is already complete/paid, process success immediately
            if ($session->status === Session::STATUS_COMPLETE || $session->payment_status === Session::PAYMENT_STATUS_PAID) {
                $order->activate($session->payment_intent);
                $order->refresh();
                return response()->redirectTo(Console::getUrl(panel: 'server', tenant: $order->server) ?? ListOrders::getUrl(panel: 'app'));
            }
            
            if (!$session->url) {
                return response()->redirectTo(Filament::getPanel('app')->getUrl());
            }
            
            return response()->redirectTo($session->url);
        } catch (Exception $e) {
            report($e);
            return response()->redirectTo(Filament::getPanel('app')->getUrl());
        }
    }

    public function checkoutPaypal(Request $request): RedirectResponse
    {
        $orderId = $request->get('order_id');
        
        /** @var ?Order $order */
        $order = Order::find($orderId);

        if (!$order) {
            return response()->redirectTo(Filament::getPanel('app')->getUrl());
        }

        try {
            $order->update(['payment_method' => 'paypal']);

            $paypalOrderId = $this->paypalService->createOrder($order);
            $orderDetails = $this->paypalService->getOrderDetails($paypalOrderId);

            foreach ($orderDetails['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    return response()->redirectTo($link['href']);
                }
            }

            throw new Exception('PayPal approval link not found');
        } catch (Exception $e) {
            report($e);
            return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
        }
    }

    public function success(Request $request): RedirectResponse
    {
        try {
            $sessionId = $request->get('session_id');

            if ($sessionId === null) {
                return response()->redirectTo(Filament::getPanel('app')->getUrl());
            }

            if (!$this->stripeClient) {
                return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
            }

            $session = $this->stripeClient->checkout->sessions->retrieve($sessionId);

            if ($session->payment_status === Session::PAYMENT_STATUS_UNPAID) {
                return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
            }

            /** @var ?Order $order */
            $order = Order::where('stripe_checkout_id', $session->id)->first();

            if (!$order) {
                return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
            }

            $order->activate($session->payment_intent);
            $order->refresh();

            return response()->redirectTo(Console::getUrl(panel: 'server', tenant: $order->server));
        } catch (Exception $e) {
            report($e);
            return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
        }
    }

    public function cancel(Request $request): RedirectResponse
    {
        $sessionId = $request->get('session_id');

        if ($sessionId) {
            /** @var ?Order $order */
            $order = Order::where('stripe_checkout_id', $sessionId)->first();
            $order?->close();
        }

        return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
    }

    public function paypalSuccess(Request $request): RedirectResponse
    {
        try {
            $orderId = $request->get('token');

            if (!$orderId) {
                return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
            }

            $paypalOrder = $this->paypalService->captureOrder($orderId);

            if ($paypalOrder['status'] !== 'COMPLETED') {
                return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
            }

            /** @var ?Order $order */
            $order = Order::where('paypal_order_id', $orderId)->first();

            if (!$order) {
                return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
            }

            $payerId = $paypalOrder['payer']['payer_info']['payer_id'] ?? $paypalOrder['payer']['email_address'] ?? null;
            $order->activate($payerId);
            $order->refresh();

            return response()->redirectTo(Console::getUrl(panel: 'server', tenant: $order->server) ?? ListOrders::getUrl(panel: 'app'));
        } catch (Exception $e) {
            report($e);
            return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
        }
    }

    public function paypalCancel(Request $request): RedirectResponse
    {
        $orderId = $request->get('token');

        if ($orderId) {
            /** @var ?Order $order */
            $order = Order::where('paypal_order_id', $orderId)->first();
            $order?->close();
        }

        return response()->redirectTo(ListOrders::getUrl(panel: 'app'));
    }

    public function paypalWebhook(Request $request): array
    {
        try {
            if (!$this->paypalService->verifyWebhookSignature($request->headers->all(), $request->getContent())) {
                return ['success' => false, 'message' => 'Invalid webhook signature'];
            }

            $event = $request->json('event_type');
            $resource = $request->json('resource');

            if ($event === 'PAYMENT.CAPTURE.COMPLETED') {
                $paypalOrderId = $resource['supplementary_data']['related_ids']['order_id'] ?? null;

                if ($paypalOrderId) {
                    $order = Order::where('paypal_order_id', $paypalOrderId)->first();
                    if ($order && $order->status->value !== 'active') {
                        $payerId = $resource['payer']['email_address'] ?? $resource['payer']['payer_info']['payer_id'] ?? null;
                        $order->activate($payerId);
                    }
                }
            }

            return ['success' => true];
        } catch (Exception $e) {
            report($e);
            return ['success' => false, 'message' => 'Webhook processing failed'];
        }
    }
}
