@extends('layouts.admin')
@section('title', __('Edit time slot'))
@section('content')
<x-page-header :back="route('admin.delivery-slots.index')" :title="$slot->label" :eyebrow="__('Edit time slot')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.slots._form')</div></div>
@endsection
