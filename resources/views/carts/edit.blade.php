@extends('layouts.form', ['title' => 'Edit Cart', 'backUrl' => route('carts.show', $cart)])
@section('form') @include('carts._form', ['action' => route('carts.update', $cart), 'method' => 'PUT', 'submitLabel' => 'Save changes', 'backUrl' => route('carts.show', $cart)]) @endsection
