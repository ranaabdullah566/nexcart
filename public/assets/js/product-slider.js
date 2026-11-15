/* Home product slider: mouse drag with momentum, snapping to cards, depth effect, progress scrubber, smooth autoplay, keyboard.
   Touch uses the browser's own scroll (with snap), so it stays as smooth as the phone allows. */
(function () {
    'use strict';
    var reduce = window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches;

    function init(root) {
        var vp = root.querySelector('[data-ps-viewport]'), tr = root.querySelector('[data-ps-track]');
        if (!vp || !tr) return;
        var items = Array.prototype.slice.call(tr.children), prev = root.querySelector('[data-ps-prev]'), next = root.querySelector('[data-ps-next]');
        var bar = root.querySelector('[data-ps-bar]'), thumb = root.querySelector('[data-ps-thumb]'), count = root.querySelector('[data-ps-count]'), play = root.querySelector('[data-ps-play]');
        var rtl = getComputedStyle(tr).direction === 'rtl', every = parseInt(root.getAttribute('data-autoplay') || '0', 10);
        var paused = false, userPaused = false, timer = null, raf = 0;

        var max = function () { return Math.max(0, tr.scrollWidth - tr.clientWidth); };
        var pos = function () { return Math.abs(tr.scrollLeft); };
        var step = function () { var a = items[0], b = items[1]; return a ? (b ? Math.abs(b.getBoundingClientRect().left - a.getBoundingClientRect().left) : a.getBoundingClientRect().width) : 240; };
        var goTo = function (x) { tr.scrollTo({ left: (rtl ? -1 : 1) * Math.max(0, Math.min(max(), x)), behavior: reduce ? 'auto' : 'smooth' }); };
        var by = function (dir) { goTo(pos() + dir * step()); };

        /* ---- state painted on every scroll frame ---- */
        function paint() {
            raf = 0;
            var m = max(), p = pos(), vr = tr.getBoundingClientRect();
            root.classList.toggle('no-scroll', m <= 4);
            vp.classList.toggle('can-prev', p > 6); vp.classList.toggle('can-next', p < m - 6);
            if (prev) prev.disabled = p <= 2; if (next) next.disabled = p >= m - 2;
            // depth: how much of each card is inside the window (0..1)
            items.forEach(function (it) {
                var r = it.getBoundingClientRect(), w = r.width || 1, vis = Math.max(0, Math.min(r.right, vr.right) - Math.max(r.left, vr.left)) / w;
                it.style.setProperty('--v', (Math.round(Math.min(1, vis) * 100) / 100).toString());
            });
            // progress thumb: as wide as the visible part, travelling along the bar
            if (thumb && bar) {
                var ratio = Math.min(1, tr.clientWidth / (tr.scrollWidth || 1)), bw = bar.clientWidth, tw = Math.max(40, bw * ratio);
                thumb.style.width = tw + 'px';
                thumb.style.transform = 'translateX(' + (m ? (p / m) * (bw - tw) * (rtl ? -1 : 1) : 0) + 'px)';
            }
            if (count) {
                var last = items.length, tl = vr.left - 4, tr2 = vr.right + 4;
                var visible = items.filter(function (it) { var r = it.getBoundingClientRect(); return r.left >= tl && r.right <= tr2; });
                var a = visible.length ? items.indexOf(visible[0]) + 1 : 1, b = visible.length ? items.indexOf(visible[visible.length - 1]) + 1 : 1;
                count.textContent = a === b ? a + ' / ' + last : a + '–' + b + ' / ' + last;
            }
        }
        var schedule = function () { if (!raf) raf = requestAnimationFrame(paint); };
        tr.addEventListener('scroll', schedule, { passive: true });
        window.addEventListener('resize', schedule);
        paint(); setTimeout(paint, 350);

        if (prev) prev.addEventListener('click', function () { by(-1); });
        if (next) next.addEventListener('click', function () { by(1); });
        tr.addEventListener('keydown', function (e) {
            if (e.key === 'ArrowRight') { e.preventDefault(); by(rtl ? -1 : 1); }
            else if (e.key === 'ArrowLeft') { e.preventDefault(); by(rtl ? 1 : -1); }
            else if (e.key === 'Home') { e.preventDefault(); goTo(0); }
            else if (e.key === 'End') { e.preventDefault(); goTo(max()); }
        });

        /* ---- scrubber: click or drag the bar ---- */
        if (bar) {
            var scrub = function (e) { var r = bar.getBoundingClientRect(), f = (e.clientX - r.left) / (r.width || 1); f = rtl ? 1 - f : f; tr.style.scrollBehavior = 'auto'; tr.scrollLeft = (rtl ? -1 : 1) * Math.max(0, Math.min(1, f)) * max(); };
            var scrubbing = false;
            bar.addEventListener('pointerdown', function (e) { scrubbing = true; paused = true; bar.setPointerCapture(e.pointerId); scrub(e); });
            bar.addEventListener('pointermove', function (e) { if (scrubbing) scrub(e); });
            bar.addEventListener('pointerup', function () { scrubbing = false; paused = false; tr.style.scrollBehavior = ''; snap(); });
        }

        /* ---- mouse drag with momentum (touch keeps native scrolling) ---- */
        var drag = null;
        function snap() {
            var s = step(), i = Math.round(pos() / s);
            goTo(i * s);
        }
        tr.addEventListener('pointerdown', function (e) {
            if (e.pointerType !== 'mouse' || e.button !== 0) return;
            drag = { x: e.clientX, left: tr.scrollLeft, t: performance.now(), lastX: e.clientX, lastT: performance.now(), v: 0, moved: false };
        });
        window.addEventListener('pointermove', function (e) {
            if (!drag) return;
            var dx = e.clientX - drag.x;
            if (!drag.moved && Math.abs(dx) > 5) { drag.moved = true; tr.classList.add('is-drag'); paused = true; }
            if (!drag.moved) return;
            tr.scrollLeft = drag.left - dx;
            var now = performance.now(), dt = now - drag.lastT;
            if (dt > 0) drag.v = 0.8 * drag.v + 0.2 * ((drag.lastX - e.clientX) / dt);   // px per ms, smoothed
            drag.lastX = e.clientX; drag.lastT = now;
        });
        window.addEventListener('pointerup', function () {
            if (!drag) return;
            var d = drag; drag = null;
            if (!d.moved) return;
            tr.classList.remove('is-drag'); paused = false;
            var target = tr.scrollLeft + d.v * 260;   // throw it a little further, then land on a card
            var s = step(); goTo(Math.round(Math.abs(target) / s) * s);
            window.__psDragged = performance.now();
        });
        tr.addEventListener('click', function (e) { if (window.__psDragged && performance.now() - window.__psDragged < 250) { e.preventDefault(); e.stopPropagation(); } }, true);
        tr.addEventListener('dragstart', function (e) { e.preventDefault(); });

        /* ---- entrance: cards rise one after another the first time the slider is seen ---- */
        items.forEach(function (it, i) { it.style.setProperty('--i', Math.min(i, 8)); });
        if ('IntersectionObserver' in window && !reduce) {
            var io = new IntersectionObserver(function (es) { es.forEach(function (en) { if (en.isIntersecting) { root.classList.add('is-in'); io.disconnect(); } }); }, { threshold: 0.12 });
            io.observe(root);
        } else { root.classList.add('is-in'); }

        /* ---- autoplay: one card at a time, loops with a smooth rewind, stops when anyone touches it ---- */
        function tick() {
            if (paused || userPaused || document.hidden || drag) return;
            var m = max(); if (m <= 4) return;
            if (pos() >= m - 2) goTo(0); else by(1);
        }
        if (every > 0 && !reduce) {
            ['mouseenter', 'touchstart'].forEach(function (ev) { root.addEventListener(ev, function () { paused = true; }, { passive: true }); });
            ['mouseleave', 'touchend'].forEach(function (ev) { root.addEventListener(ev, function () { paused = false; }, { passive: true }); });
            // keyboard users: stay put while focus is inside (a mouse click on an arrow must not freeze it for good)
            root.addEventListener('focusin', function (e) { if (e.target.matches && e.target.matches(':focus-visible')) paused = true; });
            root.addEventListener('focusout', function () { paused = false; });
            timer = setInterval(tick, every);
            if (play) play.addEventListener('click', function () {
                userPaused = !userPaused; play.setAttribute('aria-pressed', userPaused ? 'false' : 'true'); play.setAttribute('aria-label', userPaused ? 'Play' : 'Pause');
                play.firstElementChild.className = 'bi bi-' + (userPaused ? 'play-fill' : 'pause-fill');
            });
        } else { root.classList.add('no-auto'); }
    }

    function boot() { Array.prototype.forEach.call(document.querySelectorAll('[data-pslider]'), init); }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot); else boot();
})();
