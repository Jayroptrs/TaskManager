<select
    {{ $attributes->class([
        'input input-neon-select form-control',
        'focus:outline-none',
    ]) }}
>
    {{ $slot }}
</select>
