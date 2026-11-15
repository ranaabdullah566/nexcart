/* NexCart "Future" layer: scroll progress, header glow, spotlight + tilt cards, ripples, stagger, command palette (Ctrl/⌘+K or "/"). */
(function () {
    'use strict';
    var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var fine = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
    function $(s, r) { return (r || document).querySelector(s); }
    function $$(s, r) { return Array.prototype.slice.call((r || document).querySelectorAll(s)); }
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }

    /* ---- scroll progress + header state ---- */
    var bar = document.createElement('div'); bar.className = 'fx-progress'; bar.innerHTML = '<i></i>'; document.body.appendChild(bar);
    var fill = bar.firstChild, header = $('.mk-header'), ticking = false;
    function onScroll() {
        var h = document.documentElement, max = h.scrollHeight - h.clientHeight;
        fill.style.transform = 'scaleX(' + (max > 0 ? Math.min(1, h.scrollTop / max) : 0) + ')';
        if (header) header.classList.toggle('fx-scrolled', h.scrollTop > 12);
        ticking = false;
    }
    window.addEventListener('scroll', function () { if (!ticking) { ticking = true; requestAnimationFrame(onScroll); } }, { passive: true });
    onScroll();

    /* ---- stagger index for cards in revealed sections ---- */
    $$('.sf-reveal').forEach(function (sec) {
        $$('.mk-card, .mk-tile, .mk-steps li, .mk-quote', sec).forEach(function (el, i) { el.style.setProperty('--i', i % 8); });
    });
    /* sections that are already in view on load (and fallback when IntersectionObserver is missing) */
    if (!('IntersectionObserver' in window)) $$('.sf-reveal').forEach(function (e) { e.classList.add('in'); });

    /* ---- spotlight (all glass cards) + 3D tilt (product cards) ---- */
    if (fine && !reduce) {
        document.addEventListener('pointermove', function (e) {
            var t = e.target.closest && e.target.closest('.mk-card, .mk-tile, .mk-steps li, .mk-quote, .sf-cat, .mk-summary');
            if (!t) return;
            var r = t.getBoundingClientRect(), x = e.clientX - r.left, y = e.clientY - r.top;
            t.style.setProperty('--mx', x + 'px'); t.style.setProperty('--my', y + 'px');
            if (t.classList.contains('mk-card')) {
                var rx = ((y / r.height) - .5) * -7, ry = ((x / r.width) - .5) * 9;
                t.classList.add('fx-tilting');
                t.style.transform = 'perspective(900px) rotateX(' + rx.toFixed(2) + 'deg) rotateY(' + ry.toFixed(2) + 'deg) translateY(-6px)';
            }
        }, { passive: true });
        document.addEventListener('pointerout', function (e) {
            var t = e.target.closest && e.target.closest('.mk-card');
            if (t && !t.contains(e.relatedTarget)) { t.classList.remove('fx-tilting'); t.style.transform = ''; }
        });
    }

    /* ---- button ripple ---- */
    if (!reduce) document.addEventListener('pointerdown', function (e) {
        var b = e.target.closest && e.target.closest('.btn-primary, .mk-add, .mk-btn-accent, .sf-btn');
        if (!b) return;
        var r = b.getBoundingClientRect(), s = Math.max(r.width, r.height), d = document.createElement('span');
        d.className = 'fx-ripple'; d.style.cssText = 'width:' + s + 'px;height:' + s + 'px;left:' + (e.clientX - r.left - s / 2) + 'px;top:' + (e.clientY - r.top - s / 2) + 'px';
        b.appendChild(d); setTimeout(function () { d.remove(); }, 650);
    });

    /* ---- search box keyboard hint ---- */
    var sbox = $('.mk-search');
    if (sbox && fine) { var k = document.createElement('span'); k.className = 'fx-kbd'; k.textContent = /Mac/.test(navigator.platform) ? '⌘K' : 'Ctrl K'; sbox.appendChild(k); }

    /* ---- command palette ---- */
    var L = (window.SF && window.SF.t2) || {};
    var suggestUrl = window.SF && window.SF.routes && window.SF.routes.suggest;
    var cmd = document.createElement('div'); cmd.className = 'fx-cmd'; cmd.setAttribute('role', 'dialog'); cmd.setAttribute('aria-modal', 'true'); cmd.setAttribute('aria-label', 'Search');
    cmd.innerHTML = '<div class="fx-cmd-box"><div class="fx-cmd-in"><i class="bi bi-search"></i><input type="search" autocomplete="off" spellcheck="false" aria-label="Search" placeholder="' + esc((sbox && $('input', sbox) && $('input', sbox).placeholder) || 'Search medicines, categories…') + '"><kbd>Esc</kbd></div><div class="fx-cmd-list" role="listbox"></div><div class="fx-cmd-foot"><span>↑↓ navigate</span><span>↵ open</span><span>Esc close</span></div></div>';
    document.body.appendChild(cmd);
    var inp = $('input', cmd), list = $('.fx-cmd-list', cmd), sel = -1, timer, last = '', prevFocus;
    var shortcuts = [
        ['bi-shop', document.documentElement.lang === 'ur' ? 'تمام ادویات' : 'All medicines', '/shop'],
        ['bi-lightning-charge', 'Deals', '/shop?sort=discount'],
        ['bi-bag', 'Cart', '/cart'],
        ['bi-truck', 'Track an order', '/track'],
        ['bi-person', 'My account', '/account']
    ];
    function items() { return $$('.fx-cmd-item', list); }
    function mark(i) { var it = items(); if (!it.length) return; sel = (i + it.length) % it.length; it.forEach(function (e, n) { e.classList.toggle('sel', n === sel); }); it[sel].scrollIntoView({ block: 'nearest' }); }
    function home() {
        list.innerHTML = '<div class="fx-cmd-h">Quick links</div>' + shortcuts.map(function (s) { return '<a class="fx-cmd-item" href="' + s[2] + '"><span class="ph"><i class="bi ' + s[0] + '"></i></span><b>' + esc(s[1]) + '</b><i class="bi bi-arrow-right go"></i></a>'; }).join('');
        sel = -1;
    }
    function render(q, res, cats) {
        cats = cats || [];
        if (!res.length && !cats.length) { list.innerHTML = '<div class="fx-cmd-empty"><i class="bi bi-emoji-neutral fs-3 d-block mb-2"></i>' + esc('No results for “' + q + '”') + '</div>'; sel = -1; return; }
        list.innerHTML = (cats.length ? '<div class="fx-cmd-h">Categories</div>' + cats.map(function (c) {
            return '<a class="fx-cmd-item" href="' + esc(c.url) + '">' + (c.image ? '<img src="' + esc(c.image) + '" alt="" loading="lazy">' : '<span class="ph"><i class="bi bi-grid-3x3-gap"></i></span>') + '<span><b>' + esc(c.name) + '</b><small>' + c.count + '</small></span></a>';
        }).join('') : '') + (res.length ? '<div class="fx-cmd-h">Medicines</div>' : '') + res.map(function (r) {
            return '<a class="fx-cmd-item" href="' + esc(r.url) + '">' + (r.image ? '<img src="' + esc(r.image) + '" alt="" loading="lazy">' : '<span class="ph"><i class="bi bi-capsule"></i></span>') + '<span><b>' + esc(r.name) + '</b><small>' + esc(r.category || '') + '</small></span><span class="go figure">' + esc(r.price || '') + '</span></a>';
        }).join('') + '<a class="fx-cmd-item" href="/shop?q=' + encodeURIComponent(q) + '"><span class="ph"><i class="bi bi-search"></i></span><b>See all results for “' + esc(q) + '”</b><i class="bi bi-arrow-right go"></i></a>';
        mark(0);
    }
    function search(q) {
        if (!suggestUrl) return;
        fetch(suggestUrl + (suggestUrl.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function (d) { if (inp.value.trim() === q) render(q, d.results || [], d.categories || []); }).catch(function () { });
    }
    function open() { prevFocus = document.activeElement; cmd.classList.add('open'); document.body.classList.add('mk-lock'); inp.value = ''; home(); setTimeout(function () { inp.focus(); }, 30); }
    function close() { cmd.classList.remove('open'); document.body.classList.remove('mk-lock'); if (prevFocus && prevFocus.focus) prevFocus.focus(); }
    inp.addEventListener('input', function () {
        var q = inp.value.trim(); clearTimeout(timer);
        if (q.length < 2) { last = ''; home(); return; }
        if (q === last) return; last = q; timer = setTimeout(function () { search(q); }, 160);
    });
    inp.addEventListener('keydown', function (e) {
        if (e.key === 'ArrowDown') { e.preventDefault(); mark(sel + 1); }
        else if (e.key === 'ArrowUp') { e.preventDefault(); mark(sel - 1); }
        else if (e.key === 'Enter') { e.preventDefault(); var it = items(); if (sel > -1 && it[sel]) location.href = it[sel].href; else if (inp.value.trim()) location.href = '/shop?q=' + encodeURIComponent(inp.value.trim()); }
    });
    cmd.addEventListener('mousedown', function (e) { if (e.target === cmd) close(); });
    document.addEventListener('keydown', function (e) {
        var typing = /^(INPUT|TEXTAREA|SELECT)$/.test((document.activeElement || {}).tagName) || (document.activeElement && document.activeElement.isContentEditable);
        if ((e.key === 'k' || e.key === 'K') && (e.ctrlKey || e.metaKey)) { e.preventDefault(); cmd.classList.contains('open') ? close() : open(); }
        else if (e.key === '/' && !typing && !cmd.classList.contains('open')) { e.preventDefault(); open(); }
        else if (e.key === 'Escape' && cmd.classList.contains('open')) close();
    });
    /* tapping the header search icon on phones opens the palette too */
    $$('.mk-search-toggle').forEach(function (b) { b.addEventListener('click', function (e) { e.preventDefault(); e.stopImmediatePropagation(); open(); }, true); });
})();

