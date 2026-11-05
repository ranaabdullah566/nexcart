/* NexCart storefront — vanilla JS (Bootstrap 5 bundle provides offcanvas / modal / dropdown) */
(function () {
    'use strict';
    var SF = window.SF || {}, T = SF.t || {};
    var csrf = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
    var $ = function (s, r) { return (r || document).querySelector(s); };
    var $$ = function (s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); };
    var esc = function (s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; };

    /* ---------- theme ---------- */
    var root = document.documentElement;
    function paintTheme() {
        $$('[data-theme-toggle] i').forEach(function (i) { i.className = 'bi ' + (root.getAttribute('data-theme') === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'); });
    }
    paintTheme();
    document.addEventListener('click', function (e) {
        if (!e.target.closest('[data-theme-toggle]')) return;
        var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
        root.setAttribute('data-theme', next); root.setAttribute('data-bs-theme', next);
        try { localStorage.setItem('nx-theme', next); } catch (err) {}
        paintTheme();
    });

    /* ---------- toast ---------- */
    function toast(msg, kind) {
        var box = $('#sfToasts'); if (!box) return;
        var el = document.createElement('div');
        el.className = 'nx-toast ' + (kind === 'bad' ? 'bad' : 'ok');
        el.setAttribute('role', 'status');
        el.innerHTML = '<i class="bi ' + (kind === 'bad' ? 'bi-exclamation-octagon-fill' : 'bi-check-circle-fill') + '"></i><div>' + esc(msg) + '</div>';
        box.appendChild(el);
        setTimeout(function () { el.style.transition = 'all .3s'; el.style.opacity = 0; setTimeout(function () { el.remove(); }, 300); }, 3800);
    }
    SF.toast = toast;

    /* ---------- http ---------- */
    function http(method, url, body) {
        return fetch(url, {
            method: method, credentials: 'same-origin',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf },
            body: body ? JSON.stringify(body) : undefined
        }).then(function (r) {
            return r.json().catch(function () { return {}; }).then(function (j) {
                if (!r.ok) { var m = j.message; if (j.errors) m = Object.values(j.errors)[0][0]; var err = new Error(m || T.error); err.status = r.status; throw err; }
                return j;
            });
        });
    }

    /* ---------- cart ---------- */
    var drawerEl = $('#sfDrawer'), drawer = null;
    function openDrawer() { if (!drawerEl || !window.bootstrap) return; drawer = drawer || bootstrap.Offcanvas.getOrCreateInstance(drawerEl); drawer.show(); }

    function paintCart(cart) {
        $$('[data-cart-count]').forEach(function (b) { b.textContent = cart.count; b.hidden = !cart.count; });
        var body = $('#sfDrawerBody'), foot = $('#sfDrawerFoot');
        if (!body) return;
        if (!cart.lines.length) {
            body.innerHTML = '<div class="sf-empty"><i class="bi bi-bag"></i>' + esc(T.cartEmpty) + '</div>';
            if (foot) foot.hidden = true;
            return;
        }
        body.innerHTML = cart.lines.map(function (l) {
            var opts = [l.variant].concat(l.options).filter(Boolean).join(' · ');
            return '<div class="sf-line" data-key="' + esc(l.key) + '">' +
                (l.image ? '<img src="' + esc(l.image) + '" alt="">' : '<span class="ph"><i class="bi bi-box-seam"></i></span>') +
                '<div class="min-w-0"><a href="' + esc(l.url) + '">' + esc(l.name) + '</a>' +
                (opts ? '<small>' + esc(opts) + '</small>' : '') +
                (l.short ? '<small class="warn"><i class="bi bi-exclamation-triangle"></i> ' + esc(T.lowStock.replace(':n', l.available)) + '</small>' : '') +
                '<div class="sf-mini-qty"><button type="button" data-cart-qty="-1" aria-label="-">−</button><span>' + l.qty + '</span><button type="button" data-cart-qty="1" aria-label="+">+</button></div></div>' +
                '<div class="text-end"><div class="figure fw-bold">' + esc(l.total) + '</div><button type="button" class="rm mt-2" data-cart-remove aria-label="' + esc(T.remove) + '"><i class="bi bi-trash3"></i></button></div></div>';
        }).join('');
        if (foot) { foot.hidden = false; $('#sfSubtotal').textContent = cart.subtotal; }
    }

    function refreshCart() { return http('GET', SF.routes.cart).then(function (c) { SF.cart = c; paintCart(c); return c; }).catch(function () {}); }

    function addToCart(payload, opts) {
        opts = opts || {};
        return http('POST', SF.routes.add, payload).then(function (c) {
            SF.cart = c; paintCart(c);
            if (!opts.silent) { toast(T.added); openDrawer(); }
            return c;
        }).catch(function (err) { toast(err.message, 'bad'); throw err; });
    }
    SF.addToCart = addToCart;

    document.addEventListener('click', function (e) {
        // card "Add to cart" (simple products)
        var add = e.target.closest('[data-add]');
        if (add && !add.disabled) {
            add.disabled = true;
            addToCart({ product_id: +add.dataset.add, qty: 1 }).catch(function () {}).then(function () { add.disabled = false; });
            return;
        }
        var line = e.target.closest('.sf-line');
        if (line && e.target.closest('[data-cart-qty]')) {
            var span = $('.sf-mini-qty span', line), q = +span.textContent + (+e.target.closest('[data-cart-qty]').dataset.cartQty);
            http('PATCH', SF.routes.cartBase + '/' + line.dataset.key, { qty: q }).then(function (c) { SF.cart = c; paintCart(c); refreshCartPage(c); }).catch(function (err) { toast(err.message, 'bad'); });
            return;
        }
        if (line && e.target.closest('[data-cart-remove]')) {
            http('DELETE', SF.routes.cartBase + '/' + line.dataset.key).then(function (c) { SF.cart = c; paintCart(c); refreshCartPage(c); });
            return;
        }
        // cart page controls
        var row = e.target.closest('[data-cart-row]');
        if (row && e.target.closest('[data-page-qty]')) {
            var cur = +$('.q', row).textContent, nq = cur + (+e.target.closest('[data-page-qty]').dataset.pageQty);
            http('PATCH', SF.routes.cartBase + '/' + row.dataset.cartRow, { qty: nq }).then(function (c) { SF.cart = c; paintCart(c); refreshCartPage(c); }).catch(function (err) { toast(err.message, 'bad'); });
            return;
        }
        if (row && e.target.closest('[data-page-remove]')) {
            http('DELETE', SF.routes.cartBase + '/' + row.dataset.cartRow).then(function (c) { SF.cart = c; paintCart(c); refreshCartPage(c); });
            return;
        }
        if (e.target.closest('[data-open-cart]')) { e.preventDefault(); refreshCart().then(openDrawer); return; }

        // wishlist
        var wish = e.target.closest('[data-wish]');
        if (wish) {
            e.preventDefault();
            http('POST', SF.routes.wishBase + '/' + wish.dataset.wish).then(function (r) {
                $$('[data-wish="' + wish.dataset.wish + '"]').forEach(function (b) { b.classList.toggle('on', r.added); b.setAttribute('aria-pressed', r.added); });
                $$('[data-wish-count]').forEach(function (b) { b.textContent = r.count; b.hidden = !r.count; });
                toast(r.added ? T.wishAdded : T.wishRemoved);
                if (!r.added && document.body.dataset.page === 'wishlist') { var c = wish.closest('[data-wish-item]'); if (c) c.remove(); }
            }).catch(function (err) { toast(err.message, 'bad'); });
            return;
        }

        // quick view
        var qv = e.target.closest('[data-quick]');
        if (qv) {
            e.preventDefault();
            var modalEl = $('#sfQuick'), body = $('#sfQuickBody'); if (!modalEl) return;
            body.innerHTML = '<div class="sf-skeleton"></div>';
            bootstrap.Modal.getOrCreateInstance(modalEl).show();
            fetch(qv.dataset.quick, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(function (r) { if (!r.ok) throw 0; return r.text(); })
                .then(function (html) { body.innerHTML = html; initBuy(body); })
                .catch(function () { body.innerHTML = '<div class="sf-empty">' + esc(T.error) + '</div>'; });
        }
    });

    function refreshCartPage(c) {
        var page = $('#sfCartPage'); if (!page) return;
        if (!c.lines.length) { location.reload(); return; }
        $$('[data-cart-row]', page).forEach(function (r) {
            var l = c.lines.filter(function (x) { return x.key === r.dataset.cartRow; })[0];
            if (!l) { r.remove(); return; }
            $('.q', r).textContent = l.qty; $('.lt', r).textContent = l.total;
        });
        $$('[data-page-subtotal]').forEach(function (e) { e.textContent = c.subtotal; });
        if (SF.cartPageHook) SF.cartPageHook(c);
    }

    /* ---------- product buy box (page + quick view) ---------- */
    function initBuy(scope) {
        $$('[data-buy]', scope).forEach(function (form) {
            if (form.dataset.ready) return; form.dataset.ready = 1;
            var variants = JSON.parse(form.dataset.variants || '[]'), base = +form.dataset.price, compare = form.dataset.compare ? +form.dataset.compare : null;
            var track = form.dataset.track === '1', stock = +form.dataset.stock, qtyEl = $('[name=qty]', form), btn = $('[data-buy-btn]', form);
            var priceEl = $('[data-price]', form), compareEl = $('[data-compare]', form), stockEl = $('[data-stock-note]', form), totalEl = $('[data-total]', form);
            var fmt = function (n) { return SF.money(n); };

            function state() {
                var v = null, vid = ($('[name=variant_id]:checked', form) || {}).value;
                if (vid) v = variants.filter(function (x) { return String(x.id) === vid; })[0];
                var unit = v ? v.price : base, cmp = v ? v.compare : compare, avail = v ? v.stock : stock;
                var extra = $$('[data-delta]:checked', form).reduce(function (s, i) { return s + (+i.dataset.delta); }, 0);
                return { v: v, unit: unit + extra, raw: unit, cmp: cmp, avail: avail, extra: extra };
            }
            function paint() {
                var s = state(), q = Math.max(1, +qtyEl.value || 1);
                if (priceEl) priceEl.textContent = fmt(s.unit);
                if (compareEl) { compareEl.textContent = s.cmp && s.cmp > s.raw ? fmt(s.cmp) : ''; compareEl.hidden = !(s.cmp && s.cmp > s.raw); }
                if (totalEl) totalEl.textContent = fmt(s.unit * q);
                var out = form.dataset.variable === '1' && !s.v ? false : (track && s.avail <= 0);
                if (btn) { btn.disabled = out; $('span', btn).textContent = out ? T.outOfStock : T.addToCart; }
                if (stockEl) stockEl.textContent = track && s.avail > 0 && s.avail <= 5 ? T.lowStock.replace(':n', s.avail) : '';
                if (track && s.avail > 0) qtyEl.max = Math.min(99, s.avail);
            }
            // add-on checkbox groups: never more than the group's maximum
            form.addEventListener('change', function (e) {
                var g = e.target.closest('[data-group]');
                if (!g || e.target.type !== 'checkbox') return;
                var max = +g.dataset.max, boxes = $$('input[type=checkbox]', g), n = boxes.filter(function (b) { return b.checked; }).length;
                boxes.forEach(function (b) { if (!b.checked) b.disabled = n >= max; });
            });
            form.addEventListener('change', paint);
            form.addEventListener('click', function (e) {
                var b = e.target.closest('[data-q]'); if (!b) return;
                qtyEl.value = Math.max(1, Math.min(+qtyEl.max || 99, (+qtyEl.value || 1) + (+b.dataset.q))); paint();
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var s = state();
                var payload = { product_id: +form.dataset.product, qty: +qtyEl.value || 1, options: $$('[data-delta]:checked', form).map(function (i) { return +i.value; }) };
                if (form.dataset.variable === '1') {
                    if (!s.v) { toast(T.chooseOption, 'bad'); return; }
                    payload.variant_id = s.v.id;
                }
                btn.disabled = true;
                addToCart(payload).then(function () {
                    var m = form.closest('.modal'); if (m) bootstrap.Modal.getInstance(m).hide();
                }).catch(function () {}).then(function () { paint(); });
            });
            paint();
        });

        // gallery
        $$('[data-thumb]', scope).forEach(function (t) {
            t.addEventListener('click', function () {
                var main = $('[data-main-img]', scope); if (main) main.src = t.dataset.thumb;
                $$('[data-thumb]', scope).forEach(function (x) { x.classList.toggle('on', x === t); });
            });
        });
    }
    SF.initBuy = initBuy;
    initBuy(document);

    /* ---------- instant search ---------- */
    var input = $('#sfSearch'), box = $('#sfSuggest'), timer = null, ctrl = null;
    if (input && box) {
        function hide() { box.classList.remove('show'); }
        input.addEventListener('input', function () {
            clearTimeout(timer);
            var q = input.value.trim();
            if (q.length < 2) { hide(); return; }
            timer = setTimeout(function () {
                if (ctrl) ctrl.abort(); ctrl = new AbortController();
                fetch(SF.routes.suggest + '?q=' + encodeURIComponent(q), { signal: ctrl.signal, credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        box.innerHTML = d.results.length
                            ? d.results.map(function (r) {
                                return '<a href="' + esc(r.url) + '">' + (r.image ? '<img src="' + esc(r.image) + '" alt="">' : '<span class="ph"></span>') +
                                    '<span class="min-w-0"><span class="d-block text-truncate fw-semibold">' + esc(r.name) + '</span><small class="figure text-muted">' + esc(r.price) + '</small></span></a>';
                            }).join('') + '<a class="more" href="' + esc(d.more) + '">' + esc(T.seeAll) + ' <i class="bi bi-arrow-right"></i></a>'
                            : '<div class="empty">' + esc(T.noResults) + '</div>';
                        box.classList.add('show');
                    }).catch(function () {});
            }, 220);
        });
        input.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') hide();
            if (e.key === 'ArrowDown') { var a = $('a', box); if (a) { e.preventDefault(); a.focus(); } }
        });
        box.addEventListener('keydown', function (e) {
            if (e.key !== 'ArrowDown' && e.key !== 'ArrowUp') return;
            e.preventDefault();
            var items = $$('a', box), i = items.indexOf(document.activeElement);
            (items[i + (e.key === 'ArrowDown' ? 1 : -1)] || input).focus();
        });
        document.addEventListener('click', function (e) { if (!e.target.closest('.sf-search')) hide(); });
        input.addEventListener('focus', function () { if (box.innerHTML && input.value.trim().length >= 2) box.classList.add('show'); });
    }

    /* ---------- filters: submit on change ---------- */
    $$('[data-autosubmit]').forEach(function (f) {
        f.addEventListener('change', function (e) { if (e.target.matches('[data-no-auto]')) return; f.submit(); });
    });

    /* ---------- reveal on scroll ---------- */
    if ('IntersectionObserver' in window) {
        var io = new IntersectionObserver(function (es) { es.forEach(function (en) { if (en.isIntersecting) { en.target.classList.add('in'); io.unobserve(en.target); } }); }, { threshold: .08 });
        $$('.sf-reveal').forEach(function (el) { io.observe(el); });
    } else { $$('.sf-reveal').forEach(function (el) { el.classList.add('in'); }); }

    /* ---------- PWA ---------- */
    if ('serviceWorker' in navigator && location.protocol.indexOf('http') === 0) {
        window.addEventListener('load', function () { navigator.serviceWorker.register('/sw.js').catch(function () {}); });
    }

    if (SF.cart) paintCart(SF.cart); else refreshCart();
})();
