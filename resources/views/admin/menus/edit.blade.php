@extends('layouts.admin')
@section('title', __('Edit link'))
@section('content')
<x-page-header :back="route('admin.menu-items.index')" :title="$item->label" :eyebrow="__('Edit link')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.menus._form')</div></div>
@endsection
