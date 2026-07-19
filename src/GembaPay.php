<?php

namespace GembaPay;

class GembaPay
{
    private const BASE_URL = 'https://api.gembapay.com';
    private const VERSION = '1.0.1';

    private string $apiKey;
    private ?string $webhookSecret;
    private string $baseUrl;
    private int $timeout;
    private bool $isTestMode;

    /**
     * Initialize GembaPay client
     *
     * @param string      $apiKey        API key (gembapay_test_... or gembapay_live_...)
     * @param string|null $webhookSecret Webhook signing secret
     * @param string|null $baseUrl       Custom base URL
     * @param int         $timeout       Request timeout in seconds (default: 30)
     */
    public function __construct(
        string $apiKey,
        ?string $webhookSecret = null,
        ?string $baseUrl = null,
        int $timeout = 30
    ) {
        if (empty($apiKey)) {
            throw new GembaPayException('API key is required. Get yours at https://merchant.gembapay.com');
        }

        $this->apiKey = $apiKey;
        $this->webhookSecret = $webhookSecret;
        $this->baseUrl = rtrim($baseUrl ?? self::BASE_URL, '/');
        $this->timeout = $timeout;
        $this->isTestMode = str_starts_with($apiKey, 'gembapay_test_');
    }

    /**
     * Check if using test mode
     */
    public function isTestMode(): bool
    {
        return $this->isTestMode;
    }

    // ── Payments ─────────────────────────────────────────

    /**
     * Create a payment request
     *
     * @param string      $orderId     Your unique order identifier
     * @param float       $amount      Payment amount
     * @param string      $currency    Currency code (default: USD)
     * @param string|null $description Payment description
     * @return array Payment data including paymentUrl
     */
    public function createPayment(
        string $orderId,
        float $amount,
        string $currency = 'USD',
        ?string $description = null
    ): array {
        $body = [
            'orderId' => $orderId,
            'amount' => $amount,
            'currency' => $currency,
        ];

        if ($description !== null) {
            $body['description'] = $description;
        }

        return $this->request('POST', '/api/merchant/payment-request', $body);
    }

    /**
     * Get payment details
     */
    public function getPayment(string $orderId): array
    {
        return $this->request('GET', '/api/customer/payment/' . urlencode($orderId));
    }

    /**
     * Check payment status
     */
    public function getPaymentStatus(string $orderId): array
    {
        return $this->request('GET', '/api/customer/payment/' . urlencode($orderId) . '/status');
    }

    // ── Merchant ─────────────────────────────────────────

    /**
     * List merchant transactions
     */
    public function listTransactions(array $params = []): array
    {
        $query = !empty($params) ? '?' . http_build_query($params) : '';
        return $this->request('GET', '/api/merchant/transactions' . $query);
    }

    /**
     * Get merchant statistics
     */
    public function getStats(): array
    {
        return $this->request('GET', '/api/merchant/stats');
    }

    // ── Webhooks ─────────────────────────────────────────

    /**
     * Verify webhook signature
     *
     * @param string $payload   Raw request body
     * @param string $signature Value of X-GembaPay-Signature header
     * @return bool
     */
    public function verifyWebhook(string $payload, string $signature): bool
    {
        if (empty($this->webhookSecret)) {
            throw new GembaPayException('webhookSecret is required for signature verification');
        }

        // GembaPay signs webhooks as BARE hex HMAC-SHA256 (no "sha256=" prefix) over the raw body.
        $expected = hash_hmac('sha256', $payload, $this->webhookSecret);

        return hash_equals($expected, $signature);
    }

    /**
     * Parse and verify webhook from global request
     *
     * @return array Parsed webhook event
     */
    public function parseWebhookFromGlobals(): array
    {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_GEMBAPAY_SIGNATURE'] ?? '';

        if (!$this->verifyWebhook($payload, $signature)) {
            throw new GembaPayException('Invalid webhook signature', 401);
        }

        $data = json_decode($payload, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GembaPayException('Invalid webhook payload');
        }

        return $data;
    }

    // ── Internal ─────────────────────────────────────────

    private function request(string $method, string $path, ?array $body = null): array
    {
        $url = $this->baseUrl . $path;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiKey,
                'Content-Type: application/json',
                'User-Agent: gembapay-php/' . self::VERSION,
            ],
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($body !== null) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
        }

        $response = curl_exec($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new GembaPayException('Network error: ' . $error);
        }

        $data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new GembaPayException('Invalid response from API', $statusCode);
        }

        if ($statusCode >= 400) {
            $message = $data['message'] ?? $data['error'] ?? "HTTP {$statusCode}";
            throw new GembaPayException($message, $statusCode);
        }

        return $data;
    }
}
