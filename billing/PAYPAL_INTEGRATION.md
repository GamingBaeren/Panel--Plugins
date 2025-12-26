# Billing Plugin - PayPal Integration Guide

## Übersicht

Das aktualisierte Billing Plugin unterstützt nun **Stripe** und **PayPal** als Zahlungsmethoden. Beide können unabhängig aktiviert oder deaktiviert werden, und Benutzer können beim Checkout die gewünschte Zahlungsmethode auswählen.

## Konfiguration

### 1. Environment Variablen

Füge die folgenden Variablen zu deiner `.env`-Datei hinzu:

```env
# Stripe Configuration
STRIPE_ENABLED=true
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...

# PayPal Configuration
PAYPAL_ENABLED=true
PAYPAL_MODE=sandbox  # 'sandbox' oder 'live'
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_SECRET=your_secret
PAYPAL_WEBHOOK_ID=your_webhook_id  # Optional

# Allgemeine Konfiguration
BILLING_CURRENCY=USD
```

### 2. Plugin Settings (Admin Panel)

Navigiere zu **Admin > Settings > Plugins > Billing** und konfiguriere:

- **Stripe Settings**
  - Enable Stripe (Toggle)
  - Stripe Key
  - Stripe Secret

- **PayPal Settings**
  - Enable PayPal (Toggle)
  - PayPal Mode (Sandbox/Live)
  - PayPal Client ID
  - PayPal Secret
  - PayPal Webhook ID (optional)

- **General Settings**
  - Currency
  - Default node tags

## Installation & Setup

### 1. Datenbankmigrationen ausführen

```bash
php artisan migrate
```

Dies erstellt die neuen Spalten in der `orders`-Tabelle:
- `payment_method` - Zahlungsmethode (stripe/paypal)
- `paypal_order_id` - PayPal Order ID
- `paypal_payer_id` - PayPal Payer ID

### 2. PayPal Sandbox Setup

1. Gehe zu [PayPal Developer](https://developer.paypal.com/)
2. Erstelle oder logge dich in dein Konto ein
3. Erstelle eine Anwendung
4. Kopiere die **Client ID** und **Secret** von deinem Sandbox Account
5. Trage diese in den Plugin Settings ein

### 3. PayPal Production Setup

1. Wechsle von **Sandbox** zu **Live** in den Plugin Settings
2. Verwende deine Live-Credentials statt der Sandbox-Credentials

### 4. PayPal Webhook Configuration (Optional)

Für automatische Zahlungsbestätigung:

1. Gehe zu PayPal Webhook Settings
2. Registriere einen Webhook mit der URL: `https://yourdomain.com/billing/paypal/webhook`
3. Aktiviere die Events: `PAYMENT.CAPTURE.COMPLETED`
4. Kopiere die **Webhook ID** in die Plugin Settings

## Benutzerfluss

### Checkout Prozess

1. Benutzer wählt ein Produkt auf der Billing Seite
2. Wird zur Checkout-Seite weitergeleitet
3. Wählt zwischen verfügbaren Zahlungsmethoden (Stripe/PayPal)
4. Klickt auf "Pay with [Method]"
5. Wird zum Zahlungsanbieter weitergeleitet
6. Nach erfolgreicher Zahlung wird der Server erstellt und aktiviert

## Enums & Services

### PaymentMethod Enum

```php
PaymentMethod::Stripe // Stripe
PaymentMethod::PayPal // PayPal

// Methoden
$method->getLabel()          // "Stripe" oder "PayPal"
$method->isEnabled()         // Prüfe ob aktiviert
PaymentMethod::getEnabledMethods() // Alle aktivierten Methoden
```

### PayPalService

Der `PayPalService` handhabt alle PayPal API Interaktionen:

```php
$service = app(PayPalService::class);

// Order erstellen
$orderId = $service->createOrder($order);

// Order erfassen
$details = $service->captureOrder($orderId);

// Order Details abrufen
$info = $service->getOrderDetails($orderId);

// Webhook validieren
$isValid = $service->verifyWebhookSignature($headers, $body);
```

## Datenbankänderungen

### Orders Tabelle

Neue Spalten:
- `payment_method` (string, default: 'stripe') - Zahlungsmethode
- `paypal_order_id` (string, nullable) - PayPal Order ID
- `paypal_payer_id` (string, nullable) - PayPal Payer ID

## Routes

Neue API Routes für PayPal:

```
GET  /billing/paypal/success              -> PayPal Success Callback
GET  /billing/paypal/cancel               -> PayPal Cancel Callback  
POST /billing/paypal/webhook              -> PayPal Webhook Handler
```

Existierende Stripe Routes:

```
GET  /billing/checkout/success            -> Stripe Success Callback
GET  /billing/checkout/cancel             -> Stripe Cancel Callback
```

## Features

✅ **Unabhängige Aktivierung** - Stripe und PayPal können einzeln ein/ausgeschaltet werden
✅ **Admin Settings** - Konfigurieren über das Admin Panel
✅ **Benutzer-Auswahl** - Kunden wählen ihre bevorzugte Zahlungsmethode
✅ **PayPal Sandbox & Live** - Umschaltung zwischen Test- und Produktivmodus
✅ **Webhook Support** - Automatische Bestätigung via PayPal Webhooks
✅ **Fehlerbehandlung** - Robuste Exception Handling

## Troubleshooting

### PayPal Order wird nicht erstellt

- Überprüfe Client ID und Secret in den Settings
- Stelle sicher, dass PayPal in den Settings aktiviert ist
- Prüfe die Logs auf API-Fehler

### Webhook wird nicht validiert

- PayPal Webhook ID kann optional sein
- Wenn Webhook Signature Verification fehlschlägt, wird eine Exception geworfen
- Logs können aktiviert werden für Debugging

### Benutzer wird nach Zahlung nicht weitergeleitet

- Überprüfe, dass die Redirect-URLs korrekt konfiguriert sind
- Stelle sicher, dass die Routes registriert sind
- Überprüfe CORS und CSP Einstellungen

## Zukünftige Erweiterungen

- [ ] Recurring Payments / Subscriptions
- [ ] Multiple PayPal Konten
- [ ] Payment History Details
- [ ] Refund Management
- [ ] Split Payments

