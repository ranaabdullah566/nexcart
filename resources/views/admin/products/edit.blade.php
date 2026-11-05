@extends('layouts.admin')
@section('title', __('Edit product'))
@section('content')
<x-page-header :back="route('admin.products.index')" :title="$product->name" :eyebrow="__('Products')" />
@include('admin.products._form')
@endsection
