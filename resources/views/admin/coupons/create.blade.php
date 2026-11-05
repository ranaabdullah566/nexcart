@extends('layouts.admin')
@section('title', __('New coupon'))
@section('content')
<x-page-header :back="route('admin.coupons.index')" :title="__('New coupon')" :eyebrow="__('Coupons')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.coupons._form')</div></div>
@endsection
