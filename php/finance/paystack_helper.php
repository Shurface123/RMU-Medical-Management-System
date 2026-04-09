<?php
/**
 * Paystack Helper — PHP API Wrapper (Phase 8 Specification)
 * Shared by webhook, callback, and finance_actions.
 */

/**
 * Internal: Fetch decrypted Paystack configuration using MySQL AES.
 * Ensures the secret key is strictly loaded server-side.
 */
function _paystackConfig($conn): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    $cfg = [];
    if (!$conn) return $cfg;
    
    // Decrypt directly via DB
    $q = mysqli_query($conn, "SELECT config_key, CAST(AES_DECRYPT(config_value, SHA2('RMU_SICKBAY_2025_SECRET',256)) AS CHAR) AS val, is_encrypted, config_value AS raw FROM paystack_config WHERE is_active=1");
    if ($q) {
        while ($r = mysqli_fetch_assoc($q)) {
            $cfg[$r['config_key']] = $r['is_encrypted'] ? $r['val'] : $r['raw'];
        }
    }
    return $cfg;
}

/**
 * Internal: Execute generic cURL POST/GET requests to Paystack API.
 */
function _paystackRequest($endpoint, $method='GET', $body=[], $conn=null): array {
    $cfg    = _paystackConfig($conn);
    $secret = $cfg['paystack_secret_key'] ?? '';
    if (!$secret) return ['status'=>false,'message'=>'Paystack secret key not configured or failed to decrypt.'];

    $url = 'https://api.paystack.co' . $endpoint;
    $opts = [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer '.$secret,
            'Content-Type: application/json',
            'Cache-Control: no-cache',
        ],
    ];
    if ($method === 'POST') {
        $opts[CURLOPT_POST]       = true;
        $opts[CURLOPT_POSTFIELDS] = json_encode($body);
    }
    
    $ch = curl_init(); curl_setopt_array($ch, $opts);
    $raw = curl_exec($ch); 
    $err = curl_error($ch); 
    curl_close($ch);
    
    if ($err) return ['status'=>false,'message'=>'cURL error: '.$err];
    
    $decoded = json_decode($raw, true);
    return $decoded ?: ['status'=>false,'message'=>'Invalid JSON response from Paystack'];
}

// ─────────────────────────────────────────────────────────────────────────────
// SPECIFIC API ABSTRACTIONS
// ─────────────────────────────────────────────────────────────────────────────

/**
 * 1. Initialize a transaction, handling GHS to Pesewas conversion via strictly-named wrapper.
 */
function initializeTransaction($email, $amount_ghs, $reference, $metadata, $callback_url) {
    global $conn;
    $amount_pesewas = (int)round($amount_ghs * 100);
    return _paystackRequest('/transaction/initialize', 'POST', [
        'email'        => $email,
        'amount'       => $amount_pesewas,
        'reference'    => $reference,
        'callback_url' => $callback_url,
        'metadata'     => $metadata,
    ], $conn);
}

/**
 * 2. Verify a transaction server-side by reference.
 */
function verifyTransaction($reference) {
    global $conn;
    return _paystackRequest('/transaction/verify/' . urlencode($reference), 'GET', [], $conn);
}

/**
 * 3. List paginated transactions with optional status filters.
 */
function listTransactions($from, $to, $status, $perPage) {
    global $conn;
    $qs = http_build_query([
        'from' => $from,
        'to' => $to,
        'status' => $status,
        'perPage' => $perPage
    ]);
    return _paystackRequest('/transaction?' . $qs, 'GET', [], $conn);
}

/**
 * 4. Issue a refund back to the customer's card.
 */
function createRefund($transaction_reference, $amount_pesewas) {
    global $conn;
    $body = ['transaction' => $transaction_reference];
    if ($amount_pesewas > 0) $body['amount'] = $amount_pesewas;
    return _paystackRequest('/refund', 'POST', $body, $conn);
}

/**
 * 5. Get volumetric transaction totals over a time period.
 */
function getTransactionTotals($from, $to) {
    global $conn;
    $qs = http_build_query([
        'from' => $from,
        'to' => $to
    ]);
    return _paystackRequest('/transaction/totals?' . $qs, 'GET', [], $conn);
}

/**
 * 6. Validate the HMAC signature header coming from Paystack webhook.
 */
function validateWebhookSignature($payload, $signature, $secret_key) {
    if (!$secret_key) return false;
    $computed = hash_hmac('sha512', $payload, $secret_key);
    return hash_equals($computed, $signature);
}

/**
 * 7. Generate a secure, unique transaction reference string.
 */
function generateReference($prefix = 'RMU') {
    return $prefix . '-' . time() . '-' . mt_rand(1000, 9999);
}

/**
 * 8. Re-added logic to support finance settings integration testing.
 */
function paystackTestConnection() {
    $res = listTransactions(date('Y-m-d').'T00:00:00', date('Y-m-d').'T23:59:59', 'success', 1);
    if(isset($res['status'])&&$res['status']) return ['status'=>true,'env'=>'live'];
    if(isset($res['message'])&&stripos($res['message'],'secret')!==false) return ['status'=>false,'message'=>$res['message']];
    return ['status'=>isset($res['data']),'env'=>'test','message'=>$res['message']??''];
}
?>
