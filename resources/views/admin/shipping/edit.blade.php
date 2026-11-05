@extends('layouts.admin')
@section('title', __('Edit zone'))
@section('content')
<x-page-header :back="route('admin.shipping.index')" :title="$zone->name" :eyebrow="__('Edit zone')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.shipping._form')</div></div>
@endsection
