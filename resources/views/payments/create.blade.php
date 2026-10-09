@extends('layouts.form', ['title' => 'Record Payment', 'backUrl' => route('payments.index')])
@section('form') @include('payments._form', ['action' => route('payments.store'), 'method' => 'POST', 'submitLabel' => 'Record payment', 'backUrl' => route('payments.index')]) @endsection
