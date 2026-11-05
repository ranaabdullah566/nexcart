@extends('layouts.admin')
@section('title', __('New customer'))
@section('content')
<x-page-header :back="route('admin.customers.index')" :title="__('New customer')" :eyebrow="__('Customers')" />
@include('admin.customers._form')
@endsection
