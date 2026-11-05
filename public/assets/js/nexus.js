/* ==========================================================================
   NEXUS UI — shared behaviour
   1. Searchable dropdowns (Select2) everywhere, including inside popups
   2. Popup (modal) system: detail / add / edit pages open in a dialog,
      forms submit via AJAX with inline validation errors
   3. Header clock, toasts
   ========================================================================== */
(function ($) {
    'use strict';

    /* ---------- Toasts ---------- */
    window.nxToast = function (message, type) {
        var box = document.getElementById('nxToasts');
        if (!box) return;
        var t = document.createElement('div');
        t.className = 'nx-toast ' + (type === 'ok' ? 'ok' : 'bad');
        t.innerHTML = '<i class="bi bi-' + (type === 'ok' ? 'check-circle-fill' : 'exclamation-octagon-fill') + '"></i><div></div><button class="x" type="button" aria-label="Close"><i class="bi bi-x-lg"></i></button>';
        t.children[1].textContent = message;
        box.appendChild(t);
        var close = function () { t.style.transition = 'all .3s'; t.style.opacity = 0; t.style.transform = 'translateX(30px)'; setTimeout(function () { t.remove(); }, 300); };
        t.querySelector('.x').addEventListener('click', close);
        setTimeout(close, 6000);
    };

    /* ---------- Live clock: "Sun 25 Oct, 03:55:42 PM" (ticks every second) ---------- */
    function pad(n) { return (n < 10 ? '0' : '') + n; }
    /* The store's time zone (Settings → Store; Pakistan by default) no matter what the visitor's computer or the server is set to */
    var TZ = (document.querySelector('meta[name="nx-tz"]') || {}).content || 'Asia/Karachi';
    window.NX_TZ = TZ;
    var TZ_NAME = window.NX_TZ_NAME = (function () { try { return (new Intl.DateTimeFormat('en-GB', { timeZone: TZ, timeZoneName: 'short' }).formatToParts(new Date()).find(function (p) { return p.type === 'timeZoneName'; }) || {}).value || ''; } catch (e) { return ''; } })();
    var PK = new Intl.DateTimeFormat('en-GB', { timeZone: TZ, hourCycle: 'h23', weekday: 'short', year: 'numeric', month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit', second: '2-digit' });
    var PK_NUM = new Intl.DateTimeFormat('en-CA', { timeZone: TZ, year: 'numeric', month: '2-digit', day: '2-digit' });
    window.nxNow = function (date) {
        var o = {}, d = date || new Date();
        PK.formatToParts(d).forEach(function (p) { o[p.type] = p.value; });
        return { wd: o.weekday, day: parseInt(o.day, 10), mon: o.month, year: o.year, h24: parseInt(o.hour, 10) % 24, min: o.minute, sec: o.second, iso: PK_NUM.format(d) };
    };
    /* Date / time format chosen in Settings (meta nx-fmt = "d M Y|12") */
    var FMT = (((document.querySelector('meta[name="nx-fmt"]') || {}).content) || 'd M Y|12').split('|');
    var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    /** iso "2026-10-30" -> "30 Oct 2026" / "30/10/2026" … ; withYear=false gives the short form without the year */
    window.nxFormatDate = function (iso, withYear) {
        var p = String(iso).slice(0, 10).split('-'), y = p[0], m = p[1], d = p[2], mon = MON[parseInt(m, 10) - 1] || m;
        switch (FMT[0]) {
            case 'd/m/Y': return withYear ? d + '/' + m + '/' + y : d + '/' + m;
            case 'd-m-Y': return withYear ? d + '-' + m + '-' + y : d + '-' + m;
            case 'm/d/Y': return withYear ? m + '/' + d + '/' + y : m + '/' + d;
            case 'Y-m-d': return withYear ? y + '-' + m + '-' + d : m + '-' + d;
            case 'M d, Y': return withYear ? mon + ' ' + d + ', ' + y : mon + ' ' + d;
            default: return withYear ? d + ' ' + mon + ' ' + y : d + ' ' + mon;
        }
    };
    /** 18 , 5 -> "06:05 PM" or "18:05" */
    window.nxFormatTime = function (h24, min) {
        min = pad(parseInt(min, 10));
        if (FMT[1] === '24') return pad(h24) + ':' + min;
        return pad(h24 % 12 || 12) + ':' + min + ' ' + (h24 >= 12 ? 'PM' : 'AM');
    };
    window.nxClockHtml = function () {
        var n = window.nxNow(), t = window.nxFormatTime(n.h24, n.min), ap = / [AP]M$/.test(t) ? t.slice(-3) : '';
        return '<span class="cw">' + n.wd + ' </span>' + window.nxFormatDate(n.iso, false) + ', ' + (ap ? t.slice(0, -3) : t) + '<span class="cs">:' + n.sec + '</span>' + ap;
    };
    $(function () {
        var el = document.getElementById('nxClock');
        if (!el) return;
        var tick = function () { el.innerHTML = window.nxClockHtml(); };
        tick();
        setInterval(tick, 1000);
        // browsers throttle timers in background tabs — refresh the moment the tab is visible again
        document.addEventListener('visibilitychange', function () { if (!document.hidden) tick(); });
        window.addEventListener('focus', tick);
    });

    /* ---------- Phone tables: every row becomes a card, so nothing is hidden or cut off ---------- */
    function labelize(table) {
        var heads = [].map.call(table.querySelectorAll('thead th'), function (th) { return th.textContent.trim(); });
        table.querySelectorAll('tbody tr').forEach(function (tr) {
            if (tr.classList.contains('child')) return;
            [].forEach.call(tr.children, function (td, i) {
                if (td.tagName === 'TD' && !td.hasAttribute('colspan')) td.setAttribute('data-label', heads[i] || '');
            });
        });
    }
    $(function () { document.querySelectorAll('table.nx-cards').forEach(labelize); });
    $(document).on('init.dt draw.dt', function (e, settings) {
        if (!settings || !settings.nTable) return;
        settings.nTable.classList.add('nx-cards');
        labelize(settings.nTable);
    });

    /* ---------- Searchable selects ---------- */
    function infoOf(d) { return d && d.element ? (d.element.getAttribute('data-info') || '') : ''; }
    function infoTpl(d) {
        var info = infoOf(d); if (!d.id || !info) return d.text;
        var $r = $('<span class="nx-opt"></span>'); $r.append($('<span class="nx-opt-name"></span>').text(d.text)); $r.append($('<small class="nx-opt-info"></small>').text(info)); return $r;
    }
    function infoSel(d) {
        var info = infoOf(d); if (!d.id || !info) return d.text;
        if (d.element && d.element.parentNode && d.element.parentNode.multiple) return d.text;   // tags stay short: name only
        // one line: "Name  phone · address", cut with … when the box is narrow (full text in the tooltip)
        var $r = $('<span class="nx-sel-line"></span>').attr('title', d.text + ' — ' + info).text(d.text); $r.append($('<small class="nx-opt-info"></small>').text(info)); return $r;
    }
    function infoMatch(params, d) {
        var t = $.trim(params.term || ''); if (t === '') return d;
        var hay = ((d.text || '') + ' ' + infoOf(d)).toLowerCase();
        return hay.indexOf(t.toLowerCase()) > -1 ? d : null;
    }
    /** Dropdown options in alphabetical order (a leading "All / Select…" option stays first). Add data-keep-order to a select whose order has a meaning. */
    function sortSelect(sel) {
        if (sel.hasAttribute('data-keep-order') || sel.closest('.dataTables_length') || sel.querySelector('optgroup') || sel.options.length < 3) return;
        var opts = [].slice.call(sel.options), head = opts[0].value === '' ? [opts.shift()] : [];
        var sorted = opts.slice().sort(function (a, b) { return a.text.trim().localeCompare(b.text.trim(), undefined, { numeric: true, sensitivity: 'base' }); });
        if (sorted.every(function (o, i) { return o === opts[i]; })) return;
        var chosen = [].slice.call(sel.options).map(function (o) { return o.selected; }), all = [].slice.call(sel.options);
        head.concat(sorted).forEach(function (o) { sel.appendChild(o); });
        all.forEach(function (o, i) { o.selected = chosen[i]; });   // keep what was chosen, even when nothing was marked explicitly
    }
    window.nxSortSelect = sortSelect;

    /** (i) beside the title of every dropdown: hover to see how to use it (search, pick, close). */
    function addSelectHint(sel) {
        if (!(window.bootstrap && bootstrap.Tooltip) || (window.matchMedia && matchMedia('(hover: none)').matches)) return;
        var lab = sel.id && document.querySelector('label[for="' + sel.id + '"]');
        if (!lab) { var box = sel.closest('[class*="col-"], .mb-3, .mb-4, .form-group'); lab = box && box.querySelector('.form-label'); }
        if (!lab || lab.querySelector('.nx-kbd-i')) return;
        var i = document.createElement('i');
        i.className = 'bi bi-info-circle nx-kbd-i'; i.setAttribute('role', 'img'); i.setAttribute('aria-label', 'How to use this list');
        lab.appendChild(i);
        var kb = function (t) { return '<span class="kt-keys"><kbd>' + t + '</kbd></span>'; };
        new bootstrap.Tooltip(i, { title: '<span class="kt-label">Search this list</span>' + kb('Type') + '<span class="kt-label">pick</span>' + kb('↑') + kb('↓') + kb('Enter') + '<span class="kt-label">close</span>' + kb('Esc'),
            html: true, sanitize: false, trigger: 'hover', placement: 'top', container: 'body', customClass: 'nx-kbd-tip', delay: { show: 100, hide: 0 } });
    }

    function initSelects(root) {
        $(root).find('select').each(function () { sortSelect(this); });
        $(root).find('select').each(function () {
            var $s = $(this);
            if ($s.hasClass('select2-hidden-accessible') || $s.is('[data-no-select2]') || $s.closest('.dataTables_length').length) return;
            if ($s.closest('template').length) return;
            var $modal = $s.closest('.modal');
            var hasEmpty = $s.find('option[value=""]').length > 0;
            $s.select2({
                width: '100%',
                dropdownParent: $modal.length ? $modal : $(document.body),
                placeholder: $s.data('placeholder') || (hasEmpty ? $s.find('option[value=""]').first().text() : undefined),
                allowClear: hasEmpty && !$s.prop('required'),
                minimumResultsForSearch: 0,
                closeOnSelect: !$s.prop('multiple'),
                // options may carry data-info (phone, address, role …): shown next to the name and searchable
                templateResult: infoTpl, templateSelection: infoSel,
                matcher: infoMatch
            });
            addSelectHint(this);
        });
    }
    window.nxInitSelects = initSelects;
    $(function () { initSelects(document); });
    // selects that other scripts fill in later
    $(document).on('shown.bs.modal', function (e) { initSelects(e.target); });

    // Select2 search box must be able to take focus inside Bootstrap modals
    $(document).on('select2:open', function () {
        setTimeout(function () { var f = document.querySelector('.select2-container--open .select2-search__field'); if (f) f.focus(); }, 0);
    });

    // Re-measure responsive tables once fonts/layout have settled
    $(window).on('load', function () {
        var run = function () {
            if (!$.fn.dataTable) return;
            var api = $.fn.dataTable.tables({ api: true });
            if (api.responsive && api.responsive.recalc) api.responsive.recalc();
        };
        // wait for idle time (and web fonts) so this never competes with the first render
        var idle = window.requestIdleCallback || function (fn) { return setTimeout(fn, 300); };
        ((document.fonts && document.fonts.ready) || Promise.resolve()).then(function () { idle(run); });
    });


    /* ---------- Confirmation popup (replaces window.confirm) ---------- */
    window.nxConfirm = function (opts) {
        opts = opts || {};
        var tone = opts.tone || 'danger';
        return new Promise(function (resolve) {
            var wrap = document.createElement('div');
            wrap.className = 'nx-confirm';
            wrap.setAttribute('role', 'alertdialog');
            wrap.setAttribute('aria-modal', 'true');
            var icon = tone === 'danger' ? 'trash3-fill' : (tone === 'ok' ? 'check2-circle' : 'question-circle-fill');
            wrap.innerHTML =
                '<div class="nx-confirm-card ' + tone + '">' +
                '<div class="nx-confirm-ico"><i class="bi bi-' + icon + '"></i></div>' +
                '<h5 class="nx-confirm-title"></h5>' +
                '<p class="nx-confirm-msg"></p>' +
                (opts.note ? '<p class="nx-confirm-note"><i class="bi bi-recycle"></i><span></span></p>' : '') +
                '<div class="nx-confirm-actions"><button type="button" class="btn btn-outline-secondary" data-act="no"></button>' +
                '<button type="button" class="btn ' + (tone === 'danger' ? 'btn-danger' : 'btn-primary') + '" data-act="yes"></button></div></div>';
            wrap.querySelector('.nx-confirm-title').textContent = opts.title || 'Are you sure?';
            wrap.querySelector('.nx-confirm-msg').textContent = opts.message || '';
            if (opts.note) wrap.querySelector('.nx-confirm-note span').textContent = opts.note;
            wrap.querySelector('[data-act=no]').innerHTML = '<span></span> <kbd class="nx-kbd-inline">N</kbd>';
            wrap.querySelector('[data-act=no] span').textContent = opts.cancelText || 'Cancel';
            wrap.querySelector('[data-act=yes]').innerHTML = '<span></span> <kbd class="nx-kbd-inline">Y</kbd>';
            wrap.querySelector('[data-act=yes] span').textContent = opts.confirmText || 'Confirm';
            wrap.querySelector('[data-act=no]').title = 'Cancel — press N or Esc'; wrap.querySelector('[data-act=yes]').title = 'Confirm — press Y';
            document.body.appendChild(wrap);
            document.body.classList.add('nx-confirm-open');
            requestAnimationFrame(function () { wrap.classList.add('show'); });
            var yes = wrap.querySelector('[data-act=yes]'), no = wrap.querySelector('[data-act=no]');
            no.focus();

            function done(v) {
                document.removeEventListener('keydown', onKey, true);
                wrap.classList.remove('show');
                document.body.classList.remove('nx-confirm-open');
                setTimeout(function () { wrap.remove(); }, 200);
                resolve(v);
            }
            function onKey(e) {
                if (e.key === 'Escape' || e.key === 'n' || e.key === 'N') { e.preventDefault(); e.stopPropagation(); done(false); }
                if (e.key === 'y' || e.key === 'Y') { e.preventDefault(); e.stopPropagation(); done(true); }
                if (e.key === 'Tab') { // keep focus inside the dialog
                    e.preventDefault(); (document.activeElement === yes ? no : yes).focus();
                }
            }
            document.addEventListener('keydown', onKey, true);
            yes.addEventListener('click', function () { done(true); });
            no.addEventListener('click', function () { done(false); });
            wrap.addEventListener('mousedown', function (e) { if (e.target === wrap) done(false); });
        });
    };

    // <form data-confirm="Delete this?"> asks first, then submits natively
    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!form.matches || !form.hasAttribute('data-confirm') || form.dataset.confirmed === '1') return;
        e.preventDefault();
        e.stopImmediatePropagation();
        var isDelete = (form.querySelector('[name="_method"]') || {}).value === 'DELETE';
        window.nxConfirm({
            title: form.dataset.confirmTitle || (isDelete ? 'Delete this record?' : 'Please confirm'),
            message: form.dataset.confirm,
            confirmText: form.dataset.confirmButton || (isDelete ? 'Yes, delete' : 'Confirm'),
            tone: form.dataset.confirmTone || (isDelete ? 'danger' : 'primary'),
            note: form.dataset.confirmNote !== undefined ? form.dataset.confirmNote : (isDelete ? 'This is a soft delete — the record is moved to the Recycle Bin and can be restored.' : '')
        }).then(function (ok) {
            if (!ok) return;
            form.dataset.confirmed = '1';
            HTMLFormElement.prototype.submit.call(form);
        });
    }, true);

    /* ---------- Password show / hide on every password field ---------- */
    function initPasswords(root) {
        $(root).find('input[type=password]').each(function () {
            if (this.dataset.nxEye || this.hasAttribute('data-no-eye') || $(this).closest('.lg-input').length) return;
            this.dataset.nxEye = '1';
            var wrap = document.createElement('div');
            wrap.className = 'nx-pass';
            this.parentNode.insertBefore(wrap, this);
            wrap.appendChild(this);
            var btn = document.createElement('button');
            btn.type = 'button'; btn.className = 'nx-pass-btn'; btn.tabIndex = -1;
            btn.setAttribute('aria-label', 'Show password');
            btn.innerHTML = '<i class="bi bi-eye"></i>';
            wrap.appendChild(btn);
            var input = this;
            btn.addEventListener('click', function () {
                var show = input.type === 'password';
                input.type = show ? 'text' : 'password';
                btn.firstChild.className = 'bi bi-' + (show ? 'eye-slash' : 'eye');
                btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                input.focus();
            });
        });
    }
    window.nxInitPasswords = initPasswords;
    $(function () { initPasswords(document); });

    /* ---------- DataTables: shared defaults + auto-init for <table class="nx-dt"> ---------- */
    if ($.fn.dataTable) {
        $.extend(true, $.fn.dataTable.defaults, {
            autoWidth: false,   // skip the expensive clone-and-measure pass; CSS table layout sizes the columns
            deferRender: true,
            language: {
                paginate: {
                    previous: '<i class="bi bi-chevron-left"></i><span class="pg-txt">Previous</span>',
                    next: '<span class="pg-txt">Next</span><i class="bi bi-chevron-right"></i>'
                },
                emptyTable: 'No records to show',
                zeroRecords: 'No matching records found'
            }
        });
    }
    window.nxInitTables = function (root) {
        if (!$.fn.dataTable) return;
        $(root).find('table.nx-dt').each(function () {
            if ($.fn.dataTable.isDataTable(this) || this.querySelector('tbody td[colspan]')) return; // empty-state row: nothing to page
            var $t = $(this), noSort = [];
            $t.addClass('w-100');
            $t.find('thead th').each(function (i) { if (!this.textContent.trim() || this.hasAttribute('data-no-sort')) noSort.push(i); });
            $t.DataTable({
                responsive: true, order: [], pageLength: parseInt($t.data('page-length'), 10) || 10, lengthMenu: [5, 8, 10, 25, 50, 100],
                columnDefs: [{ orderable: false, targets: noSort }, { responsivePriority: 1, targets: 0 }, { responsivePriority: 2, targets: -1 }],
                language: { search: '_INPUT_', searchPlaceholder: 'Search…' }
            });
        });
    };
    $(function () { window.nxInitTables(document); });

    /* ---------- Popup system ---------- */
    var RES = /\/admin\/(products|roles|customers|staff|categories|brands|attributes|modifier-groups|home-sections|coupons|shipping|prescriptions|delivery-slots|pages|menu-items|reviews)\/(create|\d+|\d+\/edit)$/;
    var VIEW_ONLY = /\/admin\/orders\/\d+$/; // order detail opens as a popup
    var SUB = /\/admin\/(inventory\/adjust|orders\/\d+\/(refund|fulfil))$/;

    function isPopupUrl(href) {
        var u;
        try { u = new URL(href, location.href); } catch (e) { return false; }
        if (u.origin !== location.origin) return false;
        var p = u.pathname.replace(/\/+$/, '');
        return RES.test(p) || SUB.test(p) || VIEW_ONLY.test(p);
    }

    var modalEl, modal, bodyEl, dlg, current = { url: null, injected: [] };

    function ensure() {
        if (modal) return;
        modalEl = document.getElementById('nxModal');
        if (!modalEl) return;
        modal = new bootstrap.Modal(modalEl);
        bodyEl = document.getElementById('nxModalBody');
        dlg = modalEl.querySelector('.modal-dialog');
        modalEl.addEventListener('hidden.bs.modal', function () {
            bodyEl.innerHTML = '';
            current.url = null;
        });
        modalEl.addEventListener('shown.bs.modal', function () {
            var first = bodyEl.querySelector('input:not([type=hidden]):not([readonly]):not([disabled]), textarea');
            if (first && !first.matches('[type=date]')) { try { first.focus(); } catch (e) {} }
        });
    }

    function sizeFor(url) {
        var big = /\/admin\/(orders|products|roles)\//.test(url);
        dlg.className = 'modal-dialog modal-dialog-centered modal-dialog-scrollable ' + (big ? 'modal-xl modal-fullscreen-lg-down' : 'modal-lg modal-fullscreen-md-down');
    }

    function setHead(eyebrow, title) {
        document.getElementById('nxModalEyebrow').textContent = eyebrow || '';
        document.getElementById('nxModalTitle').textContent = title || '';
    }

    function spinner() {
        bodyEl.innerHTML = '<div class="nx-modal-loading"><div class="nx-spin"></div><span>Loading…</span></div>';
    }

    function fail(msg) {
        bodyEl.innerHTML = '<div class="nx-empty"><i class="bi bi-exclamation-octagon"></i>' + msg + '</div>';
    }

    function loadScripts(doc) {
        var marker = doc.getElementById('nxPageScripts');
        var list = [];
        for (var n = marker ? marker.nextElementSibling : null; n; n = n.nextElementSibling) if (n.tagName === 'SCRIPT') list.push(n);

        // styles pushed by the page
        doc.head.querySelectorAll('style').forEach(function (st) {
            var txt = st.textContent;
            if (!txt || current.injected.indexOf(txt) !== -1) return;
            current.injected.push(txt);
            var s = document.createElement('style'); s.textContent = txt; document.head.appendChild(s);
        });

        return list.reduce(function (p, sc) {
            return p.then(function () {
                if (sc.src) {
                    if (document.querySelector('script[src="' + sc.getAttribute('src') + '"]')) return;
                    return new Promise(function (res) { var s = document.createElement('script'); s.src = sc.src; s.onload = s.onerror = res; document.body.appendChild(s); });
                }
                var s = document.createElement('script');
                var nm = document.querySelector('meta[name="csp-nonce"]'); if (nm) s.nonce = nm.content;   // allowed by the page's CSP
                s.textContent = '(function(){\n' + sc.textContent + '\n}).call(window);';
                bodyEl.appendChild(s);
            });
        }, Promise.resolve());
    }

    function open(url) {
        ensure();
        if (!modal) { location.href = url; return; }
        current.url = url;
        sizeFor(url);
        setHead('', 'Loading…');
        spinner();
        modal.show();

        fetch(url, { credentials: 'same-origin', headers: { 'X-Requested-With': 'XMLHttpRequest', 'X-NX-Modal': '1' } })
            .then(function (r) {
                if (r.redirected && new URL(r.url).pathname === '/') { location.href = r.url; return null; }   // sent back to the sign-in page
                if (r.status === 403) { setHead('', 'Not allowed'); fail('You do not have permission to open this.'); return null; }
                if (!r.ok) { setHead('', 'Error'); fail('Could not load this page (' + r.status + ').'); return null; }
                return r.text();
            })
            .then(function (html) {
                if (!html) return;
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var content = doc.querySelector('.nx-content');
                if (!content) { fail('Unexpected response.'); return; }

                var eyebrow = (content.querySelector('.nx-eyebrow') || {}).textContent;
                var title = (content.querySelector('.nx-title') || {}).textContent || doc.title;
                bodyEl.dataset.printSub = ((content.querySelector('.nx-sub') || {}).textContent || '').trim();
                var psub = doc.getElementById('nxPrintSubSrc'); if (psub) bodyEl.dataset.printSub = psub.textContent.trim();
                var head = content.querySelector('.nx-head');
                if (head) {
                    var back = head.querySelector('.nx-back'); if (back) back.remove();
                    var left = head.firstElementChild; if (left) left.remove();
                    if (!head.textContent.trim() && !head.querySelector('a,button')) head.remove();
                    else head.classList.add('nx-head-compact');
                }
                setHead(eyebrow, title);
                bodyEl.innerHTML = content.innerHTML;

                // Cancel links close the popup
                bodyEl.querySelectorAll('form a.btn').forEach(function (a) {
                    if (/cancel/i.test(a.textContent)) { a.setAttribute('href', '#'); a.setAttribute('data-bs-dismiss', 'modal'); }
                });
                initSelects(bodyEl);
                initPasswords(bodyEl);
                window.nxInitTables(bodyEl);
                return loadScripts(doc).then(function () { initSelects(bodyEl); });
            })
            .catch(function () { setHead('', 'Error'); fail('Network error. Please try again.'); });
    }
    window.nxOpenModal = open;

    // Intercept clicks on links that point to a detail / create / edit page
    $(document).on('click', 'a[href]', function (e) {
        if (e.ctrlKey || e.metaKey || e.shiftKey || e.which === 2) return;
        var a = this;
        if (a.target === '_blank' || a.hasAttribute('data-no-modal') || a.classList.contains('nx-back')) return;
        var href = a.getAttribute('href');
        if (!href || href.charAt(0) === '#' || !isPopupUrl(a.href)) return;
        e.preventDefault();
        open(a.href);
    });

    /* ---------- AJAX form submit inside popup ---------- */
    function clearErrors(form) {
        $(form).find('.is-invalid').removeClass('is-invalid');
        $(form).find('.nx-field-error').remove();
        $('#nxModalBody .nx-form-alert').remove();
    }

    function fieldFor(form, key) {
        if (window.nxFieldFor) { var f = window.nxFieldFor(form, key); if (f) return f; }
        var parts = key.split('.');
        var name = parts[0], idx = parts.length > 1 ? parseInt(parts[1], 10) : null;
        var sel = idx === null ? '[name="' + name + '"]' : '[name="' + name + '[]"], [name="' + name + '[' + parts[1] + ']"]';
        var els = form.querySelectorAll(sel);
        if (!els.length) return null;
        return idx !== null && els.length > idx && form.querySelectorAll('[name="' + name + '[]"]').length ? els[idx] : els[0];
    }

    function showErrors(form, errors) {
        var all = [];
        Object.keys(errors).forEach(function (k) {
            var msg = errors[k][0]; all.push(msg);
            var el = fieldFor(form, k);
            if (!el) return;
            var $el = $(el); $el.addClass('is-invalid');
            var msgEl = $('<div class="invalid-feedback d-block nx-field-error"></div>').text(msg);
            var s2 = $el.next('.select2-container');
            (s2.length ? s2 : $el).after(msgEl);
        });
        var alert = $('<div class="nx-form-alert"><i class="bi bi-exclamation-triangle-fill"></i><div><strong>Please fix the following</strong><ul></ul></div></div>');
        all.forEach(function (m) { alert.find('ul').append($('<li></li>').text(m)); });
        $('#nxModalBody').prepend(alert);
        var first = form.querySelector('.is-invalid');
        if (first) { var pane = $(first).closest('.tab-pane'); if (pane.length && !pane.hasClass('active')) { var tab = document.querySelector('[data-bs-target="#' + pane.attr('id') + '"]'); if (tab) bootstrap.Tab.getOrCreateInstance(tab).show(); } }
        $('#nxModal .modal-body').scrollTop(0);
    }

    $(document).on('submit', '#nxModal form', function (e) {
        var form = this;
        var method = (form.querySelector('[name="_method"]') || {}).value;
        if (method === 'DELETE' || form.hasAttribute('data-no-ajax')) return; // normal submit (delete etc.)
        e.preventDefault();
        if (form.dataset.busy) return;
        form.dataset.busy = '1';
        clearErrors(form);
        var btn = form.querySelector('button[type=submit]');
        var original = btn ? btn.innerHTML : '';
        if (btn) { btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…'; }

        fetch(form.action, {
            method: 'POST', body: new FormData(form), credentials: 'same-origin', redirect: 'manual',
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then(function (r) {
            if (r.type === 'opaqueredirect' || (r.status >= 200 && r.status < 400)) {
                if (/\/roles(\/|$)/.test(form.action)) location.href = location.pathname + '?tab=roles'; else location.reload();
                return;
            }
            return r.json().catch(function () { return {}; }).then(function (j) {
                if (r.status === 422 && j.errors) showErrors(form, j.errors);
                else window.nxToast(j.message || ('Request failed (' + r.status + ')'), 'bad');
            });
        }).catch(function () {
            window.nxToast('Network error. Please try again.', 'bad');
        }).finally(function () {
            delete form.dataset.busy;
            if (btn && document.body.contains(btn)) { btn.disabled = false; btn.innerHTML = original; }
        });
    });

})(jQuery);

// ---------------------------------------------------------------------------
// Printing: header/footer text, every table row, light theme, fresh charts
// ---------------------------------------------------------------------------
(function () {
    var restore = [];
    function fill() {
        var modal = document.querySelector('#nxModal.show');
        var title, sub;
        if (modal) {
            title = (document.getElementById('nxModalTitle') || {}).textContent;
            var eb = (document.getElementById('nxModalEyebrow') || {}).textContent;
            sub = (document.getElementById('nxModalBody').dataset.printSub) || eb || '';
        } else {
            var t = document.querySelector('.nx-page-title'), s = document.getElementById('nxPrintSubSrc');
            title = (t || {}).textContent || document.title;
            sub = s ? s.textContent : '';
        }
        var set = function (id, v) { var e = document.getElementById(id); if (e) e.textContent = v || ''; };
        set('nxPrintTitle', (title || '').trim());
        set('nxPrintSub', (sub || '').trim());
        var n = window.nxNow(), h = n.h24 % 12 || 12;
        set('nxPrintStamp', window.nxFormatDate(n.iso, true) + ', ' + window.nxFormatTime(n.h24, n.min) + (window.NX_TZ_NAME ? ' (' + window.NX_TZ_NAME + ')' : ''));
    }
    window.addEventListener('beforeprint', function () {
        fill();
        var root = document.documentElement;
        restore.push(['theme', root.getAttribute('data-theme')]);
        root.setAttribute('data-theme', 'light'); root.setAttribute('data-bs-theme', 'light');
        if (window.jQuery && $.fn.dataTable) {
            $.fn.dataTable.tables({ api: true }).every(function () {
                var api = this, len = api.page.len();
                if (len !== -1) { restore.push(['dt', api, len]); api.page.len(-1).draw(false); }
                if (api.responsive && api.responsive.recalc) { try { api.responsive.recalc(); } catch (e) {} }
            });
        }
        if (window.Chart && window.nxRebuildCharts) {
            restore.push(['chart']);
            Chart.defaults.animation = false;
            window.nxRebuildCharts();
        }
    });
    window.addEventListener('afterprint', function () {
        var root = document.documentElement;
        restore.splice(0).forEach(function (r) {
            if (r[0] === 'theme') { root.setAttribute('data-theme', r[1]); root.setAttribute('data-bs-theme', r[1]); }
            else if (r[0] === 'dt') { r[1].page.len(r[2]).draw(false); }
            else if (r[0] === 'chart') { Chart.defaults.animation = {}; if (window.nxRebuildCharts) window.nxRebuildCharts(); }
        });
    });
})();

/* ---------- Accessibility: give every unlabeled control a name (from its visible label / placeholder / title) ---------- */
(function ($) {
    function nameControls(root) {
        $(root).find('input:not([type=hidden]):not([type=checkbox]):not([type=radio]), select, textarea').each(function () {
            if (this.getAttribute('aria-label') || this.getAttribute('aria-labelledby') || this.closest('label') || (this.id && document.querySelector('label[for="' + this.id + '"]'))) return;
            var $c = $(this), label = '';
            var $lab = $c.parent().find('> label, > .form-label').first();
            if (!$lab.length) $lab = $c.closest('.col, [class*=col-], .mb-3, .nx-field').find('label').first();
            label = $.trim($lab.text()) || this.getAttribute('placeholder') || this.getAttribute('title') || this.getAttribute('name') || '';
            if (label) this.setAttribute('aria-label', label.replace(/_/g, ' '));
        });
        // Select2 builds its own widgets: give them the select's name and drop the role axe rejects on <textarea>
        $(root).find('select.select2-hidden-accessible').each(function () {
            var name = this.getAttribute('aria-label') || '', $c = $(this).next('.select2-container');
            if (!name) { var $l = $(this).closest('.col, [class*=col-], .mb-3').find('label').first(); name = $.trim($l.text()); }
            if (!name) return;
            $c.find('.select2-selection').attr('aria-label', name);
            $c.find('.select2-search__field').removeAttr('role').attr('aria-label', name);
        });
        $('.select2-search__field[role]').removeAttr('role');
        $(root).find('button.x:not([aria-label])').attr('aria-label', 'Close');
        $(root).find('a:not([aria-label]), button:not([aria-label])').each(function () {
            if ($.trim($(this).text()) || $(this).find('img[alt]').length) return;
            var t = this.getAttribute('title') || this.getAttribute('data-bs-original-title');
            if (t) this.setAttribute('aria-label', t);
        });
    }
    window.nxNameControls = nameControls;
    $(function () { nameControls(document); });
    $(document).on('shown.bs.modal draw.dt nx:content select2:open', function () { nameControls(document); });
    $(window).on('load', function () { setTimeout(function () { nameControls(document); }, 200); });
})(jQuery);

/* ---------- Print buttons (CSP forbids inline onclick) ---------- */
document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-print]') : null;
    if (b) { e.preventDefault(); window.print(); }
});

