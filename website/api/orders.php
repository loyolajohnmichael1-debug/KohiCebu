<?php

/*
 * =========================================================
 * KOHI CEBU — WEBSITE ORDERS PROXY
 * =========================================================
 */

header("Content-Type: application/json");

require_once __DIR__ . "/../includes/auth.php";
require_once __DIR__ . "/../includes/api_client.php";


/*
 * ---------------------------------------------------------
 * REQUIRE CUSTOMER LOGIN
 * ---------------------------------------------------------
 */

if (!isCustomerLoggedIn() || customerTokenExpired()) {
    if (isCustomerLoggedIn()) {
        logoutCustomer();
    }
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Your session expired. Please sign in again."
    ]);
    exit;
}

if (getCustomerRole() !== 'CUSTOMER') {
    http_response_code(403);
    echo json_encode([
        "success" => false,
        "message" => "Only customer accounts can view website orders."
    ]);
    exit;
}


/*
 * ---------------------------------------------------------
 * CUSTOMER TOKEN
 * ---------------------------------------------------------
 */

$token = getCustomerToken();

if ($token === "") {

    http_response_code(401);

    echo json_encode([
        "success" => false,
        "message" => "Customer authentication token is missing."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * KOHI CEBU API
 * ---------------------------------------------------------
 */

$apiUrl = kohiApiUrl('get_orders.php');


/*
 * ---------------------------------------------------------
 * CURL REQUEST
 * ---------------------------------------------------------
 */

$ch = curl_init($apiUrl);

curl_setopt_array(
    $ch,
    [

        CURLOPT_HTTPGET => true,

        CURLOPT_HTTPHEADER => [

            "Accept: application/json",

            "Authorization: Bearer " . $token

        ],

        CURLOPT_RETURNTRANSFER => true,

        CURLOPT_FOLLOWLOCATION => false,

        CURLOPT_CONNECTTIMEOUT => 10,

        CURLOPT_TIMEOUT => 20

    ]
);


$response =
    curl_exec($ch);


$curlError =
    curl_error($ch);


$httpCode =
    curl_getinfo(
        $ch,
        CURLINFO_HTTP_CODE
    );


curl_close($ch);


/*
 * ---------------------------------------------------------
 * CONNECTION ERROR
 * ---------------------------------------------------------
 */

if (
    $response === false ||
    $curlError !== ""
) {

    error_log(
        "KohiCebu website orders API cURL error: " .
        $curlError
    );

    http_response_code(502);

    echo json_encode([
        "success" => false,
        "message" => "Unable to connect to the order service."
    ]);

    exit;
}


/*
 * ---------------------------------------------------------
 * API RESPONSE
 * ---------------------------------------------------------
 */

$result =
    json_decode(
        $response,
        true
    );


if (!is_array($result)) {

    error_log(
        "KohiCebu website orders API returned invalid JSON. " .
        "HTTP: " . $httpCode .
        " Response: " . $response
    );

    http_response_code(502);

    echo json_encode([
        "success" => false,
        "message" => "The order service returned an invalid response."
    ]);

    exit;
}

if ($httpCode === 401) {
    logoutCustomer();
    http_response_code(401);
    echo json_encode([
        "success" => false,
        "message" => "Your session expired. Please sign in again."
    ]);
    exit;
}


/*
 * ---------------------------------------------------------
 * FORWARD API STATUS
 * ---------------------------------------------------------
 */

http_response_code(
    $httpCode > 0
        ? $httpCode
        : 500
);


/*
 * ---------------------------------------------------------
 * RETURN API RESPONSE
 * ---------------------------------------------------------
 */

echo json_encode($result);

?>  