/* ---- "Deliver to" city picker: remembered in this browser, prefills checkout / prescription forms ---- */
(function () {
    'use strict';
    var KEY = 'fx-city', modal = document.getElementById('fxLoc');
    function get() { try { return localStorage.getItem(KEY) || ''; } catch (e) { return ''; } }
    function set(v) { try { localStorage.setItem(KEY, v); } catch (e) { } }
    function paint() {
        var c = get();
        Array.prototype.forEach.call(document.querySelectorAll('[data-fx-loc-name]'), function (n) { if (c) n.textContent = c; });
        if (c) Array.prototype.forEach.call(document.querySelectorAll('input[name=city], [data-fx-city]'), function (i) { if (!i.value && i.tagName === 'INPUT') i.value = c; });
        if (modal) Array.prototype.forEach.call(modal.querySelectorAll('[data-fx-city-pick]'), function (b) { b.classList.toggle('on', b.dataset.fxCityPick === c); });
    }
    function open() { if (!modal) return; modal.hidden = false; document.body.classList.add('mk-lock'); var f = modal.querySelector('button[data-fx-city-pick], input'); f && f.focus(); }
    function close() { if (!modal) return; modal.hidden = true; document.body.classList.remove('mk-lock'); }
    paint();
    if (!modal) return;
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-fx-loc]')) { e.preventDefault(); open(); return; }
        var pick = e.target.closest('[data-fx-city-pick]');
        if (pick) { set(pick.dataset.fxCityPick); paint(); close(); return; }
        if (e.target.closest('[data-fx-close]') || e.target === modal) close();
    });
    var form = modal.querySelector('[data-fx-city-form]');
    form.addEventListener('submit', function (e) { e.preventDefault(); var v = form.querySelector('input').value.trim(); if (v) { set(v); paint(); close(); } });
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !modal.hidden) close(); });
})();

