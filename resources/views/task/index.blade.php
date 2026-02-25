<x-layout>
    @php
        $baseFilters = [
            'q' => request('q'),
            'sort' => request('sort'),
            'tag' => request('tag'),
            'view' => request('view'),
        ];
        $sortOptions = [
            ['value' => 'newest', 'label' => 'Nieuwste eerst'],
            ['value' => 'oldest', 'label' => 'Oudste eerst'],
        ];
        $selectedSort = request('sort', 'newest');
        $selectedTag = request('tag', '');
        $selectedView = $selectedView ?? (request('view', 'list') === 'board' || request('view') === 'bord' ? 'board' : 'list');
        $listViewUrl = route('task.index', array_filter([...request()->except('page'), 'view' => 'list']));
        $boardViewUrl = route('task.index', array_filter([...request()->except(['page', 'status']), 'view' => 'bord']));
    @endphp

    <div
        x-data="{
            filtersOpen: true,
            init() {
                const saved = localStorage.getItem('taskFiltersOpen');
                this.filtersOpen = saved !== null ? saved === '1' : window.innerWidth >= 1024;
            },
            toggleFilters() {
                this.filtersOpen = !this.filtersOpen;
                localStorage.setItem('taskFiltersOpen', this.filtersOpen ? '1' : '0');
            }
        }"
        class="mt-4"
    >
        <section class="rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_95%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-3 shadow-[0_10px_24px_color-mix(in_srgb,black_9%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_10%,transparent)]">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight">Taken</h1>
                    <p class="text-xs text-muted-foreground mt-0.5">Snel overzicht met inklapbare filters.</p>
                </div>

                <div class="flex flex-wrap items-center justify-end gap-2">
                    <button
                        x-data
                        @click="$dispatch('open-modal', 'create-task')"
                        type="button"
                        class="btn h-9 px-4 text-sm"
                    >
                        + Nieuwe taak
                    </button>

                    <div
                        x-data="{ active: @js($selectedView) }"
                        class="ml-1"
                    >
                        <div class="relative grid grid-cols-2 items-center rounded-full bg-card/75 p-1 shadow-[0_10px_24px_rgba(0,0,0,0.12)]">
                            <span
                                class="pointer-events-none absolute top-1 bottom-1 left-1 w-[calc(50%-0.25rem)] rounded-full bg-primary shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_45%,transparent)] transition-transform duration-200 ease-out will-change-transform"
                                style="transform: translateX({{ $selectedView === 'board' ? '100%' : '0%' }});"
                                x-bind:style="`transform: translateX(${active === 'list' ? '0%' : '100%'});`"
                            ></span>

                            <a
                                href="{{ $listViewUrl }}"
                                @click.prevent="
                                    if (active === 'list') return;
                                    active = 'list';
                                    setTimeout(() => { window.location.href = '{{ $listViewUrl }}'; }, 110);
                                "
                                class="no-link-hover relative z-10 min-w-18 rounded-full px-3 py-1.5 text-center text-xs font-semibold transition-colors duration-150"
                                :class="active === 'list' ? 'text-primary-foreground hover:text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                            >
                                Lijst
                            </a>
                            <a
                                href="{{ $boardViewUrl }}"
                                @click.prevent="
                                    if (active === 'board') return;
                                    active = 'board';
                                    setTimeout(() => { window.location.href = '{{ $boardViewUrl }}'; }, 110);
                                "
                                class="no-link-hover relative z-10 min-w-18 rounded-full px-3 py-1.5 text-center text-xs font-semibold transition-colors duration-150"
                                :class="active === 'board' ? 'text-primary-foreground hover:text-primary-foreground' : 'text-muted-foreground hover:text-foreground'"
                            >
                                Bord
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-4 grid grid-cols-1 items-start gap-4 lg:grid-cols-[auto_minmax(0,1fr)]">
            <aside
                class="overflow-hidden rounded-2xl border border-border/80 bg-card/90 shadow-[0_10px_24px_color-mix(in_srgb,black_10%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_10%,transparent)] transition-[width,padding,box-shadow,border-color] duration-200 ease-out"
                :class="filtersOpen ? 'w-full p-3 lg:w-72' : 'w-full p-2 lg:w-28'"
            >
                <div
                    class="flex items-center justify-between"
                    :class="filtersOpen ? 'mb-2 border-b border-border/70 pb-2' : 'mb-0 border-b-0 pb-0'"
                >
                    <h2 class="text-sm font-semibold tracking-wide text-foreground">Filters</h2>
                    <button
                        type="button"
                        @click="toggleFilters()"
                        :aria-label="filtersOpen ? 'Filters inklappen' : 'Filters uitklappen'"
                        class="inline-flex size-6 items-center justify-center rounded-full bg-transparent text-muted-foreground transition-colors duration-200 hover:text-primary hover:[text-shadow:0_0_12px_color-mix(in_srgb,var(--color-primary)_34%,transparent)]"
                    >
                        <span class="inline-block leading-none transition-transform duration-200 ease-out" :class="filtersOpen ? '-translate-x-px rotate-180' : 'translate-x-0 rotate-0'">&#10095;</span>
                    </button>
                </div>

                <div x-show="filtersOpen" x-transition >
                    <form method="GET" action="{{ route('task.index') }}" class="space-y-4">
                        @if ($selectedView !== 'board' && request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <input type="hidden" name="view" value="{{ $selectedView === 'board' ? 'bord' : 'list' }}">

                        <div class="rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">Zoeken</p>
                            <input
                                type="search"
                                name="q"
                                value="{{ request('q') }}"
                                placeholder="Zoek op titel of beschrijving.."
                                class="input w-full
                                focus:outline-none
                                ring-0
                                focus:ring-2 focus:ring-indigo-600
                                not-placeholder-shown:ring-2 not-placeholder-shown:ring-primary
                                not-placeholder-shown:focus:ring-indigo-600"
                            >
                        </div>

                        <div class="rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">Sorteren</p>
                            <div
                                class="relative"
                                x-data="{ open: false, value: @js($selectedSort), options: @js($sortOptions) }"
                                @keydown.escape.window="open = false"
                            >
                                <input type="hidden" name="sort" x-model="value">
                                <button type="button" @click="open = !open" class="input input-neon-select w-full text-left">
                                    <span x-text="options.find(option => option.value === value)?.label ?? 'Nieuwste eerst'"></span>
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
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">Tags</p>
                            <div
                                class="relative"
                                x-data="{ open: false, value: @js($selectedTag), options: @js($availableTags->values()) }"
                                @keydown.escape.window="open = false"
                            >
                                <input type="hidden" name="tag" x-model="value">
                                <button type="button" @click="open = !open" class="input input-neon-select w-full text-left">
                                    <span x-text="value ? `#${value}` : 'Alle tags'"></span>
                                </button>

                                <div x-show="open" @click.outside="open = false" x-transition class="dropdown-panel">
                                    <button
                                        type="button"
                                        class="dropdown-item"
                                        :class="{ 'is-active': value === '' }"
                                        @click="value = ''; open = false"
                                    >
                                        Alle tags
                                    </button>

                                    <template x-for="tag in options" :key="tag">
                                        <button
                                            type="button"
                                            class="dropdown-item"
                                            :class="{ 'is-active': value === tag }"
                                            @click="value = tag; open = false"
                                            x-text="`#${tag}`"
                                        ></button>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="submit" class="btn h-10 px-4">Filter</button>
                            <a href="{{ route('task.index') }}" class="inline-flex items-center text-sm text-muted-foreground hover:text-primary no-link-hover px-2">
                                Reset
                            </a>
                        </div>
                    </form>

                    @if ($selectedView !== 'board')
                        <div class="mt-4 rounded-xl border border-border/80 bg-card/90 p-2.5 shadow-[inset_0_1px_0_color-mix(in_srgb,white_35%,transparent)]">
                            <p class="mb-1.5 text-[11px] leading-none uppercase tracking-[0.08em] text-muted-foreground">Status</p>
                            <div class="space-y-2">
                            <a href="{{ route('task.index', array_filter($baseFilters)) }}" class="btn {{ request()->has('status') ? 'btn-outlined' : '' }} w-full text-left">Alles</a>
                            @foreach (App\TaskStatus::cases() as $status)
                                <a
                                    href="{{ route('task.index', array_filter([...$baseFilters, 'status' => $status->value])) }}"
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
                @if ($selectedView === 'board')
                    <div class="grid gap-5 lg:grid-cols-3" data-kanban-board data-csrf="{{ csrf_token() }}">
                        @foreach (App\TaskStatus::cases() as $status)
                            <section class="kanban-column" data-status="{{ $status->value }}">
                                <header class="kanban-column-header">
                                    <h3 class="text-sm font-semibold tracking-wide text-foreground">{{ $status->label() }}</h3>
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

                                                @if (!empty($task->tags) && count($task->tags))
                                                    <div class="mt-2 flex flex-wrap gap-1.5">
                                                        @foreach (array_slice($task->tags, 0, 3) as $tag)
                                                            <span class="inline-flex rounded-full border border-border/80 px-2 py-0.5 text-[11px] text-foreground/80">#{{ $tag }}</span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                                <p class="mt-2 line-clamp-3 text-xs text-muted-foreground">
                                                    {{ $task->description ?: 'Geen omschrijving toegevoegd.' }}
                                                </p>
                                            </a>
                                        </article>
                                    @empty
                                        <div class="kanban-empty">Geen taken gevonden.</div>
                                    @endforelse
                                </div>
                            </section>
                        @endforeach
                    </div>
                @else
                    <div class="grid md:grid-cols-2 gap-6">
                        @forelse($tasks as $task)
                            <x-card href="{{ route('task.show', $task) }}">
                                @if ($task->image_path)
                                    <div class="mb-4 -mx-4 -mt-4 rounded-t-lg overflow-hidden">
                                        <img src="{{ asset('storage/' . $task->image_path) }}" alt="" class="w-full h-auto object-cover">
                                    </div>
                                @endif
                                <h3 class="text-foreground text-lg">{{ $task->title }}</h3>

                                <div class="mt-2">
                                    <x-task.status-label :status="$task->status->value">
                                        {{ $task->status->label() }}
                                    </x-task.status-label>
                                </div>

                                @if (!empty($task->tags) && count($task->tags))
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        @foreach ($task->tags as $tag)
                                            <span class="inline-block rounded-full border border-border px-2 py-1 text-xs text-foreground/80">#{{ $tag }}</span>
                                        @endforeach
                                    </div>
                                @endif

                                <div class="mt-5 line-clamp-3">{{ $task->description }}</div>
                                <div class="mt-4">{{ $task->created_at->diffForHumans() }}</div>
                            </x-card>
                        @empty
                            <x-card>
                                <p class="text-muted-foreground text-center">Geen taken gevonden.</p>
                            </x-card>
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
                const csrf = board.dataset.csrf;
                const transparentDragImage = new Image();
                transparentDragImage.src = 'data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///ywAAAAAAQABAAACAUwAOw==';

                const renderFollower = () => {
                    if (!dragFollower) {
                        followerFrame = null;
                        return;
                    }

                    dragFollower.style.transform = `translate3d(${followerX + 18}px, ${followerY + 14}px, 0) rotate(-1.8deg)`;
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
                        placeholder.textContent = 'Nog geen taken in deze kolom.';
                        dropzone.appendChild(placeholder);
                    }

                    if (cards > 0 && empty) {
                        empty.remove();
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
                        document.body.appendChild(dragFollower);

                        followerX = event.clientX || 0;
                        followerY = event.clientY || 0;
                        dragFollower.style.transform = `translate3d(${followerX + 18}px, ${followerY + 14}px, 0) rotate(-1.8deg)`;
                        window.addEventListener('dragover', updateFollowerPosition);

                        if (!followerFrame) {
                            followerFrame = requestAnimationFrame(renderFollower);
                        }
                    });

                    card.addEventListener('dragend', () => {
                        card.classList.remove('is-dragging');
                        draggedCard = null;
                        cleanupFollower();
                    });
                });

                board.querySelectorAll('.kanban-column').forEach((column) => {
                    const zone = column.querySelector('.kanban-dropzone');
                    if (!zone) return;

                    zone.addEventListener('dragover', (event) => {
                        event.preventDefault();
                        column.classList.add('is-over');
                    });

                    zone.addEventListener('dragleave', () => {
                        column.classList.remove('is-over');
                    });

                    zone.addEventListener('drop', async (event) => {
                        event.preventDefault();
                        column.classList.remove('is-over');

                        if (!draggedCard) return;

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
                    });
                });
            })();
        </script>
    @endif
</x-layout>

