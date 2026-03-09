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
            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.how_title') }}</h2>
                <ol class="mt-3 list-decimal pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>{{ __('support.how_1') }}</li>
                    <li>{{ __('support.how_2') }}</li>
                    <li>{{ __('support.how_3') }}</li>
                </ol>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.time_title') }}</h2>
                <ol class="mt-3 list-decimal pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>{{ __('support.time_1') }}</li>
                    <li>{{ __('support.time_2') }}</li>
                    <li>{{ __('support.time_3') }}</li>
                    <li>{{ __('support.time_4') }}</li>
                </ol>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.faq_title') }}</h2>
                <div class="mt-3 space-y-3">
                    @foreach (__('support.faqs') as $faq)
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
                </div>
            </x-card>
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <x-card is="section" hoverable="false" class="p-6">
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

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('support.my_recent') }}</h2>
                @auth
                    <div class="mt-4 space-y-3">
                        @forelse($myMessages as $ticket)
                            <div class="rounded-lg border border-border/80 bg-card/70 p-3">
                                <div class="flex items-center justify-between gap-3">
                                    <p class="font-semibold text-foreground text-sm">{{ $ticket->subject }}</p>
                                    <span class="text-xs {{ $ticket->status === 'resolved' ? 'text-primary' : 'text-muted-foreground' }}">
                                        {{ $ticket->status === 'resolved' ? __('support.resolved') : __('support.open') }}
                                    </span>
                                </div>
                                <p class="mt-1 text-xs text-muted-foreground">{{ $ticket->created_at->format('d-m-Y H:i') }}</p>
                                <p class="mt-2 text-sm text-muted-foreground line-clamp-2">{{ $ticket->message }}</p>
                            </div>
                        @empty
                            <p class="text-sm text-muted-foreground">{{ __('support.none_sent') }}</p>
                        @endforelse
                    </div>
                @else
                    <p class="mt-3 text-sm text-muted-foreground">
                        {{ __('support.has_account') }}
                    </p>
                    <div class="mt-4 flex gap-3">
                        <a href="{{ route('login') }}" class="btn">{{ __('ui.login') }}</a>
                    </div>
                @endauth
            </x-card>
        </div>
    </div>

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
