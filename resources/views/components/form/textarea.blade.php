<textarea
    {{ $attributes->class([
        'textarea form-control',
        'focus:outline-none',
    ]) }}
>{{ $slot }}</textarea>
