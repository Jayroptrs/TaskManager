@props([
    'status' => '500',
    'title' => __('ui.error_500_title'),
    'description' => __('ui.error_500_description'),
    'hint' => __('ui.error_500_hint'),
    'secondaryAction' => 'back', // back | reload
])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ $status }} - {{ __('ui.app_title') }}</title>
    <link rel="icon" type="image/svg+xml" href="/favicon.svg">
    <script>
        (() => {
            const savedTheme = localStorage.getItem('theme');
            const savedAccent = localStorage.getItem('accent') || 'green';
            const savedMotion = localStorage.getItem('motion') || 'on';
            const systemPrefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = savedTheme ?? (systemPrefersDark ? 'dark' : 'light');
            document.documentElement.dataset.theme = theme;
            document.documentElement.dataset.accent = savedAccent;
            document.documentElement.dataset.motion = savedMotion;
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-background text-foreground antialiased">
    <main class="mx-auto flex min-h-screen min-h-dvh w-full max-w-3xl items-center px-4 py-10 sm:px-6">
        <section class="w-full rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,white_4%),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-6 shadow-[0_20px_45px_color-mix(in_srgb,black_12%,transparent),0_0_20px_color-mix(in_srgb,var(--color-primary)_16%,transparent)] sm:p-8">
            <p class="text-xs font-semibold uppercase tracking-[0.08em] text-muted-foreground">Error {{ $status }}</p>
            <h1 class="mt-2 text-3xl font-bold tracking-tight sm:text-4xl">{{ $title }}</h1>
            <p class="mt-3 text-sm text-muted-foreground sm:text-base">{{ $description }}</p>
            <p class="mt-2 text-sm text-muted-foreground/90">{{ $hint }}</p>

            <div class="mt-6 flex flex-wrap gap-2">
                <a href="/" class="btn h-10 px-4">{{ __('ui.error_home') }}</a>

                @if ($secondaryAction === 'reload')
                    <button type="button" onclick="window.location.reload()" class="btn btn-outlined h-10 px-4">{{ __('ui.error_reload') }}</button>
                @else
                    <button type="button" onclick="history.back()" class="btn btn-outlined h-10 px-4">{{ __('ui.error_back') }}</button>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
