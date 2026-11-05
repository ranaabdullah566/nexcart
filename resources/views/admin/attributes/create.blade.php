@extends('layouts.admin')
@section('title', __('New field'))
@section('content')
<x-page-header :back="route('admin.attributes.index')" :title="__('New field')" :eyebrow="__('Product fields')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.attributes._form')</div></div>
@endsection
