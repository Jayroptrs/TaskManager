@props(['priority' => 'medium'])
@php
    $classes = 'inline-block rounded-full border px-2 py-1 text-xs font-medium';

    if ($priority === 'low') {
        $classes .= ' bg-slate-500/10 text-slate-400 border-slate-400/25';
    }

    if ($priority === 'medium') {
        $classes .= ' bg-amber-500/10 text-amber-400 border-amber-400/25';
    }

    if ($priority === 'high') {
        $classes .= ' bg-rose-500/10 text-rose-400 border-rose-400/25';
    }
@endphp

<span class="{{ $classes }}">
    {{ $slot }}
</span>

