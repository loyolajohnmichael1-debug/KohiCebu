<?php

$pageTitle = 'KohiCebu. | Coffee Shop in Talisay, Cebu';

require_once __DIR__ . '/includes/header.php';

?>

<main class="home">


    <!-- =========================================
         HERO
         ========================================= -->

    <section class="hero" aria-labelledby="homeHeroTitle">

        <div class="hero__image" aria-label="Featured KohiCebu drinks and treats">
            <div class="hero__slides">
                <img class="hero__slide is-active" src="assets/images/hero-matcha.jpg" alt="KohiCebu matcha drinks and pastries" fetchpriority="high" decoding="async">
                <img class="hero__slide" src="assets/images/hero-coffee-matcha.jpg" alt="Iced coffee and matcha served at KohiCebu" loading="lazy" decoding="async">
                <img class="hero__slide" src="assets/images/hero-crookie.jpg" alt="Freshly baked crookies with chocolate chip cookie dough" loading="lazy" decoding="async">
                <img class="hero__slide" src="assets/images/hero-latte.jpg" alt="KohiCebu iced latte in a cozy cafe setting" loading="lazy" decoding="async">
                <img class="hero__slide" src="assets/images/hero-test-drinks.png" alt="Three KohiCebu iced coffee, chocolate, and matcha drinks" loading="lazy" decoding="async">
            </div>
        </div>


        <div class="hero__overlay"></div>


        <div class="hero__content">

            <p class="eyebrow">
                KOHI CEBU · TALISAY
            </p>

            <h1 id="homeHeroTitle">
                Let kohi connect us.
            </h1>

            <p class="hero__description">
                Coffee, matcha, cookies, and moments
                worth staying for.
            </p>


            <div class="hero__actions">

                <a
                    href="menu.php"
                    class="button button--primary"
                >
                    Explore Menu
                </a>


                <a
                    href="visit.php"
                    class="button button--light"
                >
                    Visit Us
                </a>

                <a
                    href="downloads/KohiCebu.apk"
                    class="button button--light hero__app-link"
                    download
                >
                    Get the KohiCebu app
                </a>

            </div>

        </div>


        <div class="hero__scroll">
            Scroll to explore
        </div>

        <div class="hero__carousel-controls" aria-label="Hero image carousel controls">
            <button class="hero__carousel-arrow" type="button" data-carousel-prev aria-label="Previous image">‹</button>
            <div class="hero__carousel-dots" role="group" aria-label="Choose a hero image">
                <button type="button" class="is-active" data-carousel-slide="0" aria-label="Show image 1" aria-current="true"></button>
                <button type="button" data-carousel-slide="1" aria-label="Show image 2"></button>
                <button type="button" data-carousel-slide="2" aria-label="Show image 3"></button>
                <button type="button" data-carousel-slide="3" aria-label="Show image 4"></button>
                <button type="button" data-carousel-slide="4" aria-label="Show image 5"></button>
            </div>
            <button class="hero__carousel-arrow" type="button" data-carousel-next aria-label="Next image">›</button>
        </div>

    </section>



    <!-- =========================================
         FEATURED MENU
         ========================================= -->

    <section
        class="featured-menu section"
        id="featuredMenu"
    >

        <div class="container">


            <div class="section-heading">

                <div>

                    <p class="eyebrow">
                        FROM THE BAR
                    </p>

                    <h2>
                        Featured at kohicebu.
                    </h2>

                </div>


                <a
                    href="menu.php"
                    class="text-link"
                >
                    View Menu →
                </a>

            </div>



            <div
                class="product-grid"
                id="featuredProducts"
                aria-busy="true"
            >

                <!-- Loading state -->

                <div class="product-loading">
                    <span>
                        Loading Kohi favorites...
                    </span>
                </div>

                <div class="product-loading">
                    <span></span>
                </div>

                <div class="product-loading">
                    <span></span>
                </div>

            </div>



            <!-- API error -->

            <div
                class="product-error"
                id="featuredProductsError"
                hidden
            >

                <p>
                    We couldn't load the menu right now.
                </p>

                <button
                    type="button"
                    id="retryProducts"
                    class="text-link"
                >
                    Try Again
                </button>

            </div>

        </div>

    </section>



    <!-- =========================================
         PROMOTIONS & WEBSITE UPDATES
         ========================================= -->

    <section
        class="promos section section--dark"
        id="promos"
    >

        <div class="container">


            <div
                class="section-heading section-heading--light"
            >

                <div>

                    <p class="eyebrow">
                        WHAT'S NEW
                    </p>

                    <h2>
                        Promos & Updates
                    </h2>

                </div>

            </div>



            <!-- =====================================
                 WEBSITE UPDATES
                 Loaded from:
                 api/get_website_updates.php
                 ===================================== -->

            <div
                class="promo-grid"
                id="websiteUpdates"
                aria-busy="true"
            >

                <!-- Loading state -->

                <article class="promo-card promo-card--loading">

                    <div class="promo-card__image">

                        <span>
                            Loading...
                        </span>

                    </div>


                    <div class="promo-card__content">

                        <span class="promo-card__label">
                            PLEASE WAIT
                        </span>

                        <h3>
                            Loading updates
                        </h3>

                        <p>
                            We're checking what's new
                            at KohiCebu.
                        </p>

                    </div>

                </article>


                <article class="promo-card promo-card--loading">

                    <div class="promo-card__image">

                        <span>
                            Loading...
                        </span>

                    </div>


                    <div class="promo-card__content">

                        <span class="promo-card__label">
                            PLEASE WAIT
                        </span>

                        <h3>
                            Loading updates
                        </h3>

                        <p>
                            We're checking what's new
                            at KohiCebu.
                        </p>

                    </div>

                </article>

            </div>



            <!-- =====================================
                 API ERROR
                 ===================================== -->

            <div
                class="promo-message"
                id="websiteUpdatesError"
                hidden
            >

                <p>
                    We couldn't load the latest Kohi
                    updates right now.
                </p>

                <button
                    type="button"
                    class="text-link text-link--light"
                    id="retryWebsiteUpdates"
                >
                    Try Again →
                </button>

            </div>



            <!-- =====================================
                 EMPTY STATE
                 ===================================== -->

            <div
                class="promo-message"
                id="websiteUpdatesEmpty"
                hidden
            >

                <p>
                    There are no current promotions
                    or updates right now.
                </p>

            </div>

        </div>

    </section>



    <!-- =========================================
         OUR STORY
         ========================================= -->

    <section class="story section">

        <div class="container">

            <div class="story__grid">


                <div class="story__visual">

                    <div class="story__image">
                        <img
                            src="assets/images/gallery/interior-seating.jpg"
                            alt="Warm, welcoming seating inside the KohiCebu cafe"
                            loading="lazy"
                            decoding="async"
                        >
                    </div>


                    <div class="story__caption">

                        <span>
                            Talisay, Cebu
                        </span>

                        <span>
                            Since 2026
                        </span>

                    </div>

                </div>



                <div class="story__content">

                    <p class="eyebrow">
                        OUR STORY
                    </p>

                    <h2>
                        More than coffee.
                        A place to stay.
                    </h2>

                    <p>
                        KohiCebu is a coffee shop in Talisay,
                        Cebu built around good coffee,
                        thoughtful drinks, and a space where
                        people can slow down and enjoy
                        the moment.
                    </p>

                    <p>
                        From coffee and matcha to cookies
                        and pastries, every part of Kohi
                        is made to be enjoyed at your own pace.
                    </p>

                    <a
                        href="about.php"
                        class="text-link"
                    >
                        Discover Kohi →
                    </a>

                </div>

            </div>

        </div>

    </section>



    <!-- =========================================
     GALLERY
     ========================================= -->

