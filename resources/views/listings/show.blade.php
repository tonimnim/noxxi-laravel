@extends('layouts.vue-app')

@section('title', $event->title . ' - Noxxi')

@section('meta')
    <meta name="description" content="{{ Str::limit($event->description, 160) }}">
    <meta property="og:title" content="{{ $event->title }}">
    <meta property="og:description" content="{{ Str::limit($event->description, 160) }}">
    <meta property="og:image" content="{{ $event->cover_image_url }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="event">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $event->title }}">
    <meta name="twitter:description" content="{{ Str::limit($event->description, 160) }}">
    <meta name="twitter:image" content="{{ $event->cover_image_url }}">
@endsection

@section('content')
    <event-details-page :initial-event='@json($eventData)'></event-details-page>
@endsection