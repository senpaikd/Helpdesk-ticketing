@props(['label', 'name', 'type' => 'text'])
<label class="grid gap-2 text-sm font-semibold">{{ $label }}<input type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $attributes->get('value')) }}" {{ $attributes->except('value')->class('portal-field') }}>@error($name)<span class="text-red-600">{{ $message }}</span>@enderror</label>
