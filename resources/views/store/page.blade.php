@extends('layouts.store')
@section('title', $cms->trans('title'))
@section('description', \Illuminate\Support\Str::limit(strip_tags((string) $cms->trans('body')), 160))

@section('content')
<div class="sf-container py-5" style="max-width:820px">
    <nav class="sf-crumbs" aria-label="Breadcrumb"><a href="{{ route('store.home') }}">{{ __('Home') }}</a><span>/</span><span>{{ $cms->trans('title') }}</span></nav>
    <h1 class="mb-4">{{ $cms->trans('title') }}</h1>
    <div class="sf-prose" style="max-width:none">{!! nl2br(e($cms->trans('body'))) !!}</div>
</div>
@endsection