/* ---- recently viewed (this device only) ---- */
(function () {
    'use strict';
    var KEY = 'fx-recent', MAX = 12;
    function load() { try { var v = JSON.parse(localStorage.getItem(KEY) || '[]'); return Array.isArray(v) ? v : []; } catch (e) { return []; } }
    function save(v) { try { localStorage.setItem(KEY, JSON.stringify(v.slice(0, MAX))); } catch (e) { } }
    var cur = document.querySelector('[data-fx-viewed]');
    if (cur) {
        try { var p = JSON.parse(cur.getAttribute('data-fx-viewed')); if (p && p.id) save([p].concat(load().filter(function (x) { return x.id !== p.id; }))); } catch (e) { }
    }
    var box = document.querySelector('[data-fx-recent]');
    if (!box) return;
    function paint() {
        var skip = box.getAttribute('data-current'), list = load().filter(function (x) { return String(x.id) !== String(skip); }).slice(0, 10);
        var row = box.querySelector('[data-fx-recent-row]'); row.innerHTML = '';
        if (list.length < 1) { box.hidden = true; return; }
        var ids = list.map(function (x) { return x.id; }).join(',');
        fetch(box.getAttribute('data-url') + '?ids=' + encodeURIComponent(ids), { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { if (!r.ok) throw 0; return r.text(); })
            .then(function (html) { if (!html.trim()) { box.hidden = true; return; } row.innerHTML = html; box.hidden = false; row.dispatchEvent(new Event('scroll')); window.dispatchEvent(new Event('resize')); })
            .catch(function () { box.hidden = true; });
    }
    paint();
    var clr = box.querySelector('[data-fx-recent-clear]');
    if (clr) clr.addEventListener('click', function () { save([]); paint(); });
})();

/* ---- install as an app (Android / desktop prompt; iPhone gets instructions) ---- */
(function () {
    'use strict';
    var btns = Array.prototype.slice.call(document.querySelectorAll('[data-fx-install]'));
    if (!btns.length) return;
    var standalone = (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || window.navigator.standalone;
    if (standalone) return;
    var ev = null, ios = /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream;
    function show() { btns.forEach(function (b) { b.hidden = false; }); }
    window.addEventListener('beforeinstallprompt', function (e) { e.preventDefault(); ev = e; show(); });
    window.addEventListener('appinstalled', function () { btns.forEach(function (b) { b.hidden = true; }); ev = null; });
    if (ios) show();
    function iosHelp() {
        var m = document.createElement('div'); m.className = 'fx-modal'; m.setAttribute('role', 'dialog'); m.setAttribute('aria-modal', 'true');
        m.innerHTML = '<div class="fx-modal-box"><button type="button" class="fx-modal-x" aria-label="Close"><i class="bi bi-x-lg"></i></button><span class="fx-modal-ic"><i class="bi bi-phone"></i></span><h3>Install on your iPhone</h3><p>Tap the <b>Share</b> button <i class="bi bi-box-arrow-up"></i> in Safari, then choose <b>Add to Home Screen</b>.</p></div>';
        document.body.appendChild(m);
        function close() { m.remove(); }
        m.addEventListener('click', function (e) { if (e.target === m || e.target.closest('.fx-modal-x')) close(); });
    }
    btns.forEach(function (b) {
        b.addEventListener('click', function () {
            if (ev) { ev.prompt(); ev.userChoice.then(function () { ev = null; btns.forEach(function (x) { x.hidden = true; }); }); }
            else if (ios) iosHelp();
        });
    });
})();

/* ---- Saved (wishlist) and Compare toggles on cards, product page, saved and compare pages ---- */
(function () {
    'use strict';
    var csrf = (document.querySelector('meta[name=csrf-token]') || {}).content || '';
    function toast(msg, bad) {
        var box = document.getElementById('sfToasts'); if (!box) return;
        var el = document.createElement('div'); el.className = 'nx-toast ' + (bad ? 'bad' : 'ok'); el.setAttribute('role', 'status');
        el.innerHTML = '<i class="bi ' + (bad ? 'bi-exclamation-octagon-fill' : 'bi-check-circle-fill') + '"></i><div></div>'; el.lastChild.textContent = msg;
        box.appendChild(el); setTimeout(function () { el.style.transition = 'all .3s'; el.style.opacity = 0; setTimeout(function () { el.remove(); }, 300); }, 3200);
    }
    function post(url) {
        return fetch(url, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': csrf } })
            .then(function (r) { if (!r.ok) throw new Error('error'); return r.json(); });
    }
    function paint(sel, id, on, attr) {
        Array.prototype.forEach.call(document.querySelectorAll('[' + attr + '="' + id + '"]'), function (b) { b.classList.toggle('on', on); b.setAttribute('aria-pressed', on ? 'true' : 'false'); });
    }
    function counts(sel, n) { Array.prototype.forEach.call(document.querySelectorAll(sel), function (c) { c.textContent = n; c.hidden = !n; }); }
    document.addEventListener('click', function (e) {
        var w = e.target.closest('[data-wish]');
        if (w) {
            e.preventDefault(); var id = w.getAttribute('data-wish');
            post('/saved/' + id).then(function (r) {
                paint('', id, r.added, 'data-wish'); counts('[data-wish-count]', r.count);
                toast(r.added ? 'Saved' : 'Removed from saved');
                if (!r.added && document.body.dataset.page === 'saved') { var c = w.closest('[data-saved-item]'); if (c) c.remove(); if (!document.querySelector('[data-saved-item]')) location.reload(); }
            }).catch(function () { toast('Something went wrong', true); });
            return;
        }
        var c2 = e.target.closest('[data-compare-toggle]');
        if (c2) {
            e.preventDefault(); var cid = c2.getAttribute('data-compare-toggle');
            post('/compare/' + cid).then(function (r) {
                if (r.full) { toast('You can compare up to ' + r.max + ' products', true); return; }
                paint('', cid, r.added, 'data-compare-toggle'); counts('[data-compare-count]', r.count);
                toast(r.added ? 'Added to compare' : 'Removed from compare');
                if (c2.hasAttribute('data-compare-remove')) location.reload();
            }).catch(function () { toast('Something went wrong', true); });
        }
    });
})();
