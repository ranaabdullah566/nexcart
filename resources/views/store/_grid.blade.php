<div class="row g-3 g-md-4 row-cols-2 row-cols-md-3 {{ $cols ?? 'row-cols-xl-'.sf('cards.cols') }}">
    @foreach ($products as $p)
        <div class="col">@include('store._card', ['p' => $p])</div>
    @endforeach
</div>
