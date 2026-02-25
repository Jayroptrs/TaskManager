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
                        Van taak naar resultaat. Plan, prioriteer en werk je projecten stap voor stap af.
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground/90">Navigatie</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <a href="{{ route('task.index') }}" class="text-foreground/80 hover:text-foreground">Mijn taken</a>
                        <a href="{{ route('dashboard.index') }}" class="text-foreground/80 hover:text-foreground">Dashboard</a>
                        <a href="{{ route('profile.edit') }}" class="text-foreground/80 hover:text-foreground">Account</a>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-semibold text-foreground/90">Ondersteuning</h3>
                    <div class="mt-3 flex flex-col gap-2 text-sm">
                        <a href="{{ route('support') }}" class="text-foreground/80 hover:text-foreground">Support</a>
                        <a href="{{ route('privacy') }}" class="text-foreground/80 hover:text-foreground">Privacy</a>
                        <a href="{{ route('terms') }}" class="text-foreground/80 hover:text-foreground">Voorwaarden</a>
                    </div>
                </div>
            </div>

            <div class="mt-8 border-t border-border pt-4 text-xs text-muted-foreground flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ date('Y') }} Jayro. Alle rechten voorbehouden.</p>
                <p>Gemaakt met focus op snelheid, duidelijkheid en uitvoering.</p>
            </div>
        @endauth

        @guest
            <div class="text-center">
                <a href="{{ route('login') }}" class="{{ $brandClass }} text-2xl" aria-label="Jayro Home">Jayro</a>
            </div>

            <div class="mt-4 flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-foreground/80">
                <a href="{{ route('support') }}" class="hover:text-foreground">Support</a>
                <a href="{{ route('privacy') }}" class="hover:text-foreground">Privacy</a>
                <a href="{{ route('terms') }}" class="hover:text-foreground">Voorwaarden</a>
            </div>

            <div class="mt-6 border-t border-border pt-4 text-xs text-muted-foreground flex flex-col gap-2 text-center md:flex-row md:items-center md:justify-between">
                <p>&copy; {{ date('Y') }} Jayro. Alle rechten voorbehouden.</p>
                <p>Gemaakt met focus op snelheid, duidelijkheid en uitvoering.</p>
            </div>
        @endguest
    </div>
</footer>
