<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/api_client.php';
if (isCustomerLoggedIn()) { header('Location: index.php'); exit; }
$errorMessage = '';
$values = ['full_name'=>'', 'email'=>'', 'phone'=>''];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($values as $key => $_) $values[$key] = trim((string)($_POST[$key] ?? ''));
    $password = (string)($_POST['password'] ?? '');
    $csrf = (string)($_POST['csrf_token'] ?? '');
    if ($csrf === '' || !hash_equals(getCustomerCsrfToken(), $csrf)) $errorMessage = 'Your session expired. Refresh the page and try again.';
    elseif (strlen($values['full_name']) < 2 || strlen($values['full_name']) > 100) $errorMessage = 'Name must be between 2 and 100 characters.';
    elseif (!filter_var($values['email'], FILTER_VALIDATE_EMAIL)) $errorMessage = 'Enter a valid email address.';
    elseif (strlen($values['phone']) < 7 || strlen($values['phone']) > 20) $errorMessage = 'Enter a valid phone number.';
    elseif (strlen($password) < 6) $errorMessage = 'Password must be at least 6 characters.';
    else {
        $payload = json_encode($values + ['password'=>$password]);
        $ch = curl_init(kohiApiUrl('register.php'));
        curl_setopt_array($ch, [CURLOPT_POST=>true, CURLOPT_POSTFIELDS=>$payload, CURLOPT_HTTPHEADER=>['Content-Type: application/json','Accept: application/json'], CURLOPT_RETURNTRANSFER=>true, CURLOPT_FOLLOWLOCATION=>false, CURLOPT_CONNECTTIMEOUT=>10, CURLOPT_TIMEOUT=>20]);
        $response = curl_exec($ch); $curlError = curl_error($ch); $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
        $result = is_string($response) ? json_decode($response, true) : null;
        if ($response === false || $curlError !== '') { error_log('Website registration API connection failed: '.$curlError); $errorMessage = 'Unable to connect to registration. Please try again.'; }
        elseif (!is_array($result)) $errorMessage = 'Registration returned an invalid response. Please try again.';
        elseif ($status < 200 || $status >= 300 || empty($result['success'])) $errorMessage = $result['message'] ?? 'Could not create your account.';
        else { header('Location: login.php?registered=1'); exit; }
    }
}
$pageTitle = 'Create an account — KohiCebu.';
require_once __DIR__ . '/includes/header.php';
?>
<main class="login-page"><section class="login-section"><div class="container"><div class="login-layout">
    <div class="login-intro"><p class="eyebrow">YOUR NEXT COFFEE IS CLOSER</p><h1>Make room<br>for good coffee.</h1><p class="login-intro__text">Create a KohiCebu account to order your favorites and keep your details ready for next time.</p></div>
    <div class="login-card"><div class="login-card__header"><p class="eyebrow">CUSTOMER ACCOUNT</p><h2>Create account</h2></div>
        <?php if ($errorMessage !== ''): ?><div class="login-message login-message--error" role="alert"><?= htmlspecialchars($errorMessage, ENT_QUOTES, 'UTF-8') ?></div><?php endif; ?>
        <section class="google-auth" data-google-auth data-client-id="831845580299-ib9cd05ujm1tvh918a9fd3hm1doof99r.apps.googleusercontent.com" data-csrf="<?= htmlspecialchars(getCustomerCsrfToken(), ENT_QUOTES, 'UTF-8') ?>" data-redirect="index.php" data-phone-input="phone">
            <div data-google-button></div><p class="google-auth__hint">Google will share your verified email and name. Add your phone number below first.</p><p data-google-status class="google-auth__status" role="status" aria-live="polite" hidden></p>
        </section>
        <div class="auth-divider"><span>or create an account with email</span></div>
        <form method="POST" action="register.php" class="login-form" autocomplete="on">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCustomerCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
            <div class="login-field"><label for="full_name">Full name</label><input id="full_name" name="full_name" value="<?= htmlspecialchars($values['full_name'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="name" minlength="2" maxlength="100" required></div>
            <div class="login-field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="email" required></div>
            <div class="login-field"><label for="phone">Phone number</label><input type="tel" id="phone" name="phone" value="<?= htmlspecialchars($values['phone'], ENT_QUOTES, 'UTF-8') ?>" autocomplete="tel" minlength="7" maxlength="20" required></div>
            <div class="login-field"><label for="password">Password</label><input type="password" id="password" name="password" autocomplete="new-password" minlength="6" required></div>
            <button type="submit" class="button button--dark login-form__submit">CREATE ACCOUNT</button>
        </form>
        <div class="login-card__footer"><p>Already have an account?</p><a href="login.php" class="text-link">Log in →</a></div>
    </div>
</div></div></section></main>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
