@props(['label', 'name'])
<label class="grid gap-2 text-sm font-semibold">{{ $label }}<textarea name="{{ $name }}" {{ $attributes->class('portal-field') }}>{{ old($name, trim($slot)) }}</textarea>@error($name)<span class="text-red-600">{{ $message }}</span>@enderror</label>
