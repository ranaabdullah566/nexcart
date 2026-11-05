@extends('layouts.admin')
@section('title', __('New section'))
@section('content')
<x-page-header :back="route('admin.home-sections.index')" :title="__('New section')" :eyebrow="__('Home page')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.home._form')</div></div>
@endsection
