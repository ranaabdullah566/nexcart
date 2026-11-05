/* NexCart form validation — runs in the browser BEFORE a form is sent, and shows the message right under each field.
   The server validates everything again (the browser is never trusted); its errors are drawn under the same fields.
   Opt out for one form with data-no-validate. Required is detected from the `required` attribute OR a red * on the field's label. */
(function () {
    'use strict';
    var M = {};
    try { M = JSON.parse((document.querySelector('meta[name="nx-vmsg"]') || {}).content || '{}'); } catch (e) {}
    function t(k, v, fallback) { return String(M[k] || fallback || k).replace(/:(\w+)/g, function (_, n) { return v && v[n] !== undefined ? v[n] : ':' + n; }); }

    function labelOf(el) {
        if (el.id) { var l = document.querySelector('label[for="' + el.id.replace(/"/g, '\\"') + '"]'); if (l) return l; }
        var box = el.closest('.col-12,[class*="col-"],.mb-3,.mb-2,.form-group,.sf-field') || el.parentElement;
        return box ? box.querySelector('label') : null;
    }
    function isRequired(el) {
        if (el.required) return true;
        if (el.type === 'checkbox' || el.type === 'radio' || el.type === 'file' || el.type === 'hidden') return false;
        var l = labelOf(el);
        return !!(l && l.querySelector('.text-danger') && /\*/.test(l.textContent));
    }
    function message(el, form) {
        var v = (el.value || '').trim(), type = (el.type || '').toLowerCase();
        if (el.disabled || el.readOnly || type === 'hidden' || type === 'submit' || type === 'button' || type === 'reset' || type === 'image') return '';
        for (var h = el; h && h !== form; h = h.parentElement) { if (h.hidden || h.style.display === 'none' || h.classList.contains('d-none')) return ''; }   // fields the form itself has hidden (e.g. price of a variable product) are not checked
        if (type === 'checkbox' || type === 'radio') {
            if (!el.required) return '';
            if (type === 'radio') { var g = form.querySelectorAll('input[type=radio][name="' + el.name + '"]'); return Array.prototype.some.call(g, function (r) { return r.checked; }) ? '' : (el.dataset.msgRequired || t('required', null, 'This field is required.')); }
            return el.checked ? '' : (el.dataset.msgRequired || t('required', null, 'This field is required.'));
        }
        if (type === 'file') { return el.required && !el.files.length ? (el.dataset.msgRequired || t('required', null, 'This field is required.')) : ''; }
        if (v === '') return isRequired(el) ? (el.dataset.msgRequired || t('required', null, 'This field is required.')) : '';
        if (type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) return el.dataset.msgEmail || t('email', null, 'Enter a valid email address.');
        if (type === 'url' && !/^https?:\/\/\S+\.\S+/i.test(v)) return t('url', null, 'Enter a valid URL.');
        if (type === 'number') {
            var n = parseFloat(v);
            if (isNaN(n)) return t('number', null, 'Enter a number.');
            if (el.min !== '' && n < parseFloat(el.min)) return t('min', { min: el.min }, 'Must be at least :min.');
            if (el.max !== '' && n > parseFloat(el.max)) return t('max', { max: el.max }, 'Must be at most :max.');
            if (el.step && el.step !== 'any' && el.step !== '1' && false) return '';
            if ((!el.step || el.step === '1') && !/^-?\d+$/.test(v)) return t('integer', null, 'Enter a whole number.');
        }
        if (el.minLength > 0 && v.length < el.minLength) return t('minlength', { min: el.minLength }, 'Use at least :min characters.');
        if (el.maxLength > 0 && v.length > el.maxLength) return t('maxlength', { max: el.maxLength }, 'Use at most :max characters.');
        if (el.pattern) { try { if (!new RegExp('^(?:' + el.pattern + ')$').test(v)) return el.dataset.msgPattern || t('pattern', null, 'Please match the requested format.'); } catch (e) {} }
        if (/_confirmation$/.test(el.name)) {
            var base = form.querySelector('[name="' + el.name.replace(/_confirmation$/, '') + '"]');
            if (base && base.value !== el.value) return t('confirmed', null, 'The two values do not match.');
        }
        if (el.type === 'password' && /^(password|new_password)$/.test(el.name) && v.length > 0 && !/_confirmation/.test(el.name)) {
            if (v.length < 10 && el.minLength !== 8 && form.querySelector('[name="' + el.name + '_confirmation"]')) return t('strong', null, 'At least 10 characters, with letters and numbers.');
            if (form.querySelector('[name="' + el.name + '_confirmation"]') && !(/[A-Za-z]/.test(v) && /\d/.test(v))) return t('strong', null, 'At least 10 characters, with letters and numbers.');
        }
        return '';
    }

    function anchor(el) {
        var s2 = el.nextElementSibling && el.nextElementSibling.classList.contains('select2-container') ? el.nextElementSibling : null;
        var node = s2 || el;
        var grp = node.closest('.input-group'); if (grp) node = grp;
        if (el.type === 'radio' || el.type === 'checkbox') node = el.closest('.form-check,.ck-opt,label') || el;
        return node;
    }
    function clear(el) {
        el.classList.remove('is-invalid');
        var a = anchor(el), n = a.nextElementSibling;
        while (n && n.classList.contains('nx-field-error')) { var d = n; n = n.nextElementSibling; d.remove(); }
    }
    function show(el, msg) {
        clear(el);
        el.classList.add('is-invalid');
        // a server-rendered message already sits under it? keep one
        var a = anchor(el), nxt = a.nextElementSibling;
        if (nxt && nxt.classList.contains('invalid-feedback') && nxt.textContent.trim() === msg) return;
        var d = document.createElement('div'); d.className = 'invalid-feedback d-block nx-field-error'; d.setAttribute('role', 'alert'); d.textContent = msg;
        a.parentNode.insertBefore(d, a.nextSibling);
    }
    function validateField(el, form) { var m = message(el, form); if (m) show(el, m); else clear(el); return m; }
    function fields(form) { return Array.prototype.filter.call(form.elements, function (e) { return e.name && e.tagName !== 'BUTTON' && e.tagName !== 'FIELDSET' && !e.hasAttribute('data-no-validate'); }); }
    function revealAndFocus(el) {
        var pane = el.closest('.tab-pane');
        if (pane && !pane.classList.contains('active')) { var tab = document.querySelector('[data-bs-target="#' + pane.id + '"]'); if (tab && window.bootstrap) bootstrap.Tab.getOrCreateInstance(tab).show(); }
        setTimeout(function () { try { el.focus({ preventScroll: false }); el.scrollIntoView({ block: 'center', behavior: 'smooth' }); } catch (e) {} }, 60);
    }

    /* the browser's own bubble would block the submit event before we can draw our messages: switch it off, we do the checking */
    function takeOver(root) {
        (root.querySelectorAll ? root.querySelectorAll('form') : []).forEach(function (f) { if (!f.hasAttribute('data-no-validate')) f.noValidate = true; });
        if (root.tagName === 'FORM' && !root.hasAttribute('data-no-validate')) root.noValidate = true;
    }
    takeOver(document);
    new MutationObserver(function (list) { list.forEach(function (m) { m.addedNodes.forEach(function (n) { if (n.nodeType === 1) takeOver(n); }); }); }).observe(document.documentElement, { childList: true, subtree: true });

    document.addEventListener('submit', function (e) {
        var form = e.target;
        if (!(form instanceof HTMLFormElement) || form.hasAttribute('data-no-validate')) return;
        if ((form.method || '').toLowerCase() !== 'post') return;
        var spoof = form.querySelector('input[name="_method"]'); if (spoof && /^(delete)$/i.test(spoof.value)) return;
        if (e.submitter && e.submitter.hasAttribute('formnovalidate')) return;
        var first = null;
        fields(form).forEach(function (el) { var m = validateField(el, form); if (m && !first) first = el; });
        if (first) { e.preventDefault(); e.stopPropagation(); e.stopImmediatePropagation(); revealAndFocus(first); }
    }, true);

    function live(e) {
        var el = e.target, form = el.form;
        if (!form || !el.name || form.hasAttribute('data-no-validate') || el.hasAttribute('data-no-validate')) return;
        if ((form.method || '').toLowerCase() !== 'post') return;
        if (e.type === 'input' && !el.classList.contains('is-invalid')) return;
        // leaving an untouched empty field must not push the layout around (it would move the Submit button out from under the mouse): empty is reported on submit
        if (e.type === 'focusout' && (el.value || '') === '' && !el.classList.contains('is-invalid')) return;   // while typing, only re-check fields already flagged
        validateField(el, form);
        if (/_confirmation$/.test(el.name) === false) { var c = form.querySelector('[name="' + el.name + '_confirmation"]'); if (c && c.value) validateField(c, form); }
    }
    document.addEventListener('focusout', live, true);
    document.addEventListener('change', live, true);
    document.addEventListener('input', live, true);

    /* ---- server errors: draw them under the matching fields (works for every form, old-input aware) ---- */
    function bracket(key) { var p = key.split('.'); return p[0] + p.slice(1).map(function (x) { return '[' + x + ']'; }).join(''); }
    window.nxFieldFor = function (scope, key) {
        var b = bracket(key), q = function (s) { try { return scope.querySelector(s); } catch (e) { return null; } };
        return q('[name="' + b + '"]') || q('[name="' + b + '[]"]') || q('[name="' + key.split('.')[0] + '[]"]') || q('[name="' + key.split('.')[0] + '"]');
    };
    window.nxShowFieldError = function (el, msg) { show(el, msg); };
    function paintServerErrors() {
        var raw = (document.querySelector('meta[name="nx-errors"]') || {}).content; if (!raw) return;
        var errs; try { errs = JSON.parse(raw); } catch (e) { return; }
        var forms = Array.prototype.filter.call(document.forms, function (f) { return (f.method || '').toLowerCase() === 'post' && !f.closest('#nxModal') && f.id !== 'logout'; });
        var firstEl = null;
        Object.keys(errs).forEach(function (k) {
            for (var i = 0; i < forms.length; i++) {
                var el = window.nxFieldFor(forms[i], k);
                if (el && el.type !== 'hidden') { show(el, errs[k][0]); if (!firstEl) firstEl = el; return; }
            }
        });
        if (firstEl) revealAndFocus(firstEl);
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', paintServerErrors); else paintServerErrors();
})();
