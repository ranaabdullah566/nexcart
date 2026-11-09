@extends('layouts.admin')
@section('title', __('Edit article'))
@section('content')
<x-page-header :back="route('admin.articles.index')" :title="$article->title" :eyebrow="__('Edit article')" />
<div class="card nx-form-card"><div class="card-body p-4">@include('admin.articles._form')</div></div>
@endsection
