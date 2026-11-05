@extends('layouts.admin')
@section('title', __('New category'))
@section('content')
<x-page-header :back="route('admin.categories.index')" :title="__('New category')" :eyebrow="__('Categories')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.categories._form')</div></div>
@endsection
