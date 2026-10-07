@props(['type' => 'success', 'message' => null])
@if($message)
<div {{ $attributes->class(['rounded-2xl border px-5 py-4 text-sm font-semibold', 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:border-emerald-800 dark:bg-emerald-950 dark:text-emerald-200' => $type === 'success', 'border-red-300 bg-red-50 text-red-900 dark:border-red-900 dark:bg-red-950 dark:text-red-200' => $type === 'error']) }} role="status">{{ $message }}</div>
@endif
