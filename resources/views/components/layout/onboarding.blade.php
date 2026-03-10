@auth
    @if (! auth()->user()->hasCompletedOnboarding())
        @php
            $onboardingSteps = [
                [
                    'title' => __('ui.onboarding_step_1_title'),
                    'copy' => __('ui.onboarding_step_1_copy'),
                ],
                [
                    'title' => __('ui.onboarding_step_2_title'),
                    'copy' => __('ui.onboarding_step_2_copy'),
                ],
                [
                    'title' => __('ui.onboarding_step_3_title'),
                    'copy' => __('ui.onboarding_step_3_copy'),
                ],
                [
                    'title' => __('ui.onboarding_step_4_title'),
                    'copy' => __('ui.onboarding_step_4_copy'),
                ],
                [
                    'title' => __('ui.onboarding_step_5_title'),
                    'copy' => __('ui.onboarding_step_5_copy'),
                ],
            ];
        @endphp

        <div
            x-data="{
                open: true,
                step: 0,
                steps: @js($onboardingSteps),
                busy: false,
                csrf: @js(csrf_token()),
                completeUrl: @js(route('onboarding.complete')),
                successMessage: @js(__('ui.onboarding_done_toast')),
                errorMessage: @js(__('ui.onboarding_error_toast')),
                get isLastStep() {
                    return this.step === this.steps.length - 1;
                },
                next() {
                    if (this.isLastStep) {
                        this.complete();
                        return;
                    }

                    this.step += 1;
                },
                async complete() {
                    if (this.busy) {
                        return;
                    }

                    this.busy = true;

                    try {
                        const response = await fetch(this.completeUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrf,
                            },
                            body: JSON.stringify({}),
                        });

                        if (!response.ok) {
                            throw new Error('Onboarding completion failed');
                        }

                        this.open = false;
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'success',
                                message: this.successMessage,
                            }
                        }));
                    } catch (error) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: {
                                type: 'error',
                                message: this.errorMessage,
                            }
                        }));
                    } finally {
                        this.busy = false;
                    }
                }
            }"
            x-show="open"
            @keydown.escape.window="complete()"
            class="fixed inset-0 z-[130]"
            role="dialog"
            aria-modal="true"
            aria-label="{{ __('ui.onboarding_title') }}"
            style="display: none;"
            data-onboarding-modal
        >
            <div class="absolute inset-0 bg-black/70 backdrop-blur-sm"></div>

            <div class="relative mx-auto flex min-h-full w-full max-w-2xl items-center px-4 py-6 sm:px-6">
                <section class="w-full rounded-2xl border border-border/80 bg-card/95 p-4 shadow-[0_24px_50px_color-mix(in_srgb,black_35%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_18%,transparent)] sm:p-5">
                    <header class="border-b border-border/70 pb-3">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.1em] text-primary">{{ __('ui.onboarding_badge') }}</p>
                        <h2 class="mt-1 text-xl font-extrabold text-foreground">{{ __('ui.onboarding_title') }}</h2>
                        <p class="mt-1 text-sm text-muted-foreground">{{ __('ui.onboarding_subtitle') }}</p>
                    </header>

                    <div class="mt-4 flex items-center gap-1.5">
                        <template x-for="(_, index) in steps" :key="index">
                            <span
                                class="h-1.5 flex-1 rounded-full transition-all duration-250"
                                :class="index <= step
                                    ? 'bg-primary shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_45%,transparent)]'
                                    : 'bg-border/70'"
                            ></span>
                        </template>
                    </div>

                    <div class="mt-4 min-h-32 rounded-xl border border-border/70 bg-card/75 p-4">
                        <p class="text-xs uppercase tracking-[0.08em] text-muted-foreground">
                            {{ __('ui.onboarding_step_label') }}
                            <span x-text="`${step + 1}/${steps.length}`"></span>
                        </p>
                        <h3 class="mt-2 text-lg font-bold text-foreground" x-text="steps[step].title"></h3>
                        <p class="mt-2 text-sm leading-relaxed text-muted-foreground" x-text="steps[step].copy"></p>
                    </div>

                    <footer class="mt-4 flex items-center justify-between gap-2">
                        <button
                            type="button"
                            @click="complete()"
                            :disabled="busy"
                            class="btn btn-outlined h-9 px-3 text-sm disabled:cursor-not-allowed disabled:opacity-65"
                        >
                            {{ __('ui.onboarding_skip') }}
                        </button>

                        <button
                            type="button"
                            @click="next()"
                            :disabled="busy"
                            class="btn h-9 px-4 text-sm disabled:cursor-not-allowed disabled:opacity-65"
                            x-text="isLastStep ? @js(__('ui.onboarding_finish')) : @js(__('ui.onboarding_next'))"
                        ></button>
                    </footer>
                </section>
            </div>
        </div>
    @endif
@endauth
