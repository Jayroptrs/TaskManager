<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>{{ __('ui.app_title') }}</title>
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
    <div class="neon-scene" data-neon-scene aria-hidden="true">
        <div class="neon-scene-layer neon-scene-backdrop"></div>
        <div class="neon-scene-layer neon-orb neon-orb--green" data-neon-orb data-start-x="12" data-start-y="16"></div>
        <div class="neon-scene-layer neon-orb neon-orb--cyan" data-neon-orb data-start-x="84" data-start-y="14"></div>
        <div class="neon-scene-layer neon-orb neon-orb--rose" data-neon-orb data-start-x="72" data-start-y="72"></div>
        <div class="neon-scene-layer neon-orb neon-orb--pink" data-neon-orb data-start-x="90" data-start-y="48"></div>
        <div class="neon-scene-layer neon-orb neon-orb--orange" data-neon-orb data-start-x="54" data-start-y="20"></div>
        <div class="neon-scene-layer neon-orb neon-orb--violet" data-neon-orb data-start-x="18" data-start-y="78"></div>
        <div class="neon-scene-layer neon-orb neon-orb--yellow" data-neon-orb data-start-x="48" data-start-y="84"></div>
        <div class="neon-scene-layer neon-scene-vignette"></div>
        <div class="neon-scene-layer neon-orb neon-orb--cursor" data-cursor-orb></div>
    </div>

    <div class="app-shell min-h-screen min-h-dvh flex flex-col">
        <x-layout.nav />
        
        <main class="flex-1 max-w-7xl w-full mx-auto px-4 pb-6 sm:px-6 sm:pb-12">
            {{ $slot }}
        </main>

        <x-layout.onboarding />

        <x-layout.footer />
    </div>

    @session('success')
        <div 
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-2"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed left-4 right-4 bottom-4 sm:left-auto sm:right-4 z-90 inline-flex items-center gap-2 rounded-xl border border-[color:color-mix(in_srgb,var(--color-primary)_48%,var(--color-border))] bg-[color:color-mix(in_srgb,var(--color-card)_88%,var(--color-primary)_12%)] px-4 py-3 text-sm font-semibold text-foreground shadow-[0_12px_28px_color-mix(in_srgb,black_12%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_30%,transparent)]"
            role="status"
            aria-live="polite"
        >
            <span class="h-2 w-2 rounded-full bg-[color:color-mix(in_srgb,var(--color-primary)_82%,white_8%)] shadow-[0_0_10px_color-mix(in_srgb,var(--color-primary)_55%,transparent)]" aria-hidden="true"></span>
            <span>{{ $value }}</span>
        </div>
    @endsession

    <div
        x-data="{ show: false, message: '', type: 'success', timer: null }"
        @toast.window="
            message = $event.detail?.message ?? '';
            type = $event.detail?.type ?? 'success';
            show = true;

            if (timer) clearTimeout(timer);
            timer = setTimeout(() => show = false, 3500);
        "
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 translate-y-2"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed left-4 right-4 bottom-4 sm:left-auto sm:right-4 z-[95] inline-flex items-center gap-2 rounded-xl border px-4 py-3 text-sm font-semibold shadow-[0_12px_28px_color-mix(in_srgb,black_12%,transparent)]"
        :class="type === 'error'
            ? 'border-[color:color-mix(in_srgb,#ef4444_48%,var(--color-border))] bg-[color:color-mix(in_srgb,var(--color-card)_88%,#ef4444_10%)] text-foreground'
            : 'border-[color:color-mix(in_srgb,var(--color-primary)_48%,var(--color-border))] bg-[color:color-mix(in_srgb,var(--color-card)_88%,var(--color-primary)_12%)] text-foreground'"
        role="status"
        aria-live="polite"
        style="display: none;"
    >
        <span
            class="h-2 w-2 rounded-full"
            :class="type === 'error'
                ? 'bg-[#ef4444] shadow-[0_0_10px_color-mix(in_srgb,#ef4444_55%,transparent)]'
                : 'bg-[color:color-mix(in_srgb,var(--color-primary)_82%,white_8%)] shadow-[0_0_10px_color-mix(in_srgb,var(--color-primary)_55%,transparent)]'"
            aria-hidden="true"
        ></span>
        <span x-text="message"></span>
    </div>
</body>
</html>
