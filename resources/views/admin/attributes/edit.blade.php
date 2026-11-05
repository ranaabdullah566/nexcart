@extends('layouts.admin')
@section('title', __('Edit field'))
@section('content')
<x-page-header :back="route('admin.attributes.index')" :title="$attribute->label" :eyebrow="__('Edit field')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.attributes._form')</div></div>
@endsection
