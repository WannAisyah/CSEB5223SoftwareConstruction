@extends('layouts.form', ['title' => 'Edit Product', 'backUrl' => route('products.show', $product)])
@section('form') @include('Products._form', ['action' => route('products.update', $product), 'method' => 'PUT', 'submitLabel' => 'Save changes', 'backUrl' => route('products.show', $product)]) @endsection
