@props(['type' => 'text'])

@php
    $fieldName = $attributes->get('name');
    $hasError = $fieldName && $errors->has($fieldName);
@endphp

<input
    type="{{ $type }}"
    {{ $attributes->class([
        'input form-control',
        'is-invalid' => $hasError,
        'focus:outline-none',
    ]) }}
>
