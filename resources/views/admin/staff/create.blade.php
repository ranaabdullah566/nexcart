@extends('layouts.admin')
@section('title', __('New staff member'))

@section('content')
<x-page-header :back="route('admin.staff.index')" :title="__('New staff member')" :eyebrow="__('Staff')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.staff._form')</div></div>
@endsection
