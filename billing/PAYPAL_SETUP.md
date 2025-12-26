# PayPal Integration für Billing Plugin

## 🎯 Zusammenfassung der Änderungen

Dieses Update erweitert das Billing Plugin um vollständige **PayPal-Unterstützung** neben der existierenden Stripe-Integration. Beide Zahlungsmethoden können unabhängig aktiviert/deaktiviert werden, und Benutzer können beim Checkout auswählen.

## ✨ Neue Features

- ✅ PayPal als Zahlungsmethode
- ✅ Unabhängige Aktivierung von Stripe und PayPal
- ✅ Benutzer-Auswahl beim Checkout
- ✅ PayPal Sandbox & Live Mode Support
- ✅ Webhook Unterstützung für automatische Zahlungsbestätigung
- ✅ Admin Panel Settings für API Keys
- ✅ Robuste Fehlerbehandlung

## 📋 Installationsschritte

### 1. Code-Update ausführen
Der Code wurde bereits aktualisiert. Die neuen Dateien sind:
- `src/Enums/PaymentMethod.php` - Enum für Zahlungsmethoden
- `src/Services/PayPalService.php` - PayPal API Service
- `src/Filament/App/Resources/Orders/Pages/CheckoutPage.php` - Checkout Seite
- `database/migrations/006_add_paypal_columns_to_orders.php` - Datenbank Update
- `resources/views/checkout.blade.php` - Checkout UI

### 2. Environment-Variablen setzen

Öffne `.env` und füge hinzu:

```env
# Stripe (existierend, jetzt mit Enable Flag)
STRIPE_ENABLED=true
STRIPE_KEY=pk_test_xxxxx
STRIPE_SECRET=sk_test_xxxxx

# PayPal (neu)
PAYPAL_ENABLED=true
PAYPAL_MODE=sandbox
PAYPAL_CLIENT_ID=your_client_id
PAYPAL_SECRET=your_secret
PAYPAL_WEBHOOK_ID=your_webhook_id  # Optional

# Allgemein
BILLING_CURRENCY=USD
BILLING_DEPLOYMENT_TAGS=default
```

### 3. Datenbankmigrationen ausführen

```bash
php artisan migrate
```

Dies erstellt 3 neue Spalten in der `orders`-Tabelle:
- `payment_method` - Zahlungsmethode (stripe/paypal)
- `paypal_order_id` - PayPal Order ID
- `paypal_payer_id` - PayPal Payer ID

### 4. PayPal API Credentials beschaffen

**Für Sandbox (Testing):**
1. Gehe zu https://developer.paypal.com/
2. Melde dich an oder erstelle ein Konto
3. Gehe zu "Apps & Credentials"
4. Wähle "Sandbox" (oben)
5. Kopiere unter "Rest API apps" die **Client ID** und **Secret**

**Für Live (Production):**
1. Nach dem Testing: Wechsle zu "Live" Tab
2. Verwende deine Live-Credentials

### 5. Settings im Admin Panel konfigurieren

1. Logge dich im Admin Panel ein
2. Gehe zu **Admin > Settings > Plugins**
3. Klicke auf **Billing Plugin**
4. Unter "Stripe Settings":
   - ☑️ **Enable Stripe** (aktivieren/deaktivieren)
   - Stripe Key und Secret eintragen
5. Unter "PayPal Settings":
   - ☑️ **Enable PayPal** (aktivieren/deaktivieren)
   - Wähle **PayPal Mode** (Sandbox oder Live)
   - Trage **PayPal Client ID** ein
   - Trage **PayPal Secret** ein
   - (Optional) **PayPal Webhook ID** eintragen
6. Klicke **Save**

### 6. (Optional) PayPal Webhooks registrieren

Für automatische Zahlungsbestätigung ohne Polling:

1. Im PayPal Developer Dashboard gehe zu "Webhooks"
2. Erstelle einen neuen Webhook mit dieser URL:
   ```
   https://yourdomain.com/billing/paypal/webhook
   ```
