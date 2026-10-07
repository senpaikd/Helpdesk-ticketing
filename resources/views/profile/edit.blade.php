@extends('layouts.portal')

@section('title', 'Profile settings — HelpDesk')

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="portal-eyebrow">Your account</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight">Profile settings</h1>
                <p class="mt-2 text-sm text-[var(--portal-muted)]">Manage your contact details, password, and signed-in devices.</p>
            </div>
            <a href="{{ route('portal') }}" class="text-sm font-semibold text-[var(--portal-primary)] hover:underline">Back to dashboard</a>
        </div>

        <div class="mt-7 grid gap-6 lg:grid-cols-2">
            <section class="rounded-3xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-[var(--portal-shadow)]" aria-labelledby="profile-details-heading">
                <h2 id="profile-details-heading" class="text-lg font-bold">Contact details</h2>
                <form method="POST" action="{{ route('profile.update') }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PATCH')
                    <div>
                        <label for="name" class="mb-2 block text-sm font-semibold">Full name</label>
                        <input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required class="portal-field">
                        @error('name')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="email" class="mb-2 block text-sm font-semibold">Email address</label>
                        <input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required class="portal-field">
                        @error('email')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <button class="portal-button portal-button-primary" type="submit">Save details</button>
                </form>
            </section>

            <section class="rounded-3xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-[var(--portal-shadow)]" aria-labelledby="password-heading">
                <h2 id="password-heading" class="text-lg font-bold">Change password</h2>
                <p class="mt-2 text-sm text-[var(--portal-muted)]">Changing your password signs out every other device.</p>
                <form method="POST" action="{{ route('profile.password.update') }}" class="mt-5 space-y-4">
                    @csrf
                    @method('PATCH')
                    <div><label for="current_password" class="mb-2 block text-sm font-semibold">Current password</label><input id="current_password" name="current_password" type="password" autocomplete="current-password" required class="portal-field">@error('current_password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="new_password" class="mb-2 block text-sm font-semibold">New password</label><input id="new_password" name="password" type="password" autocomplete="new-password" required class="portal-field">@error('password')<p class="mt-2 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="password_confirmation" class="mb-2 block text-sm font-semibold">Confirm new password</label><input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required class="portal-field"></div>
                    <button class="portal-button portal-button-primary" type="submit">Update password</button>
                </form>
            </section>
        </div>

        <section class="mt-6 rounded-3xl border border-[var(--portal-border)] bg-[var(--portal-surface)] p-6 shadow-[var(--portal-shadow)]" aria-labelledby="sessions-heading">
            <h2 id="sessions-heading" class="text-lg font-bold">Active sessions</h2>
            <p class="mt-1 text-sm text-[var(--portal-muted)]">Review devices currently signed in to your HelpDesk account.</p>

            <div class="mt-5 divide-y divide-[var(--portal-border)]">
                @forelse ($sessions as $session)
                    <article class="flex flex-col gap-2 py-4 first:pt-0 last:pb-0 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <p class="font-semibold">
                                {{ $session->device }}
                                @if ($session->is_current)<span class="ml-2 rounded-full bg-[var(--portal-soft)] px-2 py-1 text-xs text-[var(--portal-primary)]">This device</span>@endif
                            </p>
                            <p class="mt-1 text-sm text-[var(--portal-muted)]">{{ $session->ip_address }} · Active {{ $session->last_active_at->diffForHumans() }}</p>
                        </div>
                    </article>
                @empty
                    <p class="py-4 text-sm text-[var(--portal-muted)]">No database session records are available yet.</p>
                @endforelse
            </div>

            <form method="POST" action="{{ route('profile.sessions.destroy-others') }}" class="mt-6 grid gap-3 border-t border-[var(--portal-border)] pt-5 sm:grid-cols-[1fr_auto] sm:items-end">
                @csrf
                @method('DELETE')
                <label class="grid gap-2 text-sm font-semibold">Confirm your password<input name="current_password" type="password" autocomplete="current-password" required class="portal-field">@error('current_password')<span class="text-sm text-red-600">{{ $message }}</span>@enderror</label>
                <button class="portal-button portal-button-secondary" type="submit">Sign out other devices</button>
            </form>
        </section>

        <form method="POST" action="{{ route('logout') }}" class="mt-6">
            @csrf
            <button class="portal-button portal-button-secondary" type="submit">Log out of this device</button>
        </form>
    </div>
@endsection
