@extends('layouts.form', ['title' => 'Create Product', 'backUrl' => route('products.index')])
@section('form') @include('products._form', ['action' => route('products.store'), 'method' => 'POST', 'submitLabel' => 'Create product', 'backUrl' => route('products.index')]) @endsection
