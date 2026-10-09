@extends('layouts.form', ['title' => 'Add Cart Item', 'backUrl' => route('cart-items.index')])
@section('form') @include('CartItems._form', ['action' => route('cart-items.store'), 'method' => 'POST', 'submitLabel' => 'Add item', 'backUrl' => route('cart-items.index')]) @endsection
