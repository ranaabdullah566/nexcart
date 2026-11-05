<div class="d-flex justify-content-end"><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
<div class="row g-4">
    <div class="col-md-5">
        <div class="sf-gallery-main" style="border-radius:20px">
            @if ($product->getFirstMedia('images'))<img src="{{ $product->getFirstMediaUrl('images') }}" alt="">@else<i class="bi bi-box-seam ph"></i>@endif
        </div>
    </div>
    <div class="col-md-7">
        <h2 class="h3">{{ $product->trans('name') }}</h2>
        @if ($product->trans('short_description'))<p class="text-muted">{{ $product->trans('short_description') }}</p>@endif
        @include('store._buy')
        <a href="{{ route('store.product', $product->slug) }}" class="sf-link-arrow d-inline-block mt-3">{{ __('Full details') }} <i class="bi bi-arrow-right"></i></a>
    </div>
</div>
