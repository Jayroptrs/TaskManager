<div x-data="{ theme: document.documentElement.dataset.theme || 'light', mobileMenuOpen: false }">
<nav class="sticky top-0 z-50 border-b border-border/80 bg-card/80 px-4 sm:px-6 py-3 backdrop-blur-md">
    <div class="max-w-7xl mx-auto min-h-16 flex items-center relative">
        @php
            $brandClass = "inline-block font-['Trebuchet_MS','Avenir_Next','Segoe_UI',sans-serif] text-[clamp(1.5rem,2.8vw,2rem)] font-extrabold uppercase tracking-[0.06em] leading-none text-[color:color-mix(in_srgb,var(--color-primary)_70%,var(--color-foreground))] [text-shadow:0_0_18px_color-mix(in_srgb,var(--color-primary)_35%,transparent)] transition-all duration-200 hover:-translate-y-px hover:text-[color:color-mix(in_srgb,var(--color-primary)_86%,var(--color-foreground))] hover:[text-shadow:0_0_10px_color-mix(in_srgb,var(--color-primary)_60%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_50%,transparent),0_0_40px_color-mix(in_srgb,var(--color-primary)_35%,transparent)]";
        @endphp
        <div>
            <a href="/" class="{{ $brandClass }}" aria-label="Jayro Home">Jayro</a>
        </div>

        @auth
            <div class="hidden md:flex absolute left-1/2 -translate-x-1/2 pointer-events-none">
                <span class="font-bold text-foreground/90 whitespace-nowrap">Welkom, {{ auth()->user()->name }}!</span>
            </div>
        @endauth

        <div class="ml-auto flex md:hidden items-center gap-1.5">
            @auth
                <button
                    type="button"
                    @click="mobileMenuOpen = true"
                    aria-label="Open menu"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border/80 bg-card/85 text-foreground/85 transition-all duration-200 hover:border-primary/45 hover:text-primary hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_28%,transparent)]"
                >
                    <span class="text-base leading-none">&#9776;</span>
                </button>
            @endauth

            @guest
                <a href="/register" class="btn h-8 leading-8 px-2.5 text-xs">Registreren</a>
                <div class="flex items-center gap-1.5">
                    <span class="text-xs text-muted-foreground">&#127769;</span>
                    <button
                        type="button"
                        class="relative h-6 w-11 rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)] transition-all duration-200 hover:border-[color:color-mix(in_srgb,var(--color-primary)_44%,var(--color-border))] hover:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_30%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                        role="switch"
                        :aria-checked="theme === 'dark' ? 'true' : 'false'"
                        @click="theme = theme === 'dark' ? 'light' : 'dark'; document.documentElement.dataset.theme = theme; localStorage.setItem('theme', theme)"
                    >
                        <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-muted-foreground transition-all duration-200" :class="theme === 'dark' ? 'translate-x-5 bg-primary shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_45%,transparent)]' : 'translate-x-0'"></span>
                    </button>
                    <span class="text-xs text-muted-foreground">&#9728;</span>
                </div>
            @endguest
        </div>

        <div class="ml-auto hidden md:flex gap-5 items-center">
            @auth
                <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('dashboard.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('dashboard.index') }}">Dashboard</a>
                @if (auth()->user()->isAdmin())
                    <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('admin.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('admin.index') }}">Admin</a>
                @endif
                <a class="text-foreground/80 hover:text-foreground" href="{{ route('profile.edit') }}">Account</a>
            @endauth

            @guest
                <a class="text-foreground/80 hover:text-foreground" href="/login">Inloggen</a>
                <a href="/register" class="btn">Registreren</a>
            @endguest

            <div class="flex items-center gap-2">
                <span class="text-xs text-muted-foreground">&#127769;</span>
                <button
                    type="button"
                    class="relative h-6 w-11 rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)] transition-all duration-200 hover:border-[color:color-mix(in_srgb,var(--color-primary)_44%,var(--color-border))] hover:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_30%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                    role="switch"
                    :aria-checked="theme === 'dark' ? 'true' : 'false'"
                    @click="theme = theme === 'dark' ? 'light' : 'dark'; document.documentElement.dataset.theme = theme; localStorage.setItem('theme', theme)"
                >
                    <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-muted-foreground transition-all duration-200" :class="theme === 'dark' ? 'translate-x-5 bg-primary shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_45%,transparent)]' : 'translate-x-0'"></span>
                </button>
                <span class="text-xs text-muted-foreground">&#9728;</span>
            </div>

            @auth
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="btn">Uitloggen</button>
                </form>
            @endauth
        </div>
    </div>
</nav>

@auth
    <div
        x-show="mobileMenuOpen"
        x-transition.opacity.duration.200ms
        class="fixed inset-0 z-[70] md:hidden"
        aria-modal="true"
        role="dialog"
    >
        <button
            type="button"
            @click="mobileMenuOpen = false"
            class="absolute inset-0 bg-black/65 backdrop-blur-sm"
            aria-label="Sluit menu"
        ></button>

        <aside
            x-show="mobileMenuOpen"
            x-transition:enter="transition ease-out duration-220"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition ease-in duration-180"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="relative z-10 h-dvh w-[85vw] max-w-sm overflow-y-auto border-r border-border/80 bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_14%,var(--color-card)))] px-4 py-5 shadow-[0_18px_45px_color-mix(in_srgb,black_32%,transparent)]"
        >
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-xs uppercase tracking-[0.08em] text-muted-foreground">Menu</p>
                    <p class="mt-1 text-sm font-semibold text-foreground/90">Welkom, {{ auth()->user()->name }}</p>
                </div>
                <button
                    type="button"
                    @click="mobileMenuOpen = false"
                    aria-label="Sluit menu"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border/80 bg-card/80 text-foreground/80 transition-all duration-200 hover:border-primary/45 hover:text-primary"
                >
                    <span class="text-base leading-none">&#10005;</span>
                </button>
            </div>

            <div class="mt-5 space-y-2 rounded-xl border border-border/80 bg-card/75 p-3">
                <a href="{{ route('profile.edit') }}" @click="mobileMenuOpen = false" class="btn btn-outlined h-9 w-auto px-3 text-sm">Account</a>
                <a href="{{ route('dashboard.index') }}" @click="mobileMenuOpen = false" class="btn btn-outlined h-9 w-auto px-3 text-sm">Dashboard</a>
                @if (auth()->user()->isAdmin())
                    <a href="{{ route('admin.index') }}" @click="mobileMenuOpen = false" class="btn btn-outlined h-9 w-auto px-3 text-sm">Admin</a>
                @endif
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="btn h-9 w-auto px-3 text-sm">Uitloggen</button>
                </form>
            </div>

            <div class="mt-4 space-y-2 rounded-xl border border-border/80 bg-card/75 p-3">
                <a href="{{ route('support') }}" @click="mobileMenuOpen = false" class="block text-sm text-foreground/80 transition-colors hover:text-foreground">Support</a>
                <a href="{{ route('privacy') }}" @click="mobileMenuOpen = false" class="block text-sm text-foreground/80 transition-colors hover:text-foreground">Privacy</a>
                <a href="{{ route('terms') }}" @click="mobileMenuOpen = false" class="block text-sm text-foreground/80 transition-colors hover:text-foreground">Voorwaarden</a>
            </div>

            <div class="mt-4 rounded-xl border border-border/80 bg-card/75 p-3">
                <div class="flex items-center gap-2">
                    <span class="text-xs text-muted-foreground">&#127769;</span>
                    <button
                        type="button"
                        class="relative h-6 w-11 rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)] transition-all duration-200 hover:border-[color:color-mix(in_srgb,var(--color-primary)_44%,var(--color-border))] hover:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_30%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                        role="switch"
                        :aria-checked="theme === 'dark' ? 'true' : 'false'"
                        @click="theme = theme === 'dark' ? 'light' : 'dark'; document.documentElement.dataset.theme = theme; localStorage.setItem('theme', theme)"
                    >
                        <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-muted-foreground transition-all duration-200" :class="theme === 'dark' ? 'translate-x-5 bg-primary shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_45%,transparent)]' : 'translate-x-0'"></span>
                    </button>
                    <span class="text-xs text-muted-foreground">&#9728;</span>
                    <span class="ml-2 text-xs text-muted-foreground" x-text="theme === 'dark' ? 'Donkere modus' : 'Lichte modus'"></span>
                </div>
            </div>
        </aside>
    </div>
@endauth
</div>
