<?php
require_once __DIR__ . '/includes/auth.php';
requireCustomerLogin();
if (getCustomerRole() !== 'CUSTOMER') { header('Location: menu.php'); exit; }
$customer = getCurrentCustomer();
$pageTitle = 'Edit Profile — KohiCebu.';
require_once __DIR__ . '/includes/header.php';
?>
<link rel="stylesheet" href="css/account.css">
<link rel="stylesheet" href="css/account-pages.css">
<main class="account-page account-page--form">
    <section class="account-hero"><div class="container">
        <p class="eyebrow">MY ACCOUNT</p><h1>Edit profile</h1>
        <p class="account-hero__text">Keep your contact details up to date for your orders.</p>
    </div></section>
    <section class="account-section"><div class="container account-form-container">
        <a class="account-back-link" href="account.php">← Back to My Account</a>
        <form class="account-form" id="profileForm" data-action="profile">
            <label for="fullName">Full name</label>
            <input id="fullName" name="full_name" autocomplete="name" maxlength="120" required value="<?= htmlspecialchars($customer['full_name'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" autocomplete="email" maxlength="254" required value="<?= htmlspecialchars($customer['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <label for="phone">Phone <span class="account-form__optional">Optional</span></label>
            <input id="phone" name="phone" type="tel" autocomplete="tel" maxlength="30" value="<?= htmlspecialchars($customer['phone'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <p class="account-form__message" id="formMessage" role="status" aria-live="polite"></p>
            <button class="button button--dark" type="submit">Save changes</button>
        </form>
    </div></section>
</main>
<script src="js/account-pages.js"></script>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
