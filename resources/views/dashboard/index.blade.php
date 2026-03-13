<x-layout>
    @php
        $cardBase = 'rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] shadow-[0_12px_28px_color-mix(in_srgb,black_11%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_11%,transparent)]';
        $kpiCard = $cardBase.' p-4 transition-all duration-200 hover:-translate-y-0.5 hover:border-primary/45 hover:shadow-[0_16px_34px_color-mix(in_srgb,black_13%,transparent),0_0_24px_color-mix(in_srgb,var(--color-primary)_24%,transparent)]';
        $panel = $cardBase.' p-4';
    @endphp

    <header class="py-8 md:py-12">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>
        <h1 class="mt-3 text-3xl font-bold tracking-tight sm:text-4xl">{{ __('dashboard.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">{{ __('dashboard.subtitle') }}</p>
    </header>

    <section class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">
        <article class="{{ $kpiCard }}">
            <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('dashboard.kpi_total_tasks') }}</p>
            <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $totalTasks }}</p>
            <p class="mt-2 text-sm text-muted-foreground">{{ __('dashboard.kpi_total_tasks_sub') }}</p>
        </article>

        <article class="{{ $kpiCard }}">
            <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('dashboard.kpi_completion_rate') }}</p>
            <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $completionRate }}%</p>
            <p class="mt-2 text-sm text-muted-foreground">{{ __('dashboard.kpi_completion_rate_sub', ['completed' => $completedTasks, 'total' => max($totalTasks, 1)]) }}</p>
        </article>

        <article class="{{ $kpiCard }}">
            <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('dashboard.kpi_created_7d') }}</p>
            <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $tasksCreatedLast7Days }}</p>
            <p class="mt-2 text-sm text-muted-foreground">{{ __('dashboard.kpi_created_7d_sub') }}</p>
        </article>

        <article class="{{ $kpiCard }}">
            <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('dashboard.kpi_completed_7d') }}</p>
            <p class="mt-2 text-[clamp(1.65rem,2.8vw,2.1rem)] leading-none font-extrabold text-foreground">{{ $tasksCompletedLast7Days }}</p>
            <p class="mt-2 text-sm text-muted-foreground">{{ __('dashboard.kpi_completed_7d_sub') }}</p>
        </article>
    </section>

    <section class="mt-6">
        <article class="{{ $panel }}">
            <header class="mb-3">
                <h2 class="text-base font-bold text-foreground">{{ __('dashboard.ownership_split') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('dashboard.ownership_split_sub') }}</p>
            </header>

            @php
                $ownedPct = $totalTasks > 0 ? (int) round(($ownedTasks / $totalTasks) * 100) : 0;
                $collabPct = $totalTasks > 0 ? (int) round(($collaborativeTasks / $totalTasks) * 100) : 0;
            @endphp

            <div class="grid gap-4 md:grid-cols-2">
                <div class="rounded-xl border border-border/75 bg-card/75 p-3">
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="text-foreground/90">{{ __('dashboard.owned_tasks') }}</span>
                        <span class="font-semibold text-foreground">{{ $ownedTasks }}</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-[color-mix(in_srgb,var(--color-input)_48%,transparent)]">
                        <div
                            class="h-full rounded-full bg-[linear-gradient(90deg,color-mix(in_srgb,var(--color-primary)_82%,white_6%),color-mix(in_srgb,var(--color-primary)_56%,var(--color-border)))] shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_42%,transparent)]"
                            style="width: {{ $ownedPct }}%;"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $ownedPct }}%</p>
                </div>

                <div class="rounded-xl border border-border/75 bg-card/75 p-3">
                    <div class="mb-1 flex items-center justify-between text-sm">
                        <span class="text-foreground/90">{{ __('dashboard.collaborative_tasks') }}</span>
                        <span class="font-semibold text-foreground">{{ $collaborativeTasks }}</span>
                    </div>
                    <div class="h-2.5 w-full overflow-hidden rounded-full bg-[color-mix(in_srgb,var(--color-input)_48%,transparent)]">
                        <div
                            class="h-full rounded-full bg-[linear-gradient(90deg,color-mix(in_srgb,var(--color-primary)_58%,#3b82f6_42%),color-mix(in_srgb,#3b82f6_62%,var(--color-border)))] shadow-[0_0_14px_color-mix(in_srgb,#3b82f6_42%,transparent)]"
                            style="width: {{ $collabPct }}%;"
                        ></div>
                    </div>
                    <p class="mt-1 text-xs text-muted-foreground">{{ $collabPct }}%</p>
                </div>
            </div>
        </article>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-[1.25fr_0.75fr]">
        <article class="{{ $panel }}">
            <header class="mb-3">
                <h2 class="text-base font-bold text-foreground">{{ __('dashboard.activity_title') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('dashboard.activity_subtitle') }}</p>
                <div class="mt-3 flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                    <span class="inline-flex items-center gap-2">
                        <span class="size-2.5 rounded-full bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_75%,white_10%),color-mix(in_srgb,var(--color-primary)_52%,var(--color-border)))] shadow-[0_0_10px_color-mix(in_srgb,var(--color-primary)_35%,transparent)]"></span>
                        <span>{{ __('dashboard.created') }}</span>
                    </span>
                    <span class="inline-flex items-center gap-2">
                        <span class="size-2.5 rounded-full bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_58%,#3b82f6_42%),color-mix(in_srgb,#3b82f6_62%,var(--color-border)))] shadow-[0_0_10px_color-mix(in_srgb,#3b82f6_35%,transparent)]"></span>
                        <span>{{ __('dashboard.completed') }}</span>
                    </span>
                </div>
            </header>

            @php
                $activityMidLabel = $activityMax > 1 ? (int) ceil($activityMax / 2) : null;
                $activityRows = collect($activity)->chunk(7);
            @endphp

            <div class="space-y-3 lg:hidden">
                @forelse ($activityRows as $row)
                    <div class="grid grid-cols-[2.2rem_1fr] items-end gap-2">
                        <div class="flex h-[7.6rem] flex-col justify-between pr-1 text-right text-[0.66rem] text-muted-foreground">
                            <span>{{ $activityMax }}</span>
                            <span aria-hidden="{{ $activityMidLabel === null ? 'true' : 'false' }}" class="{{ $activityMidLabel === null ? 'opacity-0' : '' }}">
                                {{ $activityMidLabel ?? '-' }}
                            </span>
                            <span aria-hidden="true" class="opacity-0">0</span>
                        </div>
                        <div class="grid grid-cols-7 gap-2">
                            @foreach ($row as $day)
                                <div class="min-w-0">
                                    <div class="flex h-[7.6rem] items-end justify-center gap-[0.16rem] rounded-[0.55rem] bg-[color-mix(in_srgb,var(--color-input)_18%,transparent)] px-1 py-2">
                                        <div
                                            class="w-[48%] min-h-[6px] rounded-[0.38rem_0.38rem_0.18rem_0.18rem] bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_75%,white_10%),color-mix(in_srgb,var(--color-primary)_52%,var(--color-border)))] shadow-[0_0_10px_color-mix(in_srgb,var(--color-primary)_35%,transparent)]"
                                            style="height: {{ max(6, (int) round(($day['created'] / $activityMax) * 100)) }}%;"
                                            title="{{ __('dashboard.created') }}: {{ $day['created'] }}"
                                        ></div>
                                        <div
                                            class="w-[48%] min-h-[6px] rounded-[0.38rem_0.38rem_0.18rem_0.18rem] bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_58%,#3b82f6_42%),color-mix(in_srgb,#3b82f6_62%,var(--color-border)))] shadow-[0_0_10px_color-mix(in_srgb,#3b82f6_35%,transparent)]"
                                            style="height: {{ max(6, (int) round(($day['completed'] / $activityMax) * 100)) }}%;"
                                            title="{{ __('dashboard.completed') }}: {{ $day['completed'] }}"
                                        ></div>
                                    </div>
                                    <p class="mt-1 text-center text-[0.64rem] whitespace-nowrap text-muted-foreground">{{ $day['label'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground">{{ __('dashboard.no_activity') }}</p>
                @endforelse
            </div>

            <div class="hidden grid-cols-[2.2rem_1fr] items-end gap-2 lg:grid">
                <div class="flex h-[7.6rem] flex-col justify-between pr-1 text-right text-[0.66rem] text-muted-foreground">
                    <span>{{ $activityMax }}</span>
                    <span aria-hidden="{{ $activityMidLabel === null ? 'true' : 'false' }}" class="{{ $activityMidLabel === null ? 'opacity-0' : '' }}">
                        {{ $activityMidLabel ?? '-' }}
                    </span>
                    <span aria-hidden="true" class="opacity-0">0</span>
                </div>
                <div class="grid grid-cols-14 gap-2">
                    @forelse($activity as $day)
                        <div class="min-w-0">
                            <div class="flex h-[7.6rem] items-end justify-center gap-[0.16rem] rounded-[0.55rem] bg-[color-mix(in_srgb,var(--color-input)_18%,transparent)] px-1 py-2">
                                <div
                                    class="w-[48%] min-h-[6px] rounded-[0.38rem_0.38rem_0.18rem_0.18rem] bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_75%,white_10%),color-mix(in_srgb,var(--color-primary)_52%,var(--color-border)))] shadow-[0_0_10px_color-mix(in_srgb,var(--color-primary)_35%,transparent)]"
                                    style="height: {{ max(6, (int) round(($day['created'] / $activityMax) * 100)) }}%;"
                                    title="{{ __('dashboard.created') }}: {{ $day['created'] }}"
                                ></div>
                                <div
                                    class="w-[48%] min-h-[6px] rounded-[0.38rem_0.38rem_0.18rem_0.18rem] bg-[linear-gradient(180deg,color-mix(in_srgb,var(--color-primary)_58%,#3b82f6_42%),color-mix(in_srgb,#3b82f6_62%,var(--color-border)))] shadow-[0_0_10px_color-mix(in_srgb,#3b82f6_35%,transparent)]"
                                    style="height: {{ max(6, (int) round(($day['completed'] / $activityMax) * 100)) }}%;"
                                    title="{{ __('dashboard.completed') }}: {{ $day['completed'] }}"
                                ></div>
                            </div>
                            <p class="mt-1 text-center text-[0.64rem] whitespace-nowrap text-muted-foreground">{{ $day['label'] }}</p>
                        </div>
                    @empty
                        <p class="text-sm text-muted-foreground">{{ __('dashboard.no_activity') }}</p>
                    @endforelse
                </div>
            </div>
        </article>

        <article class="{{ $panel }}">
            <header class="mb-3">
                <h2 class="text-base font-bold text-foreground">{{ __('dashboard.status_distribution') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('dashboard.status_distribution_sub') }}</p>
            </header>

            @php
                $statusRows = [
                    ['label' => __('task.status_pending'), 'value' => $pendingTasks],
                    ['label' => __('task.status_in_progress'), 'value' => $inProgressTasks],
                    ['label' => __('task.status_completed'), 'value' => $completedTasks],
                ];
                $statusMax = max(1, $pendingTasks, $inProgressTasks, $completedTasks);
            @endphp

            <div class="space-y-4">
                @foreach($statusRows as $status)
                    <div>
                        <div class="mb-1 flex items-center justify-between text-sm">
                            <span class="text-foreground/90">{{ $status['label'] }}</span>
                            <span class="font-semibold text-foreground">{{ $status['value'] }}</span>
                        </div>
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-[color-mix(in_srgb,var(--color-input)_48%,transparent)]">
                            <div
                                class="h-full rounded-full bg-[linear-gradient(90deg,color-mix(in_srgb,var(--color-primary)_82%,white_6%),color-mix(in_srgb,var(--color-primary)_56%,var(--color-border)))] shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_42%,transparent)]"
                                style="width: {{ (int) round(($status['value'] / $statusMax) * 100) }}%;"
                            ></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </article>
    </section>

    <section class="mt-8 grid gap-6 lg:grid-cols-2">
        <article class="{{ $panel }} flex h-full flex-col">
            <header class="mb-3">
                <h2 class="text-base font-bold text-foreground">{{ __('dashboard.steps_progress') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('dashboard.steps_progress_sub') }}</p>
            </header>

            <div class="space-y-4">
                <div class="flex items-end justify-between">
                    <div>
                        <p class="text-[0.74rem] uppercase tracking-[0.04em] text-muted-foreground">{{ __('dashboard.completed') }}</p>
                        <p class="text-3xl font-bold text-foreground">{{ $completedSteps }} / {{ $totalSteps }}</p>
                    </div>
                    <p class="text-xl font-semibold text-primary">{{ $stepCompletionRate }}%</p>
                </div>

                <div class="h-2.5 w-full overflow-hidden rounded-full bg-[color-mix(in_srgb,var(--color-input)_48%,transparent)]">
                    <div
                        class="h-full rounded-full bg-[linear-gradient(90deg,color-mix(in_srgb,var(--color-primary)_82%,white_6%),color-mix(in_srgb,var(--color-primary)_56%,var(--color-border)))] shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_42%,transparent)]"
                        style="width: {{ $stepCompletionRate }}%;"
                    ></div>
                </div>
            </div>
        </article>

        <article class="{{ $panel }} flex h-full flex-col">
            <header class="mb-3">
                <h2 class="text-base font-bold text-foreground">{{ __('dashboard.top_tags') }}</h2>
                <p class="mt-1 text-sm text-muted-foreground">{{ __('dashboard.top_tags_sub') }}</p>
            </header>

            <div class="mt-2 flex flex-wrap gap-2">
                @forelse($topTags as $tag => $count)
                    <span class="inline-flex items-center gap-1 rounded-full border border-border/85 bg-card/92 px-3 py-1 text-xs text-foreground shadow-[0_0_10px_color-mix(in_srgb,var(--color-primary)_14%,transparent)]">#{{ $tag }} <strong>{{ $count }}</strong></span>
                @empty
                    <p class="text-sm text-muted-foreground">{{ __('dashboard.no_tags') }}</p>
                @endforelse
            </div>
            <p class=" pt-7 text-xs text-muted-foreground">{{ __('dashboard.top_tags_hint') }}</p>
        </article>
    </section>
</x-layout>