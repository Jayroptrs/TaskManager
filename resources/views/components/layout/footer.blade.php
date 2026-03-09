<footer class="border-t border-border/80 bg-card/70 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-8 md:py-10">
        @php
            $brandClass = "inline-block font-['Trebuchet_MS','Avenir_Next','Segoe_UI',sans-serif] text-[clamp(1.5rem,2.8vw,2rem)] font-extrabold uppercase tracking-[0.06em] leading-none text-[color:color-mix(in_srgb,var(--color-primary)_70%,var(--color-foreground))] [text-shadow:0_0_18px_color-mix(in_srgb,var(--color-primary)_35%,transparent)] transition-all duration-200 hover:-translate-y-px hover:text-[color:color-mix(in_srgb,var(--color-primary)_86%,var(--color-foreground))] hover:[text-shadow:0_0_10px_color-mix(in_srgb,var(--color-primary)_60%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_50%,transparent),0_0_40px_color-mix(in_srgb,var(--color-primary)_35%,transparent)]";
        @endphp
        @auth
            <div class="grid gap-8 md:grid-cols-4">
                <div class="md:col-span-2">
                    <a href="{{ route('task.index') }}" class="{{ $brandClass }} text-2xl" aria-label="Jayro Home">Jayro</a>
                    <p class="mt-3 text-sm text-muted-foreground max-w-sm">
                        {{ __('ui.footer_tagline') }}
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground/90">{{ __('ui.navigation') }}</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <a href="{{ route('task.index') }}" class="text-foreground/80 hover:text-foreground">{{ __('ui.tasks') }}</a>
                        <a href="{{ route('dashboard.index') }}" class="text-foreground/80 hover:text-foreground">{{ __('ui.dashboard') }}</a>
                        <a href="{{ route('profile.edit') }}" class="text-foreground/80 hover:text-foreground">{{ __('ui.account') }}</a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground/90">{{ __('ui.support_section') }}</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <a href="{{ route('support') }}" class="text-foreground/80 hover:text-foreground">{{ __('ui.support') }}</a>
                        <a href="{{ route('privacy') }}" class="text-foreground/80 hover:text-foreground">{{ __('ui.privacy') }}</a>
                        <a href="{{ route('terms') }}" class="text-foreground/80 hover:text-foreground">{{ __('ui.terms') }}</a>
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-border pt-4 text-xs text-muted-foreground flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ date('Y') }} Jayro. {{ __('ui.footer_rights') }}</p>
                <p>{{ __('ui.footer_motto') }}</p>
            </div>
        @endauth

        @guest
            <div class="text-center">
                <a href="{{ route('login') }}" class="{{ $brandClass }} text-2xl" aria-label="Jayro Home">Jayro</a>
            </div>

            <div class="mt-4 flex w-full justify-center">
                <div class="flex flex-wrap items-center justify-center gap-x-3 gap-y-1 text-sm text-foreground/80">
                    <a href="{{ route('support') }}" class="text-center whitespace-nowrap hover:text-foreground">{{ __('ui.support') }}</a>
                    <span class="text-border" aria-hidden="true">|</span>
                    <a href="{{ route('privacy') }}" class="text-center whitespace-nowrap hover:text-foreground">{{ __('ui.privacy') }}</a>
                    <span class="text-border" aria-hidden="true">|</span>
                    <a href="{{ route('terms') }}" class="text-center whitespace-nowrap hover:text-foreground">{{ __('ui.terms') }}</a>
                </div>
            </div>

            <div class="mt-6 border-t border-border pt-4 text-xs text-muted-foreground flex flex-col gap-2 text-center md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ date('Y') }} Jayro. {{ __('ui.footer_rights') }}</p>
                <p>{{ __('ui.footer_motto') }}</p>
            </div>
        @endguest
    </div>
</footer>
