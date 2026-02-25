<x-layout>
    <div class="py-8 md:py-12 max-w-7xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>🡨 Terug</span>
        </a>
        <h1 class="mt-3 text-4xl font-bold tracking-tight">Admin panel</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Overzicht van supportverzoeken, gebruikersactiviteit en kernstatistieken van de site.
        </p>

        <section class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">Gebruikers</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $totalUsers }}</p>
                <p class="mt-2 text-sm text-muted-foreground">Totaal geregistreerde accounts.</p>
            </article>
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">Taken</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $totalTasks }}</p>
                <p class="mt-2 text-sm text-muted-foreground">Totaal taken in het systeem.</p>
            </article>
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">Voltooide taken</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $completedTasks }}</p>
                <p class="mt-2 text-sm text-muted-foreground">Taken met status voltooid.</p>
            </article>
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">Support open/afgehandeld</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $openSupportCount }} / {{ $resolvedSupportCount }}</p>
                <p class="mt-2 text-sm text-muted-foreground">Openstaande en afgehandelde supportberichten.</p>
            </article>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
            <x-card is="section" hoverable="false" class="p-6" x-data>
                <h2 class="text-xl font-semibold text-foreground">Ingekomen supportberichten</h2>

                <div class="mt-4 grid gap-3">
                    @forelse($supportMessages as $ticket)
                        <button
                            type="button"
                            @click="$dispatch('open-modal', 'support-{{ $ticket->id }}')"
                            class="rounded-xl border border-border/70 bg-card/70 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_10px_22px_rgba(34,197,94,0.2)]"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <p class="font-semibold text-foreground">{{ $ticket->subject }}</p>
                                <span class="text-xs {{ $ticket->status === 'resolved' ? 'text-primary' : 'text-muted-foreground' }}">
                                    {{ $ticket->status === 'resolved' ? 'Afgehandeld' : 'Open' }}
                                </span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ $ticket->created_at->format('d-m-Y H:i') }} �
                                {{ $ticket->user?->name ?? ($ticket->guest_name ?: 'Gast') }} �
                                {{ ucfirst($ticket->category) }}
                            </p>
                            <p class="mt-2 text-sm text-muted-foreground line-clamp-2">{{ $ticket->message }}</p>
                        </button>

                        <x-modal :name="'support-' . $ticket->id" :title="$ticket->subject">
                            <div class="grid gap-4 md:grid-cols-2 text-sm">
                                <div>
                                    <p class="text-muted-foreground">Naam</p>
                                    <p class="font-semibold text-foreground">{{ $ticket->user?->name ?? ($ticket->guest_name ?: 'Gast') }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">E-mail</p>
                                    <p class="font-semibold text-foreground break-all">{{ $ticket->user?->email ?? ($ticket->guest_email ?: 'Geen e-mail opgegeven') }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Categorie</p>
                                    <p class="font-semibold text-foreground">{{ ucfirst($ticket->category) }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Datum</p>
                                    <p class="font-semibold text-foreground">{{ $ticket->created_at->format('d-m-Y H:i') }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">Status</p>
                                    <p class="font-semibold {{ $ticket->status === 'resolved' ? 'text-primary' : 'text-foreground' }}">
                                        {{ $ticket->status === 'resolved' ? 'Afgehandeld' : 'Open' }}
                                    </p>
                                </div>
                                @if($ticket->resolved_at)
                                    <div>
                                        <p class="text-muted-foreground">Afgehandeld op</p>
                                        <p class="font-semibold text-foreground">{{ $ticket->resolved_at->format('d-m-Y H:i') }}</p>
                                    </div>
                                @endif
                            </div>

                            <div class="mt-4 rounded-lg border border-border/70 bg-card/70 p-4">
                                <p class="text-muted-foreground text-xs uppercase tracking-wide">Bericht</p>
                                <p class="mt-2 text-sm text-foreground whitespace-pre-wrap">{{ $ticket->message }}</p>
                            </div>

                            <div class="mt-4 rounded-lg border border-border/70 bg-card/70 p-4">
                                <p class="text-muted-foreground text-xs uppercase tracking-wide">Technisch</p>
                                <p class="mt-2 text-xs text-muted-foreground break-all">IP: {{ $ticket->ip_address ?? '-' }}</p>
                                <p class="mt-1 text-xs text-muted-foreground break-all">User-Agent: {{ $ticket->user_agent ?? '-' }}</p>
                            </div>

                            @if($ticket->status === 'open')
                                <div class="mt-5 flex justify-end">
                                    <form method="POST" action="{{ route('admin.support.resolve', $ticket) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="btn">Markeer afgehandeld</button>
                                    </form>
                                </div>
                            @endif
                        </x-modal>
                    @empty
                        <p class="py-4 text-muted-foreground">Nog geen supportberichten ontvangen.</p>
                    @endforelse
                </div>

                <div class="mt-4">
                    {{ $supportMessages->links() }}
                </div>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">Gebruikers</h2>
                @error('admin')
                    <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <div class="mt-4 space-y-3">
                    @forelse($recentUsers as $user)
                        <div class="rounded-lg border border-border/70 bg-card/70 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-foreground">{{ $user->name }}</p>
                                    <p class="text-xs text-muted-foreground">{{ $user->email }}</p>
                                    <p class="text-xs text-muted-foreground mt-1">Aangemaakt: {{ $user->created_at->format('d-m-Y H:i') }}</p>
                                </div>
                                @if(auth()->id() !== $user->id && !$user->isAdmin())
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Weet je zeker dat je deze gebruiker wilt verwijderen?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger-outlined h-8 leading-8 px-3 text-xs">Verwijder</button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">Geen gebruikers gevonden.</p>
                    @endforelse
                </div>
            </x-card>
        </section>
    </div>
</x-layout>


