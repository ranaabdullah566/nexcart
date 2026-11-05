@extends('layouts.admin')
@section('title', __('Edit page'))
@section('content')
<x-page-header :back="route('admin.pages.index')" :title="$page->title" :eyebrow="__('Edit page')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.pages._form')</div></div>
@endsection
