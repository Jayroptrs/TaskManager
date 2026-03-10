<div class="m-0 p-0" x-data="{
    theme: document.documentElement.dataset.theme || 'light',
    accent: document.documentElement.dataset.accent || 'green',
    motion: document.documentElement.dataset.motion || 'on',
    canCompact: @js(auth()->check()),
    scrolledCompact: false,
    mobileMenuOpen: false,
    guestSettingsOpen: false,
    desktopSettingsOpen: false,
    updateScrolledHeader() {
        const isDesktop = window.matchMedia('(min-width: 768px)').matches;
        this.scrolledCompact = this.canCompact && isDesktop && window.scrollY > 72;
    },
    scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    },
    setAccent(value) {
        this.accent = value;
        document.documentElement.dataset.accent = value;
        localStorage.setItem('accent', value);
    },
    toggleMotion() {
        this.motion = this.motion === 'off' ? 'on' : 'off';
        document.documentElement.dataset.motion = this.motion;
        localStorage.setItem('motion', this.motion);
    }
}" x-init="
    const syncHeaderState = () => updateScrolledHeader();
    syncHeaderState();
    window.addEventListener('scroll', syncHeaderState, { passive: true });
    window.addEventListener('resize', syncHeaderState);
    $watch('mobileMenuOpen', value => {
        document.documentElement.classList.toggle('overflow-hidden', value);
        document.body.classList.toggle('overflow-hidden', value);
    });
">
<nav
    class="fixed left-0 right-0 top-0 z-[80] m-0 border-b border-border/80 bg-card px-4 sm:px-6 transition-all duration-300 ease-out"
    :class="scrolledCompact ? 'py-1.5' : 'py-3'"
