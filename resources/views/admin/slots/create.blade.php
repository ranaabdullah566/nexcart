@extends('layouts.admin')
@section('title', __('New time slot'))
@section('content')
<x-page-header :back="route('admin.delivery-slots.index')" :title="__('New time slot')" :eyebrow="__('Delivery slots')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.slots._form')</div></div>
@endsection
