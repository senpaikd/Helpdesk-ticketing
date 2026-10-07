@props(['label', 'name'])
<label class="grid gap-2 text-sm font-semibold">{{ $label }}<select name="{{ $name }}" {{ $attributes->class('portal-field') }}>{{ $slot }}</select>@error($name)<span class="text-red-600">{{ $message }}</span>@enderror</label>
