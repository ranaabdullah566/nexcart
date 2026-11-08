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
        $$('.mk-card, .mk-tile, .mk-step, .mk-quote', sec).forEach(function (el, i) { el.style.setProperty('--i', i % 8); });
    });
    /* sections that are already in view on load (and fallback when IntersectionObserver is missing) */
    if (!('IntersectionObserver' in window)) $$('.sf-reveal').forEach(function (e) { e.classList.add('in'); });

    /* ---- spotlight (all glass cards) + 3D tilt (product cards) ---- */
    if (fine && !reduce) {
        document.addEventListener('pointermove', function (e) {
            var t = e.target.closest && e.target.closest('.mk-card, .mk-tile, .mk-step, .mk-quote, .sf-cat, .mk-summary');
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
    function render(q, res) {
        if (!res.length) { list.innerHTML = '<div class="fx-cmd-empty"><i class="bi bi-emoji-neutral fs-3 d-block mb-2"></i>' + esc('No results for “' + q + '”') + '</div>'; sel = -1; return; }
        list.innerHTML = '<div class="fx-cmd-h">Medicines</div>' + res.map(function (r) {
            return '<a class="fx-cmd-item" href="' + esc(r.url) + '">' + (r.image ? '<img src="' + esc(r.image) + '" alt="" loading="lazy">' : '<span class="ph"><i class="bi bi-capsule"></i></span>') + '<span><b>' + esc(r.name) + '</b><small>' + esc(r.category || '') + '</small></span><span class="go figure">' + esc(r.price || '') + '</span></a>';
        }).join('') + '<a class="fx-cmd-item" href="/shop?q=' + encodeURIComponent(q) + '"><span class="ph"><i class="bi bi-search"></i></span><b>See all results for “' + esc(q) + '”</b><i class="bi bi-arrow-right go"></i></a>';
        mark(0);
    }
    function search(q) {
        if (!suggestUrl) return;
        fetch(suggestUrl + (suggestUrl.indexOf('?') > -1 ? '&' : '?') + 'q=' + encodeURIComponent(q), { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); }).then(function (d) { if (inp.value.trim() === q) render(q, d.results || []); }).catch(function () { });
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
