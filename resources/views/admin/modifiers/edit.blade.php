@extends('layouts.admin')
@section('title', __('Edit add-on group'))
@section('content')
<x-page-header :back="route('admin.modifier-groups.index')" :title="$group->name" :eyebrow="__('Edit add-on group')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.modifiers._form')</div></div>
@endsection