<section
    class="gallery section"
    id="gallery"
>

    <div class="container">

        <div class="section-heading">

            <div>

                <p class="eyebrow">
                    INSIDE KOHI
                </p>

                <h2>
                    A space made
                    to be enjoyed.
                </h2>

            </div>

            <a
                href="gallery.php"
                class="text-link"
            >
                View Gallery →
            </a>

        </div>


        <div class="gallery-grid">

            <!-- Featured Interior -->
            <a
                href="gallery.php"
                class="gallery-item gallery-item--large"
                aria-label="View KohiCebu interior gallery"
            >

                <img
                    src="assets/images/gallery/interior-seating.jpg"
                    alt="KohiCebu café interior and seating area"
                >

                <span class="gallery-item__overlay">
                    KOHI CEBU
                </span>

            </a>


            <!-- Coffee -->
            <a
                href="gallery.php"
                class="gallery-item"
                aria-label="View KohiCebu coffee gallery"
            >

                <img
                    src="assets/images/gallery/flat-white.jpg"
                    alt="KohiCebu Flat White coffee"
                >

                <span class="gallery-item__overlay">
                    COFFEE
                </span>

            </a>


            <!-- Matcha -->
            <a
                href="gallery.php"
                class="gallery-item"
                aria-label="View KohiCebu matcha gallery"
            >

                <img
                    src="assets/images/gallery/matcha-cream.jpg"
                    alt="KohiCebu Matcha Cream"
                >

                <span class="gallery-item__overlay">
                    MATCHA
                </span>

            </a>


            <!-- The Space -->
            <a
                href="gallery.php"
                class="gallery-item"
                aria-label="View KohiCebu space gallery"
            >

                <img
                    src="assets/images/gallery/storefront-window.jpg"
                    alt="KohiCebu storefront and café space"
                >

                <span class="gallery-item__overlay">
                    THE SPACE
                </span>

            </a>


            <!-- Good Moments -->
            <a
                href="gallery.php"
                class="gallery-item"
                aria-label="View KohiCebu moments gallery"
            >

                <img
                    src="assets/images/gallery/counter-baristas.jpg"
                    alt="KohiCebu café counter and team"
                >

                <span class="gallery-item__overlay">
                    GOOD MOMENTS
                </span>

            </a>

        </div>

    </div>

