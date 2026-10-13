<?php

$pageTitle = 'Menu — KohiCebu.';

require_once __DIR__ . '/includes/header.php';

?>

<!-- Menu Page Styles -->
<link rel="stylesheet" href="css/menu.css">

<main class="menu-page">

    <!-- =========================================
         MENU HERO
         ========================================= -->

    <section class="menu-hero">

        <div class="container">

            <div class="menu-hero__content">

                <p class="eyebrow">
                    THE KOHI MENU
                </p>

                <h1>
                    Good drinks.<br>
                    Good choices.
                </h1>

                <p>
                    Explore coffee, matcha, cookies, and
                    other favorites from KohiCebu.
                </p>

            </div>

        </div>

    </section>


    <!-- =========================================
         MENU
         ========================================= -->

    <section class="menu-section section">

        <div class="container">

            <!-- Category Navigation -->

            <div class="menu-toolbar">

                <div
                    class="menu-categories"
                    id="menuCategories"
                    role="tablist"
                    aria-label="Menu categories"
                >

                    <button
                        type="button"
                        class="menu-category active"
                        data-category="ALL"
                    >
                        All
                    </button>

                </div>

            </div>


            <!-- Loading -->

            <div
                class="menu-loading"
                id="menuLoading"
            >

                <div class="menu-loading__item"></div>
                <div class="menu-loading__item"></div>
                <div class="menu-loading__item"></div>
                <div class="menu-loading__item"></div>

            </div>


            <!-- Error -->

            <div
                class="menu-message"
                id="menuError"
                hidden
            >

                <p>
                    We couldn't load the menu right now.
                </p>

                <button
                    type="button"
                    class="text-link"
                    id="retryMenu"
                >
                    Try Again →
                </button>

            </div>


            <!-- Empty -->

            <div
                class="menu-message"
                id="menuEmpty"
                hidden
            >

                <p>
                    There are no menu items available right now.
                </p>

            </div>


            <!-- Products -->

            <div
                class="menu-grid"
                id="menuProducts"
            ></div>

        </div>

    </section>


    <!-- =========================================
         MENU CTA
         ========================================= -->

    <section class="menu-cta section">

        <div class="container">

            <div class="menu-cta__inner">

                <p class="eyebrow">
                    MADE FOR GOOD MOMENTS
                </p>

                <h2>
                    Find your<br>
                    next favorite.
                </h2>

                <p>
                    Browse the menu and order your favorites
                    from KohiCebu.
                </p>

            </div>

        </div>

    </section>

</main>


<!-- Menu Page Script -->
<script src="js/menu.js"></script>

<?php

require_once __DIR__ . '/includes/footer.php';

?>