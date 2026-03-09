@props(['is' => 'a', 'hoverable' => true])
@php
    $baseClasses = 'border border-border/90 rounded-xl bg-card/90 p-4 md:text-sm block shadow-[0_10px_24px_rgba(0,0,0,0.08)]';
    $hoverClasses = ' transition-all duration-300 ease-out hover:border-primary/55 hover:shadow-[0_14px_32px_color-mix(in_srgb,var(--color-primary)_22%,transparent)]';
@endphp

<{{ $is }} {{ $attributes(['class' => $baseClasses . ($hoverable ? $hoverClasses : '')]) }}>
    {{ $slot }} 
</{{ $is }}>
