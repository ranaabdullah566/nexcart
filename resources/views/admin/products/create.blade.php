@extends('layouts.admin')
@section('title', __('New product'))
@section('content')
<x-page-header :back="route('admin.products.index')" :title="__('New product')" :eyebrow="__('Products')" />
@include('admin.products._form')
@endsection
