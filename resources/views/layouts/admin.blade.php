<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\Locales::dir() }}">
<head>
    @include('partials.head')
    <title>@yield('title', __('Dashboard')) · {{ setting('store.name', config('app.name')) }}</title>
    @stack('styles')
</head>
<body>
@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))->map(fn ($n) => mb_strtoupper(mb_substr($n, 0, 1)))->take(2)->implode('');
    $storeName = setting('store.name', config('app.name'));
    // little counters on menu items that need attention
    $badges = [];
    if ($user->can('orders.view')) { $badges['admin.orders.index'] = \App\Models\Order::where('status', 'pending')->count(); }
    if ($user->can('prescriptions.view') && \App\Support\BusinessProfile::hasModule('prescriptions')) { $badges['admin.prescriptions.index'] = \App\Models\Order::where('requires_prescription', true)->where('prescription_status', 'pending')->count(); }
    if ($user->can('reviews.manage') && \App\Support\BusinessProfile::hasModule('reviews')) { $badges['admin.reviews.index'] = \App\Models\Review::where('status', 'pending')->count(); }
    // [group => [[route, active-pattern, icon, label, permission]]]  — later phases append here.
    $navGroups = [
        'Overview' => [
            ['admin.dashboard', 'admin.dashboard', 'grid-1x2-fill', 'Dashboard', null, null],
        ],
        'Sales' => [
            ['admin.orders.index', 'admin.orders.*', 'receipt-cutoff', 'Orders', 'orders.view', null],
            ['admin.kitchen.index', 'admin.kitchen.*', 'fire', 'Kitchen', 'orders.view', 'kitchen_display'],
            ['admin.deliveries.index', 'admin.deliveries.*', 'bicycle', 'Deliveries', 'deliveries.view', 'riders'],
            ['admin.prescriptions.index', 'admin.prescriptions.*', 'prescription2', 'Prescriptions', 'prescriptions.view', 'prescriptions'],
            ['admin.customers.index', 'admin.customers.*', 'people-fill', 'Customers', 'customers.view', null],
            ['admin.coupons.index', 'admin.coupons.*', 'ticket-perforated-fill', 'Coupons', 'sales_settings.manage', null],
            ['admin.shipping.index', 'admin.shipping.*', 'truck', 'Shipping', 'sales_settings.manage', null],
            ['admin.delivery-slots.index', 'admin.delivery-slots.*', 'calendar2-week', 'Delivery slots', 'sales_settings.manage', 'delivery_slots'],
        ],
        'Catalog' => [
            ['admin.products.index', 'admin.products.*', 'box-seam-fill', 'Products', 'products.view', null],
            ['admin.categories.index', 'admin.categories.*', 'diagram-3-fill', 'Categories', 'categories.view', null],
            ['admin.brands.index', 'admin.brands.*', 'award-fill', 'Brands', 'products.view', null],
            ['admin.inventory.index', 'admin.inventory.*', 'clipboard2-data-fill', 'Inventory', 'inventory.view', null],
            ['admin.modifier-groups.index', 'admin.modifier-groups.*', 'plus-slash-minus', 'Add-ons', 'catalog_settings.manage', 'modifiers'],
            ['admin.attributes.index', 'admin.attributes.*', 'sliders2', 'Product fields', 'catalog_settings.manage', null],
        ],
        'Growth' => [
            ['admin.reports.index', 'admin.reports.*', 'bar-chart-line-fill', 'Reports', 'reports.view', null],
            ['admin.reviews.index', 'admin.reviews.*', 'chat-heart-fill', 'Reviews', 'reviews.manage', 'reviews'],
        ],
        'Storefront' => [
            ['admin.home-sections.index', 'admin.home-sections.*', 'layout-text-window-reverse', 'Home page', 'storefront.manage', null],
            ['admin.theme.edit', 'admin.theme.*', 'palette-fill', 'Theme', 'storefront.manage', null],
            ['admin.pages.index', 'admin.pages.*', 'file-earmark-text-fill', 'Pages', 'storefront.manage', null],
            ['admin.menu-items.index', 'admin.menu-items.*', 'list-nested', 'Menus', 'storefront.manage', null],
            ['admin.emails.edit', 'admin.emails.*', 'envelope-paper-fill', 'Email templates', 'storefront.manage', null],
            ['store.home', 'never', 'box-arrow-up-right', 'View store', null, null],
        ],
        'Admin' => [
            ['admin.staff.index', 'admin.staff.*', 'person-badge-fill', 'Staff', 'staff.view', null],
            ['admin.settings.edit', 'admin.settings.*', 'gear-fill', 'Store settings', 'settings.view', null],
        ],
    ];
@endphp

