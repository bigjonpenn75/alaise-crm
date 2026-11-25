@props(['label', 'name', 'type' => 'text', 'value' => null, 'hint' => null])
@php($key = str_replace(['[', ']'], ['.', ''], $name))
<div>
    <label for="{{ $name }}" class="mb-1 block text-sm font-medium text-slate-700">{{ $label }}</label>
    <input id="{{ $name }}" name="{{ $name }}" type="{{ $type }}"
        @if ($type !== 'password') value="{{ old($key, $value) }}" @endif
        {{ $attributes->class(['input', 'border-red-400' => $errors->has($key)]) }}>
    @if ($hint)<p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>@endif
    @error($key)<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
</div>
