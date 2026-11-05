@extends('layouts.admin')
@section('title', __('Edit section'))
@section('content')
<x-page-header :back="route('admin.home-sections.index')" :title="$section->title ?: ucfirst($section->type)" :eyebrow="__('Edit section')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.home._form')</div></div>
@endsection
