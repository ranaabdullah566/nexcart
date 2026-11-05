@extends('layouts.store')
@section('title', setting('store.tagline') ? setting('store.name').' — '.setting('store.tagline') : '')
@php($page = 'home')

@section('content')
<div class="sf-container">
    @forelse ($sections as $row)
        @includeIf('store.sections.'.$row['section']->type, ['s' => $row['section']] + $row)
    @empty
        <div class="sf-empty"><i class="bi bi-stars"></i>{{ __('This store is being set up. Please check back soon.') }}</div>
    @endforelse
</div>
@endsection
