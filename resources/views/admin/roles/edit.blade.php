@extends('layouts.admin')
@section('title', __('Edit role'))
@section('content')
<x-page-header :back="route('admin.roles.index')" :title="__('Edit role').' · '.__(ucfirst($role->name))" :eyebrow="__('Roles & permissions')" />
@include('admin.roles._form')
@endsection
