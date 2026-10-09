@extends('layouts.form', ['title' => 'Create Product', 'backUrl' => route('products.index')])
@section('form') @include('Products._form', ['action' => route('products.store'), 'method' => 'POST', 'submitLabel' => 'Create product', 'backUrl' => route('products.index')]) @endsection
