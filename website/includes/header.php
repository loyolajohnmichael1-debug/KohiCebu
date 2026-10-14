<?php

$pageTitle = $pageTitle ?? 'KohiCebu.';

$currentPage = basename($_SERVER['PHP_SELF']);

require_once __DIR__ . '/auth.php';

$isLoggedIn = isCustomerLoggedIn();

$menuLink = 'menu.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="description"
        content="KohiCebu — Coffee, matcha, cookies, and good moments in Talisay, Cebu."
    >

    <title><?= htmlspecialchars($pageTitle) ?></title>


    <!-- =====================================================
         FONT
         ===================================================== -->

    <link rel="preconnect" href="https://fonts.googleapis.com">

    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >

    <link
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap"
        rel="stylesheet"
    >


    <!-- =====================================================
         GLOBAL CSS
         ===================================================== -->

    <link
        rel="stylesheet"
        href="css/global.css"
    >


    <!-- =====================================================
         PAGE-SPECIFIC CSS
         ===================================================== -->

    <?php if ($currentPage === 'index.php'): ?>

        <link
            rel="stylesheet"
            href="css/home.css"
        >

    <?php elseif ($currentPage === 'about.php'): ?>

        <link
            rel="stylesheet"
            href="css/about.css"
        >

    <?php elseif ($currentPage === 'visit.php'): ?>

        <link
            rel="stylesheet"
            href="css/visit.css"
        >

    <?php elseif ($currentPage === 'gallery.php'): ?>

        <link
            rel="stylesheet"
            href="css/gallery.css"
        >

    <?php elseif ($currentPage === 'menu.php'): ?>

        <link
            rel="stylesheet"
            href="css/menu.css"
        >

    <?php elseif ($currentPage === 'product.php'): ?>

        <link
            rel="stylesheet"
            href="css/product.css"
        >

    <?php elseif ($currentPage === 'login.php'): ?>

        <link
            rel="stylesheet"
            href="css/login.css"
        >

    <?php elseif ($currentPage === 'register.php'): ?>

        <link rel="stylesheet" href="css/login.css">

    <?php elseif ($currentPage === 'checkout.php'): ?>

        <link
            rel="stylesheet"
            href="css/checkout.css"
        >

    <?php elseif ($currentPage === 'cart.php'): ?>

        <link
            rel="stylesheet"
            href="css/cart.css"
        >

    <?php elseif ($currentPage === 'orders.php'): ?>

        <link
            rel="stylesheet"
            href="css/orders.css"
        >

    <?php elseif ($currentPage === 'account.php'): ?>

        <link
            rel="stylesheet"
            href="css/account.css"
        >

    <?php endif; ?>


</head>

<body>

<header class="site-header">

    <div class="container site-header__inner">

        <a
            href="index.php"
            class="site-logo"
            aria-label="KohiCebu Home"
        >

            <img
                src="assets/logo/kohicebu-logo.png"
                alt="KohiCebu"
            >

        </a>


        <!-- =================================================
             DESKTOP NAVIGATION
             ================================================= -->

        <nav class="site-nav">

            <a
                href="index.php"
                class="site-nav__link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
            >
                Home
            </a>


            <a
                href="<?= htmlspecialchars($menuLink, ENT_QUOTES, 'UTF-8') ?>"
                class="site-nav__link <?= $currentPage === 'menu.php' ? 'active' : '' ?>"
            >
                Menu
            </a>


            <a
                href="about.php"
                class="site-nav__link <?= $currentPage === 'about.php' ? 'active' : '' ?>"
            >
                About
            </a>


            <a
                href="gallery.php"
                class="site-nav__link <?= $currentPage === 'gallery.php' ? 'active' : '' ?>"
            >
                Gallery
            </a>


            <a
                href="visit.php"
                class="site-nav__link <?= $currentPage === 'visit.php' ? 'active' : '' ?>"
            >
                Visit
            </a>


            <?php if ($isLoggedIn): ?>

                <a
                    href="account.php"
                    class="site-nav__link <?= $currentPage === 'account.php' ? 'active' : '' ?>"
                >
                    My Account
                </a>

                <a
                    href="menu.php"
                    class="site-nav__order"
                >
                    Order Now
                </a>

            <?php else: ?>

                <a
                    href="<?= htmlspecialchars($menuLink, ENT_QUOTES, 'UTF-8') ?>"
                    class="site-nav__order"
                >
                    Order Now
                </a>

            <?php endif; ?>

                <a
            href="downloads/KohiCebu.apk"
            class="download-app-link"
            download
        >
            Download App
        </a>
        </nav>


        <!-- =================================================
             MOBILE MENU BUTTON
             ================================================= -->

        <button
            type="button"
            class="mobile-menu-button"
            id="mobileMenuButton"
            aria-label="Open menu"
            aria-expanded="false"
        >

            <span></span>
            <span></span>
            <span></span>

        </button>

    </div>


    <!-- =====================================================
         MOBILE NAVIGATION
         ===================================================== -->

    <nav
        class="mobile-nav"
        id="mobileNav"
    >

        <a
            href="index.php"
            class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"
        >
            Home
        </a>


        <a
            href="<?= htmlspecialchars($menuLink, ENT_QUOTES, 'UTF-8') ?>"
            class="<?= $currentPage === 'menu.php' ? 'active' : '' ?>"
        >
            Menu
        </a>


        <a
            href="about.php"
            class="<?= $currentPage === 'about.php' ? 'active' : '' ?>"
        >
            About
        </a>


        <a
            href="gallery.php"
            class="<?= $currentPage === 'gallery.php' ? 'active' : '' ?>"
        >
            Gallery
        </a>


        <a
            href="visit.php"
            class="<?= $currentPage === 'visit.php' ? 'active' : '' ?>"
        >
            Visit
        </a>


        <?php if ($isLoggedIn): ?>

            <a
                href="account.php"
                class="<?= $currentPage === 'account.php' ? 'active' : '' ?>"
            >
                My Account
            </a>

            <a
                href="menu.php"
                class="mobile-nav__order"
            >
                Order Now
            </a>

        <?php else: ?>

            <a
                href="<?= htmlspecialchars($menuLink, ENT_QUOTES, 'UTF-8') ?>"
                class="mobile-nav__order"
            >
                Order Now
            </a>

        <?php endif; ?>

            <a
            href="downloads/KohiCebu.apk"
            class="download-app-link"
            download
        >
            Download App
        </a>
        </nav>

</header>