/* ---------- Admin alerts bell (polls every 30 s, toasts new ones) ---------- */
(function ($) {
    var bell = document.getElementById('nxBell');
    if (!bell) return;
    var list = document.getElementById('nxBellList'), badge = document.getElementById('nxBellBadge'), seen = null, csrf = (document.querySelector('meta[name=csrf-token]') || {}).content;

    function token() { return csrf || ''; }
    function render(data) {
        badge.hidden = !data.unread; badge.textContent = data.unread > 99 ? '99+' : data.unread;
        var nb = document.getElementById('nxNavBadge'); if (nb) { nb.hidden = !data.unread; nb.textContent = data.unread > 99 ? '99+' : data.unread; }
        list.textContent = '';
        if (!data.items.length) { var e = document.createElement('div'); e.className = 'nx-bell-empty'; e.textContent = 'No alerts yet.'; list.appendChild(e); return; }
        data.items.forEach(function (n) {
            var row = document.createElement(n.url ? 'a' : 'div'); row.className = 'nx-alert-row' + (n.read ? '' : ' unread'); if (n.url) row.href = n.url;
            var ico = document.createElement('span'); ico.className = 'nx-alert-ico tone-' + n.tone; var i = document.createElement('i'); i.className = 'bi bi-' + n.icon; ico.appendChild(i);
            var box = document.createElement('div'); box.className = 'min-w-0 flex-grow-1';
            var t = document.createElement('div'); t.className = 't'; t.textContent = n.title;
            var b = document.createElement('div'); b.className = 'b'; b.textContent = n.body;
            var a = document.createElement('div'); a.className = 'a'; a.textContent = n.ago;
            box.appendChild(t); box.appendChild(b); box.appendChild(a); row.appendChild(ico); row.appendChild(box); list.appendChild(row);
        });
    }
    function poll() {
        if (document.hidden) return;
        fetch(bell.dataset.feed, { credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (d) {
                if (!d) return;
                if (seen !== null) d.items.filter(function (n) { return !n.read && seen.indexOf(n.id) === -1; }).slice(0, 3).forEach(function (n) { window.nxToast(n.title + ' — ' + n.body, n.tone === 'bad' ? 'bad' : 'ok'); });
                seen = d.items.map(function (n) { return n.id; });
                render(d);
            }).catch(function () {});
    }
    document.getElementById('nxBellReadAll').addEventListener('click', function () {
        fetch(bell.dataset.readAll, { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-CSRF-TOKEN': token() } }).then(poll);
    });
    poll(); setInterval(poll, 30000); document.addEventListener('visibilitychange', function () { if (!document.hidden) poll(); });
})(jQuery);

/* ==========================================================================
   Keyboard shortcuts (Windows-friendly: Alt + key, so they never clash with Ctrl+N / Ctrl+S / Ctrl+P of the browser).
   Every element that has a shortcut gets a tooltip that shows it, e.g.  New Closing  [Alt] + [N].
   Press  Alt+H  (or  ?  when not typing) for the full list.
   ========================================================================== */
(function ($) {
    'use strict';
    var CAT_KEYS = { sale: '1', venret: '2', cusret: '3', fpin: '4', cashdep: '5', exp: '6', ven: '7', cus: '8', fp: '9', onl: '0', owd: '-', adv: '=', sal: '[', tmpin: ']', tmpout: '\\' };
    var NAV_KEYS = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '-', '='];
    var current = {};          // combo -> { el, desc, group }
    var applied = new WeakMap();

    function comboOf(e) {
        var k = e.code || '', key;
        if (/^Key[A-Z]$/.test(k)) key = k.slice(3);
        else if (/^Digit\d$/.test(k)) key = k.slice(5);
        else if (k === 'Slash') key = e.shiftKey ? '?' : '/';
        else if (k === 'Minus') key = '-';
        else if (k === 'Equal') key = '=';
        else if (k === 'BracketLeft') key = '[';
        else if (k === 'BracketRight') key = ']';
        else if (k === 'Backslash') key = '\\';
        else if (k === 'Enter' || k === 'NumpadEnter') key = 'Enter';
        else if (k === 'Escape') key = 'Esc';
        else key = (e.key || '').length === 1 ? e.key.toUpperCase() : e.key;
        var m = [];
        if (e.ctrlKey) m.push('Ctrl'); if (e.altKey) m.push('Alt'); if (e.shiftKey && key !== '?') m.push('Shift');
        m.push(key);
        return m.join('+');
    }
    function visible(el) { return !!(el && el.isConnected && (el.offsetWidth || el.offsetHeight || el.getClientRects().length) && !el.disabled); }
    function labelOf(el) {
        var t = el.getAttribute('data-kbd-label') || el.getAttribute('data-bs-title') || el.getAttribute('aria-label') || el.getAttribute('title') || el.textContent || '';
        return t.replace(/\s*\([^)]*\)\s*$/, '').replace(/\s+/g, ' ').trim();
    }
    function add(combo, el, desc, group) {
        if (!el || current[combo]) return;          // first match wins
        current[combo] = { el: el, desc: desc || labelOf(el), group: group || 'This page' };
    }

    function build() {
        current = {};
        var modal = document.querySelector('.modal.show');
        var root = modal || document.querySelector('.nx-content') || document.body;

        // --- always available
        add('Alt+T', document.getElementById('nxThemeBtn'), 'Switch light / dark theme', 'General');
        add('Alt+B', document.getElementById('nxCollapseBtn'), 'Collapse / expand the sidebar', 'General');
        add('Alt+L', document.getElementById('nxBellBtn'), 'Open admin alerts', 'General');
        add('Alt+M', document.querySelector('.nx-user-link'), 'My profile', 'General');
        add('Alt+Q', document.getElementById('nxLogoutBtn'), 'Sign out', 'General');
        if (modal) add('Esc', modal.querySelector('[data-bs-dismiss="modal"]'), 'Close this popup', 'General');

        // --- sidebar navigation (not while a popup is open)
        if (!modal) {
            $('.nx-sidebar .nx-link').each(function (i) {
                var combo = i < NAV_KEYS.length ? 'Alt+' + NAV_KEYS[i] : null;
                if (combo) add(combo, this, 'Go to ' + labelOf(this), 'Go to');
            });
        }

        // --- page / popup actions
        var made = 0;
        $(root).find('.nx-head a.btn, .nx-head button.btn').each(function () {
            if (!visible(this)) return;
            var t = (this.textContent || '').trim(), href = this.getAttribute('href') || '';
            if (this.hasAttribute('data-print')) add('Alt+P', this, 'Print this page');
            else if (/export/i.test(t)) add('Alt+X', this, 'Export CSV');
            else if ($(this).find('.bi-pencil-square').length) add('Alt+E', this, 'Edit this record');
            else if (/\/create$/.test(href) || /^(new|add)\b/i.test(t)) { add(made++ === 0 ? 'Alt+N' : 'Alt+Shift+N', this); }
        });

        // save buttons
        var save = null, more = null;
        if (modal && modal.id === 'entryModal') { save = modal.querySelector('#emSave'); more = modal.querySelector('#emSaveMore'); }
        else if (modal) { save = $(modal).find('form [type=submit].btn-primary, .modal-footer .btn-primary').filter(function () { return visible(this); })[0]; }
        else { save = document.getElementById('saveBtn') || $(root).find('form:not([method=GET]) [type=submit].btn-primary').filter(function () { return visible(this); })[0]; }
        add('Alt+S', save, 'Save', 'Forms');
        add('Alt+Shift+S', more, 'Save & add another', 'Forms');

        // search box of the table on this page
        var search = $(root).find('.dataTables_filter input, input[type=search]').filter(function () { return visible(this); })[0];
        add('/', search, 'Search this list', 'General'); add('Ctrl+K', search, 'Search this list', 'General');

        // drawer-closing form: add an entry
        if (!modal) $(root).find('.cf-add[data-cat]').each(function () {
            var d = CAT_KEYS[this.getAttribute('data-cat')];
            if (d !== undefined) add('Alt+Shift+' + d, this, 'Add entry — ' + labelOf(this).replace(/\d+$/, '').trim(), 'Closing entries');
        });

        // any control can declare its own shortcut: data-kbd="Alt+D" data-kbd-label="…"
        $(root).find('[data-kbd]').each(function () { add(this.getAttribute('data-kbd'), this, this.getAttribute('data-kbd-label') || undefined, this.getAttribute('data-kbd-group') || (modal ? 'Forms' : 'Closing entries')); });

        add('Alt+H', document.getElementById('nxHelpAnchor') || document.body, 'Show all shortcuts', 'General');
    }

    // touch screens have no hover and no keyboard: skip the tooltips there (they would stick after a tap)
    var tipClass = (window.bootstrap && bootstrap.Tooltip && !(window.matchMedia && matchMedia('(hover: none)').matches)) ? bootstrap.Tooltip : null;
    function kbdHtml(combo) {
        return combo.split('+').map(function (p) { return '<kbd>' + p.replace(/[<>&]/g, '') + '</kbd>'; }).join('<span class="plus">+</span>');
    }
    function esc(t) { return String(t).replace(/[&<>"]/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]; }); }

    function applyTooltips() {
        var seen = new Set();
        Object.keys(current).forEach(function (combo) {
            var it = current[combo], el = it.el;
            if (el === document.body || combo === 'Esc') return;
            var prev = applied.get(el);
            if (prev && prev.combos.indexOf(combo) === -1) prev.combos.push(combo);
            else if (!prev) {
                applied.set(el, { combos: [combo], label: it.desc, tip: null });
                prev = applied.get(el);
            }
            seen.add(el);
        });
        // a text box / dropdown never pops a tooltip over itself (it would cover what you are typing): its shortcut lives behind a small (i) next to its title
        var isField = function (el) { return /^(INPUT|SELECT|TEXTAREA)$/.test(el.tagName) && !/^(checkbox|radio|button|submit)$/.test(el.type || ''); };
        var titleOf = function (el) {
            var l = el.id && document.querySelector('label[for="' + el.id + '"]');
            if (l) return l;
            var box = el.closest('[class*="col-"], .mb-3, .mb-4, .form-group');
            return (box && box.querySelector('.form-label')) || null;
        };
        seen.forEach(function (el) {
            if (el.closest('.select2-container, .select2-dropdown, .select2-search')) return;   // the search box inside an opened dropdown
            var st = applied.get(el), key = st.combos.join('|') + '|' + st.label;
            if (st.key === key) return;
            st.key = key;
            el.setAttribute('aria-keyshortcuts', st.combos.join(' ').replace(/Alt/g, 'Alt'));
            var native = el.getAttribute('title'); if (native) { el.setAttribute('data-bs-title', native); el.removeAttribute('title'); }
            var html = '<span class="kt-label">' + esc(st.label) + '</span>' + st.combos.slice(0, 2).map(function (c) { return '<span class="kt-keys">' + kbdHtml(c) + '</span>'; }).join('');
            if (!tipClass) return;
            if (isField(el)) {
                try {
                    var lab0 = titleOf(el), ex = lab0 && lab0.querySelector('.nx-kbd-i'); if (ex && (!st.icon || !st.icon.isConnected)) st.icon = ex;   // the dropdown's own (i): show the shortcut there
                    var oldIcon = tipClass.getInstance(st.icon || el); if (oldIcon) oldIcon.dispose();
                    var oldOwn = tipClass.getInstance(el); if (oldOwn) oldOwn.dispose();
                    if (!st.icon || !st.icon.isConnected) {
                        st.icon = document.createElement('i');
                        st.icon.className = 'bi bi-info-circle nx-kbd-i'; st.icon.setAttribute('role', 'img'); st.icon.setAttribute('aria-label', 'Keyboard shortcut');
                        var lab = titleOf(el);
                        if (lab) lab.appendChild(st.icon); else el.insertAdjacentElement('afterend', st.icon);
                    }
                    st.tip = new tipClass(st.icon, { title: html, html: true, sanitize: false, trigger: 'hover', placement: 'top', container: 'body', customClass: 'nx-kbd-tip', delay: { show: 100, hide: 0 } });
                } catch (err) { st.key = null; }
                return;
            }
            try {
            var old = tipClass.getInstance(el); if (old) old.dispose();
            st.tip = new tipClass(el, { title: html, html: true, sanitize: false, trigger: 'hover focus', placement: el.closest('.nx-sidebar') ? 'right' : 'bottom', container: 'body', customClass: 'nx-kbd-tip', delay: { show: 350, hide: 0 } });
            } catch (err) { st.key = null; }
        });
    }

    var timer = null;
    function refresh() { clearTimeout(timer); timer = setTimeout(function () { build(); applyTooltips(); }, 120); }
    window.nxShortcuts = { refresh: function () { build(); applyTooltips(); }, list: function () { return current; }, label: function (el, combo, desc) { current[combo] = { el: el, desc: desc, group: 'This page' }; applyTooltips(); } };

    function typing(t) { return t && (/^(INPUT|TEXTAREA|SELECT)$/.test(t.tagName) || t.isContentEditable) && !/^(checkbox|radio|button|submit)$/.test(t.type || ''); }

    document.addEventListener('keydown', function (e) {
        if (e.isComposing || e.repeat && !/^Alt/.test(comboOf(e))) return;
        if (document.querySelector('.nx-confirm.show') || document.querySelector('.nx-help.show')) {
            if (e.key === 'Escape' && document.querySelector('.nx-help.show')) { closeHelp(); e.preventDefault(); }
            return;
        }
        var combo = comboOf(e);
        var hasMod = e.altKey || e.ctrlKey;
        if (!hasMod && !(combo === '/' || combo === '?')) return;
        if (!hasMod && typing(e.target)) return;

        build();
        if (combo === '?' || combo === 'Alt+H') { e.preventDefault(); toggleHelp(); return; }
        var it = current[combo];
        if (!it || combo === 'Esc' || it.el === document.body) return;
        if (!visible(it.el) && !(it.el.tagName === 'A' && it.el.href)) return;
        e.preventDefault(); e.stopPropagation();
        if (combo === '/' || combo === 'Ctrl+K') { it.el.focus(); if (it.el.select) it.el.select(); return; }
        if (typeof it.el.focus === 'function') { try { it.el.focus({ preventScroll: true }); } catch (x) {} }
        it.el.click();
    }, true);

    /* ---- help overlay ---- */
    var help = null;
    function closeHelp() { if (help) { help.classList.remove('show'); setTimeout(function () { if (help) { help.remove(); help = null; } }, 180); } }
    function toggleHelp() {
        if (help) return closeHelp();
        build();
        var groups = {}, order = ['General', 'Go to', 'This page', 'Forms', 'Closing entries'];
        Object.keys(current).forEach(function (c) { var g = current[c].group; (groups[g] = groups[g] || []).push([c, current[c].desc]); });
        help = document.createElement('div'); help.className = 'nx-help'; help.setAttribute('role', 'dialog'); help.setAttribute('aria-modal', 'true'); help.setAttribute('aria-label', 'Keyboard shortcuts');
        var card = document.createElement('div'); card.className = 'nx-help-card';
        var head = document.createElement('div'); head.className = 'nx-help-head';
        var h = document.createElement('h5'); h.textContent = 'Keyboard shortcuts'; var x = document.createElement('button'); x.type = 'button'; x.className = 'nx-icon-btn'; x.setAttribute('aria-label', 'Close'); x.innerHTML = '<i class="bi bi-x-lg"></i>'; x.addEventListener('click', closeHelp);
        head.appendChild(h); head.appendChild(x); card.appendChild(head);
        var body = document.createElement('div'); body.className = 'nx-help-body';
        order.concat(Object.keys(groups).filter(function (g) { return order.indexOf(g) === -1; })).forEach(function (g) {
            if (!groups[g]) return;
            var sec = document.createElement('div'); sec.className = 'nx-help-sec'; var t = document.createElement('div'); t.className = 'nx-help-title'; t.textContent = g; sec.appendChild(t);
            groups[g].forEach(function (r) {
                if (r[0] === 'Ctrl+K' ) return;
                var row = document.createElement('div'); row.className = 'nx-help-row'; var d = document.createElement('span'); d.textContent = r[1];
                var k = document.createElement('span'); k.className = 'kt-keys'; k.innerHTML = kbdHtml(r[0]); row.appendChild(d); row.appendChild(k); sec.appendChild(row);
            });
            body.appendChild(sec);
        });
        var foot = document.createElement('div'); foot.className = 'nx-help-foot'; foot.innerHTML = 'In a confirmation: <span class="kt-keys"><kbd>Y</kbd></span> confirm · <span class="kt-keys"><kbd>N</kbd></span> / <span class="kt-keys"><kbd>Esc</kbd></span> cancel. Press <span class="kt-keys"><kbd>?</kbd></span> any time to open this list.';
        card.appendChild(body); card.appendChild(foot); help.appendChild(card);
        help.addEventListener('click', function (e) { if (e.target === help) closeHelp(); });
        document.body.appendChild(help); requestAnimationFrame(function () { help.classList.add('show'); }); x.focus();
    }

    $(function () {
        refresh();
        var mo = new MutationObserver(function (list) {
            for (var i = 0; i < list.length; i++) { var a = list[i].addedNodes; for (var j = 0; j < a.length; j++) { if (a[j].nodeType === 1 && !(a[j].classList && (a[j].classList.contains('tooltip') || a[j].classList.contains('nx-help') || a[j].classList.contains('nx-kbd-i')))) { refresh(); return; } } }
        });
        mo.observe(document.body, { childList: true, subtree: true });
        $(document).on('shown.bs.modal hidden.bs.modal draw.dt', refresh);
        $(window).on('load', refresh);
    });
})(jQuery);

/* ---------- copy-to-clipboard buttons / select-on-focus fields (CSP forbids inline handlers) ---------- */
document.addEventListener('click', function (e) {
    var b = e.target.closest ? e.target.closest('[data-copy]') : null;
    if (!b) return;
    var t = document.querySelector(b.getAttribute('data-copy')); if (!t) return;
    t.select();
    var done = function () { if (window.nxToast) window.nxToast('Copied to clipboard', 'ok'); };
    if (navigator.clipboard && window.isSecureContext) navigator.clipboard.writeText(t.value).then(done); else { document.execCommand('copy'); done(); }
});
document.addEventListener('focusin', function (e) { if (e.target.matches && e.target.matches('input[data-select]')) e.target.select(); });

/* ---------- auto-submit fields (month pickers …) — CSP forbids inline onchange ---------- */
document.addEventListener('change', function (e) {
    if (e.target.matches && e.target.matches('[data-autosubmit]') && e.target.form) e.target.form.submit();
});


/* ---------- roles without the Printing permission: Ctrl+P is blocked ---------- */
(function () {
    if (!document.querySelector('meta[name="nx-no-print"]')) return;
    document.documentElement.classList.add('nx-noprint'); // theme.css blanks the printout for this class
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && (e.key === 'p' || e.key === 'P')) {
            e.preventDefault(); e.stopPropagation();
            if (window.nxToast) window.nxToast('Printing is not allowed for your role.', 'bad');
        }
    }, true);
})();

/* ==========================================================================
   Typing rules for every form: names get a capital letter on each word, notes on the first letter,
   number boxes accept numbers only, phone boxes accept phone characters only.
   (The server applies the same rules again — see App\Support\TextCase and App\Support\Rules.)
   ========================================================================== */
(function () {
    'use strict';
    var TITLE = ['name', 'contact_person', 'store_name', 'tagline', 'store_address', 'address', 'shifts', 'party'];
    var SENTENCE = ['description', 'details', 'note', 'notes', 'reason', 'title', 'footer_text', 'print_footer_note', 'login_welcome', 'login_sub', 'login_headline', 'login_headline_em'];

    function keyOf(el) {
        var n = el.getAttribute('name') || el.getAttribute('data-f') || '';
        n = n.replace(/\[\]$/, '');
        var m = n.match(/\[([^\[\]]+)\]$/); if (m) n = m[1];
        return n;
    }
    function kindOf(el) {
        var k = keyOf(el);
        if (TITLE.indexOf(k) > -1) return 'title';
        if (SENTENCE.indexOf(k) > -1 || /_details$|_description$/.test(k)) return 'sentence';
        return null;
    }
    function title(v) { return v.replace(/\s+/g, ' ').trim().replace(/(^|[\s\-\/(])(\p{Ll})/gu, function (m, a, b) { return a + b.toUpperCase(); }); }
    function sentence(v) { v = v.trim(); return v.replace(/^\p{Ll}/u, function (c) { return c.toUpperCase(); }); }
    window.nxTitleCase = title; window.nxSentenceCase = sentence;

    /* ---- text: tidy when the person leaves the box ---- */
    document.addEventListener('change', function (e) {
        var el = e.target;
        if (!el.matches || !el.matches('input[type=text], textarea') || (el.form && /^get$/i.test(el.form.method || ''))) return;
        var kind = kindOf(el); if (!kind) return;
        var nv = kind === 'title' ? title(el.value) : sentence(el.value);
        if (nv !== el.value) { el.value = nv; el.dispatchEvent(new Event('input', { bubbles: true })); }
    }, true);

    /* ---- phone: only digits and + - ( ) space (settings phone also allows , /) ---- */
    function isPhone(el) { return el.matches && el.matches('input[type=text], input[type=tel]') && /(^|\[)phone(\]|$)/.test(el.getAttribute('name') || ''); }
    document.addEventListener('focusin', function (e) { if (isPhone(e.target)) { e.target.setAttribute('inputmode', 'tel'); e.target.setAttribute('autocomplete', 'tel'); } });
    document.addEventListener('input', function (e) {
        if (!isPhone(e.target)) return;
        var allow = /store_phone/.test(e.target.name) ? /[^0-9+\-()\s,\/]/g : /[^0-9+\-()\s]/g;
        var nv = e.target.value.replace(allow, '');
        if (nv !== e.target.value) { e.target.value = nv; note(e.target, 'Phone numbers use digits only (and + - ( ) spaces).'); }
    });

    /* ---- numbers ---- */
    function isNum(el) { return el && el.tagName === 'INPUT' && el.type === 'number'; }
    function decimalsOf(el) { var s = el.getAttribute('step'); if (!s || s === 'any') return 2; var i = s.indexOf('.'); return i < 0 ? 0 : s.length - i - 1; }
    function note(el, msg) {
        var box = el.closest('.input-group') || el;
        var old = box.parentNode.querySelector(':scope > .nx-num-error[data-for="' + (el.id || el.name) + '"]'); if (old) old.remove();
        el.classList.toggle('is-invalid', !!msg);
        if (!msg) return;
        var d = document.createElement('div'); d.className = 'invalid-feedback d-block nx-num-error'; d.setAttribute('data-for', el.id || el.name); d.textContent = msg;
        box.insertAdjacentElement('afterend', d);
        clearTimeout(el._nxT); el._nxT = setTimeout(function () { if (d.parentNode && !el.classList.contains('nx-bad')) { d.remove(); el.classList.remove('is-invalid'); } }, 4000);
    }
    function problem(el) {
        if (el.disabled || el.readOnly) return '';
        if (el.validity.badInput) return 'Enter a valid number (digits only).';
        if (el.value === '') return el.required ? 'This field is required.' : '';
        var v = parseFloat(el.value), min = el.min !== '' ? parseFloat(el.min) : null, max = el.max !== '' ? parseFloat(el.max) : null;
        if (isNaN(v)) return 'Enter a valid number (digits only).';
        if (min !== null && v < min) return max !== null ? 'Enter a number from ' + min + ' to ' + max + '.' : 'The smallest allowed number is ' + min + '.';
        if (max !== null && v > max) return min !== null ? 'Enter a number from ' + min + ' to ' + max + '.' : 'The largest allowed number is ' + max + '.';
        var dec = decimalsOf(el), s = el.value, i = s.indexOf('.');
        if (i > -1 && s.length - i - 1 > dec) return dec === 0 ? 'Whole numbers only.' : 'At most ' + dec + ' decimal place' + (dec > 1 ? 's' : '') + '.';
        return '';
    }
    window.nxNumberProblem = problem;
    document.addEventListener('keydown', function (e) {
        var t = e.target; if (!isNum(t) || e.ctrlKey || e.metaKey || e.altKey || e.key.length !== 1) return;
        var nonNeg = t.min === '' || parseFloat(t.min) >= 0;
        if (/[eE+]/.test(e.key) || (e.key === '-' && nonNeg) || (e.key === '.' && decimalsOf(t) === 0) || !/[0-9.\-]/.test(e.key)) { e.preventDefault(); note(t, 'Numbers only.'); }
    }, true);
    document.addEventListener('paste', function (e) {
        var t = e.target; if (!isNum(t)) return;
        var txt = ((e.clipboardData || window.clipboardData).getData('text') || '').trim().replace(/,/g, '');
        if (!/^-?\d+(\.\d+)?$/.test(txt)) { e.preventDefault(); note(t, 'Numbers only.'); }
    }, true);
    document.addEventListener('input', function (e) {
        var t = e.target; if (!isNum(t)) return;
        var v = t.value, i = v.indexOf('.'), dec = decimalsOf(t);
        if (i > -1 && v.length - i - 1 > dec) { t.value = dec === 0 ? v.slice(0, i) : v.slice(0, i + 1 + dec); }
        t.classList.remove('nx-bad'); if (t.classList.contains('is-invalid') && !problem(t)) note(t, '');
    });
    document.addEventListener('focusout', function (e) {
        var t = e.target; if (!isNum(t)) return;
        var msg = problem(t); t.classList.toggle('nx-bad', !!msg); note(t, msg);
    });
    document.addEventListener('wheel', function (e) { if (isNum(document.activeElement) && document.activeElement === e.target) document.activeElement.blur(); }, { passive: true });
    // a form with a wrong number does not leave the page
    document.addEventListener('submit', function (e) {
        var f = e.target; if (!f || !f.querySelectorAll || /^get$/i.test(f.method || '') || f.hasAttribute('data-no-validate')) return;
        var first = null;
        [].slice.call(f.querySelectorAll('input[type=number]')).forEach(function (i) {
            if (i.closest('.d-none, [hidden]') || i.offsetParent === null) return;
            var msg = problem(i); i.classList.toggle('nx-bad', !!msg); note(i, msg); if (msg && !first) first = i;
        });
        if (first) { e.preventDefault(); e.stopImmediatePropagation(); first.focus(); if (window.nxToast) window.nxToast('Please correct the highlighted number.', 'bad'); }
    }, true);
})();

/* A normal form can only be sent once: the buttons lock after the first submit (a double click never saves twice). */
(function () {
    document.addEventListener('submit', function (e) {
        var f = e.target;
        if (e.defaultPrevented || !f || !f.matches || /^get$/i.test(f.method || '') || f.closest('#nxModal') || f.hasAttribute('data-allow-resubmit')) return;
        if (f.dataset.nxSent) { e.preventDefault(); return; }
        f.dataset.nxSent = '1';
        setTimeout(function () {
            var btns = [].slice.call(f.querySelectorAll('button[type=submit], input[type=submit]'));
            btns.forEach(function (b) { b.disabled = true; });
            setTimeout(function () { delete f.dataset.nxSent; btns.forEach(function (b) { b.disabled = false; }); }, 10000); // e.g. a download keeps the page open
        }, 0);
    });
})();

/* Amount inputs never show a pointless ".00" (1200.00 -> 1200); real decimals like 12.50 stay as they are. */
(function () {
    var WHOLE = /^(-?\d+)\.0+$/;
    function tidy(el) {
        if (!el || el.tagName !== 'INPUT' || el === document.activeElement) { return; }
        var numeric = el.type === 'number' || /^(decimal|numeric)$/.test(el.getAttribute('inputmode') || '');
        var m = numeric && WHOLE.exec(el.value);
        if (m) { el.value = m[1]; }
    }
    function sweep(root) {
        if (!root.querySelectorAll) { return; }
        if (root.tagName === 'INPUT') { tidy(root); }
        root.querySelectorAll('input[type="number"], input[inputmode="decimal"], input[inputmode="numeric"]').forEach(tidy);
    }
    function start() {
        sweep(document);
        // popups, closing-form rows and anything else added later
        new MutationObserver(function (list) { list.forEach(function (r) { r.addedNodes.forEach(function (n) { if (n.nodeType === 1) { sweep(n); } }); }); })
            .observe(document.body, { childList: true, subtree: true });
    }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
})();
