@php
    $fieldName = $attributes->get('name');
    $hasError = $fieldName && $errors->has($fieldName);
@endphp

<textarea
    {{ $attributes->class([
        'textarea form-control',
        'is-invalid' => $hasError,
        'focus:outline-none',
    ]) }}
>{{ $slot }}</textarea>
