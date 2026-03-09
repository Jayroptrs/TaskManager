@props(['type' => 'text'])

<input
    type="{{ $type }}"
    {{ $attributes->class([
        'input form-control',
        'focus:outline-none',
    ]) }}
>
