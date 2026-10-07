@extends('layouts.guest')

@section('title', 'Choose a new password')

@section('content')
    <p class="portal-eyebrow">Account recovery</p>
    <h2 class="mt-3 text-3xl font-bold tracking-tight">Choose a new password</h2>
    <form method="POST" action="{{ route('password.update') }}" class="mt-8 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">
        <div><label for="email" class="mb-2 block text-sm font-semibold">Email address</label><input id="email" name="email" type="email" value="{{ old('email', $request->email) }}" required class="portal-field"></div>
        <div><label for="password" class="mb-2 block text-sm font-semibold">New password</label><input id="password" name="password" type="password" required autocomplete="new-password" class="portal-field"></div>
        <div><label for="password_confirmation" class="mb-2 block text-sm font-semibold">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="portal-field"></div>
        <button class="portal-button portal-button-primary w-full" type="submit">Reset password</button>
    </form>
@endsection
