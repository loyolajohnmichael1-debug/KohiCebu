<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api_client.php';
header('Content-Type: application/json; charset=utf-8');

function googleWebsiteReply(int $status, array $body): void {
    http_response_code($status);
    echo json_encode($body);
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') googleWebsiteReply(405, ['success'=>false,'message'=>'Only POST is allowed.']);
$data = json_decode(file_get_contents('php://input'), true);
$csrf = (string)($data['csrf_token'] ?? '');
if (!is_array($data) || $csrf === '' || !hash_equals(getCustomerCsrfToken(), $csrf)) {
    googleWebsiteReply(403, ['success'=>false,'message'=>'Your session expired. Refresh and try again.']);
}
$payload = json_encode(['id_token'=>(string)($data['id_token'] ?? ''), 'phone'=>(string)($data['phone'] ?? '')]);
$ch = curl_init(kohiApiUrl('google_login.php'));
curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload, CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json'], CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>25]);
$response = curl_exec($ch); $curlError = curl_error($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
if ($response === false || $curlError !== '') {
    error_log('Google auth API connection failed: '.$curlError);
    googleWebsiteReply(502, ['success'=>false,'message'=>'Could not reach the sign-in service. Please try again.']);
}
$result = json_decode($response, true);
if (!is_array($result)) googleWebsiteReply(502, ['success'=>false,'message'=>'The sign-in service returned an invalid response.']);
if ($status < 200 || $status >= 300 || empty($result['success'])) {
    googleWebsiteReply($status ?: 502, ['success'=>false,'message'=>$result['message'] ?? 'Google sign-in failed.','needs_registration'=>in_array($status, [404, 422], true)]);
}
$user = $result['user'] ?? null; $token = (string)($result['token'] ?? ''); $expires = (string)($result['expires_at'] ?? '');
if (!is_array($user) || strtoupper((string)($user['role'] ?? '')) !== 'CUSTOMER' || $token === '' || $expires === '') {
    googleWebsiteReply(502, ['success'=>false,'message'=>'The sign-in response was incomplete.']);
}
loginCustomer($user, $token, $expires);
googleWebsiteReply(200, ['success'=>true,'is_new_user'=>!empty($result['is_new_user'])]);
