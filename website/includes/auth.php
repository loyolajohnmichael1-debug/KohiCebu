<?php

/*
 * =========================================================
 * KOHI CEBU — WEBSITE AUTHENTICATION HELPER
 * =========================================================
 *
 * This helper manages the customer's authenticated
 * website session.
 *
 * The actual authentication is still handled by:
 *
 *      ../api/login.php
 *
 * The API token returned by the API is stored in the
 * server-side PHP session and can later be used when
 * communicating with protected KohiCebu API endpoints.
 */


/*
 * ---------------------------------------------------------
 * START SESSION
 * ---------------------------------------------------------
 */

if (session_status() === PHP_SESSION_NONE) {

    session_start();

}


/*
 * ---------------------------------------------------------
 * CHECK LOGIN STATUS
 * ---------------------------------------------------------
 */

function isCustomerLoggedIn(): bool
{
    if (
        !isset($_SESSION["kohi_customer"])
        || !is_array($_SESSION["kohi_customer"])
        || ($_SESSION["kohi_customer"]["logged_in"] ?? false) !== true
        || trim((string) ($_SESSION["kohi_customer"]["token"] ?? "")) === ""
    ) {
        return false;
    }

    $expiresAt = (string) ($_SESSION["kohi_customer"]["expires_at"] ?? "");
    $expirationTime = $expiresAt !== "" ? strtotime($expiresAt) : false;

    return $expirationTime !== false && $expirationTime > time();
}


/*
 * ---------------------------------------------------------
 * GET CURRENT CUSTOMER
 * ---------------------------------------------------------
 */

function getCurrentCustomer(): ?array
{
    if (!isCustomerLoggedIn()) {
        return null;
    }

    return $_SESSION["kohi_customer"];
}


/*
 * ---------------------------------------------------------
 * GET CUSTOMER ID
 * ---------------------------------------------------------
 */

function getCustomerId(): int
{
    if (!isCustomerLoggedIn()) {
        return -1;
    }

    return (int) (
        $_SESSION["kohi_customer"]["user_id"] ?? -1
    );
}


/*
 * ---------------------------------------------------------
 * GET CUSTOMER NAME
 * ---------------------------------------------------------
 */

function getCustomerName(): string
{
    if (!isCustomerLoggedIn()) {
        return "";
    }

    return (string) (
        $_SESSION["kohi_customer"]["full_name"] ?? ""
    );
}


/*
 * ---------------------------------------------------------
 * GET CUSTOMER EMAIL
 * ---------------------------------------------------------
 */

function getCustomerEmail(): string
{
    if (!isCustomerLoggedIn()) {
        return "";
    }

    return (string) (
        $_SESSION["kohi_customer"]["email"] ?? ""
    );
}


/*
 * ---------------------------------------------------------
 * GET CUSTOMER PHONE
 * ---------------------------------------------------------
 */

function getCustomerPhone(): string
{
    if (!isCustomerLoggedIn()) {
        return "";
    }

    return (string) (
        $_SESSION["kohi_customer"]["phone"] ?? ""
    );
}


/*
 * ---------------------------------------------------------
 * GET CUSTOMER ROLE
 * ---------------------------------------------------------
 */

function getCustomerRole(): string
{
    if (!isCustomerLoggedIn()) {
        return "";
    }

    return (string) (
        $_SESSION["kohi_customer"]["role"] ?? ""
    );
}


/*
 * ---------------------------------------------------------
 * GET API TOKEN
 * ---------------------------------------------------------
 */

function getCustomerToken(): string
{
    if (!isCustomerLoggedIn()) {
        return "";
    }

    return (string) (
        $_SESSION["kohi_customer"]["token"] ?? ""
    );
}


/*
 * ---------------------------------------------------------
 * GET TOKEN EXPIRATION
 * ---------------------------------------------------------
 */

function getCustomerTokenExpiry(): string
{
    if (!isCustomerLoggedIn()) {
        return "";
    }

    return (string) (
        $_SESSION["kohi_customer"]["expires_at"] ?? ""
    );
}

