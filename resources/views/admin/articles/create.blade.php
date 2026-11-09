@extends('layouts.admin')
@section('title', __('New article'))
@section('content')
<x-page-header :back="route('admin.articles.index')" :title="__('New article')" :eyebrow="__('Health blog')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.articles._form')</div></div>
@endsection
