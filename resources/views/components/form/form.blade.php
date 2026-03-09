@props([
    'title',
    'description',
    'maxWidth' => 'max-w-md',
    'backHref' => null,
    'backText' => null,
])
<div class="flex items-start justify-center px-4 pt-24 pb-8 sm:min-h-[calc(100dvh-4rem)] sm:items-start sm:pt-14 sm:pb-4">
    <div class="w-full {{ $maxWidth }}">
        <div class="rounded-2xl border border-border/80 bg-[linear-gradient(160deg,color-mix(in_srgb,var(--color-card)_96%,white_4%),color-mix(in_srgb,var(--color-input)_20%,var(--color-card)))] p-6 sm:p-8 shadow-[0_20px_45px_color-mix(in_srgb,black_10%,transparent),0_0_22px_color-mix(in_srgb,var(--color-primary)_14%,transparent)]">
        @if($backHref)
            <a href="{{ $backHref }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
                <span>&larr; {{ $backText ?: __('ui.back') }}</span>
            </a>
        @endif

        <div class="{{ $backHref ? 'mt-3 text-left' : 'text-center' }}">
            <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>
            <p class="text-muted-foreground mt-1">{{ $description }}</p>
        </div>

            {{ $slot }}
        </div>

        @isset($outside)
            <div class="mt-2 text-center text-sm text-muted-foreground">
                {{ $outside }}
            </div>
        @endisset
    </div>
</div>

