<?php
require_once __DIR__ . '/includes/auth.php';
requireCustomerLogin();
if (getCustomerRole() !== 'CUSTOMER') { header('Location: menu.php'); exit; }
$pageTitle = 'Change Password — KohiCebu.';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="css/account.css">
<link rel="stylesheet" href="css/account-pages.css">
<main class="account-page account-page--form">
    <section class="account-hero"><div class="container">
        <p class="eyebrow">ACCOUNT SECURITY</p><h1>Change password</h1>
        <p class="account-hero__text">Choose a new password you do not use for other accounts.</p>
    </div></section>
    <section class="account-section"><div class="container account-form-container">
        <a class="account-back-link" href="account.php">← Back to My Account</a>
        <form class="account-form" id="passwordForm" data-action="password">
            <label for="currentPassword">Current password</label>
            <input id="currentPassword" name="current_password" type="password" autocomplete="current-password" required>
            <label for="newPassword">New password</label>
            <input id="newPassword" name="new_password" type="password" autocomplete="new-password" minlength="6" required>
            <p class="account-form__hint">Use at least 6 characters.</p>
            <p class="account-form__message" id="formMessage" role="status" aria-live="polite"></p>
            <button class="button button--dark" type="submit">Update password</button>
        </form>
    </div></section>
</main>
<script src="js/account-pages.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
