@extends('layouts.guest')

@section('title', 'Register')

@section('content')
    <div>
        <p class="portal-eyebrow">Join your support portal</p>
        <h2 class="mt-3 text-3xl font-bold tracking-tight">Create your account</h2>
        <p class="mt-2 text-sm leading-6 text-[var(--portal-muted)]">Register as a user to submit and follow support requests.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="mt-8 space-y-5">
        @csrf
        <div><label for="name" class="mb-2 block text-sm font-semibold">Full name</label><input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" class="portal-field" placeholder="Alex Rivera"></div>
        <div><label for="email" class="mb-2 block text-sm font-semibold">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="portal-field" placeholder="alex@example.com"></div>
        <div><label for="password" class="mb-2 block text-sm font-semibold">Password</label><input id="password" name="password" type="password" required autocomplete="new-password" class="portal-field" placeholder="At least 8 characters"></div>
        <div><label for="password_confirmation" class="mb-2 block text-sm font-semibold">Confirm password</label><input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="portal-field" placeholder="Repeat your password"></div>
        <button class="portal-button portal-button-primary w-full" type="submit">Create account</button>
    </form>
    <p class="mt-7 text-center text-sm text-[var(--portal-muted)]">Already registered? <a href="{{ route('login') }}" class="font-semibold text-[var(--portal-primary)] hover:underline">Sign in</a></p>
@endsection
