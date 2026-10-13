<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';

function checkoutRespond(int $status, array $body): void
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

function checkoutApiRequest(string $endpoint, string $token, ?array $body = null): array
{
    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ];

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => $headers,
    ];

    if ($body !== null) {
        $json = json_encode($body, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            return ['status' => 500, 'body' => null, 'error' => 'Unable to encode API request.'];
        }
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $json;
        $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
    }

    $curl = curl_init(kohiApiUrl($endpoint));
    curl_setopt_array($curl, $options);
    $response = curl_exec($curl);
    $error = curl_error($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    $decoded = is_string($response) ? json_decode($response, true) : null;
    return [
        'status' => $status,
        'body' => is_array($decoded) ? $decoded : null,
        'error' => $response === false ? $error : null,
    ];
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    checkoutRespond(405, ['success' => false, 'message' => 'Only POST requests are allowed.']);
}

if (!isCustomerLoggedIn() || customerTokenExpired()) {
    if (isCustomerLoggedIn()) {
        logoutCustomer();
    }
    checkoutRespond(401, ['success' => false, 'message' => 'Your session expired. Please sign in again.']);
}

if (getCustomerRole() !== 'CUSTOMER') {
    checkoutRespond(403, ['success' => false, 'message' => 'Only customer accounts can place website orders.']);
}

$token = getCustomerToken();
if ($token === '') {
    checkoutRespond(401, ['success' => false, 'message' => 'Please sign in again to place your order.']);
}

$request = json_decode(file_get_contents('php://input'), true);
if (!is_array($request) || !isset($request['items']) || !is_array($request['items']) || $request['items'] === []) {
    checkoutRespond(422, ['success' => false, 'message' => 'Your cart is empty.']);
}

$items = [];
foreach ($request['items'] as $item) {
    if (!is_array($item)) {
        checkoutRespond(422, ['success' => false, 'message' => 'One or more cart items are invalid.']);
    }
    $productId = filter_var($item['product_id'] ?? null, FILTER_VALIDATE_INT);
    $quantity = filter_var($item['quantity'] ?? null, FILTER_VALIDATE_INT);
    if ($productId === false || $productId < 1 || $quantity === false || $quantity < 1) {
        checkoutRespond(422, ['success' => false, 'message' => 'One or more cart items are invalid.']);
    }
    $items[] = ['product_id' => $productId, 'quantity' => $quantity];
}

$paymentMethod = strtoupper(trim((string) ($request['payment_method'] ?? '')));
if (!in_array($paymentMethod, ['CASHIER', 'ONLINE'], true)) {
    checkoutRespond(422, ['success' => false, 'message' => 'Choose a valid payment method.']);
}

$created = checkoutApiRequest('add_order.php', $token, [
    'items' => $items,
    'payment_method' => $paymentMethod,
    'order_source' => 'ONLINE_APP',
]);

if ($created['error'] !== null || !is_array($created['body'])) {
    error_log('Website checkout order request failed: ' . (string) $created['error']);
    checkoutRespond(502, ['success' => false, 'message' => 'The order service could not be reached. Please try again.']);
}

if ($created['status'] === 401) {
    logoutCustomer();
    checkoutRespond(401, ['success' => false, 'message' => 'Your session expired. Please sign in again.']);
}

if ($created['status'] < 200 || $created['status'] >= 300 || empty($created['body']['success'])) {
    checkoutRespond($created['status'] >= 400 ? $created['status'] : 400, [
        'success' => false,
        'message' => $created['body']['message'] ?? 'We could not place your order.',
    ]);
}

$order = $created['body']['order'] ?? [];
$orderId = (int) ($order['order_id'] ?? 0);
if ($orderId < 1) {
    error_log('Website checkout API returned success without an order ID.');
    checkoutRespond(502, ['success' => false, 'message' => 'The order service returned an invalid response.']);
}

if ($paymentMethod === 'CASHIER') {
    checkoutRespond(200, [
        'success' => true,
        'order_created' => true,
        'order' => $order,
        'message' => 'Your order was placed. Please pay at the cashier when you pick it up.',
    ]);
}

$checkout = checkoutApiRequest('create_checkout.php', $token, ['order_id' => $orderId]);
if ($checkout['error'] !== null || !is_array($checkout['body']) || $checkout['status'] < 200 || $checkout['status'] >= 300 || empty($checkout['body']['success'])) {
    error_log('Website Maya checkout creation failed for order ' . $orderId . '.');
    checkoutRespond(502, [
        'success' => false,
        'order_created' => true,
        'order' => $order,
        'message' => 'Your order was created, but online payment could not be started. You can find the order in My Orders.',
    ]);
}

$paymentUrl = $checkout['body']['checkout']['redirectUrl']
    ?? $checkout['body']['checkout']['url']
    ?? null;

if (!is_string($paymentUrl) || filter_var($paymentUrl, FILTER_VALIDATE_URL) === false || strtolower((string) parse_url($paymentUrl, PHP_URL_SCHEME)) !== 'https') {
    error_log('Website Maya checkout response had no valid redirect URL for order ' . $orderId . '.');
    checkoutRespond(502, [
        'success' => false,
        'order_created' => true,
        'order' => $order,
        'message' => 'Your order was created, but online payment could not be started. You can find the order in My Orders.',
    ]);
}

checkoutRespond(200, [
    'success' => true,
    'order_created' => true,
    'order' => $order,
    'payment_url' => $paymentUrl,
    'message' => 'Your order was created.',
]);

