@props(['title', 'description'])
<div {{ $attributes->class('px-6 py-16 text-center') }}><h2 class="text-lg font-bold">{{ $title }}</h2><p class="mt-2 text-sm text-[var(--portal-muted)]">{{ $description }}</p>{{ $slot }}</div>
