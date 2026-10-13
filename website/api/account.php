<?php

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/api_client.php';

function accountRespond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Allow: POST');
    accountRespond(405, ['success' => false, 'message' => 'Only POST requests are allowed.']);
}

if (!isCustomerLoggedIn()) {
    accountRespond(401, ['success' => false, 'message' => 'Please sign in again.']);
}

if (customerTokenExpired()) {
    logoutCustomer();
    accountRespond(401, ['success' => false, 'message' => 'Your session expired. Please sign in again.']);
}

if (getCustomerRole() !== 'CUSTOMER') {
    accountRespond(403, ['success' => false, 'message' => 'Customer account access is required.']);
}

$request = json_decode(file_get_contents('php://input'), true);
if (!is_array($request)) {
    accountRespond(400, ['success' => false, 'message' => 'Invalid request.']);
}

$action = (string) ($request['action'] ?? '');
$token = getCustomerToken();
$endpoint = null;
$payload = null;
$method = 'POST';

switch ($action) {
    case 'profile':
        $endpoint = 'update_profile.php';
        $payload = [
            'full_name' => trim((string) ($request['full_name'] ?? '')),
            'email' => trim((string) ($request['email'] ?? '')),
            'phone' => trim((string) ($request['phone'] ?? '')),
        ];
        if ($payload['full_name'] === '' || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
            accountRespond(422, ['success' => false, 'message' => 'Enter your name and a valid email address.']);
        }
        break;

    case 'password':
        $endpoint = 'change_password.php';
        $payload = [
            'current_password' => (string) ($request['current_password'] ?? ''),
            'new_password' => (string) ($request['new_password'] ?? ''),
        ];
        break;

    case 'activity':
        $endpoint = 'get_login_activity.php';
        $method = 'GET';
        break;

    default:
        accountRespond(400, ['success' => false, 'message' => 'Unknown account action.']);
}

$headers = ['Accept: application/json', 'Authorization: Bearer ' . $token];
$options = [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => false,
    CURLOPT_CONNECTTIMEOUT => 10,
    CURLOPT_TIMEOUT => 20,
    CURLOPT_CUSTOMREQUEST => $method,
    CURLOPT_HTTPHEADER => $headers,
];

if ($payload !== null) {
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    if ($json === false) {
        accountRespond(500, ['success' => false, 'message' => 'Unable to prepare your request.']);
    }
    $options[CURLOPT_POSTFIELDS] = $json;
    $options[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
}

$curl = curl_init(kohiApiUrl($endpoint));
curl_setopt_array($curl, $options);
$response = curl_exec($curl);
$curlError = curl_error($curl);
$status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
curl_close($curl);

if ($response === false || $curlError !== '') {
    error_log('Customer account API request failed: ' . $curlError);
    accountRespond(502, ['success' => false, 'message' => 'The account service is unavailable. Please try again.']);
}

$result = json_decode($response, true);
if (!is_array($result)) {
    accountRespond(502, ['success' => false, 'message' => 'The account service returned an invalid response.']);
}

if ($status === 401) {
    logoutCustomer();
    accountRespond(401, ['success' => false, 'message' => 'Your session expired. Please sign in again.']);
}

if ($action === 'profile' && !empty($result['success'])) {
    $_SESSION['kohi_customer']['full_name'] = $payload['full_name'];
    $_SESSION['kohi_customer']['email'] = $payload['email'];
    $_SESSION['kohi_customer']['phone'] = $payload['phone'];
}

http_response_code($status >= 400 ? $status : 200);
echo json_encode($result, JSON_UNESCAPED_SLASHES);