</section>



    <!-- =========================================
         VISIT KOHI
         ========================================= -->

    <section
        class="visit section"
        id="visit"
    >

        <div class="container">

            <div class="visit__grid">


                <div class="visit__content">

                    <p class="eyebrow">
                        VISIT KOHI
                    </p>

                    <h2>
                        Come by.
                        Stay awhile.
                    </h2>

                    <p>
                        Find KohiCebu along San Isidro Road
                        in Dumlog, Talisay, Cebu.
                        Come by for coffee, matcha, cookies,
                        and a good moment.
                    </p>



                    <div class="visit__details">


                        <div class="visit__detail">

                            <span class="visit__detail-label">
                                ADDRESS
                            </span>

                            <span class="visit__detail-value">
                                San Isidro Road,<br>
                                Dumlog, Talisay,<br>
                                Cebu 6045
                            </span>

                        </div>



                        <div class="visit__detail">

                            <span class="visit__detail-label">
                                FIND US
                            </span>

                            <a
                                href="https://www.google.com/maps/place/Kohicebu+Coffee+Shop/@10.251683,123.8366979,17z"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-link"
                            >
                                Open in Google Maps →
                            </a>

                        </div>

                    </div>

                </div>



                <div class="visit__map">

                    <iframe
                        src="https://www.google.com/maps?q=Kohicebu+Coffee+Shop,+San+Isidro+Road,+Dumlog,+Talisay,+Cebu&output=embed"
                        width="100%"
                        height="100%"
                        style="border:0;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Kohicebu Coffee Shop location"
                    ></iframe>

                </div>

            </div>

        </div>

    </section>



    <!-- =========================================
         ORDER CTA
         ========================================= -->

    <section class="order-cta">

        <div class="order-cta__content">

            <p class="eyebrow">
                READY FOR KOHI?
            </p>

            <h2>
                Your next cup<br>
                is waiting.
            </h2>

            <a
                href="menu.php"
                class="button button--light"
            >
                Order Now
            </a>

        </div>

    </section>

</main>

<script src="js/home-carousel.js?v=3" defer></script>



<?php

require_once __DIR__ . '/includes/footer.php';

?>