>
    <div class="max-w-7xl lg:max-w-[76rem] mx-auto flex items-center relative transition-all duration-300 ease-out" :class="scrolledCompact ? 'min-h-12' : 'min-h-16'">
        @php
            $brandClass = "inline-block font-['Trebuchet_MS','Avenir_Next','Segoe_UI',sans-serif] text-[clamp(1.5rem,2.8vw,2rem)] font-extrabold uppercase tracking-[0.06em] leading-none text-[color:color-mix(in_srgb,var(--color-primary)_70%,var(--color-foreground))] [text-shadow:0_0_18px_color-mix(in_srgb,var(--color-primary)_35%,transparent)] transition-all duration-200 hover:-translate-y-px hover:text-[color:color-mix(in_srgb,var(--color-primary)_86%,var(--color-foreground))] hover:[text-shadow:0_0_10px_color-mix(in_srgb,var(--color-primary)_60%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_50%,transparent),0_0_40px_color-mix(in_srgb,var(--color-primary)_35%,transparent)]";
            $locale = app()->getLocale();
            $pendingCollabRequests = auth()->check()
                ? auth()->user()->incomingCollaborationRequests()->pending()->with(['task:id,title', 'inviter:id,name'])->latest()->take(6)->get()
                : collect();
            $unreadMentionNotifications = auth()->check()
                ? auth()->user()->unreadTaskCommentMentions()->with(['task:id,title', 'mentionedBy:id,name', 'comment:id,idea_id,body'])->latest()->take(6)->get()
                : collect();
            $unreadDueDateReminders = auth()->check()
                ? auth()->user()->unreadTaskDueDateReminders()->with(['task:id,title'])->latest()->take(6)->get()
                : collect();
            $unreadSupportReplies = auth()->check()
                ? auth()->user()->unreadSupportReplies()->with(['supportMessage:id,user_id,subject', 'user:id,name'])->latest()->take(6)->get()
                : collect();
            $pendingCollabCount = auth()->check()
                ? auth()->user()->incomingCollaborationRequests()->pending()->count()
                : 0;
            $unreadMentionCount = auth()->check()
                ? auth()->user()->unreadTaskCommentMentions()->count()
                : 0;
            $unreadReminderCount = auth()->check()
                ? auth()->user()->unreadTaskDueDateReminders()->count()
                : 0;
            $unreadSupportReplyCount = auth()->check()
                ? auth()->user()->unreadSupportReplies()->count()
                : 0;
            $inboxCount = $pendingCollabCount + $unreadMentionCount + $unreadReminderCount + $unreadSupportReplyCount;
        @endphp
        <div class="absolute left-1/2 -translate-x-1/2 md:static md:translate-x-0 transition-all duration-200" :class="scrolledCompact ? 'md:hidden' : ''">
            <a href="/" class="{{ $brandClass }}" aria-label="Jayro Home">Jayro</a>
        </div>
        @auth
            <div class="pointer-events-none absolute left-1/2 hidden max-w-[34rem] -translate-x-1/2 items-center gap-8 transition-all duration-200 xl:flex" :class="scrolledCompact ? 'xl:hidden' : ''">
                <span class="max-w-[22rem] truncate font-bold text-foreground/90">{{ __('ui.welcome', ['name' => auth()->user()->name]) }}</span>
            </div>
        @endauth

        <div class="ml-auto flex md:hidden items-center gap-2">
            @auth
                <button
                    type="button"
                    @click="mobileMenuOpen = true"
                    aria-label="{{ __('ui.open_menu') }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border/80 bg-card/85 text-foreground/85 transition-all duration-200 hover:border-primary/45 hover:text-primary hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_28%,transparent)]"
                >
                    <span class="text-base leading-none">&#9776;</span>
                </button>
            @endauth

            @guest
                <div class="relative" @click.outside="guestSettingsOpen = false">
                    <button
                        type="button"
                        @click="guestSettingsOpen = !guestSettingsOpen"
                        aria-label="{{ __('ui.settings') }}"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border/80 bg-card/85 text-foreground/85 transition-all duration-200 hover:border-primary/45 hover:text-primary hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_28%,transparent)]"
                    >
                        <span class="text-sm leading-none">&#9881;</span>
                    </button>

                    <div
                        x-show="guestSettingsOpen"
                        x-transition.origin.top.right.duration.180ms
                        class="absolute right-0 top-10 z-30 w-56 rounded-xl border border-border/80 bg-card/95 p-3 shadow-[0_16px_34px_color-mix(in_srgb,black_18%,transparent),0_0_16px_color-mix(in_srgb,var(--color-primary)_16%,transparent)] backdrop-blur-md"
                    >
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.settings') }}</p>

                        <div class="flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                            <p class="text-xs text-muted-foreground">{{ __('ui.language') }}</p>
                            <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-1.5">
                                @csrf
                                <input type="hidden" name="locale" value="{{ $locale === 'nl' ? 'en' : 'nl' }}">
                                <span class="text-[10px] font-semibold uppercase leading-none text-muted-foreground">NL</span>
                                <button
                                    type="submit"
                                    role="switch"
                                    aria-label="{{ __('ui.language') }}"
                                    aria-checked="{{ $locale === 'en' ? 'true' : 'false' }}"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)] transition-all duration-200 hover:border-[color:color-mix(in_srgb,var(--color-primary)_44%,var(--color-border))] hover:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_30%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                                >
                                    <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-primary shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_45%,transparent)] transition-all duration-200 {{ $locale === 'en' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                </button>
                                <span class="text-[10px] font-semibold uppercase leading-none text-muted-foreground">EN</span>
                            </form>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                            <p class="text-xs text-muted-foreground" x-text="theme === 'dark' ? @js(__('ui.dark_mode')) : @js(__('ui.light_mode'))"></p>
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
                        </div>

                        <div class="mt-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                            <p class="mb-2 text-xs text-muted-foreground">{{ __('ui.accent_color') }}</p>
                            <div class="flex items-center gap-2">
                                <button type="button" @click="setAccent('green')" :class="accent === 'green' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#22c55e]"></button>
                                <button type="button" @click="setAccent('yellow')" :class="accent === 'yellow' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#facc15]"></button>
                                <button type="button" @click="setAccent('cyan')" :class="accent === 'cyan' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#06b6d4]"></button>
                                <button type="button" @click="setAccent('rose')" :class="accent === 'rose' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#f43f5e]"></button>
                                <button type="button" @click="setAccent('pink')" :class="accent === 'pink' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#ec4899]"></button>
                                <button type="button" @click="setAccent('orange')" :class="accent === 'orange' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#fb923c]"></button>
                                <button type="button" @click="setAccent('violet')" :class="accent === 'violet' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#8b5cf6]"></button>
                            </div>
                        </div>

                        <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                            <p class="text-xs text-muted-foreground" x-text="motion === 'off' ? @js(__('ui.animations_off')) : @js(__('ui.animations_on'))"></p>
                            <button
                                type="button"
                                class="relative h-6 w-11 rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)]"
                                role="switch"
                                :aria-checked="motion === 'on' ? 'true' : 'false'"
                                @click="toggleMotion()"
                            >
                                <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-muted-foreground" :class="motion === 'on' ? 'translate-x-5 bg-primary' : 'translate-x-0'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            @endguest
        </div>

        <div
            class="ml-auto hidden items-center gap-3 transition-all duration-300 ease-out md:flex lg:gap-5"
            :class="scrolledCompact ? '-translate-x-4 opacity-0 pointer-events-none' : 'translate-x-0 opacity-100'"
        >
            @auth
                @if (auth()->user()->isAdmin())
                    <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('admin.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('admin.index') }}">{{ __('ui.admin') }}</a>
                @endif
                <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('task.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('task.index') }}">{{ __('ui.tasks') }}</a>
                <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('dashboard.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('dashboard.index') }}">{{ __('ui.dashboard') }}</a>
                <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('profile.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('profile.edit') }}">{{ __('ui.account') }}</a>

                <div class="relative" x-data="{ inviteOpen: false }" @click.outside="inviteOpen = false" x-show="!scrolledCompact" x-transition.opacity.duration.160ms>
                    <button
                        type="button"
                        @click="inviteOpen = !inviteOpen"
                        aria-label="{{ __('ui.inbox') }}"
                        class="relative inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border/80 bg-card/85 text-foreground/85 transition-all duration-200 hover:border-primary/45 hover:text-primary hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_28%,transparent)]"
                    >
                        <span class="text-sm leading-none">&#9993;</span>
                        @if ($inboxCount > 0)
                            <span class="absolute -right-1 -top-1 inline-flex min-w-4 items-center justify-center rounded-full bg-primary px-1 text-[10px] font-semibold text-primary-foreground">{{ $inboxCount }}</span>
                        @endif
                    </button>

                    <div
                        x-show="inviteOpen"
                        x-transition.origin.top.right.duration.180ms
                        class="absolute right-0 top-10 z-40 w-80 rounded-xl border border-border/80 bg-card/95 p-3 shadow-[0_16px_34px_color-mix(in_srgb,black_18%,transparent),0_0_16px_color-mix(in_srgb,var(--color-primary)_16%,transparent)] backdrop-blur-md"
                    >
                        <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.inbox') }}</p>

                        @if ($pendingCollabRequests->isEmpty() && $unreadMentionNotifications->isEmpty() && $unreadDueDateReminders->isEmpty() && $unreadSupportReplies->isEmpty())
                            <p class="text-sm text-muted-foreground">{{ __('ui.no_messages') }}</p>
                        @endif

                        @if ($pendingCollabRequests->isNotEmpty())
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.invites') }}</p>
                            <div class="space-y-2">
                                @foreach ($pendingCollabRequests as $inviteRequest)
                                    <div class="rounded-lg border border-border/70 bg-card/70 p-2.5">
                                        <p class="text-xs text-foreground/85">{{ __('ui.invite_from', ['name' => $inviteRequest->inviter->name]) }}</p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $inviteRequest->task->title]) }}</p>
                                        <div class="mt-2 flex gap-2">
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
                            </div>
                        @endif

                        @if ($unreadMentionNotifications->isNotEmpty())
                            <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.mentions') }}</p>
                            <div class="space-y-2">
                                @foreach ($unreadMentionNotifications as $mention)
                                    <a
                                        href="{{ route('inbox.mentions.open', $mention) }}"
                                        class="no-link-hover block rounded-lg border border-border/70 bg-card/70 p-2.5 transition-colors hover:border-primary/45"
                                    >
                                        <p class="text-xs text-foreground/90">{{ __('ui.mentioned_you', ['name' => $mention->mentionedBy->name]) }}</p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $mention->task->title]) }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ \Illuminate\Support\Str::limit($mention->comment->body ?? '', 90) }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($unreadDueDateReminders->isNotEmpty())
                            <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.reminders') }}</p>
                            <div class="space-y-2">
                                @foreach ($unreadDueDateReminders as $reminder)
                                    <a
                                        href="{{ route('inbox.reminders.open', $reminder) }}"
                                        class="no-link-hover block rounded-lg border border-border/70 bg-card/70 p-2.5 transition-colors hover:border-primary/45"
                                    >
                                        <p class="text-xs text-foreground/90">{{ __('ui.reminder_due_on', ['date' => $reminder->due_date->translatedFormat('j M Y')]) }}</p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $reminder->task->title]) }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        @if ($unreadSupportReplies->isNotEmpty())
                            <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.support_updates') }}</p>
                            <div class="space-y-2">
                                @foreach ($unreadSupportReplies as $reply)
                                    <a
                                        href="{{ route('inbox.support.open', $reply) }}"
                                        class="no-link-hover block rounded-lg border border-border/70 bg-card/70 p-2.5 transition-colors hover:border-primary/45"
                                    >
                                        <p class="text-xs text-foreground/90">{{ __('ui.support_reply_from', ['name' => $reply->user?->name ?? __('support.support_team')]) }}</p>
                                        <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.support_ticket_subject', ['subject' => $reply->supportMessage?->subject ?? '-']) }}</p>
                                        <p class="mt-1 text-xs text-muted-foreground">{{ \Illuminate\Support\Str::limit($reply->message ?? '', 90) }}</p>
                                    </a>
                                @endforeach
                            </div>
                        @endif

                        <div class="mt-3 border-t border-border/70 pt-2">
                            <a href="{{ route('inbox.index') }}" class="no-link-hover inline-flex items-center text-xs font-medium text-primary hover:text-foreground">
                                {{ __('ui.view_all_notifications') }}
                            </a>
                        </div>
                    </div>
                </div>
            @endauth

            <div class="flex items-center gap-3 lg:gap-5" x-show="!scrolledCompact" x-transition.opacity.duration.160ms>
                @guest
                    <a class="text-foreground/80 hover:text-foreground" href="/login">{{ __('ui.login') }}</a>
                    <a href="/register" class="btn">{{ __('ui.register') }}</a>
                @endguest

                <div class="relative" @click.outside="desktopSettingsOpen = false">
                    <button
                        type="button"
                        @click="desktopSettingsOpen = !desktopSettingsOpen"
                        aria-label="{{ __('ui.settings') }}"
                        class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border/80 bg-card/85 text-foreground/85 transition-all duration-200 hover:border-primary/45 hover:text-primary hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_28%,transparent)]"
                    >
                        <span class="text-sm leading-none">&#9881;</span>
                    </button>

                    <div
                        x-show="desktopSettingsOpen"
                        x-transition.origin.top.right.duration.180ms
                        class="absolute right-0 top-10 z-40 w-60 rounded-xl border border-border/80 bg-card/95 p-3 shadow-[0_16px_34px_color-mix(in_srgb,black_18%,transparent),0_0_16px_color-mix(in_srgb,var(--color-primary)_16%,transparent)] backdrop-blur-md"
                    >
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.settings') }}</p>

                    <div class="flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                        <p class="text-xs text-muted-foreground">{{ __('ui.language') }}</p>
                        <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-1.5">
                            @csrf
                            <input type="hidden" name="locale" value="{{ $locale === 'nl' ? 'en' : 'nl' }}">
                            <span class="text-[10px] font-semibold uppercase leading-none text-muted-foreground">NL</span>
                            <button
                                type="submit"
                                role="switch"
                                aria-label="{{ __('ui.language') }}"
                                aria-checked="{{ $locale === 'en' ? 'true' : 'false' }}"
                                class="relative inline-flex h-6 w-11 items-center rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)] transition-all duration-200 hover:border-[color:color-mix(in_srgb,var(--color-primary)_44%,var(--color-border))] hover:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_30%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                            >
                                <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-primary shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_45%,transparent)] transition-all duration-200 {{ $locale === 'en' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                            </button>
                            <span class="text-[10px] font-semibold uppercase leading-none text-muted-foreground">EN</span>
                        </form>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                        <p class="text-xs text-muted-foreground" x-text="theme === 'dark' ? @js(__('ui.dark_mode')) : @js(__('ui.light_mode'))"></p>
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
                    </div>

                    <div class="mt-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                        <p class="mb-2 text-xs text-muted-foreground">{{ __('ui.accent_color') }}</p>
                        <div class="flex items-center gap-2">
                            <button type="button" @click="setAccent('green')" :class="accent === 'green' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#22c55e]"></button>
                            <button type="button" @click="setAccent('yellow')" :class="accent === 'yellow' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#facc15]"></button>
                            <button type="button" @click="setAccent('cyan')" :class="accent === 'cyan' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#06b6d4]"></button>
                            <button type="button" @click="setAccent('rose')" :class="accent === 'rose' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#f43f5e]"></button>
                            <button type="button" @click="setAccent('pink')" :class="accent === 'pink' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#ec4899]"></button>
                            <button type="button" @click="setAccent('orange')" :class="accent === 'orange' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#fb923c]"></button>
                            <button type="button" @click="setAccent('violet')" :class="accent === 'violet' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#8b5cf6]"></button>
                        </div>
                    </div>

                    <div class="mt-2 flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-card/70 px-2.5 py-2">
                        <p class="text-xs text-muted-foreground" x-text="motion === 'off' ? @js(__('ui.animations_off')) : @js(__('ui.animations_on'))"></p>
                        <button
                            type="button"
                            class="relative h-6 w-11 rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)]"
                            role="switch"
                            :aria-checked="motion === 'on' ? 'true' : 'false'"
                            @click="toggleMotion()"
                        >
                            <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-muted-foreground" :class="motion === 'on' ? 'translate-x-5 bg-primary' : 'translate-x-0'"></span>
                        </button>
                    </div>
                    </div>
                </div>

                @auth
                    <form action="/logout" method="POST">
                        @csrf
                        <button type="submit" class="btn">{{ __('ui.logout') }}</button>
                    </form>
                @endauth
            </div>
        </div>

        @auth
            <div class="pointer-events-none absolute left-1/2 hidden -translate-x-1/2 md:flex">
                <div
                    class="flex items-center gap-3 transition-all duration-400 ease-out lg:gap-5"
                    :class="scrolledCompact ? 'translate-x-0 opacity-100 pointer-events-auto' : 'translate-x-8 opacity-0 pointer-events-none'"
                >
                    @if (auth()->user()->isAdmin())
                        <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('admin.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('admin.index') }}">{{ __('ui.admin') }}</a>
                    @endif
                    <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('task.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('task.index') }}">{{ __('ui.tasks') }}</a>
                    <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('dashboard.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('dashboard.index') }}">{{ __('ui.dashboard') }}</a>
                    <a class="text-foreground/80 hover:text-foreground {{ request()->routeIs('profile.*') ? 'text-primary font-semibold' : '' }}" href="{{ route('profile.edit') }}">{{ __('ui.account') }}</a>
                </div>
            </div>
        @endauth

        <button
            type="button"
            x-show="scrolledCompact"
            x-transition.opacity.duration.180ms
            @click="scrollToTop()"
            class="absolute right-0 top-1/2 hidden h-8 w-8 -translate-y-1/2 items-center justify-center rounded-full border border-border/80 bg-card/90 text-base text-foreground/90 shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_22%,transparent)] transition-all duration-200 hover:-translate-y-[55%] hover:border-primary/55 hover:text-primary md:inline-flex"
            aria-label="{{ __('ui.back_to_top') }}"
            x-cloak
        >
            &uarr;
        </button>
    </div>
</nav>

<div
    aria-hidden="true"
    class="h-[88px] transition-all duration-300 ease-out md:h-[88px]"
    :class="scrolledCompact ? 'md:h-[60px]' : 'md:h-[88px]'"
></div>

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
            aria-label="{{ __('ui.close_menu') }}"
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
                    <p class="text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.menu') }}</p>
                    <p class="mt-1 text-sm font-semibold text-foreground/90">{{ __('ui.welcome', ['name' => auth()->user()->name]) }}</p>
                </div>
                <button
                    type="button"
                    @click="mobileMenuOpen = false"
                    aria-label="{{ __('ui.close_menu') }}"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-border/80 bg-card/80 text-foreground/80 transition-all duration-200 hover:border-primary/45 hover:text-primary"
                >
                    <span class="text-base leading-none">&#10005;</span>
                </button>
            </div>

            <div class="mt-5 rounded-xl border border-border/80 bg-card/75 p-3">
                <p class="mb-2 text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.navigation') }}</p>
                <div class="space-y-1.5">
                    <a href="{{ route('task.index') }}" @click="mobileMenuOpen = false" class="block rounded-lg px-2.5 py-2 text-sm text-foreground/80 transition-colors hover:bg-card/80 hover:text-foreground">{{ __('ui.tasks') }}</a>
                    <a href="{{ route('profile.edit') }}" @click="mobileMenuOpen = false" class="block rounded-lg px-2.5 py-2 text-sm transition-colors hover:bg-card/80 {{ request()->routeIs('profile.*') ? 'text-primary font-semibold' : 'text-foreground/80 hover:text-foreground' }}">{{ __('ui.account') }}</a>
                    <a href="{{ route('dashboard.index') }}" @click="mobileMenuOpen = false" class="block rounded-lg px-2.5 py-2 text-sm text-foreground/80 transition-colors hover:bg-card/80 hover:text-foreground">{{ __('ui.dashboard') }}</a>
                    @if (auth()->user()->isAdmin())
                        <a href="{{ route('admin.index') }}" @click="mobileMenuOpen = false" class="block rounded-lg px-2.5 py-2 text-sm text-foreground/80 transition-colors hover:bg-card/80 hover:text-foreground">{{ __('ui.admin') }}</a>
                    @endif
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-border/80 bg-card/75 p-3">
                <p class="mb-2 text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.support_section') }}</p>
                <div class="space-y-1.5">
                    <a href="{{ route('support') }}" @click="mobileMenuOpen = false" class="block rounded-lg px-2.5 py-2 text-sm text-foreground/80 transition-colors hover:bg-card/80 hover:text-foreground">{{ __('ui.support') }}</a>
                    <a href="{{ route('privacy') }}" @click="mobileMenuOpen = false" class="block rounded-lg px-2.5 py-2 text-sm text-foreground/80 transition-colors hover:bg-card/80 hover:text-foreground">{{ __('ui.privacy') }}</a>
                    <a href="{{ route('terms') }}" @click="mobileMenuOpen = false" class="block rounded-lg px-2.5 py-2 text-sm text-foreground/80 transition-colors hover:bg-card/80 hover:text-foreground">{{ __('ui.terms') }}</a>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-border/80 bg-card/75 p-3">
                <p class="mb-2 text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.inbox') }}</p>
                @if ($pendingCollabRequests->isEmpty() && $unreadMentionNotifications->isEmpty() && $unreadDueDateReminders->isEmpty() && $unreadSupportReplies->isEmpty())
                    <p class="text-sm text-muted-foreground">{{ __('ui.no_messages') }}</p>
                @endif

                @if ($pendingCollabRequests->isNotEmpty())
                    <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.invites') }}</p>
                    <div class="space-y-2">
                        @foreach ($pendingCollabRequests as $inviteRequest)
                            <div class="rounded-lg border border-border/70 bg-card/70 p-2.5">
                                <p class="text-xs text-foreground/85">{{ __('ui.invite_from', ['name' => $inviteRequest->inviter->name]) }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $inviteRequest->task->title]) }}</p>
                                <div class="mt-2 flex gap-2">
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
                    </div>
                @endif

                @if ($unreadMentionNotifications->isNotEmpty())
                    <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.mentions') }}</p>
                    <div class="space-y-2">
                        @foreach ($unreadMentionNotifications as $mention)
                            <a
                                href="{{ route('inbox.mentions.open', $mention) }}"
                                @click="mobileMenuOpen = false"
                                class="no-link-hover block rounded-lg border border-border/70 bg-card/70 p-2.5 transition-colors hover:border-primary/45"
                            >
                                <p class="text-xs text-foreground/90">{{ __('ui.mentioned_you', ['name' => $mention->mentionedBy->name]) }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $mention->task->title]) }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ \Illuminate\Support\Str::limit($mention->comment->body ?? '', 90) }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($unreadDueDateReminders->isNotEmpty())
                    <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.reminders') }}</p>
                    <div class="space-y-2">
                        @foreach ($unreadDueDateReminders as $reminder)
                            <a
                                href="{{ route('inbox.reminders.open', $reminder) }}"
                                @click="mobileMenuOpen = false"
                                class="no-link-hover block rounded-lg border border-border/70 bg-card/70 p-2.5 transition-colors hover:border-primary/45"
                            >
                                <p class="text-xs text-foreground/90">{{ __('ui.reminder_due_on', ['date' => $reminder->due_date->translatedFormat('j M Y')]) }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.invite_for_task', ['title' => $reminder->task->title]) }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($unreadSupportReplies->isNotEmpty())
                    <p class="mb-2 mt-3 text-[11px] font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.support_updates') }}</p>
                    <div class="space-y-2">
                        @foreach ($unreadSupportReplies as $reply)
                            <a
                                href="{{ route('inbox.support.open', $reply) }}"
                                @click="mobileMenuOpen = false"
                                class="no-link-hover block rounded-lg border border-border/70 bg-card/70 p-2.5 transition-colors hover:border-primary/45"
                            >
                                <p class="text-xs text-foreground/90">{{ __('ui.support_reply_from', ['name' => $reply->user?->name ?? __('support.support_team')]) }}</p>
                                <p class="mt-0.5 text-xs text-muted-foreground">{{ __('ui.support_ticket_subject', ['subject' => $reply->supportMessage?->subject ?? '-']) }}</p>
                                <p class="mt-1 text-xs text-muted-foreground">{{ \Illuminate\Support\Str::limit($reply->message ?? '', 90) }}</p>
                            </a>
                        @endforeach
                    </div>
                @endif

                <div class="mt-3 border-t border-border/70 pt-2">
                    <a href="{{ route('inbox.index') }}" @click="mobileMenuOpen = false" class="no-link-hover inline-flex items-center text-xs font-medium text-primary hover:text-foreground">
                        {{ __('ui.view_all_notifications') }}
                    </a>
                </div>
            </div>

            <div class="mt-4 rounded-xl border border-border/80 bg-card/75 p-3">
                <p class="mb-3 text-xs uppercase tracking-[0.08em] text-muted-foreground">{{ __('ui.settings') }}</p>

                <div class="flex items-center justify-between gap-3 rounded-lg border border-border/70 bg-card/70 px-3 py-2">
                    <div class="text-xs text-muted-foreground">
                        <p class="font-medium text-foreground/85">{{ __('ui.language') }}</p>
                        <p>{{ $locale === 'nl' ? __('ui.lang_nl') : __('ui.lang_en') }}</p>
                    </div>
                    <form method="POST" action="{{ route('locale.update') }}" class="flex items-center gap-1.5">
                        @csrf
                        <input type="hidden" name="locale" value="{{ $locale === 'nl' ? 'en' : 'nl' }}">
                        <span class="text-[10px] font-semibold uppercase leading-none text-muted-foreground">NL</span>
                        <button
                            type="submit"
                            role="switch"
                            aria-label="{{ __('ui.language') }}"
                            aria-checked="{{ $locale === 'en' ? 'true' : 'false' }}"
                            class="relative inline-flex h-6 w-11 items-center rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)] transition-all duration-200 hover:border-[color:color-mix(in_srgb,var(--color-primary)_44%,var(--color-border))] hover:shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-primary)_30%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]"
                        >
                            <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-primary shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_45%,transparent)] transition-all duration-200 {{ $locale === 'en' ? 'translate-x-5' : 'translate-x-0' }}"></span>
                        </button>
                        <span class="text-[10px] font-semibold uppercase leading-none text-muted-foreground">EN</span>
                    </form>
                </div>

                <div class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-border/70 bg-card/70 px-3 py-2">
                    <div class="text-xs text-muted-foreground">
                        <p class="font-medium text-foreground/85" x-text="theme === 'dark' ? @js(__('ui.dark_mode')) : @js(__('ui.light_mode'))"></p>
                    </div>
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
                </div>

                <div class="mt-2 rounded-lg border border-border/70 bg-card/70 px-3 py-2">
                    <p class="mb-2 text-xs text-muted-foreground">{{ __('ui.accent_color') }}</p>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="setAccent('green')" :class="accent === 'green' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#22c55e]"></button>
                        <button type="button" @click="setAccent('yellow')" :class="accent === 'yellow' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#facc15]"></button>
                        <button type="button" @click="setAccent('cyan')" :class="accent === 'cyan' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#06b6d4]"></button>
                        <button type="button" @click="setAccent('rose')" :class="accent === 'rose' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#f43f5e]"></button>
                        <button type="button" @click="setAccent('pink')" :class="accent === 'pink' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#ec4899]"></button>
                        <button type="button" @click="setAccent('orange')" :class="accent === 'orange' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#fb923c]"></button>
                        <button type="button" @click="setAccent('violet')" :class="accent === 'violet' ? 'ring-2 ring-primary' : 'ring-1 ring-border'" class="size-5 rounded-full bg-[#8b5cf6]"></button>
                    </div>
                </div>

                <div class="mt-2 flex items-center justify-between gap-3 rounded-lg border border-border/70 bg-card/70 px-3 py-2">
                    <p class="text-xs text-muted-foreground" x-text="motion === 'off' ? @js(__('ui.animations_off')) : @js(__('ui.animations_on'))"></p>
                    <button
                        type="button"
                        class="relative h-6 w-11 rounded-full border border-border bg-[color-mix(in_srgb,var(--color-card)_84%,var(--color-input))] shadow-[inset_0_0_0_1px_color-mix(in_srgb,var(--color-border)_65%,transparent)]"
                        role="switch"
                        :aria-checked="motion === 'on' ? 'true' : 'false'"
                        @click="toggleMotion()"
                    >
                        <span class="absolute left-0.5 top-0.5 h-[18px] w-[18px] rounded-full bg-muted-foreground" :class="motion === 'on' ? 'translate-x-5 bg-primary' : 'translate-x-0'"></span>
                    </button>
                </div>
            </div>

            <div class="mt-4 pt-3 border-t border-border/70">
                <form action="/logout" method="POST">
                    @csrf
                    <button type="submit" class="btn h-9 w-auto px-3 text-sm">{{ __('ui.logout') }}</button>
                </form>
            </div>
        </aside>
    </div>
@endauth
</div>



