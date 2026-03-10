<x-layout>
    <div class="page-shell px-4 sm:px-6 lg:px-8">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>
        <h1 class="page-title mt-3">{{ __('ui.inbox') }}</h1>
        <p class="page-subtitle">{{ __('ui.inbox_subtitle') }}</p>

        <div class="surface-card mt-5 rounded-2xl bg-card/85 p-3 sm:p-4">
            <div class="grid grid-cols-1 gap-2 rounded-xl border border-border/70 bg-card/70 p-1 sm:grid-cols-3">
                <a href="{{ route('inbox.index', ['tab' => 'mentions', 'mention' => $mentionFilter]) }}"
                   class="no-link-hover inline-flex h-9 items-center justify-center rounded-lg px-3 text-xs font-semibold whitespace-nowrap transition-[box-shadow,border-color,transform] duration-300 ease-out hover:-translate-y-0.5 shadow-[0_0_0_color-mix(in_srgb,var(--color-primary)_0%,transparent)] sm:text-sm {{ $activeTab === 'mentions' ? 'bg-primary text-primary-foreground shadow-[0_0_20px_color-mix(in_srgb,var(--color-primary)_50%,transparent)]' : 'border border-border/80 text-foreground/85 hover:border-primary/45 hover:shadow-[0_0_20px_color-mix(in_srgb,var(--color-primary)_40%,transparent)]' }}">
                    {{ __('ui.mentions') }} <span class="ml-1 text-[11px]">{{ $unreadMentionCount }}</span>
                </a>
                <a href="{{ route('inbox.index', ['tab' => 'invites']) }}"
                   class="no-link-hover inline-flex h-9 items-center justify-center rounded-lg px-3 text-xs font-semibold whitespace-nowrap transition-[box-shadow,border-color,transform] duration-300 ease-out hover:-translate-y-0.5 shadow-[0_0_0_color-mix(in_srgb,var(--color-primary)_0%,transparent)] sm:text-sm {{ $activeTab === 'invites' ? 'bg-primary text-primary-foreground shadow-[0_0_20px_color-mix(in_srgb,var(--color-primary)_50%,transparent)]' : 'border border-border/80 text-foreground/85 hover:border-primary/45 hover:shadow-[0_0_20px_color-mix(in_srgb,var(--color-primary)_40%,transparent)]' }}">
                    {{ __('ui.invites') }} <span class="ml-1 text-[11px]">{{ $pendingInviteCount }}</span>
                </a>
                <a href="{{ route('inbox.index', ['tab' => 'reminders', 'reminder' => $reminderFilter]) }}"
                   class="no-link-hover inline-flex h-9 items-center justify-center rounded-lg px-3 text-xs font-semibold whitespace-nowrap transition-[box-shadow,border-color,transform] duration-300 ease-out hover:-translate-y-0.5 shadow-[0_0_0_color-mix(in_srgb,var(--color-primary)_0%,transparent)] sm:text-sm {{ $activeTab === 'reminders' ? 'bg-primary text-primary-foreground shadow-[0_0_20px_color-mix(in_srgb,var(--color-primary)_50%,transparent)]' : 'border border-border/80 text-foreground/85 hover:border-primary/45 hover:shadow-[0_0_20px_color-mix(in_srgb,var(--color-primary)_40%,transparent)]' }}">
                    {{ __('ui.reminders') }} <span class="ml-1 text-[11px]">{{ $unreadReminderCount }}</span>
                </a>
            </div>

            @if ($activeTab === 'mentions')
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="grid w-full grid-cols-2 gap-2 rounded-lg border border-border/70 bg-card/70 p-1 sm:w-auto">
                        <a href="{{ route('inbox.index', ['tab' => 'mentions', 'mention' => 'unread']) }}"
                           class="no-link-hover inline-flex h-8 items-center justify-center rounded-md px-2.5 text-xs font-semibold whitespace-nowrap transition-[box-shadow,border-color,transform] duration-300 ease-out hover:-translate-y-0.5 shadow-[0_0_0_color-mix(in_srgb,var(--color-primary)_0%,transparent)] {{ $mentionFilter === 'unread' ? 'bg-primary text-primary-foreground shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_46%,transparent)]' : 'border border-border/80 text-foreground/80 hover:border-primary/45 hover:shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_36%,transparent)]' }}">{{ __('ui.unread') }}</a>
                        <a href="{{ route('inbox.index', ['tab' => 'mentions', 'mention' => 'all']) }}"
                           class="no-link-hover inline-flex h-8 items-center justify-center rounded-md px-2.5 text-xs font-semibold whitespace-nowrap transition-[box-shadow,border-color,transform] duration-300 ease-out hover:-translate-y-0.5 shadow-[0_0_0_color-mix(in_srgb,var(--color-primary)_0%,transparent)] {{ $mentionFilter === 'all' ? 'bg-primary text-primary-foreground shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_46%,transparent)]' : 'border border-border/80 text-foreground/80 hover:border-primary/45 hover:shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_36%,transparent)]' }}">{{ __('ui.all_items') }}</a>
                    </div>

                    @if ($unreadMentionCount > 0)
                        <form method="POST" action="{{ route('inbox.mentions.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-outlined h-8 px-2.5 text-xs">{{ __('ui.mark_all_read') }}</button>
                        </form>
                    @endif
                </div>
            @elseif ($activeTab === 'reminders')
                <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                    <div class="grid w-full grid-cols-2 gap-2 rounded-lg border border-border/70 bg-card/70 p-1 sm:w-auto">
                        <a href="{{ route('inbox.index', ['tab' => 'reminders', 'reminder' => 'unread']) }}"
                           class="no-link-hover inline-flex h-8 items-center justify-center rounded-md px-2.5 text-xs font-semibold whitespace-nowrap transition-[box-shadow,border-color,transform] duration-300 ease-out hover:-translate-y-0.5 shadow-[0_0_0_color-mix(in_srgb,var(--color-primary)_0%,transparent)] {{ $reminderFilter === 'unread' ? 'bg-primary text-primary-foreground shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_46%,transparent)]' : 'border border-border/80 text-foreground/80 hover:border-primary/45 hover:shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_36%,transparent)]' }}">{{ __('ui.unread') }}</a>
                        <a href="{{ route('inbox.index', ['tab' => 'reminders', 'reminder' => 'all']) }}"
                           class="no-link-hover inline-flex h-8 items-center justify-center rounded-md px-2.5 text-xs font-semibold whitespace-nowrap transition-[box-shadow,border-color,transform] duration-300 ease-out hover:-translate-y-0.5 shadow-[0_0_0_color-mix(in_srgb,var(--color-primary)_0%,transparent)] {{ $reminderFilter === 'all' ? 'bg-primary text-primary-foreground shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_46%,transparent)]' : 'border border-border/80 text-foreground/80 hover:border-primary/45 hover:shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_36%,transparent)]' }}">{{ __('ui.all_items') }}</a>
                    </div>

                    @if ($unreadReminderCount > 0)
                        <form method="POST" action="{{ route('inbox.reminders.read-all') }}">
                            @csrf
                            <button type="submit" class="btn btn-outlined h-8 px-2.5 text-xs">{{ __('ui.mark_all_read') }}</button>
                        </form>
                    @endif
                </div>
            @endif
        </div>

        <section class="mt-4 space-y-3">
            @if ($activeTab === 'mentions')
                @if ($mentions && $mentions->count() > 0)
                    @foreach ($mentions as $mention)
                        <a
                            href="{{ route('inbox.mentions.open', $mention) }}"
                            class="no-link-hover block rounded-xl border border-border/80 bg-card/80 p-3 shadow-[0_8px_20px_color-mix(in_srgb,black_8%,transparent)] transition-colors hover:border-primary/45"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-foreground/90">{{ __('ui.mentioned_you', ['name' => $mention->mentionedBy->name]) }}</p>
                                <span class="text-xs text-muted-foreground">{{ $mention->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $mention->task->title]) }}</p>
                            <p class="mt-1.5 text-xs text-muted-foreground">{{ \Illuminate\Support\Str::limit($mention->comment->body ?? '', 140) }}</p>
                        </a>
                    @endforeach
                    <div class="pt-2">{{ $mentions->links() }}</div>
                @else
                    <div class="empty-state">
                        <p class="empty-state-title">{{ __('ui.no_messages') }}</p>
                        <p class="empty-state-copy">{{ __('ui.inbox_subtitle') }}</p>
                    </div>
                @endif
            @elseif ($activeTab === 'invites')
                @if ($invites && $invites->count() > 0)
                    @foreach ($invites as $inviteRequest)
                        <div class="rounded-xl border border-border/80 bg-card/80 p-3 shadow-[0_8px_20px_color-mix(in_srgb,black_8%,transparent)]">
                            <p class="text-sm text-foreground/90">{{ __('ui.invite_from', ['name' => $inviteRequest->inviter->name]) }}</p>
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $inviteRequest->task->title]) }}</p>
                            <div class="mt-3 flex gap-2">
                                <form method="POST" action="{{ route('task.collab-requests.accept', $inviteRequest) }}">
                                    @csrf
                                    <button class="btn h-8 px-3 text-xs">{{ __('ui.accept') }}</button>
                                </form>
                                <form method="POST" action="{{ route('task.collab-requests.reject', $inviteRequest) }}">
                                    @csrf
                                    <button class="btn btn-outlined h-8 px-3 text-xs">{{ __('ui.decline') }}</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                    <div class="pt-2">{{ $invites->links() }}</div>
                @else
                    <div class="empty-state">
                        <p class="empty-state-title">{{ __('ui.no_invites') }}</p>
                        <p class="empty-state-copy">{{ __('ui.inbox_subtitle') }}</p>
                    </div>
                @endif
            @else
                @if ($reminders && $reminders->count() > 0)
                    @foreach ($reminders as $reminder)
                        <a
                            href="{{ route('inbox.reminders.open', $reminder) }}"
                            class="no-link-hover block rounded-xl border border-border/80 bg-card/80 p-3 shadow-[0_8px_20px_color-mix(in_srgb,black_8%,transparent)] transition-colors hover:border-primary/45"
                        >
                            <div class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm text-foreground/90">{{ __('ui.reminder_due_on', ['date' => $reminder->due_date->translatedFormat('j M Y')]) }}</p>
                                <span class="text-xs text-muted-foreground">{{ $reminder->created_at->diffForHumans() }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $reminder->task->title]) }}</p>
                        </a>
                    @endforeach
                    <div class="pt-2">{{ $reminders->links() }}</div>
                @else
                    <div class="empty-state">
                        <p class="empty-state-title">{{ __('ui.no_reminders') }}</p>
                        <p class="empty-state-copy">{{ __('ui.inbox_subtitle') }}</p>
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-layout>
