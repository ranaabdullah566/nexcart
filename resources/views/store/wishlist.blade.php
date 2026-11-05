@extends('layouts.store')
@php($page = 'wishlist')
@section('title', __('Wishlist'))

@section('content')
<div class="sf-container py-4">
    <h1 class="h2 mb-4">{{ __('Wishlist') }}</h1>
    @if ($products->isEmpty())
        <div class="sf-empty"><i class="bi bi-heart"></i><h2 class="h5">{{ __('Nothing saved yet') }}</h2><a href="{{ route('store.shop') }}" class="btn btn-primary mt-3">{{ __('Browse products') }}</a></div>
    @else
        @include('store._grid', ['products' => $products])
    @endif
</div>
@endsection
