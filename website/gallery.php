<?php

$pageTitle = 'Gallery — KohiCebu.';

require_once 'includes/header.php';

?>

<!-- Gallery Page Styles -->
<link rel="stylesheet" href="css/gallery.css">

<main class="gallery-page">

    <!-- Gallery Hero -->
    <section class="gallery-hero">
        <div class="container gallery-hero__inner">

            <div class="gallery-hero__content">
                <p class="eyebrow">THE KOHI CEBU EXPERIENCE</p>

                <h1>
                    A space made<br>
                    to be enjoyed.
                </h1>

                <p class="gallery-hero__description">
                    Take a look at the drinks, spaces, and little moments
                    that make KohiCebu what it is.
                </p>

                <a href="#gallery-grid" class="text-link">
                    View Gallery →
                </a>
            </div>

        </div>
    </section>


    <!-- Featured Gallery -->
    <section class="gallery-featured section" id="gallery-grid">

        <div class="container">

            <div class="section-heading">
                <div>
                    <p class="eyebrow">KOHI CEBU</p>
                    <h2>Inside the café.</h2>
                </div>
            </div>


            <div class="gallery-grid">

                <!-- Featured — Interior -->
                <article class="gallery-card gallery-card--large">

                    <div class="gallery-card__image">
                        <img
                            src="assets/images/gallery/interior-seating.jpg"
                            alt="KohiCebu café interior and seating area"
                        >
                    </div>

                    <div class="gallery-card__content">
                        <span class="gallery-card__label">
                            KOHI CEBU
                        </span>

                        <h3>
                            Good coffee.<br>
                            Good moments.
                        </h3>
                    </div>

                </article>


                <!-- Coffee -->
                <article class="gallery-card">

                    <div class="gallery-card__image">
                        <img
                            src="assets/images/gallery/flat-white.jpg"
                            alt="KohiCebu Flat White coffee"
                        >
                    </div>

                    <div class="gallery-card__content">
                        <span class="gallery-card__label">
                            COFFEE
                        </span>

                        <h3>
                            Made for your everyday.
                        </h3>
                    </div>

                </article>


                <!-- Matcha -->
                <article class="gallery-card">

                    <div class="gallery-card__image">
                        <img
                            src="assets/images/gallery/matcha-cream.jpg"
                            alt="KohiCebu Matcha Cream"
                        >
                    </div>

                    <div class="gallery-card__content">
                        <span class="gallery-card__label">
                            MATCHA
                        </span>

                        <h3>
                            Something a little different.
                        </h3>
                    </div>

                </article>


                <!-- The Space -->
                <article class="gallery-card">

                    <div class="gallery-card__image">
                        <img
                            src="assets/images/gallery/storefront-window.jpg"
                            alt="KohiCebu storefront and café space"
                        >
                    </div>

                    <div class="gallery-card__content">
                        <span class="gallery-card__label">
                            THE SPACE
                        </span>

                        <h3>
                            Come in. Stay awhile.
                        </h3>
                    </div>

                </article>


                <!-- Good Moments -->
                <article class="gallery-card">

                    <div class="gallery-card__image">
                        <img
                            src="assets/images/gallery/counter-baristas.jpg"
                            alt="KohiCebu counter and café team"
                        >
                    </div>

                    <div class="gallery-card__content">
                        <span class="gallery-card__label">
                            GOOD MOMENTS
                        </span>

                        <h3>
                            Shared over coffee.
                        </h3>
                    </div>

                </article>

            </div>

        </div>

    </section>


    <!-- Full Gallery -->
    <section class="gallery-all section section--light">

        <div class="container">

            <div class="section-heading">
                <div>
                    <p class="eyebrow">MORE FROM KOHI CEBU</p>
                    <h2>Good moments, captured.</h2>
                </div>
            </div>


            <div class="gallery-all__grid">

                <!-- Storefront Sign -->
                <div class="gallery-all__item">
                    <div class="gallery-placeholder">
                        <img
                            src="assets/images/gallery/storefront-sign.jpg"
                            alt="KohiCebu coffee shop storefront sign"
                        >
                    </div>
                </div>


                <!-- Sign Close-up -->
                <div class="gallery-all__item">
                    <div class="gallery-placeholder">
                        <img
                            src="assets/images/gallery/sign-closeup.jpg"
                            alt="KohiCebu coffee shop sign"
                        >
                    </div>
                </div>


                <!-- Storefront -->
                <div class="gallery-all__item">
                    <div class="gallery-placeholder">
                        <img
                            src="assets/images/gallery/storefront-window.jpg"
                            alt="KohiCebu storefront window"
                        >
                    </div>
                </div>


                <!-- Café Counter -->
                <div class="gallery-all__item">
                    <div class="gallery-placeholder">
                        <img
                            src="assets/images/gallery/counter-baristas.jpg"
                            alt="KohiCebu café counter"
                        >
                    </div>
                </div>


                <!-- Interior -->
                <div class="gallery-all__item">
                    <div class="gallery-placeholder">
                        <img
                            src="assets/images/gallery/interior-seating.jpg"
                            alt="KohiCebu café seating area"
                        >
                    </div>
                </div>


                <!-- Coffee -->
                <div class="gallery-all__item">
                    <div class="gallery-placeholder">
                        <img
                            src="assets/images/gallery/flat-white.jpg"
                            alt="KohiCebu Flat White"
                        >
                    </div>
                </div>


                <!-- Matcha -->
                <div class="gallery-all__item">
                    <div class="gallery-placeholder">
                        <img
                            src="assets/images/gallery/matcha-cream.jpg"
                            alt="KohiCebu Matcha Cream"
                        >
                    </div>
                </div>

            </div>

        </div>

    </section>


    <!-- Gallery CTA -->
    <section class="gallery-cta section">

        <div class="container">

            <div class="gallery-cta__inner">

                <p class="eyebrow">COME VISIT</p>

                <h2>
                    See it for yourself.
                </h2>

                <p>
                    Drop by KohiCebu and enjoy your coffee,
                    matcha, cookies, and good moments with us.
                </p>

                <a href="visit.php" class="button button--dark">
                    Visit KohiCebu →
                </a>

            </div>

        </div>

    </section>

</main>

<?php require_once 'includes/footer.php'; ?>