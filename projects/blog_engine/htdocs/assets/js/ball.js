/**
 * ball.js — GSAP Custom Cursor
 * =============================
 * Tracks mouse movement and animates the .ball div using GSAP.
 * Expands on hover over interactive elements.
 *
 * Requires: GSAP (loaded in _master.php)
 * HTML:     <div class="ball" id="ball"></div>
 */

(function () {
    const ball = document.getElementById('ball');
    if (!ball) return;

    let mouseX = 0, mouseY = 0;
    let posX = 0, posY = 0;

    // Track raw mouse position
    document.addEventListener('mousemove', (e) => {
        mouseX = e.clientX;
        mouseY = e.clientY;
    });

    // GSAP smooth-follow animation loop
    gsap.ticker.add(() => {
        posX += (mouseX - posX) * 0.12;
        posY += (mouseY - posY) * 0.12;
        gsap.set(ball, { x: posX, y: posY });
    });

    // Expand ball::before ring on interactive elements
    const interactiveSelector = 'a, button, [data-cursor-expand], input, label, .nav-item';

    document.querySelectorAll(interactiveSelector).forEach((el) => {
        el.addEventListener('mouseenter', () => ball.classList.add('ball--hover'));
        el.addEventListener('mouseleave', () => ball.classList.remove('ball--hover'));
    });

    // Re-attach for dynamically added elements (MutationObserver)
    const observer = new MutationObserver(() => {
        document.querySelectorAll(interactiveSelector).forEach((el) => {
            if (!el._ballListenerAttached) {
                el.addEventListener('mouseenter', () => ball.classList.add('ball--hover'));
                el.addEventListener('mouseleave', () => ball.classList.remove('ball--hover'));
                el._ballListenerAttached = true;
            }
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Hide ball when cursor leaves the window
    document.addEventListener('mouseleave', () => { ball.style.opacity = '0'; });
    document.addEventListener('mouseenter', () => { ball.style.opacity = '1'; });
})();
