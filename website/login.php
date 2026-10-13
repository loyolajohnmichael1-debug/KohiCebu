<?php

/*
 * =========================================================
 * KOHI CEBU — CUSTOMER LOGIN
 * =========================================================
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api_client.php';


/*
 * ---------------------------------------------------------
 * SAFE REDIRECT HANDLER
 * ---------------------------------------------------------
 */

function getSafeLoginRedirect(string $redirect): string
{
    /*
     * Only allow internal website paths.
     */
    if ($redirect === "") {
        return "index.php";
    }

    /*
     * Block absolute URLs such as:
     *
     * https://example.com
     * //example.com
     */
    if (
        preg_match(
            '/^(https?:)?\/\//i',
            $redirect
        )
    ) {
        return "index.php";
    }

    /*
     * Block protocols such as:
     *
     * javascript:
     * data:
     * etc.
     */
    if (
        preg_match(
            '/^[a-z][a-z0-9+.-]*:/i',
            $redirect
        )
    ) {
        return "index.php";
    }

    return ltrim(
        $redirect,
        "/"
    );
}


/*
 * ---------------------------------------------------------
 * IF ALREADY LOGGED IN
 * ---------------------------------------------------------
 */

if (isCustomerLoggedIn()) {

    $redirect =
        $_GET["redirect"] ?? "index.php";

    $redirect =
        getSafeLoginRedirect($redirect);

    header(
        "Location: " . $redirect
    );

    exit;
}


/*
 * ---------------------------------------------------------
 * REDIRECT DESTINATION
 * ---------------------------------------------------------
 */

$redirect =
    $_GET["redirect"]
    ?? $_POST["redirect"]
    ?? "index.php";

$redirect =
    getSafeLoginRedirect($redirect);


/*
 * ---------------------------------------------------------
 * LOGIN STATE
 * ---------------------------------------------------------
 */

$errorMessage = "";

$emailValue = "";