3. Wähle diese Events:
   - `PAYMENT.CAPTURE.COMPLETED`
4. Kopiere die **Webhook ID** 
5. Trage sie in die Plugin Settings ein

## 🔄 Checkout Flow

### Für Endbenutzer:

1. Benutzer navigiert zur Billing Seite
2. Wählt ein Produkt und einen Plan
3. Wird zur Checkout-Seite weitergeleitet
4. **Wählt Zahlungsmethode:**
   - Option 1: Stripe
   - Option 2: PayPal
5. Wird zum Zahlungsanbieter weitergeleitet
6. Nach erfolgreicher Zahlung:
   - Order wird aktiviert
   - Server wird automatisch erstellt
   - Benutzer wird zum Server konsole weitergeleitet

## 🛠️ Technische Details

### Neue Klassen

#### PaymentMethod Enum
```php
// Alle verfügbaren Methoden
PaymentMethod::Stripe
PaymentMethod::PayPal

// Check if enabled
if (PaymentMethod::PayPal->isEnabled()) { ... }

// Alle aktivierten Methoden
$enabled = PaymentMethod::getEnabledMethods();
```

#### PayPalService
```php
$service = app(PayPalService::class);

// Order erstellen
$orderId = $service->createOrder($order);

// Zahlung erfassen
$details = $service->captureOrder($orderId);

// Details abrufen
$info = $service->getOrderDetails($orderId);

// Webhook validieren
$valid = $service->verifyWebhookSignature($headers, $body);
```

### Aktualisierte Modelle

#### Order Model
- Neue Properties: `payment_method`, `paypal_order_id`, `paypal_payer_id`
- Neue Fillable: diese 3 Felder
- Updated `activate()` Method: handhabt beide Methoden

#### BillingPlugin Settings
- Neue Form Sections für Stripe und PayPal
- Separate Enable/Disable Toggles
- Alle Settings werden in `.env` geschrieben

## 🐛 Troubleshooting

### Problem: PayPal Settings werden nicht angezeigt

**Lösung:** Stelle sicher, dass du den Browser Cache geleert hast und die Seite neu lädst.

### Problem: "Invalid payment method" Error

**Lösung:** Überprüfe, dass die gewählte Zahlungsmethode in Settings aktiviert ist.

### Problem: PayPal Order wird nicht erstellt

**Lösung:** 
- Überprüfe Client ID und Secret in Settings
- Überprüfe, dass PAYPAL_ENABLED=true in .env ist
- Schaue die Anwendungs-Logs an: `storage/logs/laravel.log`

### Problem: Webhook Signature wird nicht validiert

**Lösung:**
- Webhook ID ist optional (kann leer sein)
- Stelle sicher, dass die Webhook URL öffentlich erreichbar ist
- Verwende das gleiche Secret wie in PayPal Dashboard

## 📚 Weiterführende Links

- [PayPal Developer Docs](https://developer.paypal.com/api/rest/)
- [Stripe Docs](https://stripe.com/docs)
- [Plugin Documentation](./PAYPAL_INTEGRATION.md)

## 🔐 Sicherheit

- Alle API Keys werden in `.env` gespeichert (nicht im Code)
- Webhook Signatures werden validiert
- Alle externen API Calls verwenden HTTPS
- Database Queries sind vor SQL Injection geschützt

## 📝 Nächste Schritte

Nach dem Setup:

1. **Testen im Sandbox Mode**
   - Nutze PayPal Sandbox Credentials
   - Erstelle Test-Orders
   - Verifiziere den kompletten Flow

2. **Live-Schaltung**
   - Wechsle zu Live Credentials
   - Ändere PAYPAL_MODE zu "live"
   - Teste eine echte Transaction (kleine Betrag)

3. **Monitoring**
   - Überwache Orders in der Admin Console
   - Überprüfe Logs bei Fehlern
   - Verifiziere Webhook Bestätigungen

