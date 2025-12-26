<?php

namespace Boy132\Billing\Services;

use Boy132\Billing\Models\Order;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class PayPalService
{
    private string $clientId;
    private string $secret;
    private string $mode;
    private string $baseUrl;
    private ?string $accessToken = null;

    public function __construct()
    {
        $this->clientId = config('billing.paypal.client_id');
        $this->secret = config('billing.paypal.secret');
        $this->mode = config('billing.paypal.mode', 'sandbox');
        $this->baseUrl = $this->mode === 'live'
            ? 'https://api.paypal.com'
            : 'https://api.sandbox.paypal.com';
    }

    /**
     * Get PayPal access token
     */
    public function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = Http::withBasicAuth($this->clientId, $this->secret)
            ->asForm()
            ->post($this->baseUrl . '/v1/oauth2/token', [
                'grant_type' => 'client_credentials',
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to get PayPal access token: ' . $response->body());
        }

        $this->accessToken = $response->json('access_token');

        return $this->accessToken;
    }

    /**
     * Create a PayPal order
     */
    public function createOrder(Order $order): string
    {
        $price = $order->productPrice;
        $amount = number_format($price->cost, 2, '.', '');

        $response = Http::withToken($this->getAccessToken())
            ->post($this->baseUrl . '/v2/checkout/orders', [
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'amount' => [
                            'currency_code' => config('billing.currency', 'USD'),
                            'value' => $amount,
                        ],
                        'description' => $price->name . ' - ' . $price->product->name,
                    ],
                ],
                'application_context' => [
                    'brand_name' => config('app.name'),
                    'landing_page' => 'LOGIN',
                    'user_action' => 'PAY_NOW',
                    'return_url' => route('billing.paypal.success', [], true),
                    'cancel_url' => route('billing.paypal.cancel', [], true),
                ],
            ]);

        if (!$response->successful()) {
            throw new Exception('Failed to create PayPal order: ' . $response->body());
        }

        $paypalOrderId = $response->json('id');
        
        $order->update([
            'paypal_order_id' => $paypalOrderId,
        ]);

        return $paypalOrderId;
    }

    /**
     * Capture PayPal order
     */
    public function captureOrder(string $paypalOrderId): array
    {
        // Per PayPal Orders API, capture endpoint should be called without a malformed
        // payment_source payload. Sending an explicit payment_source.paypal body
        // can result in MALFORMED_REQUEST_JSON. Use an empty POST body.
        $url = $this->baseUrl . '/v2/checkout/orders/' . $paypalOrderId . '/capture';

        $payload = '{}';

        $response = Http::withToken($this->getAccessToken())
            ->acceptJson()
            ->withBody($payload, 'application/json')
            ->post($url);

        if (!$response->successful()) {
            throw new Exception('Failed to capture PayPal order: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Get PayPal order details
     */
    public function getOrderDetails(string $paypalOrderId): array
    {
        $response = Http::withToken($this->getAccessToken())
            ->get($this->baseUrl . '/v2/checkout/orders/' . $paypalOrderId);

        if (!$response->successful()) {
            throw new Exception('Failed to get PayPal order details: ' . $response->body());
        }

        return $response->json();
    }

    /**
     * Verify webhook signature
     */
    public function verifyWebhookSignature(array $headers, string $body): bool
    {
        try {
            $response = Http::withToken($this->getAccessToken())
                ->post($this->baseUrl . '/v1/notifications/verify-webhook-signature', [
                    'transmission_id' => $headers['paypal-transmission-id'] ?? '',
                    'transmission_time' => $headers['paypal-transmission-time'] ?? '',
                    'cert_url' => $headers['paypal-cert-url'] ?? '',
                    'auth_algo' => $headers['paypal-auth-algo'] ?? '',
                    'transmission_sig' => $headers['paypal-transmission-sig'] ?? '',
                    'webhook_id' => config('billing.paypal.webhook_id'),
                    'webhook_event' => json_decode($body, true),
                ]);

            return $response->json('verification_status') === 'SUCCESS';
        } catch (Exception $e) {
            report($e);
            return false;
        }
    }
}
