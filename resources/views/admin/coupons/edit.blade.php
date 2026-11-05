@extends('layouts.admin')
@section('title', __('Edit coupon'))
@section('content')
<x-page-header :back="route('admin.coupons.index')" :title="$coupon->code" :eyebrow="__('Edit coupon')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.coupons._form')</div></div>
@endsection
