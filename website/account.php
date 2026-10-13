<?php

/*
 * =========================================================
 * KOHI CEBU — CUSTOMER ACCOUNT
 * =========================================================
 */

require_once __DIR__ . '/includes/auth.php';


/*
 * ---------------------------------------------------------
 * REQUIRE CUSTOMER LOGIN
 * ---------------------------------------------------------
 */

requireCustomerLogin();
if (getCustomerRole() !== 'CUSTOMER') {
    header('Location: menu.php');
    exit;
}


/*
 * ---------------------------------------------------------
 * CUSTOMER DATA
 * ---------------------------------------------------------
 */

$customer = getCurrentCustomer();

$customerName =
    getCustomerName();

$customerEmail =
    getCustomerEmail();

$customerPhone =
    getCustomerPhone();

$customerRole =
    getCustomerRole();

$customerCreatedAt =
    $customer["created_at"] ?? "";


/*
 * ---------------------------------------------------------
 * PAGE TITLE
 * ---------------------------------------------------------
 */

$pageTitle = 'My Account — KohiCebu.';

require_once __DIR__ . '/includes/header.php';

?>

<link
    rel="stylesheet"
    href="css/account.css"
>


<main class="account-page">

    <!-- =====================================================
         ACCOUNT HERO
         ===================================================== -->

    <section class="account-hero">

        <div class="container">

            <p class="eyebrow">
                CUSTOMER ACCOUNT
            </p>

            <h1>
                Welcome back,
                <?= htmlspecialchars(
                    $customerName,
                    ENT_QUOTES,
                    'UTF-8'
                ) ?>.
            </h1>

            <p class="account-hero__text">
                Manage your KohiCebu account,
                orders, and personal information.
            </p>

        </div>

    </section>


    <!-- =====================================================
         ACCOUNT CONTENT
         ===================================================== -->

    <section class="account-section">

        <div class="container">

            <div class="account-grid">


                <!-- =================================================
                     PROFILE CARD
                     ================================================= -->

                <article class="account-card account-card--profile">

                    <div class="account-card__header">

                        <div>

                            <p class="eyebrow">
                                PROFILE
                            </p>

                            <h2>
                                Personal Information
                            </h2>

                        </div>

                    </div>


                    <div class="account-profile">

                        <div class="account-profile__avatar">

                            <?= htmlspecialchars(
                                strtoupper(
                                    substr(
                                        $customerName,
                                        0,
                                        1
                                    )
                                ),
                                ENT_QUOTES,
                                'UTF-8'
                            ) ?>

                        </div>


                        <div class="account-profile__info">

                            <h3>
                                <?= htmlspecialchars(
                                    $customerName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </h3>

                            <p>
                                <?= htmlspecialchars(
                                    $customerEmail,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </p>

                        </div>

                    </div>


                    <div class="account-details">

                        <div class="account-detail">

                            <span>
                                Full Name
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $customerName,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-detail">

                            <span>
                                Email
                            </span>

                            <strong>
                                <?= htmlspecialchars(
                                    $customerEmail,
                                    ENT_QUOTES,
                                    'UTF-8'
                                ) ?>
                            </strong>

                        </div>


                        <div class="account-detail">

                            <span>
                                Phone
                            </span>

                            <strong>
                                <?= $customerPhone !== ""
                                    ? htmlspecialchars(
                                        $customerPhone,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    )
                                    : "Not provided" ?>
                            </strong>

                        </div>


                        <?php if ($customerCreatedAt !== ""): ?>

                            <div class="account-detail">

                                <span>
                                    Member Since
                                </span>

                                <strong>
                                    <?= htmlspecialchars(
                                        date(
                                            "F Y",
                                            strtotime(
                                                $customerCreatedAt
                                            )
                                        ),
                                        ENT_QUOTES,
                                        'UTF-8'
                                    ) ?>
                                </strong>

                            </div>

                        <?php endif; ?>

                    </div>


                    <a
                        href="edit-profile.php"
                        class="button button--dark account-card__button"
                    >
                        EDIT PROFILE
                    </a>

                </article>


                <!-- =================================================
                     ACCOUNT ACTIONS
                     ================================================= -->

                <div class="account-actions">


                    <!-- My Orders -->

                    <a
                        href="orders.php"
                        class="account-action"
                    >

                        <div class="account-action__content">

                            <p class="eyebrow">
                                ORDERS
                            </p>

                            <h2>
                                My Orders
                            </h2>

                            <p>
                                View your order history
                                and track your current orders.
                            </p>

                        </div>

                        <span class="account-action__arrow">
                            →
                        </span>

                    </a>


                    <!-- Change Password -->

                    <a
                        href="change-password.php"
                        class="account-action"
                    >

                        <div class="account-action__content">

                            <p class="eyebrow">
                                SECURITY
                            </p>

                            <h2>
                                Change Password
                            </h2>

                            <p>
                                Update your account password
                                and keep your account secure.
                            </p>

                        </div>

                        <span class="account-action__arrow">
                            →
                        </span>

                    </a>


                    <!-- Login Activity -->

                    <a
                        href="login-activity.php"
                        class="account-action"
                    >

                        <div class="account-action__content">

                            <p class="eyebrow">
                                SECURITY
                            </p>

                            <h2>
                                Login Activity
                            </h2>

                            <p>
                                Review recent activity
                                on your KohiCebu account.
                            </p>

                        </div>

                        <span class="account-action__arrow">
                            →
                        </span>

                    </a>

                </div>

            </div>


            <!-- =====================================================
                 LOG OUT
                 ===================================================== -->

            <div class="account-logout">

                <form action="logout.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(getCustomerCsrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                    <button type="submit" class="account-logout__link">Log Out</button>
                </form>

            </div>

        </div>

    </section>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>