function getCustomerCsrfToken(): string
{
    if (empty($_SESSION["kohi_csrf_token"])) {
        $_SESSION["kohi_csrf_token"] = bin2hex(random_bytes(32));
    }

    return (string) $_SESSION["kohi_csrf_token"];
}


/*
 * ---------------------------------------------------------
 * SAVE CUSTOMER SESSION
 * ---------------------------------------------------------
 */

function loginCustomer(
    array $user,
    string $token,
    string $expiresAt
): void {

    /*
     * Regenerate the session ID after authentication.
     *
     * This helps prevent session fixation.
     */
    session_regenerate_id(true);

    $_SESSION["kohi_customer"] = [

        "logged_in" => true,

        "user_id" => (int) (
            $user["user_id"] ?? -1
        ),

        "full_name" => (string) (
            $user["full_name"] ?? ""
        ),

        "email" => (string) (
            $user["email"] ?? ""
        ),

        "phone" => (string) (
            $user["phone"] ?? ""
        ),

        "role" => (string) (
            $user["role"] ?? ""
        ),

        "created_at" => (string) (
            $user["created_at"] ?? ""
        ),

        /*
         * Keep the API token server-side.
         */
        "token" => $token,

        "expires_at" => $expiresAt

    ];

}


/*
 * ---------------------------------------------------------
 * LOGOUT CUSTOMER
 * ---------------------------------------------------------
 */

function logoutCustomer(): void
{
    /*
     * Clear the authenticated customer session.
     */
    unset($_SESSION["kohi_customer"]);

    /*
     * Destroy the current PHP session.
     */
    session_destroy();
}


/*
 * ---------------------------------------------------------
 * REQUIRE LOGIN
 * ---------------------------------------------------------
 *
 * Used by protected website pages.
 *
 * Example:
 *
 * requireCustomerLogin();
 *
 * A guest will be redirected to login.php.
 */

function requireCustomerLogin(): void
{
    if (isCustomerLoggedIn()) {
        return;
    }

    if (isset($_SESSION["kohi_customer"])) {
        unset($_SESSION["kohi_customer"]);
    }

    /*
     * Remember the page the customer was trying
     * to access.
     */
    $requestUri = $_SERVER["REQUEST_URI"] ?? "";
    $requestPath = parse_url($requestUri, PHP_URL_PATH);
    $page = is_string($requestPath) ? basename($requestPath) : "";
    $query = parse_url($requestUri, PHP_URL_QUERY);
    $currentUrl = $page !== "" ? $page : "index.php";
    if (is_string($query) && $query !== "") {
        $currentUrl .= "?" . $query;
    }

    $redirect = urlencode($currentUrl);

    header(
        "Location: login.php?redirect=" . $redirect
    );

    exit;
}


/*
 * ---------------------------------------------------------
 * CHECK TOKEN EXPIRATION
 * ---------------------------------------------------------
 */

function customerTokenExpired(): bool
{
    if (!isCustomerLoggedIn()) {
        return true;
    }

    $expiresAt =
        getCustomerTokenExpiry();

    if ($expiresAt === "") {
        return true;
    }

    $expirationTime =
        strtotime($expiresAt);

    if ($expirationTime === false) {
        return true;
    }

    return $expirationTime <= time();
}


/*
 * ---------------------------------------------------------
 * REQUIRE VALID CUSTOMER SESSION
 * ---------------------------------------------------------
 *
 * This is slightly stricter than requireCustomerLogin().
 *
 * It checks both:
 *
 * 1. PHP session exists
 * 2. API token has not expired locally
 *
 * The API itself remains responsible for validating
 * the token against api_tokens.
 */

function requireValidCustomerSession(): void
{
    if (!isCustomerLoggedIn()) {

        requireCustomerLogin();

        return;
    }

    if (customerTokenExpired()) {

        logoutCustomer();

        $currentUrl =
            $_SERVER["REQUEST_URI"] ?? "index.php";

        $redirect =
            urlencode($currentUrl);

        header(
            "Location: login.php?redirect=" . $redirect
        );

        exit;
    }
}

?>
