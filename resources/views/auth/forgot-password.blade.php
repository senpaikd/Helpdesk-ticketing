@extends('layouts.guest')

@section('title', 'Forgot password')

@section('content')
    <p class="portal-eyebrow">Account recovery</p>
    <h2 class="mt-3 text-3xl font-bold tracking-tight">Reset your password</h2>
    <p class="mt-2 text-sm leading-6 text-[var(--portal-muted)]">Enter your email and HelpDesk will send a secure reset link.</p>
    <form method="POST" action="{{ route('password.email') }}" class="mt-8 space-y-5">
        @csrf
        <div><label for="email" class="mb-2 block text-sm font-semibold">Email address</label><input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="portal-field" placeholder="you@example.com"></div>
        <button class="portal-button portal-button-primary w-full" type="submit">Email reset link</button>
    </form>
    <p class="mt-7 text-center text-sm"><a href="{{ route('login') }}" class="font-semibold text-[var(--portal-primary)] hover:underline">Back to sign in</a></p>
@endsection
