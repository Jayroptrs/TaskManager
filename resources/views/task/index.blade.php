<x-layout>
    @php
        $baseFilters = [
            'q' => request('q'),
            'sort' => request('sort'),
            'tag' => request('tag'),
            'work' => request('work'),
            'due' => request('due'),
            'view' => request('view'),
            'month' => request('month'),
        ];
        $sortOptions = [
            ['value' => 'newest', 'label' => __('task.sort_newest')],
            ['value' => 'oldest', 'label' => __('task.sort_oldest')],
            ['value' => 'deadline_soon', 'label' => __('task.sort_deadline_soon')],
            ['value' => 'deadline_late', 'label' => __('task.sort_deadline_late')],
            ['value' => 'priority_high', 'label' => __('task.sort_priority_high')],
        ];
        $selectedSort = $selectedSort ?? request('sort', 'newest');
        $selectedTag = $selectedTag ?? request('tag', '');
        $selectedWork = $selectedWork ?? request('work', 'all');
        $isArchivePage = $isArchivePage ?? false;
        $taskIndexRouteName = $taskIndexRouteName ?? 'task.index';
        $selectedView = $selectedView ?? match (request('view', 'list')) {
            'board', 'bord' => 'board',
            'calendar', 'kalender' => 'calendar',
            default => 'list',
        };
        $pageTitle = $pageTitle ?? __('task.page_title');
        $pageSubtitle = $pageSubtitle ?? __('task.page_subtitle');
        $listViewUrl = route($taskIndexRouteName, array_filter([...request()->except('page'), 'view' => 'list']));
        $boardViewUrl = route($taskIndexRouteName, array_filter([...request()->except(['page', 'status']), 'view' => 'board']));
        $calendarViewUrl = route($taskIndexRouteName, array_filter([...request()->except(['page', 'status']), 'view' => 'calendar']));
    @endphp

    <div
        x-data="{
            filtersOpen: false,
            desktopQuery: null,
            init() {
                this.desktopQuery = window.matchMedia('(min-width: 1024px)');
                this.filtersOpen = this.desktopQuery.matches;

                const syncWithViewport = (event) => {
                    this.filtersOpen = event.matches;
                };

                if (typeof this.desktopQuery.addEventListener === 'function') {
                    this.desktopQuery.addEventListener('change', syncWithViewport);
                } else if (typeof this.desktopQuery.addListener === 'function') {
                    this.desktopQuery.addListener(syncWithViewport);
                }
            },
            toggleFilters() {
                this.filtersOpen = !this.filtersOpen;
            }
        }"
        class="page-shell px-4 sm:px-6 lg:px-8"
    >
        <section class="surface-card rounded-2xl bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_95%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-3 sm:p-4">
            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-5">
                <div>
                    <h1 class="page-title">{{ $pageTitle }}</h1>
                    <p class="page-subtitle">{{ $pageSubtitle }}</p>
                </div>

                <div class="flex w-full flex-wrap items-center justify-start gap-2 sm:mt-0 sm:w-auto sm:justify-end">
                    @if (! $isArchivePage)
                        <button
                            x-data
                            @click="$dispatch('open-modal', 'create-task')"
                            type="button"
                            class="btn h-9 px-4 text-sm"
                        >
                            + {{ __('task.new_task') }}
                        </button>
                    @endif

                    <a
                        href="{{ $isArchivePage ? route('task.index') : route('task.archived') }}"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-full border border-border/80 bg-card/75 px-4 text-sm font-semibold text-foreground transition-[border-color,background-color,box-shadow,color] duration-200 hover:border-primary/60 hover:bg-card hover:text-foreground hover:shadow-[0_0_18px_color-mix(in_srgb,var(--color-primary)_22%,transparent)]"
                    >
                        @if ($isArchivePage)
                            <svg class="size-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M6.5 3.5 2 8m0 0 4.5 4.5M2 8h12" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                            </svg>
                        @else
                            <svg class="size-3.5" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                <path d="M3.5 5.5h9m-8 0 .7-1.6A1 1 0 0 1 6.1 3h3.8a1 1 0 0 1 .9.9l.7 1.6m-8 0v6.3c0 .7.5 1.2 1.2 1.2h6.6c.7 0 1.2-.5 1.2-1.2V5.5m-6 2.3h3.2" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"/>
                            </svg>
                        @endif
                        <span>{{ $isArchivePage ? __('task.back_to_tasks') : __('task.open_archive') }}</span>
                    </a>

                    @if (! $isArchivePage)
                        <div
                            x-data="{ active: @js($selectedView) }"
                            class="ml-0 w-full sm:ml-1 sm:w-auto"
                        >
                            <div class="relative grid w-full grid-cols-3 items-center rounded-full bg-card/75 p-1 shadow-[0_10px_24px_rgba(0,0,0,0.12)] sm:w-auto">
                                <span
                                    class="pointer-events-none absolute top-1 bottom-1 left-1 w-[calc((100%-0.5rem)/3)] rounded-full bg-primary shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_45%,transparent)] transition-transform duration-200 ease-out will-change-transform"
                                    style="transform: translateX({{ $selectedView === 'board' ? '100%' : ($selectedView === 'calendar' ? '200%' : '0%') }});"
                                    x-bind:style="`transform: translateX(${({ list: '0%', board: '100%', calendar: '200%' })[active] ?? '0%'});`"
                                ></span>

                                <a
                                    href="{{ $listViewUrl }}"
                                    @click.prevent="
                                        if (active === 'list') return;
                                        active = 'list';
                                        setTimeout(() => { window.location.href = '{{ $listViewUrl }}'; }, 110);
                                    "
                                    class="no-link-hover relative z-10 min-w-0 flex-1 rounded-full px-2.5 py-1.5 text-center text-xs font-semibold transition-colors duration-150 sm:flex-none sm:px-3"
                                    :class="active === 'list' ? 'text-primary-foreground hover:text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                                >
                                    {{ __('task.view_list') }}
                                </a>
                                <a
                                    href="{{ $boardViewUrl }}"
                                    @click.prevent="
                                        if (active === 'board') return;
                                        active = 'board';
                                        setTimeout(() => { window.location.href = '{{ $boardViewUrl }}'; }, 110);
                                    "
                                    class="no-link-hover relative z-10 min-w-0 flex-1 rounded-full px-2.5 py-1.5 text-center text-xs font-semibold transition-colors duration-150 sm:flex-none sm:px-3"
                                    :class="active === 'board' ? 'text-primary-foreground hover:text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                                >
                                    {{ __('task.view_board') }}
                                </a>
                                <a
                                    href="{{ $calendarViewUrl }}"
                                    @click.prevent="
                                        if (active === 'calendar') return;
                                        active = 'calendar';
                                        setTimeout(() => { window.location.href = '{{ $calendarViewUrl }}'; }, 110);
                                    "
                                    class="no-link-hover relative z-10 min-w-0 flex-1 rounded-full px-2.5 py-1.5 text-center text-xs font-semibold transition-colors duration-150 sm:flex-none sm:px-3"
                                    :class="active === 'calendar' ? 'text-primary-foreground hover:text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                                >
                                    {{ __('task.view_calendar') }}
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <section class="mt-4 grid grid-cols-1 items-start gap-4 lg:grid-cols-[auto_minmax(0,1fr)]">
            <aside
                class="rounded-2xl border border-border/80 bg-card/90 shadow-[0_10px_24px_color-mix(in_srgb,black_10%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_10%,transparent)] transition-[width,padding,box-shadow,border-color] duration-200 ease-out"
                :class="filtersOpen ? 'overflow-visible w-full p-3 lg:w-72' : 'overflow-hidden w-full p-2 lg:w-28'"
            >
                <div
                    class="flex items-center justify-between"
                    :class="filtersOpen ? 'mb-2 border-b border-border/70 pb-2' : 'mb-0 border-b-0 pb-0'"
                >
                    <h2 class="text-sm font-semibold tracking-wide text-foreground">{{ __('task.filters') }}</h2>
                    <button
                        type="button"
                        @click="toggleFilters()"
                        :aria-label="filtersOpen ? @js(__('task.filters_collapse')) : @js(__('task.filters_expand'))"
                        class="inline-flex size-6 items-center justify-center rounded-full bg-transparent text-muted-foreground transition-colors duration-200 hover:text-primary hover:[text-shadow:0_0_12px_color-mix(in_srgb,var(--color-primary)_34%,transparent)]"
                    >
                        <span class="inline-block leading-none transition-transform duration-200 ease-out" :class="filtersOpen ? '-translate-x-px rotate-180' : 'translate-x-0 rotate-0'">&#10095;</span>
                    </button>
                </div>

                <div x-show="filtersOpen" x-transition >
                    <form method="GET" action="{{ route($taskIndexRouteName) }}" class="space-y-4" x-ref="filtersForm">
                        @if ($selectedView !== 'board' && request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        @if (! $isArchivePage)
                            <input type="hidden" name="view" value="{{ in_array($selectedView, ['board', 'calendar'], true) ? $selectedView : 'list' }}">
                        @endif
                        <input type="hidden" name="save_last_filter" value="1">

                        <div class="rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.search') }}</p>
                            <x-form.input
                                type="search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="{{ __('task.search_placeholder') }}"
                                class="w-full"
                            />
                        </div>

                        <div class="rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.sort') }}</p>
                            <div
                                class="relative"
                                x-data="{
                                    open: false,
                                    value: @js($selectedSort),
                                    options: @js($sortOptions),
                                }"
                                @keydown.escape.window="open = false"
                            >
                                <input type="hidden" name="sort" x-model="value">
                                <button type="button" @click="open = !open" class="input input-neon-select w-full text-left">
                                    <span x-text="options.find(option => option.value === value)?.label ?? @js(__('task.sort_newest'))"></span>
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
                        </div>

                        <div class="rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.tags') }}</p>
                            <div
                                class="relative"
                                x-data="{
                                    open: false,
                                    value: @js($selectedTag),
                                    options: @js($availableTags->values()),
                                    search: '',
                                    filteredOptions() {
                                        const term = String(this.search ?? '').trim().toLowerCase();
                                        if (term === '') return this.options;
                                        return this.options.filter((tag) => String(tag).toLowerCase().includes(term));
                                    },
                                }"
                                @keydown.escape.window="open = false"
                            >
                                <input type="hidden" name="tag" x-model="value">
                                <button
                                    type="button"
                                    @click="
                                        open = !open;
                                        if (open) {
                                            $nextTick(() => $refs.tagSearch?.focus());
                                        } else {
                                            search = '';
                                        }
                                    "
                                    class="input input-neon-select w-full text-left"
                                >
                                    <span x-text="value ? `#${value}` : @js(__('task.all_tags'))"></span>
                                </button>

                                <div x-show="open" @click.outside="open = false; search = ''" x-transition class="dropdown-panel">
                                    <div class="px-1 pb-1">
                                        <input
                                            x-ref="tagSearch"
                                            type="text"
                                            x-model="search"
                                            class="input h-9 text-sm"
                                            placeholder="{{ __('task.search_tags_placeholder') }}"
                                            @keydown.stop
                                        >
                                    </div>

                                    <div class="max-h-56 space-y-0.5 overflow-y-auto pr-1">
                                        <button
                                            type="button"
                                            class="dropdown-item"
                                            :class="{ 'is-active': value === '' }"
                                            @click="value = ''; open = false; search = ''"
                                        >
                                            {{ __('task.all_tags') }}
                                        </button>

                                        <template x-for="tag in filteredOptions()" :key="tag">
                                            <button
                                                type="button"
                                                class="dropdown-item"
                                                :class="{ 'is-active': value === tag }"
                                                @click="value = tag; open = false; search = ''"
                                                x-text="`#${tag}`"
                                            ></button>
                                        </template>

                                        <p
                                            x-show="filteredOptions().length === 0"
                                            class="px-3 py-2 text-xs text-muted-foreground"
                                        >
                                            {{ __('task.no_tags_match') }}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.work_scope') }}</p>
                            <div
                                class="relative"
                                x-data="{
                                    open: false,
                                    value: @js($selectedWork),
                                    options: [
                                        { value: 'all', label: @js(__('task.work_all')) },
                                        { value: 'solo', label: @js(__('task.work_solo')) },
                                        { value: 'team', label: @js(__('task.work_team')) },
                                    ]
                                }"
                                @keydown.escape.window="open = false"
                            >
                                <input type="hidden" name="work" x-model="value">
                                <button type="button" @click="open = !open" class="input input-neon-select w-full text-left">
                                    <span x-text="options.find(option => option.value === value)?.label ?? @js(__('task.work_all'))"></span>
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
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="btn h-10 px-4">{{ __('task.filter') }}</button>
                            <a href="{{ route('task.index') }}" class="inline-flex h-10 items-center px-2 text-sm text-muted-foreground transition-colors duration-200 hover:text-primary no-link-hover">
                                {{ __('task.reset') }}
                            </a>
                            <a
                                href="{{ $lastFilterUrl ?? '#' }}"
                                class="ml-auto inline-flex h-10 items-center rounded-lg border border-border/70 bg-card/45 px-3 text-sm text-muted-foreground transition-all duration-200 hover:border-primary/35 hover:text-foreground hover:shadow-[0_0_12px_color-mix(in_srgb,var(--color-primary)_18%,transparent)] {{ $lastFilterUrl ? '' : 'pointer-events-none opacity-50' }}"
                                aria-disabled="{{ $lastFilterUrl ? 'false' : 'true' }}"
                                data-test="apply-last-filter"
                            >
                                {{ __('task.apply_last_filter') }}
                            </a>
                        </div>
                    </form>

                    @if ($selectedView !== 'board')
                        <div class="mt-4 rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.status') }}</p>
                            <div class="space-y-2">
                            <a href="{{ route($taskIndexRouteName, array_filter($baseFilters)) }}" class="btn {{ request()->has('status') ? 'btn-outlined' : '' }} w-full text-left">{{ __('task.all_statuses') }}</a>
                            @foreach (App\TaskStatus::cases() as $status)
                                <a
                                    href="{{ route($taskIndexRouteName, array_filter([...$baseFilters, 'status' => $status->value])) }}"
                                    class="btn {{ request('status') === $status->value ? '' : 'btn-outlined' }} w-full text-left"
                                >
                                    {{ $status->label() }} <span class="text-xs pl-3">{{ $statusCounts->get($status->value) }}</span>
                                </a>
                            @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            </aside>

            <div class="min-w-0 text-muted-foreground">
                @if (! $isArchivePage && $selectedView === 'board')
                    <div class="grid gap-5 lg:grid-cols-3" data-kanban-board data-csrf="{{ csrf_token() }}">
                        @foreach (App\TaskStatus::cases() as $status)
                            <section class="kanban-column" data-status="{{ $status->value }}">
                                <header class="kanban-column-header">
                                    <div>
                                        <h3 class="text-sm font-semibold tracking-wide text-foreground">{{ $status->label() }}</h3>
                                        <p class="kanban-target-hint mt-1 min-h-[1rem] text-[11px] font-medium text-primary opacity-0 transition-opacity duration-150" data-kanban-hint>
                                            {{ __('task.move_to_prefix') }} {{ $status->label() }}
                                        </p>
                                    </div>
                                    <span class="kanban-count">{{ $tasks->where('status', $status)->count() }}</span>
                                </header>

                                <div class="kanban-dropzone">
                                    @forelse($tasks->where('status', $status) as $task)
                                        <article
                                            class="kanban-card"
                                            draggable="true"
                                            data-task-id="{{ $task->id }}"
                                            data-update-url="{{ route('task.status.update', $task) }}"
                                        >
                                            <a href="{{ route('task.show', $task) }}" class="block" draggable="false">
                                                <h4 class="font-semibold text-foreground leading-snug">{{ $task->title }}</h4>
                                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                                    <x-task.priority-label :priority="$task->priority?->value ?? 'medium'">
                                                        {{ $task->priority?->label() ?? __('task.priority_medium') }}
                                                    </x-task.priority-label>
                                                    @if ($task->isArchived())
                                                        <span class="inline-flex rounded-full border border-border/80 px-2 py-0.5 text-[11px] text-muted-foreground">{{ __('task.archived_badge') }}</span>
                                                    @endif
                                                    @if ($task->due_date)
                                                        <p class="text-[11px] text-muted-foreground">{{ __('task.due_date_short', ['date' => $task->due_date->translatedFormat('j M Y')]) }}</p>
                                                    @endif
                                                </div>

                                                @if (!empty($task->tags) && count($task->tags))
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        @foreach (array_slice($task->tags, 0, 3) as $tag)
                                                            <span class="inline-flex rounded-full border border-border/80 px-2 py-0.5 text-[11px] text-foreground/80">#{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <p class="mt-2 line-clamp-3 text-xs text-muted-foreground">
                                                    {{ $task->description ?: __('task.no_description') }}
                                                </p>
                                            </a>
                                        </article>
                                    @empty
                                        <div class="kanban-empty">{{ __('task.no_tasks_found') }}</div>
                                    @endforelse
                                </div>
                            </section>
                        @endforeach
                    </div>
                @elseif (! $isArchivePage && $selectedView === 'calendar')
                    @php
                        $calendarMonth = $calendarMonth ?? now()->startOfMonth();
                        $tasksByDueDate = $tasksByDueDate ?? collect();
                        $calendarDays = $calendarDays ?? collect();
                        $calendarMonthTaskCount = $calendarMonthTaskCount ?? 0;
                        $monthBase = request()->except(['page', 'month']);
                        $previousMonthUrl = route('task.index', array_filter([...$monthBase, 'view' => 'calendar', 'month' => $calendarMonth->copy()->subMonth()->format('Y-m')]));
                        $nextMonthUrl = route('task.index', array_filter([...$monthBase, 'view' => 'calendar', 'month' => $calendarMonth->copy()->addMonth()->format('Y-m')]));
                        $currentMonthUrl = route('task.index', array_filter([...$monthBase, 'view' => 'calendar', 'month' => now()->startOfMonth()->format('Y-m')]));
                        $weekdays = collect(range(0, 6))->map(fn ($dayOffset) => $calendarMonth->copy()->startOfWeek(\Carbon\CarbonInterface::MONDAY)->addDays($dayOffset));
                    @endphp

                    <section class="surface-card rounded-2xl p-3 sm:p-4">
                        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-border/70 pb-3">
                            <div>
                                <h3 class="text-lg font-bold text-foreground">{{ $calendarMonth->translatedFormat('F Y') }}</h3>
                                <p class="text-xs text-muted-foreground">
                                    {{ __('task.calendar_due_in_month', ['count' => $calendarMonthTaskCount]) }}
                                </p>
                            </div>

                            <div class="grid w-full grid-cols-3 gap-2 sm:flex sm:w-auto sm:flex-wrap sm:items-center sm:justify-end">
                                <a href="{{ $previousMonthUrl }}" class="btn btn-outlined inline-flex h-8 items-center justify-center px-2 text-center text-xs sm:flex-none sm:px-3">
                                    <span class="sm:hidden">&larr;</span>
                                    <span class="hidden sm:inline">{{ __('task.calendar_previous_month') }}</span>
                                </a>
                                <a href="{{ $currentMonthUrl }}" class="btn btn-outlined inline-flex h-8 items-center justify-center px-2 text-center text-xs sm:flex-none sm:px-3">
                                    <span class="sm:hidden">{{ now()->format('j M') }}</span>
                                    <span class="hidden sm:inline">{{ __('task.calendar_today') }}</span>
                                </a>
                                <a href="{{ $nextMonthUrl }}" class="btn btn-outlined inline-flex h-8 items-center justify-center px-2 text-center text-xs sm:flex-none sm:px-3">
                                    <span class="sm:hidden">&rarr;</span>
                                    <span class="hidden sm:inline">{{ __('task.calendar_next_month') }}</span>
                                </a>
                            </div>
                        </header>

                        @if ($calendarMonthTaskCount === 0)
                            <div class="empty-state mt-3">
                                <p class="empty-state-title">{{ __('task.calendar_empty_title') }}</p>
                                <p class="empty-state-copy">{{ __('task.calendar_empty_copy') }}</p>
                            </div>
                        @endif

                        <div class="mt-3">
                            <div class="grid grid-cols-7 gap-1.5 sm:gap-2">
                                @foreach ($weekdays as $weekday)
                                    <div class="rounded-lg border border-border/70 bg-card/70 py-1 text-center text-[10px] font-semibold uppercase tracking-[0.08em] text-muted-foreground sm:py-1.5 sm:text-[11px]">
                                        <span class="sm:hidden">{{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($weekday->translatedFormat('dd'), 0, 2)) }}</span>
                                        <span class="hidden sm:inline">{{ $weekday->translatedFormat('D') }}</span>
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-2 grid grid-cols-7 gap-1.5 sm:gap-2">
                                @foreach ($calendarDays as $day)
                                    @php
                                        $dayTasks = $tasksByDueDate->get($day->toDateString(), collect());
                                        $isCurrentMonth = $day->isSameMonth($calendarMonth);
                                        $isToday = $day->isSameDay(now());
                                    @endphp
                                    <div class="group relative min-h-24 overflow-hidden rounded-xl border p-1.5 transition-colors duration-150 sm:min-h-32 sm:p-2 {{ $isCurrentMonth ? 'border-border/80 bg-card/88' : 'border-border/50 bg-card/55' }} {{ $isToday ? 'shadow-[0_0_0_1px_color-mix(in_srgb,var(--color-primary)_55%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_20%,transparent)]' : '' }}">
                                        <button
                                            type="button"
                                            class="btn btn-outlined absolute right-1 top-1 z-10 inline-flex h-5 w-5 items-center justify-center rounded-md px-0 text-xs opacity-100 transition-all duration-200 sm:right-1.5 sm:top-1.5 sm:h-6 sm:w-6 sm:text-sm sm:opacity-0 sm:group-hover:opacity-100 sm:group-focus-within:opacity-100"
                                            aria-label="{{ __('task.calendar_add_task') }}"
                                            title="{{ __('task.calendar_add_task') }}"
                                            @click="
                                                window.dispatchEvent(new CustomEvent('task-create-due-date-selected', { detail: { dueDate: '{{ $day->toDateString() }}' } }));
                                                $dispatch('open-modal', 'create-task');
                                            "
                                        >
                                            +
                                        </button>
                                        <div class="flex items-center justify-between gap-1">
                                            <span class="text-[11px] font-semibold sm:text-xs {{ $isCurrentMonth ? 'text-foreground/90' : 'text-muted-foreground/70' }}">{{ $day->day }}</span>
                                            @if ($dayTasks->count() > 0)
                                                <span class="inline-flex min-w-5 items-center justify-center rounded-full bg-primary/85 px-1.5 py-0.5 text-[10px] font-semibold text-primary-foreground">{{ $dayTasks->count() }}</span>
                                            @endif
                                        </div>

                                        <div class="mt-1 space-y-1 sm:hidden">
                                            @if ($dayTasks->count() > 0)
                                                <p class="truncate rounded-md border border-border/60 bg-card/80 px-1.5 py-0.5 text-[10px] font-medium text-foreground/85">
                                                    {{ $dayTasks->first()->title }}
                                                </p>

                                                @if ($dayTasks->count() > 1)
                                                    <p class="px-0.5 text-[10px] font-medium text-muted-foreground">
                                                        {{ __('task.calendar_more_tasks', ['count' => $dayTasks->count() - 1]) }}
                                                    </p>
                                                @endif
                                            @endif
                                        </div>

                                        <div class="mt-2 hidden space-y-1.5 sm:block">
                                            @foreach ($dayTasks->take(3) as $task)
                                                <a
                                                    href="{{ route('task.show', $task) }}"
                                                    class="no-link-hover block rounded-md border border-border/70 bg-card/80 px-1.5 py-1 text-[10px] font-medium text-foreground/85 transition-colors duration-150 hover:border-primary/55 hover:bg-card sm:px-2 sm:text-[11px]"
                                                    title="{{ $task->title }}"
                                                >
                                                    <span class="line-clamp-2">{{ $task->title }}</span>
                                                </a>
                                            @endforeach

                                            @if ($dayTasks->count() > 3)
                                                <p class="px-1 text-[11px] font-medium text-muted-foreground">
                                                    {{ __('task.calendar_more_tasks', ['count' => $dayTasks->count() - 3]) }}
                                                </p>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @else
                    <div class="grid md:grid-cols-2 gap-6">
                        @forelse($tasks as $task)
                            <x-card href="{{ route('task.show', $task) }}" class="flex h-full flex-col">
                                @if ($task->imageUrl())
                                    <div class="mb-4 -mx-4 -mt-4 h-60 sm:h-64 rounded-t-lg overflow-hidden">
                                        <img src="{{ $task->imageUrl() }}" alt="" class="h-full w-full object-cover object-center">
                                    </div>
                                @endif

                                <div class="flex flex-1 flex-col">
                                    <div class="flex items-start justify-between gap-3">
                                        <h3 class="text-lg font-semibold leading-tight text-foreground">{{ $task->title }}</h3>
                                        <span class="shrink-0 pt-0.5 text-[11px] font-medium uppercase tracking-[0.08em] text-muted-foreground">
                                            {{ $task->created_at->diffForHumans() }}
                                        </span>
                                    </div>

                                    <div class="mt-3 flex flex-wrap items-center gap-2">
                                        <x-task.status-label :status="$task->status->value">
                                            {{ $task->status->label() }}
                                        </x-task.status-label>
                                        <x-task.priority-label :priority="$task->priority?->value ?? 'medium'">
                                            {{ $task->priority?->label() ?? __('task.priority_medium') }}
                                        </x-task.priority-label>
                                        @if ($task->isArchived())
                                            <span class="inline-flex rounded-full border border-border px-2 py-1 text-xs text-muted-foreground">{{ __('task.archived_badge') }}</span>
                                        @endif
                                    </div>

                                    @if (!empty($task->description))
                                        <div class="mt-4 rounded-xl border border-border/65 bg-card/55 px-3 py-2.5 text-sm leading-relaxed text-foreground/90 shadow-[inset_0_1px_0_color-mix(in_srgb,white_18%,transparent)]">
                                            <p class="line-clamp-3">{{ $task->description }}</p>
                                        </div>
                                    @endif

                                    <div class="mt-auto space-y-3 pt-4">
                                        @if ($task->due_date)
                                            <div class="inline-flex items-center gap-2 text-xs text-muted-foreground">
                                                <span class="inline-block h-2 w-2 rounded-full bg-primary/80 shadow-[0_0_10px_color-mix(in_srgb,var(--color-primary)_36%,transparent)]"></span>
                                                <span>{{ __('task.due_date_short', ['date' => $task->due_date->translatedFormat('j M Y')]) }}</span>
                                            </div>
                                        @endif

                                        @if (!empty($task->tags) && count($task->tags))
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($task->tags as $tag)
                                                    <span class="inline-flex items-center rounded-full border border-border px-2.5 py-1 text-[11px] font-medium text-foreground/80">#{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </x-card>
                        @empty
                            <div class="empty-state md:col-span-2">
                                <p class="empty-state-title">{{ __('task.no_tasks_found') }}</p>
                                <p class="empty-state-copy">{{ $pageSubtitle }}</p>
                                @unless ($isArchivePage)
                                    <button
                                        x-data
                                        @click="$dispatch('open-modal', 'create-task')"
                                        type="button"
                                        class="btn mt-3 h-9 px-4 text-sm"
                                    >
                                        + {{ __('task.new_task') }}
                                    </button>
                                @endunless
                            </div>
                        @endforelse
                    </div>
                @endif
            </div>
        </section>

        <x-task.modal />
    </div>

    @if ($selectedView === 'board')
        <script>
            (() => {
                const board = document.querySelector('[data-kanban-board]');
                if (!board) return;

                let draggedCard = null;
                let dragFollower = null;
                let followerX = 0;
                let followerY = 0;
                let followerFrame = null;
                let followerOffsetX = 0;
                let followerOffsetY = 0;
                const csrf = board.dataset.csrf;
                const transparentDragImage = new Image();
                transparentDragImage.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

                const renderFollower = () => {
                    if (!dragFollower) {
                        followerFrame = null;
                        return;
                    }

                    dragFollower.style.transform = `translate3d(${followerX - followerOffsetX}px, ${followerY - followerOffsetY}px, 0) rotate(-1.8deg)`;
                    followerFrame = requestAnimationFrame(renderFollower);
                };

                const updateFollowerPosition = (event) => {
                    if (!dragFollower) return;
                    if (!event.clientX && !event.clientY) return;

                    followerX = event.clientX;
                    followerY = event.clientY;

                    if (!followerFrame) {
                        followerFrame = requestAnimationFrame(renderFollower);
                    }
                };

                const cleanupFollower = () => {
                    window.removeEventListener('dragover', updateFollowerPosition);

                    if (followerFrame) {
                        cancelAnimationFrame(followerFrame);
                        followerFrame = null;
                    }

                    if (dragFollower) {
                        dragFollower.remove();
                        dragFollower = null;
                    }
                };

                const refreshColumnState = (column) => {
                    if (!column) return;

                    const cards = column.querySelectorAll('.kanban-card').length;
                    const count = column.querySelector('.kanban-count');
                    if (count) {
                        count.textContent = cards;
                    }

                    const dropzone = column.querySelector('.kanban-dropzone');
                    if (!dropzone) return;

                    const empty = dropzone.querySelector('.kanban-empty');
                    if (cards === 0 && !empty) {
                        const placeholder = document.createElement('div');
                        placeholder.className = 'kanban-empty';
                        placeholder.textContent = @js(__('task.empty_column'));
                        dropzone.appendChild(placeholder);
                    }

                    if (cards > 0 && empty) {
                        empty.remove();
                    }
                };

                const setColumnHint = (column, visible) => {
                    if (!column) return;

                    const hint = column.querySelector('[data-kanban-hint]');
                    if (!hint) return;

                    hint.classList.toggle('opacity-0', !visible);
                };

                let activeDropColumn = null;

                const clearDropIndicators = () => {
                    board.querySelectorAll('.kanban-column').forEach((column) => {
                        column.classList.remove('is-over');
                        setColumnHint(column, false);
                    });
                    activeDropColumn = null;
                };

                const setActiveDropColumn = (column) => {
                    if (!column || activeDropColumn === column) return;

                    board.querySelectorAll('.kanban-column').forEach((otherColumn) => {
                        const isActive = otherColumn === column;
                        otherColumn.classList.toggle('is-over', isActive);
                        setColumnHint(otherColumn, isActive);
                    });

                    activeDropColumn = column;
                };

                const resolveColumnFromPointer = (event) => {
                    const directColumn = event.target instanceof Element
                        ? event.target.closest('.kanban-column')
                        : null;
                    if (directColumn) {
                        return directColumn;
                    }

                    const columns = Array.from(board.querySelectorAll('.kanban-column'));
                    if (columns.length === 0) return null;

                    const pointerX = event.clientX;
                    let nearestColumn = null;
                    let smallestDistance = Number.POSITIVE_INFINITY;

                    columns.forEach((column) => {
                        const rect = column.getBoundingClientRect();
                        const distance = pointerX < rect.left
                            ? rect.left - pointerX
                            : pointerX > rect.right
                                ? pointerX - rect.right
                                : 0;

                        if (distance < smallestDistance) {
                            smallestDistance = distance;
                            nearestColumn = column;
                        }
                    });

                    return nearestColumn;
                };

                const dropCardToColumn = async (column) => {
                    if (!draggedCard || !column) return;

                    const zone = column.querySelector('.kanban-dropzone');
                    if (!zone) return;

                    const sourceColumn = draggedCard.closest('.kanban-column');
                    const sourceZone = sourceColumn?.querySelector('.kanban-dropzone');
                    const previousNextSibling = draggedCard.nextElementSibling;
                    const targetStatus = column.dataset.status;
                    const sourceStatus = sourceColumn?.dataset.status;

                    if (!targetStatus || targetStatus === sourceStatus) return;

                    zone.appendChild(draggedCard);
                    refreshColumnState(sourceColumn);
                    refreshColumnState(column);

                    try {
                        const response = await fetch(draggedCard.dataset.updateUrl, {
                            method: 'PATCH',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': csrf,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ status: targetStatus }),
                        });

                        if (!response.ok) {
                            throw new Error('Update failed');
                        }
                    } catch (error) {
                        if (sourceZone) {
                            if (previousNextSibling && previousNextSibling.parentElement === sourceZone) {
                                sourceZone.insertBefore(draggedCard, previousNextSibling);
                            } else {
                                sourceZone.appendChild(draggedCard);
                            }
                        }

                        refreshColumnState(sourceColumn);
                        refreshColumnState(column);
                    }
                };

                board.querySelectorAll('.kanban-card').forEach((card) => {
                    card.querySelectorAll('a, img, span, p, h4').forEach((node) => {
                        node.setAttribute('draggable', 'false');
                    });

                    card.addEventListener('dragstart', (event) => {
                        draggedCard = card;
                        card.classList.add('is-dragging');

                        if (!event.dataTransfer) {
                            return;
                        }

                        event.dataTransfer.effectAllowed = 'move';
                        event.dataTransfer.setData('text/plain', card.dataset.taskId ?? '');
                        event.dataTransfer.setDragImage(transparentDragImage, 0, 0);

                        dragFollower = card.cloneNode(true);
                        dragFollower.classList.add('drag-follower');
                        dragFollower.style.width = `${card.offsetWidth}px`;

                        const cardRect = card.getBoundingClientRect();
                        const hasPointerPosition = Number.isFinite(event.clientX) && Number.isFinite(event.clientY) && (event.clientX !== 0 || event.clientY !== 0);
                        followerOffsetX = hasPointerPosition ? Math.max(0, event.clientX - cardRect.left) : (cardRect.width / 2);
                        followerOffsetY = hasPointerPosition ? Math.max(0, event.clientY - cardRect.top) : (cardRect.height / 2);

                        followerX = event.clientX || 0;
                        followerY = event.clientY || 0;
                        dragFollower.style.transform = `translate3d(${followerX - followerOffsetX}px, ${followerY - followerOffsetY}px, 0) rotate(-1.8deg)`;
                        document.body.appendChild(dragFollower);
                        window.addEventListener('dragover', updateFollowerPosition);

                        if (!followerFrame) {
                            followerFrame = requestAnimationFrame(renderFollower);
                        }
                    });

                    card.addEventListener('dragend', () => {
                        card.classList.remove('is-dragging');
                        draggedCard = null;
                        clearDropIndicators();
                        cleanupFollower();
                    });
                });

                board.addEventListener('dragover', (event) => {
                    event.preventDefault();
                    if (!draggedCard) return;

                    const targetColumn = resolveColumnFromPointer(event);
                    if (targetColumn) {
                        setActiveDropColumn(targetColumn);
                    }
                });

                board.addEventListener('drop', async (event) => {
                    event.preventDefault();
                    const targetColumn = activeDropColumn || resolveColumnFromPointer(event);
                    clearDropIndicators();
                    await dropCardToColumn(targetColumn);
                });

                board.querySelectorAll('.kanban-column').forEach((column) => {
                    const zone = column.querySelector('.kanban-dropzone');
                    if (!zone) return;

                    column.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        if (!draggedCard) return;
                        setActiveDropColumn(column);
                    });

                    column.addEventListener('dragleave', (event) => {
                        if (event.relatedTarget && column.contains(event.relatedTarget)) return;
                        if (activeDropColumn === column) {
                            activeDropColumn = null;
                            column.classList.remove('is-over');
                            setColumnHint(column, false);
                        }
                    });

                    column.addEventListener('drop', async (event) => {
                        event.preventDefault();
                        event.stopPropagation();
                        clearDropIndicators();
                        await dropCardToColumn(column);
                    });
                });
            })();
        </script>
    @endif
</x-layout>


