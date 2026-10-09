@extends('layouts.form', ['title' => 'Create Order', 'backUrl' => route('orders.index')])
@section('form') @include('orders._form', ['action' => route('orders.store'), 'method' => 'POST', 'submitLabel' => 'Create order', 'backUrl' => route('orders.index')]) @endsection
