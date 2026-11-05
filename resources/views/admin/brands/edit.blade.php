@extends('layouts.admin')
@section('title', __('Edit brand'))
@section('content')
<x-page-header :back="route('admin.brands.index')" :title="$brand->name" :eyebrow="__('Edit brand')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.brands._form')</div></div>
@endsection