<div class="nx-shell">
    <aside class="nx-sidebar" id="nxSidebar">
        <a href="{{ route('admin.dashboard') }}" class="nx-brand">
            <span class="nx-brand-logo"><img src="{{ brand_logo() }}" alt="" style="width:100%;height:100%;object-fit:contain;border-radius:inherit"></span>
            <span class="min-w-0">
                <span class="nx-brand-name d-block text-truncate">{{ $storeName }}</span>
                <span class="nx-brand-sub d-block">{{ __(\App\Support\BusinessProfile::current()['label']) }}</span>
            </span>
        </a>

        <nav class="nx-nav">
            @foreach ($navGroups as $groupLabel => $items)
                @php $visible = array_values(array_filter($items, fn ($i) => ($i[4] === null || $user->can($i[4])) && ($i[5] === null || \App\Support\BusinessProfile::hasModule($i[5])))); @endphp
                @if ($visible)
                    <div class="nx-nav-label">{{ __($groupLabel) }}</div>
                    @foreach ($visible as [$route, $pattern, $icon, $label])
                        <a href="{{ route($route) }}" title="{{ __($label) }}" class="nx-link {{ request()->routeIs($pattern) ? 'active' : '' }}">
                            <i class="bi bi-{{ $icon }}"></i><span class="nx-link-text">{{ __($label) }}</span>@if (! empty($badges[$route]))<span class="nx-nav-badge">{{ $badges[$route] > 99 ? '99+' : $badges[$route] }}</span>@endif
                        </a>
                    @endforeach
                @endif
            @endforeach
        </nav>

        <div class="nx-user">
            <div class="d-flex align-items-center gap-2 min-w-0 flex-grow-1">
                <div class="nx-avatar">{{ $initials }}</div>
                <div class="min-w-0 flex-grow-1">
                    <div class="nx-user-name text-truncate">{{ $user->name }}</div>
                    <div class="nx-user-role">{{ __(ucfirst($user->getRoleNames()->first() ?? '')) }}</div>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="m-0">
                @csrf
                <button type="submit" class="nx-icon-btn" style="width:34px;height:34px" title="{{ __('Sign out') }}"><i class="bi bi-power"></i></button>
            </form>
        </div>
    </aside>
    <div class="nx-scrim" id="nxScrim"></div>

    <div class="nx-main">
        <header class="nx-topbar">
            <div class="nx-crumb">
                <button type="button" class="nx-icon-btn d-lg-none" id="nxMenuBtn" aria-label="Menu"><i class="bi bi-list fs-5"></i></button>
                <button type="button" class="nx-icon-btn d-none d-lg-grid" id="nxCollapseBtn" tabindex="-1" aria-label="Toggle sidebar"><i class="bi bi-layout-sidebar-inset"></i></button>
                <h1 class="nx-page-title">@yield('title', __('Dashboard'))</h1>
            </div>
            <div class="d-flex align-items-center gap-2">
                <span class="nx-clock d-none d-md-inline-flex"><span class="dot"></span><span id="nxClock">&nbsp;</span></span>
                @include('partials.lang-switcher')
                <button type="button" class="nx-icon-btn" id="nxThemeBtn" tabindex="-1" title="{{ __('Toggle light/dark mode') }}" aria-label="{{ __('Toggle light/dark mode') }}">
                    <i class="bi bi-sun-fill" id="nxThemeIcon"></i>
                </button>
            </div>
        </header>

        <div class="nx-toasts" id="nxToasts">
            @if (session('success'))
                <div class="nx-toast ok"><i class="bi bi-check-circle-fill"></i><div>{{ session('success') }}</div><button class="x" type="button" aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
            @endif
            @if (session('error'))
                <div class="nx-toast bad"><i class="bi bi-exclamation-octagon-fill"></i><div>{{ session('error') }}</div><button class="x" type="button" aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
            @endif
            @if ($errors->any())
                <div class="nx-toast bad">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div><strong class="d-block mb-1">{{ __('Please fix the following') }}</strong>
                        <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                    <button class="x" type="button" aria-label="Close"><i class="bi bi-x-lg"></i></button>
                </div>
            @endif
        </div>

        <main class="nx-content">
            @yield('content')
        </main>

        <footer class="nx-footer"><span>© {{ date('Y') }} {{ $storeName }}</span></footer>
    </div>
</div>

{{-- Popup used for add / edit / detail pages --}}
<div class="modal fade" id="nxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg modal-fullscreen-md-down">
        <div class="modal-content">
            <div class="modal-header nx-modal-head">
                <div class="min-w-0">
                    <div class="nx-eyebrow mb-1" id="nxModalEyebrow"></div>
                    <h5 class="modal-title" id="nxModalTitle"></h5>
                </div>
                <button type="button" class="nx-icon-btn" data-bs-dismiss="modal" aria-label="Close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="modal-body" id="nxModalBody"></div>
        </div>
    </div>
</div>

<script>
    (function () {
        var root = document.documentElement, icon = document.getElementById('nxThemeIcon');
        function paint() { icon.className = 'bi ' + (root.getAttribute('data-theme') === 'dark' ? 'bi-sun-fill' : 'bi-moon-stars-fill'); }
        paint();
        window.nxSetTheme = function (next) {
            root.setAttribute('data-theme', next); root.setAttribute('data-bs-theme', next);
            try { localStorage.setItem('nx-theme', next); } catch (e) {}
            paint();
            window.dispatchEvent(new CustomEvent('nx-theme', { detail: next }));
        };
        document.getElementById('nxThemeBtn').addEventListener('click', function () {
            window.nxSetTheme(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
        });

        document.getElementById('nxCollapseBtn').addEventListener('click', function () {
            var on = root.classList.toggle('nx-collapsed');
            try { localStorage.setItem('nx-collapsed', on ? '1' : '0'); } catch (e) {}
            setTimeout(function () { window.dispatchEvent(new Event('resize')); if (window.jQuery && $.fn.dataTable) $.fn.dataTable.tables({ api: true }).columns.adjust().responsive.recalc(); }, 320);
        });

        var sb = document.getElementById('nxSidebar'), sc = document.getElementById('nxScrim');
        function toggle(open) { sb.classList.toggle('open', open); sc.classList.toggle('show', open); }
        document.getElementById('nxMenuBtn').addEventListener('click', function () { toggle(!sb.classList.contains('open')); });
        sc.addEventListener('click', function () { toggle(false); });

        document.querySelectorAll('.nx-toast').forEach(function (t) {
            var close = function () { t.style.transition = 'all .3s'; t.style.opacity = 0; t.style.transform = 'translateX(30px)'; setTimeout(function () { t.remove(); }, 300); };
            t.querySelector('.x').addEventListener('click', close);
            setTimeout(close, 6000);
        });
    })();
</script>
@include('partials.scripts')
</body>
</html>
