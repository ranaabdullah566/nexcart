@extends('layouts.admin')
@section('title', __('New zone'))
@section('content')
<x-page-header :back="route('admin.shipping.index')" :title="__('New zone')" :eyebrow="__('Shipping')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.shipping._form')</div></div>
@endsection
