@php($full = (int) floor($value + 0.25))
@for ($i = 1; $i <= 5; $i++)<i class="bi bi-star-fill {{ $i <= $full ? '' : 'off' }}"></i>@endfor
