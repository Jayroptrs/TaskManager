<x-layout>
    @php
        $supportCategoryLabels = __('support.categories');
        $supportStatusLabels = __('support.statuses');
        $supportStatusClasses = [
            'open' => 'text-foreground',
            'in_progress' => 'text-blue-400',
            'waiting_for_user' => 'text-amber-400',
            'resolved' => 'text-primary',
        ];
    @endphp
    <div class="py-8 md:py-12 max-w-7xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('admin.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            {{ __('admin.subtitle') }}
        </p>

        <section class="mt-8 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('admin.users') }}</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $totalUsers }}</p>
                <p class="mt-2 text-sm text-muted-foreground">{{ __('admin.users_total_sub') }}</p>
            </article>
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('admin.tasks') }}</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $totalTasks }}</p>
                <p class="mt-2 text-sm text-muted-foreground">{{ __('admin.tasks_total_sub') }}</p>
            </article>
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('admin.completed_tasks') }}</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $completedTasks }}</p>
                <p class="mt-2 text-sm text-muted-foreground">{{ __('admin.completed_tasks_sub') }}</p>
            </article>
            <article class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)] transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]">
                <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('admin.support_open_resolved') }}</p>
                <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $openSupportCount }} / {{ $resolvedSupportCount }}</p>
                <p class="mt-2 text-sm text-muted-foreground">{{ __('admin.support_open_resolved_sub') }}</p>
            </article>
        </section>

        <section class="mt-8 grid gap-6 lg:grid-cols-[1.4fr_0.6fr]">
            <x-card is="section" hoverable="false" class="p-6" x-data>
                <h2 class="text-xl font-semibold text-foreground">{{ __('admin.incoming_support') }}</h2>
                <p class="mt-2 text-sm text-muted-foreground">
                    {{ __('admin.support_split_hint') }}
                </p>

                <div class="mt-4 grid gap-4 md:grid-cols-3">
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'support-members-list')"
                        class="rounded-xl border border-border/70 bg-card/70 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_10px_22px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                    >
                        <p class="text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('admin.support_logged_in') }}</p>
                        <p class="mt-2 text-2xl font-bold text-foreground">{{ $memberSupportMessages->count() }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ __('admin.open_list_modal') }}</p>
                    </button>

                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'support-guests-list')"
                        class="rounded-xl border border-border/70 bg-card/70 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_10px_22px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                    >
                        <p class="text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('admin.support_guests') }}</p>
                        <p class="mt-2 text-2xl font-bold text-foreground">{{ $guestSupportMessages->count() }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ __('admin.open_list_modal') }}</p>
                    </button>

                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'support-resolved-list')"
                        class="rounded-xl border border-border/70 bg-card/70 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_10px_22px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                    >
                        <p class="text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('admin.support_resolved') }}</p>
                        <p class="mt-2 text-2xl font-bold text-foreground">{{ $resolvedSupportMessages->count() }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">{{ __('admin.open_list_modal') }}</p>
                    </button>
                </div>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('admin.users') }}</h2>
                @error('admin')
                    <p class="mt-3 text-sm text-red-500">{{ $message }}</p>
                @enderror
                <div class="mt-4 space-y-3">
                    @forelse($recentUsers as $user)
                        <div class="rounded-lg border border-border/70 bg-card/70 p-3">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <p class="font-semibold text-foreground">
                                        <a href="{{ route('admin.users.tasks', $user) }}" class="no-link-hover hover:text-primary">
                                            {{ $user->name }}
                                        </a>
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ $user->email }}</p>
                                    <p class="text-xs text-muted-foreground mt-1">{{ __('admin.created_at') }}: {{ $user->created_at->format('d-m-Y H:i') }}</p>
                                    <a href="{{ route('admin.users.tasks', $user) }}" class="mt-2 inline-block text-xs text-primary hover:text-foreground no-link-hover">
                                        {{ __('admin.view_tasks') }}
                                    </a>
                                </div>
                                @if(auth()->id() !== $user->id && !$user->isAdmin())
                                    <button
                                        type="button"
                                        x-data
                                        @click="$dispatch('open-modal', 'delete-user-confirmation-{{ $user->id }}')"
                                        class="btn btn-danger-outlined h-8 leading-8 px-3 text-xs"
                                    >
                                        {{ __('admin.delete') }}
                                    </button>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">{{ __('admin.no_users') }}</p>
                    @endforelse
                </div>
            </x-card>
        </section>

        @foreach($recentUsers as $user)
            @if(auth()->id() !== $user->id && !$user->isAdmin())
                <x-modal :name="'delete-user-confirmation-' . $user->id" :title="__('admin.delete_confirm_title')" maxWidth="max-w-md">
                    <div class="space-y-4">
                        <p class="text-sm text-muted-foreground">{{ __('admin.delete_confirm_message') }}</p>

                        <div class="rounded-lg border border-border/70 bg-card/60 px-3 py-2 text-xs text-muted-foreground">
                            <p class="font-medium text-foreground">{{ $user->name }}</p>
                            <p class="break-all">{{ $user->email }}</p>
                        </div>

                        <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                            <button
                                type="button"
                                @click="$dispatch('close-modal')"
                                class="btn btn-outlined h-10 px-4"
                            >
                                {{ __('task.cancel') }}
                            </button>

                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-danger-outlined h-10 px-4">
                                    {{ __('admin.delete_confirm_button') }}
                                </button>
                            </form>
                        </div>
                    </div>
                </x-modal>
            @endif
        @endforeach

        <x-modal name="support-members-list" :title="__('admin.support_logged_in')">
            <div class="space-y-3">
                @forelse($memberSupportMessages as $ticket)
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'support-detail-{{ $ticket->id }}')"
                        class="w-full rounded-xl border border-border/70 bg-card/70 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-foreground">{{ $ticket->subject }}</p>
                            <span class="text-xs {{ $supportStatusClasses[$ticket->status] ?? 'text-muted-foreground' }}">
                                {{ data_get($supportStatusLabels, $ticket->status, ucfirst((string) $ticket->status)) }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $ticket->created_at->format('d-m-Y H:i') }} &bull; {{ $ticket->user?->name ?? __('admin.guest') }}
                        </p>
                    </button>
                @empty
                    <p class="text-sm text-muted-foreground">{{ __('admin.no_support_messages') }}</p>
                @endforelse
            </div>
        </x-modal>

        <x-modal name="support-guests-list" :title="__('admin.support_guests')">
            <div class="space-y-3">
                @forelse($guestSupportMessages as $ticket)
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'support-detail-{{ $ticket->id }}')"
                        class="w-full rounded-xl border border-border/70 bg-card/70 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-foreground">{{ $ticket->subject }}</p>
                            <span class="text-xs {{ $supportStatusClasses[$ticket->status] ?? 'text-muted-foreground' }}">
                                {{ data_get($supportStatusLabels, $ticket->status, ucfirst((string) $ticket->status)) }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $ticket->created_at->format('d-m-Y H:i') }} &bull; {{ $ticket->guest_name ?: __('admin.guest') }}
                        </p>
                    </button>
                @empty
                    <p class="text-sm text-muted-foreground">{{ __('admin.no_support_messages') }}</p>
                @endforelse
            </div>
        </x-modal>

        <x-modal name="support-resolved-list" :title="__('admin.support_resolved')">
            <div class="space-y-3">
                @forelse($resolvedSupportMessages as $ticket)
                    <button
                        type="button"
                        @click="$dispatch('open-modal', 'support-detail-{{ $ticket->id }}')"
                        class="w-full rounded-xl border border-border/70 bg-card/70 p-4 text-left transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45"
                    >
                        <div class="flex items-center justify-between gap-3">
                            <p class="font-semibold text-foreground">{{ $ticket->subject }}</p>
                            <span class="text-xs {{ $supportStatusClasses[$ticket->status] ?? 'text-muted-foreground' }}">
                                {{ data_get($supportStatusLabels, $ticket->status, ucfirst((string) $ticket->status)) }}
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ $ticket->created_at->format('d-m-Y H:i') }} &bull; {{ $ticket->user?->name ?? ($ticket->guest_name ?: __('admin.guest')) }}
                        </p>
                    </button>
                @empty
                    <p class="text-sm text-muted-foreground">{{ __('admin.no_support_messages') }}</p>
                @endforelse
            </div>
        </x-modal>

        @foreach($memberSupportMessages->concat($guestSupportMessages)->concat($resolvedSupportMessages)->unique('id') as $ticket)
            <x-modal :name="'support-detail-' . $ticket->id" :title="$ticket->subject">
                <div class="grid gap-4 md:grid-cols-2 text-sm">
                    <div>
                        <p class="text-muted-foreground">{{ __('admin.name') }}</p>
                        <p class="font-semibold text-foreground">{{ $ticket->user?->name ?? ($ticket->guest_name ?: __('admin.guest')) }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">{{ __('admin.email') }}</p>
                        <p class="font-semibold text-foreground break-all">{{ $ticket->user?->email ?? ($ticket->guest_email ?: __('admin.no_email')) }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">{{ __('admin.category') }}</p>
                        <p class="font-semibold text-foreground">{{ data_get($supportCategoryLabels, $ticket->category, ucfirst((string) $ticket->category)) }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">{{ __('admin.date') }}</p>
                        <p class="font-semibold text-foreground">{{ $ticket->created_at->format('d-m-Y H:i') }}</p>
                    </div>
                    <div>
                        <p class="text-muted-foreground">{{ __('admin.status') }}</p>
                        <p class="font-semibold {{ $supportStatusClasses[$ticket->status] ?? 'text-foreground' }}">
                            {{ data_get($supportStatusLabels, $ticket->status, ucfirst((string) $ticket->status)) }}
                        </p>
                    </div>
                    @if($ticket->status === 'resolved' && $ticket->resolved_at)
                        <div>
                            <p class="text-muted-foreground">{{ __('admin.resolved_at') }}</p>
                            <p class="font-semibold text-foreground">{{ $ticket->resolved_at->format('d-m-Y H:i') }}</p>
                        </div>
                    @endif
                    @if($ticket->admin_resolved_at)
                        <div>
                            <p class="text-muted-foreground">{{ __('admin.admin_resolved_at') }}</p>
                            <p class="font-semibold text-foreground">{{ $ticket->admin_resolved_at->format('d-m-Y H:i') }}</p>
                        </div>
                    @endif
                    @if($ticket->user_resolved_at)
                        <div>
                            <p class="text-muted-foreground">{{ __('admin.user_resolved_at') }}</p>
                            <p class="font-semibold text-foreground">{{ $ticket->user_resolved_at->format('d-m-Y H:i') }}</p>
                        </div>
                    @endif
                </div>

                <div class="mt-4 rounded-lg border border-border/70 bg-card/70 p-4">
                    <p class="text-muted-foreground text-xs uppercase tracking-wide">{{ __('admin.message') }}</p>
                    <p class="mt-2 text-sm text-foreground whitespace-pre-wrap">{{ $ticket->message }}</p>
                </div>

                <div class="mt-4 rounded-lg border border-border/70 bg-card/70 p-4">
                    <p class="text-muted-foreground text-xs uppercase tracking-wide">{{ __('admin.technical') }}</p>
                    <p class="mt-2 text-xs text-muted-foreground break-all">IP: {{ $ticket->ip_address ?? '-' }}</p>
                    <p class="mt-1 text-xs text-muted-foreground break-all">User-Agent: {{ $ticket->user_agent ?? '-' }}</p>
                </div>

                <div class="mt-4 rounded-lg border border-border/70 bg-card/70 p-4">
                    <p class="text-muted-foreground text-xs uppercase tracking-wide">{{ __('admin.conversation') }}</p>
                    <div class="mt-3 space-y-3">
                        <div class="rounded-lg border border-primary/30 bg-primary/8 px-3 py-2">
                            <div class="flex items-center justify-between gap-3">
                                <p class="text-xs font-semibold text-primary">
                                    {{ $ticket->user?->name ?? ($ticket->guest_name ?: __('admin.guest')) }}
                                </p>
                                <p class="text-[11px] text-muted-foreground">{{ $ticket->created_at->format('d-m-Y H:i') }}</p>
                            </div>
                            <p class="mt-1.5 whitespace-pre-wrap text-sm text-foreground">{{ $ticket->message }}</p>
                        </div>

                        @foreach($ticket->replies as $reply)
                            <div class="rounded-lg border px-3 py-2 {{ $reply->is_admin ? 'border-blue-400/35 bg-blue-500/8' : 'border-primary/25 bg-primary/7' }}">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="text-xs font-semibold {{ $reply->is_admin ? 'text-blue-300' : 'text-primary' }}">
                                        {{ $reply->is_admin ? __('admin.support_team') : ($reply->user?->name ?? __('admin.guest')) }}
                                    </p>
                                    <p class="text-[11px] text-muted-foreground">{{ $reply->created_at->format('d-m-Y H:i') }}</p>
                                </div>
                                <p class="mt-1.5 whitespace-pre-wrap text-sm text-foreground">{{ $reply->message }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-5 grid gap-3 lg:grid-cols-2">
                    <form method="POST" action="{{ route('admin.support.status', $ticket) }}" class="rounded-lg border border-border/70 bg-card/70 p-4 space-y-3">
                        @csrf
                        @method('PATCH')
                        <label for="support-status-{{ $ticket->id }}" class="label">{{ __('admin.update_status') }}</label>
                        <select id="support-status-{{ $ticket->id }}" name="status" class="input" required>
                            @foreach(\App\Models\SupportMessage::statuses() as $status)
                                <option value="{{ $status }}" @selected($ticket->status === $status)>
                                    {{ data_get($supportStatusLabels, $status, ucfirst((string) $status)) }}
                                </option>
                            @endforeach
                        </select>
                        <button type="submit" class="btn w-full">{{ __('admin.save_status') }}</button>
                    </form>

                    <form method="POST" action="{{ route('admin.support.reply', $ticket) }}" class="rounded-lg border border-border/70 bg-card/70 p-4 space-y-3">
                        @csrf
                        <label for="support-admin-reply-{{ $ticket->id }}" class="label">{{ __('admin.reply_label') }}</label>
                        <textarea
                            id="support-admin-reply-{{ $ticket->id }}"
                            name="message"
                            class="input min-h-24"
                            placeholder="{{ __('admin.reply_placeholder') }}"
                            required
                        ></textarea>
                        <div>
                            <label for="support-admin-reply-status-{{ $ticket->id }}" class="label">{{ __('admin.reply_status_label') }}</label>
                            <select id="support-admin-reply-status-{{ $ticket->id }}" name="status" class="input">
                                @foreach(\App\Models\SupportMessage::statuses() as $status)
                                    <option value="{{ $status }}" @selected($status === 'waiting_for_user')>
                                        {{ data_get($supportStatusLabels, $status, ucfirst((string) $status)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="btn w-full">{{ __('admin.send_reply') }}</button>
                    </form>
                </div>
            </x-modal>
        @endforeach
    </div>
</x-layout>

