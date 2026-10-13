<footer class="site-footer">

    <div class="container">

        <div class="site-footer__main">

            <div class="site-footer__brand">

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

                <p>
                    Coffee, matcha, cookies<br>
                    and good moments.
                </p>

            </div>


            <div class="site-footer__links">

                <div>

                    <span>
                        Explore
                    </span>

                    <a href="menu.php">
                        Menu
                    </a>

                    <a href="about.php">
                        About
                    </a>

                    <a href="gallery.php">
                        Gallery
                    </a>

                </div>


                <div>

                    <span>
                        Visit
                    </span>

                    <a href="visit.php">
                        Location
                    </a>

                    <a href="menu.php">
                        Order
                    </a>

                </div>

            </div>

        </div>


        <div class="site-footer__bottom">

            <span>
                © <?= date('Y') ?> KohiCebu.
            </span>

            <span>
                Talisay, Cebu
            </span>

        </div>

    </div>

</footer>


<?php if (in_array($currentPage ?? '', ['login.php', 'register.php'], true)): ?>
<script src="https://accounts.google.com/gsi/client" async defer></script>
<script src="js/google-auth.js" defer></script>
<?php endif; ?>
<script src="js/global.js"></script>
<script src="js/home.js"></script>

</body>
</html>
