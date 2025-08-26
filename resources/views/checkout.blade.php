@extends('layouts.vue-app')

@section('title', 'Checkout - Noxxi')

@section('content')
    <checkout-page event-id="{{ $eventId }}"></checkout-page>
@endsection