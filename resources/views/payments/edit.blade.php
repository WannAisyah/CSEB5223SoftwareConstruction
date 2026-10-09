@extends('layouts.form', ['title' => 'Edit Payment', 'backUrl' => route('payments.show', $payment)])
@section('form') @include('payments._form', ['action' => route('payments.update', $payment), 'method' => 'PUT', 'submitLabel' => 'Save changes', 'backUrl' => route('payments.show', $payment)]) @endsection
