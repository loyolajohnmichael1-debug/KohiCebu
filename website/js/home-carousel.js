(() => {
    const hero = document.querySelector(".hero");
    if (!hero) return;

    const slides = Array.from(hero.querySelectorAll(".hero__slide"));
    const dots = Array.from(hero.querySelectorAll("[data-carousel-slide]"));
    const previous = hero.querySelector("[data-carousel-prev]");
    const next = hero.querySelector("[data-carousel-next]");
    if (slides.length < 2 || dots.length !== slides.length) return;

    let activeIndex = 0;
    let timer;

    function showSlide(index) {
        activeIndex = (index + slides.length) % slides.length;
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === activeIndex;
            slide.classList.toggle("is-active", active);
            slide.setAttribute("aria-hidden", String(!active));
        });
        dots.forEach((dot, dotIndex) => {
            const active = dotIndex === activeIndex;
            dot.classList.toggle("is-active", active);
            if (active) dot.setAttribute("aria-current", "true");
            else dot.removeAttribute("aria-current");
        });
    }

    function stopTimer() { window.clearInterval(timer); }
    function startTimer() {
        stopTimer();
        if (!window.matchMedia("(prefers-reduced-motion: reduce)").matches) {
            timer = window.setInterval(() => showSlide(activeIndex + 1), 6000);
        }
    }

    previous.addEventListener("click", () => { showSlide(activeIndex - 1); startTimer(); });
    next.addEventListener("click", () => { showSlide(activeIndex + 1); startTimer(); });
    dots.forEach((dot) => dot.addEventListener("click", () => {
        showSlide(Number(dot.dataset.carouselSlide));
        startTimer();
    }));

    hero.addEventListener("mouseenter", stopTimer);
    hero.addEventListener("mouseleave", startTimer);
    hero.addEventListener("focusin", stopTimer);
    hero.addEventListener("focusout", (event) => {
        if (!hero.contains(event.relatedTarget)) startTimer();
    });

    showSlide(0);
    startTimer();
})();
