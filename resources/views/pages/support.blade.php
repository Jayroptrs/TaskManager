<x-layout>
    <div class="py-8 md:py-12 max-w-5xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">{{ __('support.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            {{ __('support.subtitle') }}
        </p>

        <div class="mt-8 space-y-6">
            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.how_title') }}</h2>
                <ol class="mt-3 list-decimal pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>{{ __('support.how_1') }}</li>
                    <li>{{ __('support.how_2') }}</li>
                    <li>{{ __('support.how_3') }}</li>
                </ol>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.time_title') }}</h2>
                <ol class="mt-3 list-decimal pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>{{ __('support.time_1') }}</li>
                    <li>{{ __('support.time_2') }}</li>
                    <li>{{ __('support.time_3') }}</li>
                    <li>{{ __('support.time_4') }}</li>
                </ol>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.faq_title') }}</h2>
                @php
                    $faqs = collect(__('support.faqs'));
                    $primaryFaqs = $faqs->take(5);
                    $extraFaqs = $faqs->slice(5)->values();
                @endphp
                <div class="mt-3 space-y-3" x-data="{ showMoreFaqs: false }">
                    @foreach ($primaryFaqs as $faq)
                        <div x-data="{ open: false }" class="faq-item rounded-lg border border-border/80 bg-card/70 px-4 py-3">
                            <button type="button" class="faq-summary w-full text-left text-sm font-semibold text-foreground" @click="open = !open" :aria-expanded="open.toString()">
                                <span>{{ $faq['q'] }}</span>
                                <span class="faq-chevron" aria-hidden="true" :class="{ 'is-rotated': open }">&#9656;</span>
                            </button>
                            <div
                                class="faq-answer"
                                x-ref="answer"
                                :style="open ? `max-height: ${$refs.answer.scrollHeight}px; opacity: 1;` : 'max-height: 0px; opacity: 0.65;'"
                            >
                                <div class="faq-answer-inner">
                                    <p class="pt-2 text-sm text-muted-foreground">{{ $faq['a'] }}</p>
                                </div>
                            </div>
                        </div>
                    @endforeach

                    @if ($extraFaqs->isNotEmpty())
                        <div
                            class="overflow-hidden transition-[max-height,opacity] duration-500 ease-out"
                            :style="showMoreFaqs
                                ? `max-height: ${$refs.extraFaqInner.scrollHeight}px; opacity: 1;`
                                : 'max-height: 0px; opacity: 0;'"
                        >
                            <div x-ref="extraFaqInner" class="space-y-3 pt-3">
                                @foreach ($extraFaqs as $index => $faq)
                                    <div
                                        x-data="{ open: false }"
                                        class="faq-item rounded-lg border border-border/80 bg-card/70 px-4 py-3 transition-all duration-350 ease-out"
                                        :class="showMoreFaqs ? 'translate-y-0 opacity-100' : 'translate-y-2 opacity-0'"
                                        :style="showMoreFaqs ? 'transition-delay: {{ $index * 75 }}ms;' : 'transition-delay: 0ms;'"
                                    >
                                        <button type="button" class="faq-summary w-full text-left text-sm font-semibold text-foreground" @click="open = !open" :aria-expanded="open.toString()">
                                            <span>{{ $faq['q'] }}</span>
                                            <span class="faq-chevron" aria-hidden="true" :class="{ 'is-rotated': open }">&#9656;</span>
                                        </button>
                                        <div
                                            class="faq-answer"
                                            x-ref="answer"
                                            :style="open ? `max-height: ${$refs.answer.scrollHeight}px; opacity: 1;` : 'max-height: 0px; opacity: 0.65;'"
                                        >
                                            <div class="faq-answer-inner">
                                                <p class="pt-2 text-sm text-muted-foreground">{{ $faq['a'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="pt-1">
                            <button
                                type="button"
                                class="btn btn-outlined w-full sm:w-auto"
                                @click="showMoreFaqs = !showMoreFaqs"
                                :aria-expanded="showMoreFaqs.toString()"
                            >
                                <span x-text="showMoreFaqs ? @js(__('support.faq_show_less')) : @js(__('support.faq_show_more'))"></span>
                            </button>
                        </div>
                    @endif
                </div>
            </x-card>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.new_message') }}</h2>
                <form method="POST" action="{{ route('support.store') }}" class="mt-4 space-y-4">
                    @csrf
                    @guest
                        <div class="grid gap-4 md:grid-cols-2">
                            <x-form.field
                                name="guest_name"
                                :label="__('support.name')"
                                :placeholder="__('support.name_placeholder')"
                                required
                            />
                            <x-form.field
                                name="guest_email"
                                type="email"
                                :label="__('support.email')"
                                :placeholder="__('support.email_placeholder')"
                                required
                            />
                        </div>
                    @endguest

                    <x-form.field
                        name="subject"
                        :label="__('support.subject')"
                        :placeholder="__('support.subject_placeholder')"
                        required
                    />

                    <div>
                        <label for="category" class="label">{{ __('support.category') }}</label>
                        @php
                            $categories = [
                                ['value' => 'algemeen', 'label' => __('support.categories.algemeen')],
                                ['value' => 'account', 'label' => __('support.categories.account')],
                                ['value' => 'bug', 'label' => __('support.categories.bug')],
                                ['value' => 'privacy', 'label' => __('support.categories.privacy')],
                                ['value' => 'security', 'label' => __('support.categories.security')],
                                ['value' => 'billing', 'label' => __('support.categories.billing')],
                            ];
                        @endphp
                        <div
                            class="relative mt-2"
                            x-data="{ open: false, value: @js(old('category', 'algemeen')), options: @js($categories) }"
                            @keydown.escape.window="open = false"
                        >
                            <input type="hidden" id="category" name="category" x-model="value" required>
                            <button type="button" @click="open = !open" class="input input-neon-select w-full text-left">
                                <span x-text="options.find(option => option.value === value)?.label ?? @js(__('support.category_placeholder'))"></span>
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

                    <x-form.field
                        name="message"
                        type="textarea"
                        :label="__('support.message')"
                        :placeholder="__('support.message_placeholder')"
                        class="min-h-20 max-h-100"
                        required
                    />

                    @guest
                        @if($recaptchaEnabledForGuest ?? false)
                            <div>
                                <div id="support-recaptcha"></div>
                                <p id="recaptcha-load-warning" class="mt-2 hidden text-xs text-red-500">
                                    {{ __('messages.recaptcha_required') }}
                                </p>
                                @error('g-recaptcha-response')
                                    <p class="error mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif
                    @endguest

                    <button class="btn" type="submit">{{ __('support.send') }}</button>
                </form>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.my_recent') }}</h2>
                @php
                    $supportStatusLabels = __('support.statuses');
                    $supportStatusClasses = [
                        'open' => 'text-foreground',
                        'in_progress' => 'text-blue-400',
                        'waiting_for_user' => 'text-amber-400',
                        'resolved' => 'text-primary',
                    ];
                @endphp
                @auth
                    <div class="mt-4 space-y-3">
                        @forelse($myMessages as $ticket)
                            <a
                                href="{{ route('support', ['ticket' => $ticket->id]) }}"
                                class="block w-full rounded-lg border border-border/80 bg-card/70 p-3 text-left transition-colors hover:border-primary/45"
                            >
                                <div class="flex flex-wrap items-start justify-between gap-3">
                                    <p class="min-w-0 break-words font-semibold text-foreground text-sm">{{ $ticket->subject }}</p>
                                    <span class="text-xs {{ $supportStatusClasses[$ticket->status] ?? 'text-muted-foreground' }}">
                                        {{ data_get($supportStatusLabels, $ticket->status, ucfirst((string) $ticket->status)) }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">{{ $ticket->created_at->format('d-m-Y H:i') }}</p>
                                <p class="mt-2 text-sm text-muted-foreground line-clamp-2">{{ $ticket->message }}</p>
                            </a>
                        @empty
                            <p class="text-sm text-muted-foreground">{{ __('support.none_sent') }}</p>
                        @endforelse
                    </div>

                    @foreach($myMessages as $ticket)
                        <x-modal :name="'support-ticket-' . $ticket->id" :title="$ticket->subject">
                            <div class="grid gap-3 sm:grid-cols-2 text-sm">
                                <div>
                                    <p class="text-muted-foreground">{{ __('support.category') }}</p>
                                    <p class="font-semibold text-foreground">{{ data_get(__('support.categories'), $ticket->category, ucfirst((string) $ticket->category)) }}</p>
                                </div>
                                <div>
                                    <p class="text-muted-foreground">{{ __('support.status_label') }}</p>
                                    <p class="font-semibold {{ $supportStatusClasses[$ticket->status] ?? 'text-foreground' }}">
                                        {{ data_get($supportStatusLabels, $ticket->status, ucfirst((string) $ticket->status)) }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-4 rounded-lg border border-border/70 bg-card/70 p-4">
                                <p class="text-muted-foreground text-xs uppercase tracking-wide">{{ __('support.conversation') }}</p>
                                <div class="mt-3 space-y-3">
                                    <div class="rounded-lg border border-primary/30 bg-primary/8 px-3 py-2">
                                        <div class="flex items-center justify-between gap-3">
                                            <p class="text-xs font-semibold text-primary">{{ __('support.you') }}</p>
                                            <p class="text-[11px] text-muted-foreground">{{ $ticket->created_at->format('d-m-Y H:i') }}</p>
                                        </div>
                                        <p class="mt-1.5 whitespace-pre-wrap text-sm text-foreground">{{ $ticket->message }}</p>
                                    </div>

                                    @foreach($ticket->replies as $reply)
                                        <div class="rounded-lg border px-3 py-2 {{ $reply->is_admin ? 'border-blue-400/35 bg-blue-500/8' : 'border-primary/25 bg-primary/7' }}">
                                            <div class="flex items-center justify-between gap-3">
                                                <p class="text-xs font-semibold {{ $reply->is_admin ? 'text-blue-300' : 'text-primary' }}">
                                                    {{ $reply->is_admin ? __('support.support_team') : __('support.you') }}
                                                </p>
                                                <p class="text-[11px] text-muted-foreground">{{ $reply->created_at->format('d-m-Y H:i') }}</p>
                                            </div>
                                            <p class="mt-1.5 whitespace-pre-wrap text-sm text-foreground">{{ $reply->message }}</p>
                                        </div>
                                    @endforeach
                                </div>
                            </div>

                            @if($ticket->requiresUserResolutionConfirmation())
                                <div class="mt-4 rounded-lg border border-amber-400/35 bg-amber-500/8 p-4">
                                    <p class="text-sm text-amber-200">{{ __('support.awaiting_user_resolution') }}</p>
                                    <form method="POST" action="{{ route('support.resolve', $ticket) }}" class="mt-3">
                                        @csrf
                                        <button type="submit" class="btn">{{ __('support.mark_resolved') }}</button>
                                    </form>
                                </div>
                            @endif

                            <form method="POST" action="{{ route('support.reply', $ticket) }}" class="mt-4 space-y-3">
                                @csrf
                                <div>
                                    <label for="support-reply-{{ $ticket->id }}" class="label">{{ __('support.reply_label') }}</label>
                                    <textarea
                                        id="support-reply-{{ $ticket->id }}"
                                        name="message"
                                        class="input min-h-24"
                                        placeholder="{{ __('support.reply_placeholder') }}"
                                        required
                                    >{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="error mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex justify-end">
                                    <button type="submit" class="btn">{{ __('support.send_reply') }}</button>
                                </div>
                            </form>
                        </x-modal>
                    @endforeach
                @else
                    <p class="mt-3 text-sm text-muted-foreground">
                        {{ __('support.has_account') }}
                    </p>
                    <div class="mt-4 flex flex-wrap gap-3">
                        <a href="{{ route('login') }}" class="btn">{{ __('ui.login') }}</a>
                    </div>
                @endauth
            </x-card>
        </div>
    </div>

    @auth
        @if(!empty($activeTicketId))
            <script>
                window.addEventListener('load', () => {
                    const ticketId = @js((int) $activeTicketId);
                    if (!ticketId) return;

                    window.dispatchEvent(new CustomEvent('open-modal', {
                        detail: `support-ticket-${ticketId}`,
                    }));
                });
            </script>
        @endif
    @endauth

    @guest
        @if($recaptchaEnabledForGuest ?? false)
            <script>
                window.renderSupportRecaptcha = function () {
                    const container = document.getElementById('support-recaptcha');
                    const warning = document.getElementById('recaptcha-load-warning');

                    if (!container || typeof grecaptcha === 'undefined') {
                        if (warning) warning.classList.remove('hidden');
                        return;
                    }

                    try {
                        grecaptcha.render(container, {
                            sitekey: @js(config('services.recaptcha.site_key')),
                        });
                    } catch (e) {
                        if (warning) warning.classList.remove('hidden');
                    }
                };
            </script>
            <script src="https://www.google.com/recaptcha/api.js?onload=renderSupportRecaptcha&render=explicit" async defer></script>
            <script>
                window.addEventListener('load', () => {
                    setTimeout(() => {
                        const hasWidget = !!document.querySelector('#support-recaptcha iframe');
                        const warning = document.getElementById('recaptcha-load-warning');

                        if (!hasWidget && warning) {
                            warning.classList.remove('hidden');
                        }
                    }, 2500);
                });
            </script>
        @endif
    @endguest
</x-layout>
