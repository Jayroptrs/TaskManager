@props([
    'title',
    'description',
    'maxWidth' => 'max-w-md',
    'backHref' => null,
    'backText' => 'Terug',
])
<div class="flex min-h-[calc(100dvh-4rem)] items-center justify-center px-4">
    <div class="w-full {{ $maxWidth }} border border-border rounded-lg p-8">
        @if($backHref)
            <a href="{{ $backHref }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
                <span>🡨 {{ $backText }}</span>
            </a>
        @endif

        <div class="{{ $backHref ? 'mt-3 text-left' : 'text-center' }}">
            <h1 class="text-3xl font-bold tracking-tight">{{ $title }}</h1>
            <p class="text-muted-foreground mt-1">{{ $description }}</p>
        </div>

        {{ $slot }}
    </div>
</div>
