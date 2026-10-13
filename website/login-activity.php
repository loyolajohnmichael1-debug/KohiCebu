<?php
require_once __DIR__ . '/includes/auth.php';
requireCustomerLogin();
if (getCustomerRole() !== 'CUSTOMER') { header('Location: menu.php'); exit; }
$pageTitle = 'Login Activity — KohiCebu.';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="css/account.css">
<link rel="stylesheet" href="css/account-pages.css">
<main class="account-page account-page--form">
    <section class="account-hero"><div class="container">
        <p class="eyebrow">ACCOUNT SECURITY</p><h1>Login activity</h1>
        <p class="account-hero__text">Recent sign-ins recorded for your account.</p>
    </div></section>
    <section class="account-section"><div class="container account-form-container">
        <a class="account-back-link" href="account.php">← Back to My Account</a>
        <div id="activityState" class="account-form__message" role="status" aria-live="polite">Loading activity…</div>
        <ol class="account-activity" id="activityList" hidden></ol>
    </div></section>
</main>
<script src="js/account-pages.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
