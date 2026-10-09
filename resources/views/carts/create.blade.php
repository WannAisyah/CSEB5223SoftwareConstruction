@extends('layouts.form', ['title' => 'Create Cart', 'backUrl' => route('carts.index')])
@section('form') @include('carts._form', ['action' => route('carts.store'), 'method' => 'POST', 'submitLabel' => 'Create cart', 'backUrl' => route('carts.index')]) @endsection
