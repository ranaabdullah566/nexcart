<i id="nxPageEnd" hidden></i>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/jquery.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/jquery.dataTables.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/dataTables.bootstrap5.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/dataTables.responsive.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/responsive.bootstrap5.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/select2.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/chart.umd.min.js') }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/nx-validate.js') }}?v={{ @filemtime(public_path('assets/js/nx-validate.js')) }}"></script>
<script defer nonce="{{ csp_nonce() }}" src="{{ asset('assets/js/nexus.js') }}?v={{ @filemtime(public_path('assets/js/nexus.js')) }}"></script>
<i id="nxPageScripts" hidden></i>
@php
    // Libraries are deferred, so a page's inline scripts wait for DOMContentLoaded.
    // Popup fetches (X-NX-Modal) keep raw scripts: nexus.js runs those itself.
    $pageScripts = $__env->yieldPushContent('scripts');
    if (! request()->hasHeader('X-NX-Modal')) {
        $pageScripts = preg_replace_callback('#<script(?![^>]*\bsrc=)([^>]*)>(.*?)</script>#s',
            fn ($m) => '<script'.$m[1].">document.addEventListener('DOMContentLoaded',function(){".$m[2].'});</script>', $pageScripts);
        $pageScripts = preg_replace('#<script (?=[^>]*\bsrc=)#', '<script defer ', $pageScripts);
    }
@endphp
{!! $pageScripts !!}
