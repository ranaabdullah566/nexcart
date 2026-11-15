/* NexCart storefront shell: mega menu, mobile drawer, hero carousel, tabs, rails, cookie bar, back-to-top, share */
(function () {
    'use strict';
    function one(s, r) { return (r || document).querySelector(s); }
    function all(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
    var csrf = (one('meta[name=csrf-token]') || {}).content;
    function toast(m, bad) { if (window.SF && SF.toast) SF.toast(m, bad ? 'bad' : 'ok'); }
    function t2() { return (window.SF && SF.t2) || {}; }

    /* ---- category finder (header menu + drawer) ---- */
    all('[data-cat-filter]').forEach(function (inp) {
        var scope = inp.closest('.mk-mega-panel, [data-drawer-panel]') || document;
        var list = one('[data-cat-list]', scope), none = list && one('.mk-mega-none', list);
        inp.addEventListener('input', function () {
            var q = inp.value.trim().toLowerCase(), shown = 0;
            all('a[data-name]', list).forEach(function (a) { var ok = !q || a.dataset.name.indexOf(q) > -1; a.hidden = !ok; if (ok) shown++; });
            if (none) none.hidden = shown > 0;
        });
    });

    /* ---- mega menu ---- */
    var mega = one('[data-mega]');
    if (mega) {
        var btn = one('.mk-mega-btn', mega), timer;
        var setOpen = function (open) { mega.classList.toggle('open', open); btn.setAttribute('aria-expanded', open ? 'true' : 'false'); };
        btn.addEventListener('click', function (e) { e.stopPropagation(); /* with a mouse the menu already opened on hover, so a click keeps it open instead of closing it */ setOpen(matchMedia('(hover:hover)').matches ? true : !mega.classList.contains('open')); });
        mega.addEventListener('mouseenter', function () { if (matchMedia('(hover:hover)').matches) { clearTimeout(timer); setOpen(true); } });
        mega.addEventListener('mouseleave', function () { if (matchMedia('(hover:hover)').matches) timer = setTimeout(function () { setOpen(false); }, 180); });
        document.addEventListener('click', function (e) { if (!mega.contains(e.target)) setOpen(false); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') setOpen(false); });
        all('[data-mega-target]', mega).forEach(function (b) {
            var show = function () {
                all('[data-mega-target]', mega).forEach(function (x) { x.classList.toggle('is-active', x === b); });
                all('.mk-mega-sub section', mega).forEach(function (s) { s.classList.toggle('is-active', s.id === b.dataset.megaTarget); });
            };
            b.addEventListener('mouseenter', show); b.addEventListener('focus', show); b.addEventListener('click', show);
        });
    }

    /* ---- mobile drawer ---- */
    var layer = one('[data-mobile-nav]');
    if (layer) {
        var opener = one('[data-mobile-open]');
        var closeDrawer = function () { layer.hidden = true; document.body.classList.remove('mk-lock'); if (opener) { opener.setAttribute('aria-expanded', 'false'); opener.focus(); } };
        if (opener) opener.addEventListener('click', function () { layer.hidden = false; document.body.classList.add('mk-lock'); opener.setAttribute('aria-expanded', 'true'); var d = one('.mk-drawer', layer); if (d) d.focus(); });
        all('[data-mobile-close]', layer).forEach(function (b) { b.addEventListener('click', closeDrawer); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !layer.hidden) closeDrawer(); });
        all('[data-drawer-tab]', layer).forEach(function (t) {
            t.addEventListener('click', function () {
                all('[data-drawer-tab]', layer).forEach(function (x) { x.classList.toggle('on', x === t); });
                all('[data-drawer-panel]', layer).forEach(function (p) { p.hidden = p.dataset.drawerPanel !== t.dataset.drawerTab; });
            });
        });
    }

    /* ---- phone search toggle ---- */
    var searchBtn = one('.mk-search-toggle'), searchForm = one('#mkSearch');
    if (searchBtn && searchForm) searchBtn.addEventListener('click', function () {
        var o = searchForm.classList.toggle('open');
        searchBtn.setAttribute('aria-expanded', o ? 'true' : 'false');
        if (o) { var i = one('input', searchForm); if (i) i.focus(); }
    });

    /* ---- hero carousel ---- */
    all('[data-carousel]').forEach(function (c) {
        var slides = all('.mk-slide', c), dots = all('[data-carousel-dot]', c), cur = 0, timerId;
        if (slides.length < 2) return;
        function go(n) {
            cur = (n + slides.length) % slides.length;
            slides.forEach(function (s, i) {
                s.classList.toggle('is-active', i === cur);
                s.setAttribute('aria-hidden', i === cur ? 'false' : 'true');
                all('a', s).forEach(function (a) { a.tabIndex = i === cur ? 0 : -1; });
            });
            dots.forEach(function (d, i) { d.classList.toggle('on', i === cur); });
        }
        var secs = parseInt(c.dataset.autoplay, 10) || 0;
        function auto() {
            clearInterval(timerId);
            if (secs > 0 && !matchMedia('(prefers-reduced-motion: reduce)').matches) timerId = setInterval(function () { go(cur + 1); }, secs * 1000);
        }
        var p = one('[data-carousel-prev]', c), n = one('[data-carousel-next]', c);
        if (p) p.addEventListener('click', function () { go(cur - 1); auto(); });
        if (n) n.addEventListener('click', function () { go(cur + 1); auto(); });
        dots.forEach(function (d, i) { d.addEventListener('click', function () { go(i); auto(); }); });
        c.addEventListener('mouseenter', function () { clearInterval(timerId); });
        c.addEventListener('mouseleave', auto);
        var x0 = null;
        c.addEventListener('touchstart', function (e) { x0 = e.touches[0].clientX; }, { passive: true });
        c.addEventListener('touchend', function (e) {
            if (x0 === null) return;
            var dx = e.changedTouches[0].clientX - x0;
            if (Math.abs(dx) > 50) { go(cur + (dx < 0 ? 1 : -1)); auto(); }
            x0 = null;
        }, { passive: true });
        auto();
    });

    /* ---- product tabs, rails, share, sticky CTA ---- */
    all('[data-tabs]').forEach(function (box) {
        var tabs = all('[data-tab]', box), panes = all('[data-pane]', box);
        tabs.forEach(function (t) {
            t.addEventListener('click', function () {
                tabs.forEach(function (x) { x.classList.toggle('on', x === t); });
                panes.forEach(function (p) { p.classList.toggle('on', p.dataset.pane === t.dataset.tab); });
            });
        });
        all('a[href="#reviews"]').forEach(function (a) { a.addEventListener('click', function () { var t = one('[data-tab=reviews]', box); if (t) t.click(); }); });
    });
    all('[data-rail]').forEach(function (r) {
        var tr = one('[data-rail-track]', r), rtl = getComputedStyle(tr).direction === 'rtl';
        var step = function () { return Math.max(240, tr.clientWidth * 0.8); };
        var b = one('[data-rail-prev]', r), f = one('[data-rail-next]', r), dots = one('[data-rail-dots]', r);
        var go = function (dir) { tr.scrollBy({ left: (rtl ? -1 : 1) * dir * step(), behavior: 'smooth' }); };
        if (b) b.addEventListener('click', function () { go(-1); });
        if (f) f.addEventListener('click', function () { go(1); });
        // arrows only when there is something to scroll; dim at either end
        var ctrl = one('[data-rail-ctrl]', r) || one('.mk-rail-ctrl', r);
        var pages = 1;
        var sync = function () {
            var max = tr.scrollWidth - tr.clientWidth, pos = Math.abs(tr.scrollLeft);
            if (ctrl) ctrl.hidden = max <= 4;
            var atStart = pos <= 2, atEnd = pos >= max - 2;
            if (b) b.disabled = atStart; if (f) f.disabled = atEnd;
            if (dots) {
                var n = max <= 4 ? 0 : Math.ceil(tr.scrollWidth / tr.clientWidth - 0.05);
                if (n !== pages) { pages = n; dots.innerHTML = ''; for (var i = 0; i < n; i++) { (function (i) { var d = document.createElement('button'); d.type = 'button'; d.setAttribute('aria-label', 'Slide ' + (i + 1)); d.addEventListener('click', function () { tr.scrollTo({ left: (rtl ? -1 : 1) * Math.min(max, i * tr.clientWidth), behavior: 'smooth' }); }); dots.appendChild(d); })(i); } }
                var cur = max <= 4 ? 0 : (atEnd ? pages - 1 : Math.round(pos / (max / Math.max(1, pages - 1))));
                Array.prototype.forEach.call(dots.children, function (d, i) { d.classList.toggle('on', i === cur); });
            }
        };
        tr.addEventListener('scroll', function () { window.requestAnimationFrame(sync); }, { passive: true });
        window.addEventListener('resize', sync); sync(); setTimeout(sync, 400);

        // home slider: moves by itself, loops back to the start, and stops while someone is using it
        var every = parseInt(r.getAttribute('data-autoplay') || '0', 10);
        if (every > 0 && !(window.matchMedia && matchMedia('(prefers-reduced-motion: reduce)').matches)) {
            var held = false, timer = null;
            var tick = function () {
                if (held || document.hidden) return;
                var max = tr.scrollWidth - tr.clientWidth;
                if (max <= 4) return;
                if (Math.abs(tr.scrollLeft) >= max - 2) tr.scrollTo({ left: 0, behavior: 'smooth' }); else go(1);
            };
            var hold = function () { held = true; }, free = function () { held = false; };
            ['mouseenter', 'focusin', 'touchstart', 'pointerdown'].forEach(function (ev) { r.addEventListener(ev, hold, { passive: true }); });
            ['mouseleave', 'focusout', 'touchend'].forEach(function (ev) { r.addEventListener(ev, free, { passive: true }); });
            timer = setInterval(tick, every);
        }
    });

    /* ---- cards: when no card in a row has a salt / tags line, that line takes no space in the row ---- */
    var tidyCards = function () {
        var cards = all('.mk-card');
        cards.forEach(function (c) { all('.fx-chips, .mk-save', c).forEach(function (e) { e.classList.remove('is-gone'); }); });
        var rows = {};
        cards.forEach(function (c) {
            if (c.closest('.mk-list')) return;
            var box = c.closest('[data-rail-track], .row, .mk-grid, .sf-grid') || c.parentNode.parentNode, k = c.getBoundingClientRect().top + window.scrollY;
            var id = (box.__rid = box.__rid || ++tidyCards.n) + ':' + Math.round(k / 6);
            (rows[id] = rows[id] || []).push(c);
        });
        Object.keys(rows).forEach(function (id) {
            ['.mk-save', '.fx-chips'].forEach(function (sel) {
                var slots = rows[id].map(function (c) { return one(sel, c); }).filter(Boolean);
                if (slots.length && slots.every(function (e) { return e.classList.contains('is-empty'); })) slots.forEach(function (e) { e.classList.add('is-gone'); });
            });
        });
    };
    tidyCards.n = 0;
    var tidyT; var tidyLater = function () { clearTimeout(tidyT); tidyT = setTimeout(tidyCards, 120); };
    tidyCards(); window.addEventListener('load', tidyCards); window.addEventListener('resize', tidyLater);
    document.addEventListener('click', function (e) {
        var sh = e.target.closest('[data-share]');
        if (!sh) return;
        var data = { title: sh.dataset.title || document.title, url: location.href };
        if (navigator.share) { navigator.share(data).catch(function () {}); return; }
        if (navigator.clipboard) navigator.clipboard.writeText(location.href).then(function () { toast(t2().copied || 'Link copied'); });
    });
    var bar = one('[data-sticky-cta]'), buy = one('#mkBuy');
    if (bar && buy && 'IntersectionObserver' in window) {
        new IntersectionObserver(function (en) { bar.hidden = en[0].isIntersecting || en[0].boundingClientRect.top > 0; }, { threshold: 0 }).observe(buy);
        var goBtn = one('[data-sticky-go]', bar);
        if (goBtn) goBtn.addEventListener('click', function () { buy.scrollIntoView({ behavior: 'smooth', block: 'center' }); });
    }

    /* ---- cart page: free-delivery progress follows quantity changes ---- */
    if (window.SF) {
        window.SF.cartPageHook = function (c) {
            var box = one('[data-free-ship]');
            if (!box) return;
            var need = parseFloat(box.dataset.freeShip), have = c.subtotal_raw || 0, left = Math.max(0, need - have);
            box.classList.toggle('done', left <= 0);
            var bar = one('.bar > i', box); if (bar) bar.style.width = Math.min(100, have / need * 100) + '%';
            var msg = one('[data-free-msg]', box);
            if (msg) msg.textContent = left <= 0 ? box.dataset.doneText : box.dataset.moreText.replace(':amount', window.SF.money(left));
        };
    }

    /* ---- shop: grid / list view, remembered ---- */
    var viewBox = one('[data-view-toggle]'), listWrap = one('[data-product-list]');
    if (viewBox && listWrap) {
        var VK = 'nx-shop-view', cur;
        var applyView = function (v) {
            listWrap.classList.toggle('mk-list', v === 'list');
            all('button', viewBox).forEach(function (b) { b.classList.toggle('on', b.dataset.view === v); b.setAttribute('aria-pressed', b.dataset.view === v ? 'true' : 'false'); });
        };
        try { cur = localStorage.getItem(VK) || 'grid'; } catch (e) { cur = 'grid'; }
        applyView(cur);
        all('button', viewBox).forEach(function (b) { b.addEventListener('click', function () { applyView(b.dataset.view); try { localStorage.setItem(VK, b.dataset.view); } catch (e) {} }); });
    }

    /* ---- sign-in / sign-up: show-hide password + live password rules ---- */
    all('[data-pw-toggle]').forEach(function (b) {
        b.addEventListener('click', function () {
            var input = one('input', b.parentNode);
            if (!input) return;
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            b.setAttribute('aria-pressed', show ? 'true' : 'false');
            var ic = one('i', b); if (ic) ic.className = 'bi bi-' + (show ? 'eye-slash' : 'eye');
        });
    });
    all('[data-strength]').forEach(function (input) {
        var list = one(input.dataset.strength);
        if (!list) return;
        function check() {
            var v = input.value;
            var ok = { len: v.length >= 10, letter: /[A-Za-z]/.test(v), digit: /\d/.test(v) };
            all('[data-rule]', list).forEach(function (li) {
                var good = ok[li.dataset.rule];
                li.classList.toggle('ok', good);
                var ic = one('i', li); if (ic) ic.className = 'bi bi-' + (good ? 'check-circle-fill' : 'circle');
            });
        }
        input.addEventListener('input', check); check();
    });

    /* ---- cookie choices ---- */
    var ck = one('#mkCookie'), KEY = 'nx-cookie-choice';
    function saved() { try { return localStorage.getItem(KEY); } catch (e) { return 'essential'; } }
    if (ck && !saved()) ck.hidden = false;
    all('[data-cookie-choice]').forEach(function (b) { b.addEventListener('click', function () { try { localStorage.setItem(KEY, b.dataset.cookieChoice); } catch (e) {} ck.hidden = true; }); });
    all('[data-cookie-settings]').forEach(function (b) { b.addEventListener('click', function () { if (ck) ck.hidden = false; }); });

    /* ---- back to top ---- */
    var topBtn = one('#mkTop');
    if (topBtn) {
        var vis = function () { topBtn.hidden = (window.scrollY || 0) < 600; };
        addEventListener('scroll', vis, { passive: true }); vis();
        topBtn.addEventListener('click', function () { scrollTo({ top: 0, behavior: 'smooth' }); });
    }
})();
