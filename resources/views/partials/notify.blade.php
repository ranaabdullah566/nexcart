{{-- Laravel Notify messages, drawn with NEXUS toasts (the package's own Alpine view needs 'unsafe-eval', which our CSP forbids). --}}
@php
    $items = session('notify_list');
    if (! is_array($items) && is_array($n = session('notify')) && filled($n['message'] ?? null)) {
        $items = [['type' => $n['type'] instanceof \BackedEnum ? $n['type']->value : (string) $n['type'], 'message' => $n['message']]];
    }
    $items = is_array($items) ? $items : [];
@endphp
@foreach ($items as $it)
    @php
        $type = $it['type'];
        $cls = ['success' => 'ok', 'error' => 'bad', 'warning' => 'warn', 'info' => 'info'][$type] ?? 'info';
        $icon = ['success' => 'check-circle-fill', 'error' => 'exclamation-octagon-fill', 'warning' => 'exclamation-triangle-fill', 'info' => 'info-circle-fill'][$type] ?? 'info-circle-fill';
    @endphp
    <div class="nx-toast {{ $cls }} nx-notify" role="{{ $type === 'error' ? 'alert' : 'status' }}"><i class="bi bi-{{ $icon }}"></i><div>{{ $it['message'] }}</div><button class="x" type="button" aria-label="Close"><i class="bi bi-x-lg"></i></button></div>
@endforeach
@if ($items)
    <script nonce="{{ csp_nonce() }}">
        (function () {
            document.querySelectorAll('.nx-notify').forEach(function (t) {
                var close = function () { t.style.transition = 'all .3s'; t.style.opacity = 0; t.style.transform = 'translateX(30px)'; setTimeout(function () { t.remove(); }, 300); };
                var b = t.querySelector('.x'); if (b) b.addEventListener('click', close);
                setTimeout(close, t.classList.contains('bad') ? 8000 : 5500);
            });
        })();
    </script>
@endif
