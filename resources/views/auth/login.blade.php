@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <div>
        <p class="portal-eyebrow">Secure account access</p>
        <h2 class="mt-3 text-3xl font-bold tracking-tight">Welcome back</h2>
        <p class="mt-2 text-sm leading-6 text-[var(--portal-muted)]">Sign in to view your tickets and continue conversations.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="mt-8 space-y-5" novalidate>
        @csrf
        <div>
            <label for="email" class="mb-2 block text-sm font-semibold">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" class="portal-field" placeholder="you@example.com" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
            @error('email')<p id="email-error" class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
        </div>
        <div>
            <div class="mb-2 flex items-center justify-between gap-4">
                <label for="password" class="text-sm font-semibold">Password</label>
                <a href="{{ route('password.request') }}" class="text-sm font-semibold text-[var(--portal-primary)] hover:underline">Forgot password?</a>
            </div>
            <div class="relative">
                <input id="password" name="password" type="password" required autocomplete="current-password" class="portal-field pr-20" placeholder="Enter your password" @error('password') aria-invalid="true" aria-describedby="password-error" @enderror>
                <button type="button" class="absolute inset-y-0 right-3 text-sm font-semibold text-[var(--portal-primary)]" data-password-toggle="password" aria-label="Show password">Show</button>
            </div>
            @error('password')<p id="password-error" class="mt-2 text-sm text-red-600 dark:text-red-300">{{ $message }}</p>@enderror
        </div>
        <label class="flex items-center gap-3 text-sm text-[var(--portal-muted)]">
            <input type="checkbox" name="remember" value="1" class="size-4 rounded border-[var(--portal-border)] text-[var(--portal-primary)]" @checked(old('remember'))>
            Keep me signed in for 30 days on this device
        </label>
        <button class="portal-button portal-button-primary w-full" type="submit">Sign in</button>
    </form>

    <p class="mt-7 text-center text-sm text-[var(--portal-muted)]">New to HelpDesk? <a href="{{ route('register') }}" class="font-semibold text-[var(--portal-primary)] hover:underline">Create an account</a></p>
@endsection
