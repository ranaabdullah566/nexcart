@extends('layouts.admin')
@section('title', __('New role'))
@section('content')
<x-page-header :back="route('admin.roles.index')" :title="__('New role')" :eyebrow="__('Roles & permissions')" />
@include('admin.roles._form')
@endsection
