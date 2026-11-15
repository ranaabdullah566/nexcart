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
        var body = $('#sfDrawerBody'), foot = $('#sfDrawerFoot'), ship = $('#mcShip'), cnt = $('#mcCount');
        if (cnt) cnt.textContent = cart.count ? (cart.count + ' ' + (cart.count === 1 ? T.item : T.items)) : ' ';
        if (!body) return;
        // free-delivery progress
        if (ship) {
            if (cart.free_over && cart.lines.length) {
                var have = cart.subtotal_raw, left = Math.max(0, cart.free_over - have), pct = Math.min(100, have / cart.free_over * 100);
                ship.hidden = false; ship.className = 'mc-ship' + (left <= 0 ? ' done' : '');
                ship.innerHTML = '<div class="mc-ship-top"><i class="bi bi-truck"></i><span>' + (left <= 0 ? esc(T.freeDone) : esc(T.freeMore.replace(':amount', SF.money(left)))) + '</span></div>' +
                    '<div class="mc-bar"><i style="width:' + pct.toFixed(1) + '%"></i><b style="left:' + pct.toFixed(1) + '%"></b></div>';
            } else { ship.hidden = true; }
        }
        if (!cart.lines.length) {
            body.innerHTML = '<div class="mc-empty"><span class="mc-orb"><i class="bi bi-bag"></i></span><h6>' + esc(T.cartEmpty) + '</h6><p>' + esc(T.cartEmptySub) + '</p><a href="' + esc(SF.routes.shop) + '" class="mc-cta mc-cta-sm">' + esc(T.startShopping) + ' <i class="bi bi-arrow-right mc-arrow"></i></a></div>';
            if (foot) foot.hidden = true;
            return;
        }
        var html = cart.lines.map(function (l, i) {
            var opts = l.variant || '';
            return '<article class="mc-line sf-line" data-key="' + esc(l.key) + '" style="--i:' + i + '">' +
                '<a class="mc-img" href="' + esc(l.url) + '">' + (l.image ? '<img src="' + esc(l.image) + '" alt="">' : '<span class="ph"><i class="bi bi-box-seam"></i></span>') + (l.rx ? '<em>Rx</em>' : '') + '</a>' +
                '<div class="mc-info"><a class="mc-name" href="' + esc(l.url) + '">' + esc(l.name) + '</a>' +
                (opts ? '<small>' + esc(opts) + '</small>' : '') +
                '<small class="mc-unit">' + esc(l.unit) + ' ' + esc(T.each) + '</small>' +
                (l.short ? '<small class="warn"><i class="bi bi-exclamation-triangle"></i> ' + esc(T.lowStock.replace(':n', l.available)) + '</small>' : '') +
                '<div class="mc-ctl"><div class="mc-step sf-mini-qty"><button type="button" data-cart-qty="-1" aria-label="' + esc(T.less) + '">−</button><span>' + l.qty + '</span><button type="button" data-cart-qty="1" aria-label="' + esc(T.more) + '">+</button></div>' +
                '<button type="button" class="mc-rm rm" data-cart-remove aria-label="' + esc(T.remove) + '"><i class="bi bi-trash3"></i></button></div></div>' +
                '<div class="mc-price"><b>' + esc(l.total) + '</b>' + (l.was ? '<s>' + esc(l.was) + '</s>' : '') + '</div></article>';
        }).join('');
        if (cart.suggest && cart.suggest.length) {
            html += '<section class="mc-up"><h6><i class="bi bi-stars"></i> ' + esc(T.complete) + '</h6><div class="mc-up-row">' + cart.suggest.map(function (p) {
                return '<div class="mc-up-card"><a href="' + esc(p.url) + '" class="mc-up-img">' + (p.image ? '<img src="' + esc(p.image) + '" alt="" loading="lazy">' : '<i class="bi bi-capsule"></i>') + '</a>' +
                    '<a href="' + esc(p.url) + '" class="mc-up-name">' + esc(p.name) + '</a><div class="mc-up-foot"><b>' + esc(p.price) + '</b>' +
                    (p.options ? '<button type="button" class="mc-up-add" data-quick="' + esc(p.quick) + '" aria-label="' + esc(T.chooseOption) + '"><i class="bi bi-sliders2"></i></button>' : '<button type="button" class="mc-up-add" data-add="' + p.id + '" aria-label="' + esc(T.addToCart) + '"><i class="bi bi-plus-lg"></i></button>') + '</div></div>';
            }).join('') + '</div></section>';
        }
        body.innerHTML = html;
        if (foot) {
            foot.hidden = false; $('#sfSubtotal').textContent = cart.subtotal;
            var sr = $('#mcSaveRow'); if (sr) { sr.hidden = !cart.savings; $('#mcSave').textContent = cart.savings || ''; }
        }
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
        var sv = row && e.target.closest('[data-page-save]');
        if (sv) {
            var pid = sv.dataset.pageSave, tok = (document.querySelector('meta[name=csrf-token]') || {}).content;
            var toggle = function () { return fetch('/saved/' + pid, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': tok, 'X-Requested-With': 'XMLHttpRequest' } }).then(function (r) { return r.json(); }); };
            toggle().then(function (r) { return r.added ? r : toggle(); })   // keep it saved: toggling an already-saved item would remove it
                .then(function () { return http('DELETE', SF.routes.cartBase + '/' + row.dataset.cartRow); })
                .then(function (c) { SF.cart = c; paintCart(c); refreshCartPage(c); toast(T.savedLater); })
                .catch(function (err) { toast(err.message || T.error, 'bad'); });
            return;
        }
        if (row && e.target.closest('[data-page-remove]')) {
            http('DELETE', SF.routes.cartBase + '/' + row.dataset.cartRow).then(function (c) { SF.cart = c; paintCart(c); refreshCartPage(c); });
            return;
        }
        if (e.target.closest('[data-open-cart]')) { e.preventDefault(); refreshCart().then(openDrawer); return; }

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
        var label = c.count + ' ' + (c.count === 1 ? T.item : T.items);
        $$('[data-page-count]').forEach(function (e) { e.textContent = label; });
        $$('[data-page-count2]').forEach(function (e) { e.textContent = '· ' + label; });
        $$('[data-page-saverow]').forEach(function (e) { e.hidden = !c.savings; });
        $$('[data-page-save-amt]').forEach(function (e) { e.textContent = c.savings || ''; });
        $$('[data-cart-row]', page).forEach(function (r) { var l = c.lines.filter(function (x) { return x.key === r.dataset.cartRow; })[0]; var s = $('.cx-total s', r); if (l && s) { s.textContent = l.was || ''; s.hidden = !l.was; } });
        var est = $('#cxEst'); if (est) est.dataset.estBase = c.subtotal_raw;
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
                return { v: v, unit: unit, raw: unit, cmp: cmp, avail: avail };
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
            form.addEventListener('change', paint);
            form.addEventListener('click', function (e) {
                var b = e.target.closest('[data-q]'); if (!b) return;
                qtyEl.value = Math.max(1, Math.min(+qtyEl.max || 99, (+qtyEl.value || 1) + (+b.dataset.q))); paint();
            });
            form.addEventListener('submit', function (e) {
                e.preventDefault();
                var s = state();
                var payload = { product_id: +form.dataset.product, qty: +qtyEl.value || 1 };
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
        f.addEventListener('change', function (e) {
            if (e.target.matches('[data-no-auto]')) return;
            // in the phone filter sheet people tick several things, then press "Show results"
            if (f.closest('.offcanvas-lg') && window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches) return;
            f.submit();
        });
    });

    /* ---------- shop sidebar: category expanders and filter search ---------- */
    document.addEventListener('click', function (e) {
        var b = e.target.closest ? e.target.closest('.tq-exp') : null; if (!b) return;
        var host = b.closest('li'), ul = host.querySelector(':scope > ul, :scope > .sx-chips'); if (!ul) return;
        var open = b.getAttribute('aria-expanded') === 'true'; b.setAttribute('aria-expanded', open ? 'false' : 'true'); ul.hidden = open;
    });
    /* price: dual-handle slider + quick ranges, synced with the Min / Max boxes */
    $$('[data-sx-price]').forEach(function (box) {
        var lo = +box.dataset.min, hi = +box.dataset.max, r1 = $('.sx-r1', box), r2 = $('.sx-r2', box), fill = $('.sx-fill', box), form = box.closest('form');
        var n1 = $('[name=price_min]', box), n2 = $('[name=price_max]', box), t;
        function paintFill() { var a = (+r1.value - lo) / (hi - lo) * 100, b = (+r2.value - lo) / (hi - lo) * 100; fill.style.insetInlineStart = Math.min(a, b) + '%'; fill.style.insetInlineEnd = (100 - Math.max(a, b)) + '%'; }
        function toBoxes() { var a = Math.min(+r1.value, +r2.value), b = Math.max(+r1.value, +r2.value); n1.value = a > lo ? a : ''; n2.value = b < hi ? b : ''; paintFill(); }
        function fromBoxes() { r1.value = n1.value === '' ? lo : n1.value; r2.value = n2.value === '' ? hi : n2.value; paintFill(); }
        function go() { clearTimeout(t); t = setTimeout(function () { if (!(window.matchMedia && window.matchMedia('(max-width: 991.98px)').matches)) form.submit(); }, 450); }
        [r1, r2].forEach(function (r) { r.addEventListener('input', toBoxes); r.addEventListener('change', function () { toBoxes(); go(); }); });
        [n1, n2].forEach(function (n) { n.addEventListener('input', fromBoxes); });
        $$('.sx-quick button', box).forEach(function (btn) { btn.addEventListener('click', function () { n1.value = +btn.dataset.lo > lo ? btn.dataset.lo : ''; n2.value = btn.dataset.hi === '' ? '' : btn.dataset.hi; fromBoxes(); go(); }); });
        fromBoxes();
    });
    $$('[data-flt-search]').forEach(function (inp) {
        var form = inp.closest('form');
        inp.addEventListener('keydown', function (e) { if (e.key === 'Enter') e.preventDefault(); });
        inp.addEventListener('input', function () {
            var q = inp.value.trim().toLowerCase();
            form.querySelectorAll('[data-flt-item]').forEach(function (it) { it.hidden = q !== '' && it.textContent.toLowerCase().indexOf(q) === -1 && !it.querySelector('[data-flt-item]:not([hidden])'); });
            form.querySelectorAll('[data-flt-sec]').forEach(function (sec) {
                var any = sec.querySelector('[data-flt-item]:not([hidden])'); sec.hidden = q !== '' && !any;
                sec.querySelectorAll('.tq-cattree ul').forEach(function (ul) { if (q !== '') ul.hidden = false; });
            });
        });
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
