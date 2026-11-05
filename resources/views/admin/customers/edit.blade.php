@extends('layouts.admin')
@section('title', __('Edit customer'))
@section('content')
<x-page-header :back="route('admin.customers.index')" :title="__('Edit customer')" :eyebrow="__('Customers')" />
@include('admin.customers._form')
@endsection
