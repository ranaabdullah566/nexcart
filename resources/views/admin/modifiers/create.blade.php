@extends('layouts.admin')
@section('title', __('New add-on group'))
@section('content')
<x-page-header :back="route('admin.modifier-groups.index')" :title="__('New add-on group')" :eyebrow="__('Add-ons')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.modifiers._form')</div></div>
@endsection
