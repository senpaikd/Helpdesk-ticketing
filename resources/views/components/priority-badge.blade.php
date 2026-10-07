@props(['priority'])
<span {{ $attributes->class(['rounded-full px-2.5 py-1 text-xs font-semibold', $priority->badgeClasses()]) }}>{{ $priority->label() }}</span>
