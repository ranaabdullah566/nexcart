@extends('layouts.admin')
@section('title', __('New page'))
@section('content')
<x-page-header :back="route('admin.pages.index')" :title="__('New page')" :eyebrow="__('Pages')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.pages._form')</div></div>
@endsection
