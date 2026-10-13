<?php

$pageTitle = 'About KohiCebu. | Coffee Shop in Talisay, Cebu';

require_once __DIR__ . '/includes/header.php';

?>

<main class="about-page">

    <!-- =========================================
         ABOUT HERO
         ========================================= -->

    <section class="about-hero">

        <div class="container">

            <div class="about-hero__content">

                <p class="eyebrow">
                    ABOUT kohicebu
                </p>

                <h1>
                    Let kohi<br>
                    connect us.
                </h1>

                <p class="about-hero__intro">
                    More than coffee.
                    A place to stay, slow down,
                    and enjoy the moment.
                </p>

            </div>

        </div>

    </section>


    <!-- =========================================
         INTRODUCTION
         ========================================= -->

    <section class="about-intro section">

        <div class="container">

            <div class="about-intro__grid">

                <div class="about-intro__visual">

                    <div class="about-image about-image--primary">

                        <img
                            src="assets/images/hero.jpg"
                            alt="KohiCebu coffee shop"
                        >

                    </div>

                    <div class="about-image__caption">

                        <span>
                            KOHI CEBU
                        </span>

                        <span>
                            TALISAY, CEBU
                        </span>

                    </div>

                </div>


                <div class="about-intro__content">

                    <p class="eyebrow">
                        OUR STORY
                    </p>

                    <h2>
                        More than coffee.
                        A place to stay.
                    </h2>

                    <p>
                        KohiCebu is a coffee shop in
                        Talisay, Cebu built around good
                        coffee, thoughtful drinks, and a
                        space where people can slow down
                        and enjoy the moment.
                    </p>

                    <p>
                        From coffee and matcha to cookies
                        and pastries, Kohi is made to be
                        enjoyed at your own pace.
                    </p>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================
         THE KOHI EXPERIENCE
         ========================================= -->

    <section class="about-experience section">

        <div class="container">

            <div class="section-heading">

                <div>

                    <p class="eyebrow">
                        THE KOHI EXPERIENCE
                    </p>

                    <h2>
                        Something for
                        every kind of moment.
                    </h2>

                </div>

            </div>


            <div class="experience-grid">

                <article class="experience-card">

                    <span class="experience-card__number">
                        01
                    </span>

                    <div>

                        <h3>
                            Coffee
                        </h3>

                        <p>
                            Thoughtful coffee and drinks
                            made for everyday moments.
                        </p>

                    </div>

                </article>


                <article class="experience-card">

                    <span class="experience-card__number">
                        02
                    </span>

                    <div>

                        <h3>
                            Matcha
                        </h3>

                        <p>
                            Matcha and other favorites
                            for something a little different.
                        </p>

                    </div>

                </article>


                <article class="experience-card">

                    <span class="experience-card__number">
                        03
                    </span>

                    <div>

                        <h3>
                            Cookies & Treats
                        </h3>

                        <p>
                            Sweet treats made to pair
                            with your cup.
                        </p>

                    </div>

                </article>


                <article class="experience-card">

                    <span class="experience-card__number">
                        04
                    </span>

                    <div>

                        <h3>
                            The Space
                        </h3>

                        <p>
                            A comfortable place to slow down,
                            stay awhile, and enjoy good moments.
                        </p>

                    </div>

                </article>

            </div>

        </div>

    </section>


    <!-- =========================================
         A PLACE TO CONNECT
         ========================================= -->

    <section class="about-connect section">

        <div class="container">

            <div class="about-connect__grid">

                <div class="about-connect__visual">

                    <div class="about-image about-image--secondary">

                        <img
                            src="assets/images/gallery/interior-seating.jpg"
                            alt="KohiCebu café interior and seating area"
                        >

                    </div>

                </div>


                <div class="about-connect__content">

                    <p class="eyebrow">
                        A PLACE TO CONNECT
                    </p>

                    <h2>
                        Come for the cup.
                        Stay for the moment.
                    </h2>

                    <p>
                        Whether you're catching up with
                        someone, taking a quiet break, or
                        simply looking for a place to enjoy
                        your drink, KohiCebu is made for
                        moments worth staying for.
                    </p>

                    <a
                        href="gallery.php"
                        class="text-link"
                    >
                        Explore the Space →
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================
         VISIT KOHI
         ========================================= -->

    <section class="about-visit section">

        <div class="container">

            <div class="about-visit__inner">

                <p class="eyebrow">
                    VISIT KOHI
                </p>

                <h2>
                    Come by.
                    Stay awhile.
                </h2>

                <p class="about-visit__description">
                    Find KohiCebu along San Isidro Road
                    in Dumlog, Talisay, Cebu.
                </p>


                <div class="about-visit__details">

                    <div class="about-visit__detail">

                        <span>
                            ADDRESS
                        </span>

                        <strong>
                            San Isidro Road,<br>
                            Dumlog, Talisay,<br>
                            Cebu 6045
                        </strong>

                    </div>


                    <div class="about-visit__actions">

                        <a
                            href="menu.php"
                            class="button button--primary"
                        >
                            View Menu
                        </a>

                        <a
                            href="visit.php"
                            class="button button--light"
                        >
                            Visit Us
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