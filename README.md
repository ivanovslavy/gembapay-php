# GembaPay PHP SDK

**Unified payment gateway for crypto, cards, and PayPal.**

Accept ETH, BNB, POL, USDC, USDT, credit cards (via Stripe), and PayPal through a single API. Non-custodial crypto payments — funds go directly to your wallet via smart contracts.

[![Packagist](https://img.shields.io/packagist/v/gembapay/gembapay-php)](https://packagist.org/packages/gembapay/gembapay-php)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D7.4-777BB4)](https://php.net)

---

## Features

- **One API, three payment methods** — Crypto, Stripe (cards/Apple Pay/Google Pay), PayPal
- **Non-custodial crypto** — Payments route directly to your wallet via smart contracts
- **86+ currencies** — Price in any fiat currency, settle in crypto or fiat
- **Multi-chain** — Ethereum, BNB Smart Chain, Polygon
- **Test mode built-in** — Testnets + sandbox environments for development
- **Zero dependencies** — Uses only PHP built-in curl and json extensions
- **Laravel & WordPress compatible** — Works with any PHP framework

## Install

```bash
composer require gembapay/gembapay-php
```

## Quick Start

```php
use GembaPay\GembaPay;

$gembapay = new GembaPay(
    apiKey: 'gembapay_test_your_key',  // test key for development
    webhookSecret: 'your_webhook_secret'
);

// Create a payment
$payment = $gembapay->createPayment(
    orderId: 'ORDER-123',
    amount: 100.00,
    currency: 'EUR'
);

echo $payment['paymentUrl'];
// → https://payment.gembapay.com/checkout/ORDER-123

echo implode(', ', $payment['allowedMethods']);
// → crypto, stripe, paypal
```

## Usage

### Create Payment

```php
$payment = $gembapay->createPayment(
    orderId: 'ORDER-456',
    amount: 49.99,
    currency: 'USD',
    description: 'Premium Plan'
);

// Redirect customer to unified checkout
header('Location: ' . $payment['paymentUrl']);
exit;
```

### Check Status

```php
$status = $gembapay->getPaymentStatus('ORDER-456');

echo $status['status'];   // 'completed'
echo $status['network'];  // 'bsc', 'stripe', 'paypal', etc.
```

### Webhook Handling

```php
// webhook.php
$gembapay = new GembaPay(
    apiKey: $_ENV['GEMBAPAY_API_KEY'],
    webhookSecret: $_ENV['GEMBAPAY_WEBHOOK_SECRET']
);

try {
    $event = $gembapay->parseWebhookFromGlobals();

    if ($event['event'] === 'payment.completed') {
        $orderId = $event['payment']['orderId'];
        $amount = $event['payment']['usdAmount'];
        $method = $event['payment']['network'];

        // Fulfill the order
        fulfillOrder($orderId);
    }

    http_response_code(200);
    echo json_encode(['received' => true]);

} catch (\GembaPay\GembaPayException $e) {
    http_response_code(401);
    echo json_encode(['error' => $e->getMessage()]);
}
```

Or verify manually:

```php
$payload = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_GEMBAPAY_SIGNATURE'] ?? '';

if ($gembapay->verifyWebhook($payload, $signature)) {
    $data = json_decode($payload, true);
    // Process event...
}
```

### Transactions & Stats

```php
$transactions = $gembapay->listTransactions();
$stats = $gembapay->getStats();
```

## Laravel Integration

```php
// config/services.php
'gembapay' => [
    'api_key' => env('GEMBAPAY_API_KEY'),
    'webhook_secret' => env('GEMBAPAY_WEBHOOK_SECRET'),
],

// app/Providers/AppServiceProvider.php
$this->app->singleton(GembaPay::class, function () {
    return new GembaPay(
        apiKey: config('services.gembapay.api_key'),
        webhookSecret: config('services.gembapay.webhook_secret')
    );
});

// In your controller
public function checkout(Request $request, GembaPay $gembapay)
{
    $payment = $gembapay->createPayment(
        orderId: $request->order_id,
        amount: $request->amount,
        currency: $request->currency ?? 'USD'
    );

    return redirect($payment['paymentUrl']);
}
```

## Test Mode

Use test API keys (`gembapay_test_...`) for development. Test mode automatically uses:

| Method | Test Environment |
|--------|-----------------|
| Crypto | Sepolia, BSC Testnet, Polygon Amoy |
| Stripe | Test cards (`4242 4242 4242 4242`) |
| PayPal | Sandbox accounts |

```php
$gembapay = new GembaPay(apiKey: 'gembapay_test_your_key');
var_dump($gembapay->isTestMode()); // true
```

Claim free test tokens at [Developer Resources](https://merchant.gembapay.com/developer-resources).

## API Reference

### Constructor

```php
new GembaPay(
    string $apiKey,               // Required
    ?string $webhookSecret = null, // For webhook verification
    ?string $baseUrl = null,       // Custom API URL
    int $timeout = 30              // Timeout in seconds
)
```

### Methods

| Method | Description |
|--------|-------------|
| `createPayment($orderId, $amount, $currency, $description)` | Create payment → returns array with `paymentUrl` |
| `getPayment($orderId)` | Get payment details |
| `getPaymentStatus($orderId)` | Check payment status |
| `listTransactions($params)` | List merchant transactions |
| `getStats()` | Get merchant statistics |
| `verifyWebhook($payload, $signature)` | Verify webhook signature → bool |
| `parseWebhookFromGlobals()` | Parse & verify from PHP globals |
| `isTestMode()` | Check if using test mode |

## Fee Structure

| Method | Fee |
|--------|-----|
| Crypto (ETH, BNB, POL, USDC, USDT) | 1% |
| Stripe (Cards, Apple Pay, Google Pay) | 1% + €0.20 + Stripe fees |
| PayPal (Balance, Bank, Pay Later) | 1% + €0.20 + PayPal fees |

## Requirements

- PHP >= 7.4
- ext-curl
- ext-json

## Links

- [Documentation](https://docs.gembapay.com)
- [Merchant Dashboard](https://merchant.gembapay.com)
- [Integration Guide](https://docs.gembapay.com/integration)
- [GitHub](https://github.com/ivanovslavy/gembapay)
- [npm Package](https://www.npmjs.com/package/gembapay)

## Support

- Email: contacts@gembapay.com
- GitHub Issues: [github.com/ivanovslavy/gembapay/issues](https://github.com/ivanovslavy/gembapay/issues)

## License

[MIT](LICENSE) © GEMBA EOOD
