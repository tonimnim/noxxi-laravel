@extends('layouts.vue-app')

@section('title', 'Booking Confirmed - Noxxi')

@section('content')
    <booking-confirmation-page booking-id="{{ $bookingId }}"></booking-confirmation-page>
@endsection