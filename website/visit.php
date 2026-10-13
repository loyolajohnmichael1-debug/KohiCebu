<?php

$pageTitle = 'Visit KohiCebu. | Coffee Shop in Talisay, Cebu';

require_once __DIR__ . '/includes/header.php';

?>

<main class="visit-page">


    <!-- =========================================
         VISIT HERO
         ========================================= -->

    <section class="visit-hero">

        <div class="container">

            <div class="visit-hero__content">

                <p class="eyebrow">
                    VISIT KOHI
                </p>

                <h1>
                    Come by.<br>
                    Stay awhile.
                </h1>

                <p class="visit-hero__intro">
                    Find KohiCebu in Dumlog, Talisay, Cebu.
                    Come by for coffee, matcha, cookies,
                    and good moments.
                </p>

            </div>

        </div>

    </section>


    <!-- =========================================
         LOCATION
         ========================================= -->

    <section class="visit-location">

        <div class="container">

            <div class="visit-location__grid">


                <!-- LOCATION CONTENT -->

                <div class="visit-location__content">

                    <p class="eyebrow">
                        FIND US
                    </p>

                    <h2>
                        Your next cup
                        is here.
                    </h2>

                    <p class="visit-location__description">
                        KohiCebu is located along San Isidro
                        Road in Dumlog, Talisay, Cebu.
                        Drop by, grab your favorite drink,
                        and make yourself comfortable.
                    </p>


                    <div class="visit-details">

                        <div class="visit-detail">

                            <span class="visit-detail__label">
                                ADDRESS
                            </span>

                            <strong>
                                San Isidro Road,<br>
                                Dumlog, Talisay,<br>
                                Cebu 6045
                            </strong>

                        </div>


                        <div class="visit-detail">

                            <span class="visit-detail__label">
                                LOCATION
                            </span>

                            <span class="visit-detail__value">
                                Talisay City, Cebu
                            </span>

                        </div>

                    </div>


                    <a
                        href="https://www.google.com/maps/place/Kohicebu+Coffee+Shop/@10.251683,123.8366979,17z"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="button button--primary"
                    >
                        Get Directions
                    </a>

                </div>


                <!-- GOOGLE MAP -->

                <div class="visit-location__map">

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
         THE SPACE
         ========================================= -->

    <section class="visit-space">

        <div class="container">

            <div class="visit-space__grid">


                <!-- SPACE IMAGE -->

                <div class="visit-space__visual">

                    <div class="visit-space__image">

                        <img
                            src="assets/images/gallery/interior-seating.jpg"
                            alt="KohiCebu café interior and seating area"
                        >

                    </div>

                    <div class="visit-space__caption">

                        <span>
                            INSIDE KOHI
                        </span>

                        <span>
                            DUMLOG, TALISAY
                        </span>

                    </div>

                </div>


                <!-- SPACE CONTENT -->

                <div class="visit-space__content">

                    <p class="eyebrow">
                        THE SPACE
                    </p>

                    <h2>
                        A place to
                        slow down.
                    </h2>

                    <p>
                        Whether you're meeting someone,
                        taking a break, or simply enjoying
                        your own time, KohiCebu is a place
                        to stay awhile.
                    </p>

                    <a
                        href="gallery.php"
                        class="text-link"
                    >
                        Explore the Gallery →
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- =========================================
         GOOD TO KNOW
         ========================================= -->

    <section class="visit-info">

        <div class="container">

            <div class="visit-info__heading">

                <p class="eyebrow">
                    GOOD TO KNOW
                </p>

                <h2>
                    Everything starts
                    with showing up.
                </h2>

            </div>


            <div class="visit-info__grid">


                <!-- CARD 01 -->

                <article class="visit-info__card">

                    <div>

                        <span class="visit-info__number">
                            01
                        </span>

                    </div>

                    <div>

                        <h3>
                            Come as you are.
                        </h3>

                        <p>
                            Grab a drink, take a seat,
                            and enjoy your time at Kohi.
                        </p>

                    </div>

                </article>


                <!-- CARD 02 -->

                <article class="visit-info__card">

                    <div>

                        <span class="visit-info__number">
                            02
                        </span>

                    </div>

                    <div>

                        <h3>
                            Bring someone along.
                        </h3>

                        <p>
                            Good coffee is even better
                            when shared with good company.
                        </p>

                    </div>

                </article>


                <!-- CARD 03 -->

                <article class="visit-info__card">

                    <div>

                        <span class="visit-info__number">
                            03
                        </span>

                    </div>

                    <div>

                        <h3>
                            Stay awhile.
                        </h3>

                        <p>
                            Slow down, enjoy your drink,
                            and make the moment yours.
                        </p>

                    </div>

                </article>

            </div>

        </div>

    </section>


    <!-- =========================================
         FINAL CTA
         ========================================= -->

    <section class="visit-cta">

        <div class="visit-cta__content">

            <p class="eyebrow">
                SEE YOU AT KOHI
            </p>

            <h2>
                Your next cup
                is waiting.
            </h2>

            <div class="visit-cta__actions">

                <a
                    href="menu.php"
                    class="button button--light"
                >
                    View Menu
                </a>

                <a
                    href="gallery.php"
                    class="button button--outline-light"
                >
                    View Gallery
                </a>

            </div>

        </div>

    </section>

</main>


<?php

require_once __DIR__ . '/includes/footer.php';

?>