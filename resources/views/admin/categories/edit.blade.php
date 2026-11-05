@extends('layouts.admin')
@section('title', __('Edit category'))
@section('content')
<x-page-header :back="route('admin.categories.index')" :title="$category->name" :eyebrow="__('Edit category')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.categories._form')</div></div>
@endsection
