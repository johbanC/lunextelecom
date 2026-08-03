@extends('layouts.admin')

@section('title', 'Mi perfil')

@section('content')
    <div class="max-w-xl mx-auto space-y-6">
        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        <div class="rounded-2xl border border-gray-200 bg-white shadow-sm p-6">
            @include('profile.partials.update-password-form')
        </div>

        <div class="rounded-2xl border border-red-200 bg-white shadow-sm p-6">
            @include('profile.partials.delete-user-form')
        </div>
    </div>
@endsection
