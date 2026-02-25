<x-layout>
    <div class="py-8 md:py-12 max-w-5xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>🡨 Terug</span>
        </a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">Support en klachten</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Dien je vraag of klacht in via het supportformulier hieronder. Dan komt je bericht direct in ons beheerpaneel.
        </p>

        <div class="mt-8 space-y-6">
            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">1. Zo werkt support</h2>
                <ol class="mt-3 list-decimal pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>Vul het supportformulier hieronder zo volledig mogelijk in (ook zonder account mogelijk).</li>
                    <li>Na verzenden wordt je bericht direct geregistreerd in ons supportoverzicht.</li>
                    <li>Ingelogde gebruikers zien recente berichten onder "Mijn recente berichten".</li>
                </ol>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">2. Reactietijd en afhandeling</h2>
                <ol class="mt-3 list-decimal pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>We behandelen supportverzoeken op werkdagen (ma-vr).</li>
                    <li>We hanteren streeftermijnen voor eerste reactie en inhoudelijke afhandeling.</li>
                    <li>Complexe verzoeken kunnen meer tijd kosten.</li>
                    <li>Als meer tijd nodig is, laten we dat zo snel mogelijk weten.</li>
                </ol>
            </x-card>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">Nieuw supportbericht</h2>
                <form method="POST" action="{{ route('support.store') }}" class="mt-4 space-y-4">
                    @csrf
                    @guest
                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label for="guest_name" class="label">Naam</label>
                                <input id="guest_name" name="guest_name" value="{{ old('guest_name') }}" required class="input mt-2" placeholder="Je naam">
                                @error('guest_name')
                                    <p class="error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="guest_email" class="label">E-mail</label>
                                <input id="guest_email" name="guest_email" value="{{ old('guest_email') }}" type="email" required class="input mt-2" placeholder="naam@voorbeeld.nl">
                                @error('guest_email')
                                    <p class="error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                    @endguest

                    <div>
                        <label for="subject" class="label">Onderwerp</label>
                        <input id="subject" name="subject" value="{{ old('subject') }}" required class="input mt-2" placeholder="Bijv. probleem met inloggen">
                        @error('subject')
                            <p class="error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="category" class="label">Categorie</label>
                        @php
                            $categories = [
                                ['value' => 'algemeen', 'label' => 'Algemeen'],
                                ['value' => 'account', 'label' => 'Account / Inloggen'],
                                ['value' => 'bug', 'label' => 'Bug'],
                                ['value' => 'privacy', 'label' => 'Privacy'],
                                ['value' => 'security', 'label' => 'Security'],
                                ['value' => 'billing', 'label' => 'Facturatie'],
                            ];
                        @endphp
                        <div
                            class="relative mt-2"
                            x-data="{ open: false, value: @js(old('category', 'algemeen')), options: @js($categories) }"
                            @keydown.escape.window="open = false"
                        >
                            <input type="hidden" id="category" name="category" x-model="value" required>
                            <button type="button" @click="open = !open" class="input input-neon-select w-full text-left">
                                <span x-text="options.find(option => option.value === value)?.label ?? 'Kies een categorie'"></span>
                            </button>

                            <div x-show="open" @click.outside="open = false" x-transition class="dropdown-panel">
                                <template x-for="option in options" :key="option.value">
                                    <button
                                        type="button"
                                        class="dropdown-item"
                                        :class="{ 'is-active': value === option.value }"
                                        @click="value = option.value; open = false"
                                        x-text="option.label"
                                    ></button>
                                </template>
                            </div>
                        </div>
                        @error('category')
                            <p class="error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="message" class="label">Bericht</label>
                        <textarea id="message" name="message" required class="textarea mt-2 min-h-20 max-h-100" placeholder="Omschrijf je vraag of probleem zo volledig mogelijk.">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="error mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    @guest
                        @if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
                            <div>
                                <div class="g-recaptcha" data-sitekey="{{ config('services.recaptcha.site_key') }}"></div>
                                @error('g-recaptcha-response')
                                    <p class="error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    @endguest

                    <button class="btn" type="submit">Verstuur supportbericht</button>
                </form>
                @guest
                    <p class="mt-3 text-sm text-muted-foreground">
                        Heb je al een account? Log in om je eerdere supportberichten terug te zien.
                    </p>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ route('login') }}" class="btn">Inloggen</a>
                        <a href="/register" class="btn btn-outlined">Registreren</a>
                    </div>
                @endguest
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">Mijn recente berichten</h2>
                @auth
                    <div class="mt-4 space-y-3">
                        @forelse($myMessages as $ticket)
                            <div class="rounded-lg border border-border/80 bg-card/70 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold text-foreground text-sm">{{ $ticket->subject }}</p>
                                    <span class="text-xs {{ $ticket->status === 'resolved' ? 'text-primary' : 'text-muted-foreground' }}">
                                        {{ $ticket->status === 'resolved' ? 'Afgehandeld' : 'Open' }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">{{ $ticket->created_at->format('d-m-Y H:i') }}</p>
                                <p class="mt-2 text-sm text-muted-foreground line-clamp-2">{{ $ticket->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">Je hebt nog geen supportberichten verzonden.</p>
                        @endforelse
                    </div>
                @else
                    <p class="mt-3 text-sm text-muted-foreground">
                        Log in om je eerdere supportberichten te bekijken.
                    </p>
                @endauth
            </x-card>
        </div>
    </div>

    @guest
        @if(config('services.recaptcha.enabled') && config('services.recaptcha.site_key'))
            <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        @endif
    @endguest
</x-layout>
