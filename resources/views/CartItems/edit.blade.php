@extends('layouts.form', ['title' => 'Edit Cart Item', 'backUrl' => route('cart-items.show', $cartItem)])
@section('form') @include('CartItems._form', ['action' => route('cart-items.update', $cartItem), 'method' => 'PUT', 'submitLabel' => 'Save changes', 'backUrl' => route('cart-items.show', $cartItem)]) @endsection