/*
 * ---------------------------------------------------------
 * PROCESS LOGIN
 * ---------------------------------------------------------
 */

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $email =
        strtolower(
            trim(
                $_POST["email"] ?? ""
            )
        );

    $password =
        $_POST["password"] ?? "";

    $emailValue = $email;


    /*
     * -----------------------------------------------------
     * VALIDATE EMAIL
     * -----------------------------------------------------
     */

    if ($email === "") {

        $errorMessage =
            "Email is required.";

    } elseif (
        !filter_var(
            $email,
            FILTER_VALIDATE_EMAIL
        )
    ) {

        $errorMessage =
            "Please enter a valid email address.";

    }


    /*
     * -----------------------------------------------------
     * VALIDATE PASSWORD
     * -----------------------------------------------------
     */

    elseif ($password === "") {

        $errorMessage =
            "Password is required.";

    } elseif (strlen($password) < 6) {

        $errorMessage =
            "Password must be at least 6 characters.";

    }


    /*
     * -----------------------------------------------------
     * CALL LOGIN API
     * -----------------------------------------------------
     */

    if ($errorMessage === "") {

        /*
         * IMPORTANT:
         *
         * This is the same API URL currently used
         * by the Android application.
         */
        $apiUrl = kohiApiUrl('login.php');


        /*
         * Request body.
         */
        $requestBody = json_encode(
            [
                "email" => $email,
                "password" => $password
            ],
            JSON_UNESCAPED_SLASHES
        );


        /*
         * Make sure JSON encoding succeeded.
         */
        if ($requestBody === false) {

            $errorMessage =
                "Unable to prepare the login request.";

        } else {

            /*
             * -------------------------------------------------
             * cURL REQUEST
             * -------------------------------------------------
             */

            $ch =
                curl_init($apiUrl);

            curl_setopt_array(
                $ch,
                [

                    CURLOPT_POST => true,

                    CURLOPT_POSTFIELDS =>
                        $requestBody,

                    CURLOPT_HTTPHEADER => [

                        "Content-Type: application/json",

                        "Accept: application/json",

                        "Content-Length: " .
                            strlen($requestBody)

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
             * -------------------------------------------------
             * CONNECTION ERROR
             * -------------------------------------------------
             */

            if (
                $response === false ||
                $curlError !== ""
            ) {

                error_log(
                    "KohiCebu website login cURL error: " .
                    $curlError
                );

                $errorMessage =
                    "Unable to connect to the login service. Please try again.";

            } else {

                /*
                 * Decode API response.
                 */
                $result =
                    json_decode(
                        $response,
                        true
                    );


                /*
                 * -------------------------------------------------
                 * INVALID API RESPONSE
                 * -------------------------------------------------
                 */

                if (
                    !is_array($result)
                ) {

                    error_log(
                        "KohiCebu website login invalid API response. " .
                        "HTTP: " . $httpCode .
                        " Response: " . $response
                    );

                    $errorMessage =
                        "The login service returned an invalid response.";

                }


                /*
                 * -------------------------------------------------
                 * LOGIN SUCCESS
                 * -------------------------------------------------
                 */

                elseif (
                    isset($result["success"])
                    && $result["success"] === true
                ) {

                    $user =
                        $result["user"]
                        ?? null;

                    $token =
                        $result["token"]
                        ?? "";

                    $expiresAt =
                        $result["expires_at"]
                        ?? "";


                    /*
                     * Make sure the API returned
                     * everything required.
                     */

                    if (
                        !is_array($user)
                        || $token === ""
                        || $expiresAt === ""
                        || strtoupper((string) ($user["role"] ?? "")) !== "CUSTOMER"
                    ) {

                        error_log(
                            "KohiCebu website login succeeded " .
                            "but session information was incomplete."
                        );

                        $errorMessage =
                            "This login is for customer accounts only.";

                    } else {

                        /*
                         * Save authenticated customer
                         * into the PHP website session.
                         */
                        loginCustomer(
                            $user,
                            $token,
                            $expiresAt
                        );


                        /*
                         * Redirect customer to the
                         * page they originally requested.
                         */
                        header(
                            "Location: " . $redirect
                        );

                        exit;
                    }

                }


                /*
                 * -------------------------------------------------
                 * LOGIN FAILED
                 * -------------------------------------------------
                 */

                else {

                    /*
                     * Log the actual API response for debugging.
                     */
                    error_log(
                        "KohiCebu website login failed. " .
                        "HTTP: " . $httpCode .
                        " Response: " . $response
                    );


                    /*
                     * Show the clean API message.
                     */
                    $apiMessage =
                        $result["message"]
                        ?? "Invalid email or password.";


                    $errorMessage =
                        $apiMessage;

                }

            }

        }

    }

}


/*
 * ---------------------------------------------------------
 * PAGE TITLE
 * ---------------------------------------------------------
 */

$pageTitle = 'Login — KohiCebu.';

require_once __DIR__ . '/includes/header.php';

?>


<link
    rel="stylesheet"
    href="css/login.css"
>


<main class="login-page">

    <section class="login-section">

        <div class="container">

            <div class="login-layout">


                <!-- =========================================
                     LOGIN INTRO
                     ========================================= -->

                <div class="login-intro">

                    <p class="eyebrow">
                        WELCOME BACK
                    </p>

                    <h1>
                        Log in to<br>
                        your Kohi.
                    </h1>

                    <p class="login-intro__text">
                        Log in to your KohiCebu account
                        to browse the menu and place
                        your order.
                    </p>

                </div>


                <!-- =========================================
                     LOGIN FORM
                     ========================================= -->

                <div class="login-card">

                    <div class="login-card__header">

                        <p class="eyebrow">
                            CUSTOMER ACCOUNT
                        </p>

                        <h2>
                            Log in
                        </h2>

                    </div>


                    <?php if ($errorMessage !== ""): ?>

                        <div
                            class="login-message login-message--error"
                            role="alert"
                        >
                            <?= htmlspecialchars(
                                $errorMessage,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>
                        </div>

                    <?php endif; ?>

                    <?php if (isset($_GET['registered'])): ?>
                        <div class="login-message login-message--success" role="status">Your account is ready. Log in to continue.</div>
                    <?php endif; ?>

                    <section class="google-auth" data-google-auth
                        data-client-id="831845580299-ib9cd05ujm1tvh918a9fd3hm1doof99r.apps.googleusercontent.com"
                        data-csrf="<?= htmlspecialchars(getCustomerCsrfToken(), ENT_QUOTES, 'UTF-8') ?>"
                        data-redirect="<?= htmlspecialchars($redirect, ENT_QUOTES, 'UTF-8') ?>">
                        <div data-google-button></div>
                        <p data-google-status class="google-auth__status" role="status" aria-live="polite" hidden></p>
                    </section>
                    <div class="auth-divider"><span>or log in with email</span></div>


                    <form
                        method="POST"
                        action="login.php"
                        class="login-form"
                    >

                        <!-- Preserve destination -->

                        <input
                            type="hidden"
                            name="redirect"
                            value="<?= htmlspecialchars(
                                $redirect,
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>"
                        >


                        <!-- Email -->

                        <div class="login-field">

                            <label
                                for="email"
                            >
                                Email
                            </label>

                            <input
                                type="email"
                                id="email"
                                name="email"
                                value="<?= htmlspecialchars(
                                    $emailValue,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>"
                                autocomplete="email"
                                required
                            >

                        </div>


                        <!-- Password -->

                        <div class="login-field">

                            <label
                                for="password"
                            >
                                Password
                            </label>

                            <input
                                type="password"
                                id="password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >

                        </div>


                        <!-- Submit -->

                        <button
                            type="submit"
                            class="button button--dark login-form__submit"
                        >
                            LOG IN
                        </button>

                    </form>


                    <div class="login-card__footer">

                        <p>
                            Don't have an account?
                        </p>

                        <a
                            href="register.php"
                            class="text-link"
                        >
                            Create an account →
                        </a>

                    </div>

                </div>

            </div>

        </div>

    </section>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>
