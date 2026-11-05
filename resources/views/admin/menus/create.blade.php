@extends('layouts.admin')
@section('title', __('New link'))
@section('content')
<x-page-header :back="route('admin.menu-items.index')" :title="__('New link')" :eyebrow="__('Menus')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.menus._form')</div></div>
@endsection
