@extends('layouts.admin')
@section('title', __('Edit staff member'))

@section('content')
<x-page-header :back="route('admin.staff.index')" :title="$staff->name" :eyebrow="__('Edit staff member')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.staff._form')</div></div>
@endsection
