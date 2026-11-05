@extends('layouts.admin')
@section('title', __('New brand'))
@section('content')
<x-page-header :back="route('admin.brands.index')" :title="__('New brand')" :eyebrow="__('Brands')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.brands._form')</div></div>
@endsection
