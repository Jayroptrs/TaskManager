<x-layout>
    <div class="py-6 sm:py-8 max-w-4xl mx-auto">
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
            <a href="{{ route('task.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-primary transition-colors duration-200">
                <span>&larr; {{ __('ui.back') }}</span>
            </a>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <button
                    x-data
                    @click="$dispatch('open-modal', 'task-collaborators-activity')"
                    type="button"
                    class="btn btn-outlined w-full sm:w-auto"
                >
                    {{ __('task.manage') }}
                </button>

                @if ($canEditTask)
                    <button
                        x-data
                        @click="$dispatch('open-modal', 'edit-task')"
                        class="btn btn-outlined w-full sm:w-auto transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(0,100,100,0.25)]">
                        &#x270F;&#xFE0F; {{ __('task.edit_task') }}
                    </button>
                @endif

                @if ($task->user_id === auth()->id())
                    <button
                        x-data
                        type="button"
                        @click="$dispatch('open-modal', 'delete-task-confirmation')"
                        class="btn btn-danger-outlined w-full sm:w-auto transition-all duration-200 ease-out hover:-translate-y-0.5"
                    >
                        &#x1F5D1;&#xFE0F; {{ __('task.delete_task') }}
                    </button>
                @elseif ($task->collaborators->contains('id', auth()->id()))
                    <form method="POST" action="{{ route('task.leave', $task) }}" class="w-full sm:w-auto">
                        @csrf
                        <button class="btn btn-outlined w-full sm:w-auto">{{ __('task.leave_task') }}</button>
                    </form>
                @endif
            </div>
        </div>

        <div class="mt-8 space-y-6">
            @if ($task->imageUrl())
                <div class="rounded-lg overflow-hidden">
                    <img src="{{ $task->imageUrl() }}" alt="" class="w-full h-auto object-cover">
                </div>
            @endif

            <h1 class="font-bold text-3xl sm:text-4xl">{{ $task->title }}</h1>

            <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
                <x-task.status-label :status="$task->status->value">{{ $task->status->label() }}</x-task.status-label>
                <div class="text-muted-foreground text-sm">{{ $task->created_at->diffForHumans() }}</div>
                @if ($task->due_date)
                    <div id="task-deadline" class="text-muted-foreground text-sm">{{ __('task.due_date_short', ['date' => $task->due_date->translatedFormat('j M Y')]) }}</div>
                @endif
            </div>

            @if (!empty($task->tags) && count($task->tags))
                <div class="flex flex-wrap gap-2">
                    @foreach ($task->tags as $tag)
                        <span class="inline-block rounded-full border border-border px-2 py-1 text-xs text-foreground/80">#{{ $tag }}</span>
                    @endforeach
                </div>
            @endif

            @if ($task->description)
                <x-card class="mt-6" is="div">
                    <div class="text-foreground max-w-none cursor-pointer prose prose-invert">{!! $task->formattedDescription !!}</div>
                </x-card>
            @endif

            @if ($task->steps->count())
                <div>
                    <h3 class="font-bold text-xl mt-6">{{ __('task.subtasks') }}</h3>

                    <div class="mt-3 space-y-4">
                        @foreach ($task->steps as $step)
                            <x-card class="text-primary font-medium flex gap-x-3 items-center">
                                @if ($canToggleSteps)
                                    <form method="POST" action="{{ route('step.update', $step) }}">
                                        @csrf
                                        @method('PATCH')
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                            <button
                                                type="submit"
                                                role="checkbox"
                                                aria-checked="{{ $step->completed ? 'true' : 'false' }}"
                                                class="size-5 flex items-center justify-center rounded-lg border border-primary {{ $step->completed ? 'bg-primary text-primary-foreground' : 'bg-transparent text-transparent' }}"
                                            >
                                                &check;
                                            </button>
                                            <span class="{{ $step->completed ? 'line-through text-muted-foreground' : 'text-foreground' }}">{{ $step->description }}</span>
                                            @if ($step->assignee)
                                                <span class="inline-flex rounded-full border border-border px-2 py-0.5 text-[11px] text-foreground/80">
                                                    {{ __('task.assignee_short') }}: {{ $step->assignee->name }}
                                                </span>
                                            @endif
                                        </div>
                                    </form>
                                @else
                                    <div class="flex flex-wrap items-center gap-x-3 gap-y-2">
                                        <span
                                            role="checkbox"
                                            aria-checked="{{ $step->completed ? 'true' : 'false' }}"
                                            class="size-5 flex items-center justify-center rounded-lg border border-primary {{ $step->completed ? 'bg-primary text-primary-foreground' : 'bg-transparent text-transparent' }}"
                                        >
                                            &check;
                                        </span>
                                        <span class="{{ $step->completed ? 'line-through text-muted-foreground' : 'text-foreground' }}">{{ $step->description }}</span>
                                        @if ($step->assignee)
                                            <span class="inline-flex rounded-full border border-border px-2 py-0.5 text-[11px] text-foreground/80">
                                                {{ __('task.assignee_short') }}: {{ $step->assignee->name }}
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </x-card>
                        @endforeach
                    </div>
                </div>
            @endif

            @php
                $mentionableUsers = collect([$task->user])
                    ->merge($task->collaborators)
                    ->filter()
                    ->unique('id')
                    ->reject(fn ($user) => $user->id === auth()->id())
                    ->values()
                    ->map(fn ($user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                    ]);
            @endphp

            <x-card class="mt-6" is="div">
                <div class="space-y-4">
                    <div class="flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold tracking-wide text-foreground">{{ __('task.comments') }}</h3>
                        <span class="text-xs text-muted-foreground">{{ $task->comments->count() }}</span>
                    </div>

                    @if ($canCommentOnTask)
                        <form method="POST" action="{{ route('task.comments.store', $task) }}" enctype="multipart/form-data" class="space-y-2" x-data="commentMentionComposer(@js($mentionableUsers))">
                            @csrf
                            <div class="relative">
                                <x-form.textarea
                                    name="comment"
                                    rows="3"
                                    class="h-24 min-h-24 max-h-56 resize-y overflow-y-auto"
                                    placeholder="{{ __('task.comment_placeholder') }}"
                                    x-ref="commentField"
                                    @input="handleInput($event)"
                                    @keydown="handleKeydown($event)"
                                    @click="handleInput($event)"
                                    required
                                >{{ old('comment') }}</x-form.textarea>
                                <div
                                    x-show="open && filteredUsers.length > 0"
                                    x-transition
                                    @click.outside="close()"
                                    class="absolute z-30 mt-1 w-full rounded-xl border border-border/80 bg-card/95 p-1.5 shadow-[0_10px_24px_color-mix(in_srgb,black_14%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_16%,transparent)]"
                                    style="display: none;"
                                >
                                    <template x-for="(user, index) in filteredUsers" :key="user.id">
                                        <button
                                            type="button"
                                            @mousedown.prevent="choose(user)"
                                            @mouseenter="activeIndex = index"
                                            class="flex w-full items-center justify-between gap-3 rounded-lg px-2.5 py-2 text-left transition-colors"
                                            :class="activeIndex === index ? 'bg-primary/20 text-foreground' : 'text-foreground/90 hover:bg-card/70'"
                                        >
                                            <span class="min-w-0 truncate pr-2 text-xs font-medium" x-text="user.name"></span>
                                            <span class="max-w-[45%] truncate text-[11px] text-muted-foreground" x-text="user.email"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                            <p class="text-xs text-muted-foreground">{{ __('task.comment_mention_hint') }}</p>
                            <div class="space-y-2 rounded-xl border border-border/70 bg-card/60 p-3">
                                <p class="text-xs font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.comment_attachments_label') }}</p>
                                <input
                                    id="comment-attachments-main"
                                    type="file"
                                    name="attachments[]"
                                    multiple
                                    class="input"
                                />
                                <p class="text-xs text-muted-foreground">{{ __('task.comment_attachments_help') }}</p>
                            </div>
                            <div class="flex justify-end">
                                <button type="submit" class="btn">{{ __('task.comment_submit') }}</button>
                            </div>
                            <x-form.error name="comment" />
                            <x-form.error name="attachments" />
                            <x-form.error name="attachments.0" />
                            <x-form.error name="parent_comment_id" />
                        </form>
                    @endif

                    @if ($task->comments->isEmpty())
                        <p class="text-sm text-muted-foreground">{{ __('task.comments_empty') }}</p>
                    @else
                        @php
                            $orderedComments = $task->comments->sortByDesc('created_at');
                            $topLevelComments = $orderedComments->whereNull('parent_comment_id')->values();
                            $repliesByParent = $orderedComments->whereNotNull('parent_comment_id')->groupBy('parent_comment_id');
                        @endphp
                        <div class="space-y-3">
                            @foreach ($topLevelComments as $comment)
                                <div id="comment-{{ $comment->id }}" class="rounded-lg border border-border/70 bg-card/70 px-3 py-2" x-data="{ replyOpen: false }">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <img
                                                src="{{ $comment->user ? $comment->user->avatarUrl() : asset('images/avatar-anonymous.svg') }}"
                                                alt=""
                                                class="size-7 rounded-full border border-border/80 object-cover"
                                                loading="lazy"
                                            >
                                            <p class="truncate text-sm font-medium text-foreground">
                                                {{ $comment->user?->name ?? __('task.activity_system') }}
                                            </p>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-2">
                                            <p class="text-xs text-muted-foreground">{{ $comment->created_at->diffForHumans() }}</p>
                                            @if ($canCommentOnTask)
                                                <button type="button" @click="replyOpen = !replyOpen" class="text-xs text-muted-foreground hover:text-primary">
                                                    {{ __('task.comment_reply') }}
                                                </button>
                                            @endif
                                            @if ($canCommentOnTask && ($comment->user_id === auth()->id() || $task->user_id === auth()->id()))
                                                <form method="POST" action="{{ route('task.comments.destroy', [$task, $comment]) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="text-xs text-muted-foreground hover:text-red-400">{{ __('task.comment_delete') }}</button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                    <p class="mt-2 text-sm text-foreground/90 whitespace-pre-line">{{ $comment->body }}</p>

                                    @if ($comment->attachments->isNotEmpty())
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            @foreach ($comment->attachments as $attachment)
                                                <a
                                                    href="{{ route('task.comments.attachments.download', [$task, $comment, $attachment]) }}"
                                                    class="no-link-hover inline-flex max-w-full items-center gap-1 rounded-full border border-border/80 px-2 py-1 text-[11px] text-foreground/80 hover:text-primary"
                                                >
                                                    <span class="truncate">{{ __('task.comment_attachment_download') }}: {{ $attachment->original_name }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif

                                    @if ($canCommentOnTask)
                                        <form
                                            x-show="replyOpen"
                                            x-transition
                                            method="POST"
                                            action="{{ route('task.comments.store', $task) }}"
                                            enctype="multipart/form-data"
                                            class="mt-3 space-y-2 rounded-lg border border-border/60 bg-card/60 p-2.5"
                                            x-data="commentMentionComposer(@js($mentionableUsers))"
                                        >
                                            @csrf
                                            <input type="hidden" name="parent_comment_id" value="{{ $comment->id }}">
                                            <div class="relative">
                                                <x-form.textarea
                                                    name="comment"
                                                    rows="2"
                                                    class="h-20 min-h-20 max-h-48 resize-y overflow-y-auto"
                                                    placeholder="{{ __('task.comment_reply_placeholder') }}"
                                                    x-ref="commentField"
                                                    @input="handleInput($event)"
                                                    @keydown="handleKeydown($event)"
                                                    @click="handleInput($event)"
                                                    required
                                                ></x-form.textarea>
                                                <div
                                                    x-show="open && filteredUsers.length > 0"
                                                    x-transition
                                                    @click.outside="close()"
                                                    class="absolute z-30 mt-1 w-full rounded-xl border border-border/80 bg-card/95 p-1.5 shadow-[0_10px_24px_color-mix(in_srgb,black_14%,transparent),0_0_14px_color-mix(in_srgb,var(--color-primary)_16%,transparent)]"
                                                    style="display: none;"
                                                >
                                                    <template x-for="(user, index) in filteredUsers" :key="user.id">
                                                        <button
                                                            type="button"
                                                            @mousedown.prevent="choose(user)"
                                                            @mouseenter="activeIndex = index"
                                                            class="flex w-full items-center justify-between gap-3 rounded-lg px-2.5 py-2 text-left transition-colors"
                                                            :class="activeIndex === index ? 'bg-primary/20 text-foreground' : 'text-foreground/90 hover:bg-card/70'"
                                                        >
                                                            <span class="min-w-0 truncate pr-2 text-xs font-medium" x-text="user.name"></span>
                                                            <span class="max-w-[45%] truncate text-[11px] text-muted-foreground" x-text="user.email"></span>
                                                        </button>
                                                    </template>
                                                </div>
                                            </div>
                                            <div class="space-y-2 rounded-lg border border-border/60 bg-card/70 p-2.5">
                                                <input
                                                    id="reply-attachments-{{ $comment->id }}"
                                                    type="file"
                                                    name="attachments[]"
                                                    multiple
                                                    class="input"
                                                />
                                            </div>
                                            <div class="flex justify-end gap-2">
                                                <button type="button" @click="replyOpen = false" class="btn btn-outlined h-9 px-3 text-xs">
                                                    {{ __('task.comment_reply_cancel') }}
                                                </button>
                                                <button type="submit" class="btn h-9 px-3 text-xs">
                                                    {{ __('task.comment_reply_submit') }}
                                                </button>
                                            </div>
                                        </form>
                                    @endif

                                    @if (($repliesByParent[$comment->id] ?? collect())->isNotEmpty())
                                        <div class="mt-3 ml-3 space-y-2 border-l border-border/70 pl-3">
                                            @foreach (($repliesByParent[$comment->id] ?? collect())->sortBy('created_at') as $reply)
                                                <div id="comment-{{ $reply->id }}" class="rounded-lg border border-border/60 bg-card/60 px-3 py-2">
                                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                                        <div class="flex min-w-0 items-center gap-2">
                                                            <img
                                                                src="{{ $reply->user ? $reply->user->avatarUrl() : asset('images/avatar-anonymous.svg') }}"
                                                                alt=""
                                                                class="size-6 rounded-full border border-border/80 object-cover"
                                                                loading="lazy"
                                                            >
                                                            <p class="truncate text-xs font-medium text-foreground">
                                                                {{ $reply->user?->name ?? __('task.activity_system') }}
                                                            </p>
                                                        </div>
                                                        <div class="flex flex-wrap items-center gap-2">
                                                            <p class="text-[11px] text-muted-foreground">{{ $reply->created_at->diffForHumans() }}</p>
                                                            @if ($canCommentOnTask && ($reply->user_id === auth()->id() || $task->user_id === auth()->id()))
                                                                <form method="POST" action="{{ route('task.comments.destroy', [$task, $reply]) }}">
                                                                    @csrf
                                                                    @method('DELETE')
                                                                    <button type="submit" class="text-[11px] text-muted-foreground hover:text-red-400">{{ __('task.comment_delete') }}</button>
                                                                </form>
                                                            @endif
                                                        </div>
                                                    </div>
                                                    <p class="mt-2 text-xs text-foreground/90 whitespace-pre-line">{{ $reply->body }}</p>

                                                    @if ($reply->attachments->isNotEmpty())
                                                        <div class="mt-2 flex flex-wrap gap-2">
                                                            @foreach ($reply->attachments as $attachment)
                                                                <a
                                                                    href="{{ route('task.comments.attachments.download', [$task, $reply, $attachment]) }}"
                                                                    class="no-link-hover inline-flex max-w-full items-center gap-1 rounded-full border border-border/80 px-2 py-1 text-[10px] text-foreground/80 hover:text-primary"
                                                                >
                                                                    <span class="truncate">{{ __('task.comment_attachment_download') }}: {{ $attachment->original_name }}</span>
                                                                </a>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-card>

            @if (!empty($task->links) && count($task->links))
                <div>
                    <h3 class="font-bold text-xl mt-6">{{ __('task.links') }}</h3>

                    <div class="mt-3 space-y-4">
                        @foreach ($task->links as $link)
                            <x-card :href="$link" class="text-primary font-medium flex gap-x-3 items-center">
                                <p>{{ __('task.link') }}</p>
                                {{ $link }}
                            </x-card>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        @once
            <script>
                document.addEventListener('alpine:init', () => {
                    window.commentMentionComposer = (users = []) => ({
                        users: Array.isArray(users) ? users : [],
                        open: false,
                        query: '',
                        filteredUsers: [],
                        activeIndex: 0,
                        mentionStart: -1,
                        mentionEnd: -1,

                        handleInput(event) {
                            this.updateSuggestions(event.target);
                        },

                        handleKeydown(event) {
                            if (!this.open || this.filteredUsers.length === 0) {
                                return;
                            }

                            if (event.key === 'ArrowDown') {
                                event.preventDefault();
                                this.activeIndex = (this.activeIndex + 1) % this.filteredUsers.length;
                                return;
                            }

                            if (event.key === 'ArrowUp') {
                                event.preventDefault();
                                this.activeIndex = (this.activeIndex - 1 + this.filteredUsers.length) % this.filteredUsers.length;
                                return;
                            }

                            if (event.key === 'Enter' || event.key === 'Tab') {
                                event.preventDefault();
                                this.choose(this.filteredUsers[this.activeIndex]);
                                return;
                            }

                            if (event.key === 'Escape') {
                                event.preventDefault();
                                this.close();
                            }
                        },

                        updateSuggestions(field) {
                            const cursor = field.selectionStart ?? 0;
                            const textBeforeCursor = field.value.slice(0, cursor);
                            const match = textBeforeCursor.match(/(^|[\s(])@([^\s@]*)$/u);

                            if (!match) {
                                this.close();

                                return;
                            }

                            this.query = match[2] ?? '';
                            this.mentionStart = cursor - this.query.length - 1;
                            this.mentionEnd = cursor;

                            const normalizedQuery = this.normalize(this.query);
                            this.filteredUsers = this.users
                                .filter((user) => {
                                    const name = this.normalize(user.name ?? '');

                                    return normalizedQuery === ''
                                        || name.includes(normalizedQuery);
                                })
                                .slice(0, 7);

                            if (this.filteredUsers.length === 0) {
                                this.close();

                                return;
                            }

                            this.activeIndex = 0;
                            this.open = true;
                        },

                        choose(user) {
                            const field = this.$refs.commentField;
                            if (!field || !user || this.mentionStart < 0) {
                                this.close();

                                return;
                            }

                            const mentionToken = this.toMentionToken(user.name ?? '');
                            if (!mentionToken) {
                                this.close();

                                return;
                            }

                            const beforeMention = field.value.slice(0, this.mentionStart);
                            const afterMention = field.value.slice(this.mentionEnd);
                            const insertion = `@${mentionToken} `;

                            field.value = `${beforeMention}${insertion}${afterMention}`;

                            const cursorPosition = (beforeMention + insertion).length;
                            field.focus();
                            field.setSelectionRange(cursorPosition, cursorPosition);
                            field.dispatchEvent(new Event('input', { bubbles: true }));

                            this.close();
                        },

                        close() {
                            this.open = false;
                            this.query = '';
                            this.filteredUsers = [];
                            this.activeIndex = 0;
                            this.mentionStart = -1;
                            this.mentionEnd = -1;
                        },

                        normalize(value) {
                            return String(value)
                                .normalize('NFKD')
                                .replace(/[\u0300-\u036f]/g, '')
                                .toLowerCase()
                                .trim();
                        },

                        toMentionToken(value) {
                            return String(value)
                                .normalize('NFKD')
                                .replace(/[\u0300-\u036f]/g, '')
                                .toLowerCase()
                                .trim()
                                .replace(/\s+/g, '.')
                                .replace(/[^a-z0-9._+\-]/g, '')
                                .replace(/\.{2,}/g, '.')
                                .replace(/^\.|\.$/g, '');
                        },
                    });
                });
            </script>
        @endonce

        @if ($canEditTask)
            <x-task.modal :task="$task" />
        @endif

        @if ($task->user_id === auth()->id())
            <x-modal name="delete-task-confirmation" :title="__('task.delete_task_confirm_title')" maxWidth="max-w-md">
                <div class="space-y-4">
                    <p class="text-sm text-muted-foreground">{{ __('task.delete_task_confirm_message') }}</p>

                    <div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-2">
                        <button
                            type="button"
                            @click="$dispatch('close-modal')"
                            class="btn btn-outlined h-10 px-4"
                        >
                            {{ __('task.cancel') }}
                        </button>

                        <form method="POST" action="{{ route('task.destroy', $task) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger-outlined h-10 px-4">
                                {{ __('task.delete_task_confirm_button') }}
                            </button>
                        </form>
                    </div>
                </div>
            </x-modal>
        @endif
    </div>

    <x-modal name="task-collaborators-activity" :title="__('task.collaborators')">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-border/70 bg-card/60 px-3 py-2">
                <div class="flex min-w-0 items-center gap-2">
                    <span class="inline-flex size-6 items-center justify-center rounded-full border border-primary/45 bg-primary/15 text-xs font-bold text-primary">S</span>
                    <h3 class="truncate text-sm font-semibold tracking-wide text-foreground">{{ __('task.collaborators') }}</h3>
                </div>
                <span class="inline-flex items-center rounded-full border border-border/80 bg-card/80 px-2 py-0.5 text-[11px] text-muted-foreground">
                    {{ $task->collaborators->count() }} {{ __('task.collaborators_count_label') }}
                </span>
            </div>

            @if ($task->collaborators->isNotEmpty())
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($task->collaborators as $collaborator)
                        <div class="flex items-center justify-between gap-2 rounded-lg border border-border/70 bg-card/65 px-3 py-2 text-xs">
                            <div class="min-w-0">
                                <p class="truncate font-medium text-foreground">{{ $collaborator->name }}</p>
                                <p class="text-[10px] uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.collaborators') }}</p>
                            </div>

                            @if ($canManageCollaborators)
                                <form method="POST" action="{{ route('task.collaborators.destroy', [$task, $collaborator]) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="inline-flex size-6 items-center justify-center rounded-full border border-border/70 text-muted-foreground transition-colors hover:border-red-400/60 hover:text-red-400" aria-label="{{ __('task.remove_collaborator') }}">&#10005;</button>
                                </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="rounded-lg border border-dashed border-border/70 bg-card/50 px-3 py-3 text-sm text-muted-foreground">{{ __('task.no_collaborators') }}</p>
            @endif

            @if ($canManageCollaborators)
                <div class="space-y-2 rounded-xl border border-border/70 bg-card/50 p-3">
                    <h4 class="text-xs font-semibold uppercase tracking-[0.08em] text-muted-foreground">{{ __('task.invite_section_title') }}</h4>

                    <form method="POST" action="{{ route('task.collaborators.email', $task) }}" class="flex flex-col gap-2 sm:flex-row">
                        @csrf
                        <x-form.input
                            type="email"
                            name="email"
                            class="flex-1"
                            placeholder="{{ __('task.invite_email_placeholder') }}"
                            required
                        />
                        <button type="submit" class="btn h-9 px-3 text-xs sm:text-sm">{{ __('task.invite_by_email') }}</button>
                    </form>

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center">
                        <x-form.input
                            type="text"
                            readonly
                            class="flex-1 opacity-85"
                            value="{{ $activeInvite ? route('task.invites.accept', $activeInvite->token) : '' }}"
                            placeholder="{{ __('task.no_invite_link_yet') }}"
                        />

                        <div class="flex flex-wrap gap-2">
                            <form method="POST" action="{{ route('task.invites.link', $task) }}">
                                @csrf
                                <button type="submit" class="btn btn-outlined h-9 px-3 text-xs sm:text-sm">{{ __('task.generate_invite_link') }}</button>
                            </form>

                            @if ($activeInvite)
                                <button
                                    type="button"
                                    class="btn btn-outlined h-9 px-3 text-xs sm:text-sm"
                                    x-data
                                    @click="
                                        (async () => {
                                            const link = @js(route('task.invites.accept', $activeInvite->token));
                                            let copied = false;

                                            try {
                                                if (navigator.clipboard && window.isSecureContext) {
                                                    await navigator.clipboard.writeText(link);
                                                    copied = true;
                                                } else {
                                                    const temp = document.createElement('textarea');
                                                    temp.value = link;
                                                    temp.setAttribute('readonly', '');
                                                    temp.style.position = 'fixed';
                                                    temp.style.opacity = '0';
                                                    temp.style.left = '-9999px';
                                                    document.body.appendChild(temp);
                                                    temp.select();
                                                    copied = document.execCommand('copy');
                                                    temp.remove();
                                                }
                                            } catch (e) {
                                                copied = false;
                                            }

                                            if (copied) {
                                                $dispatch('toast', { message: @js(__('messages.invite_link_copied')), type: 'success' });
                                                $dispatch('close-modal');
                                            } else {
                                                $dispatch('toast', { message: @js(__('messages.invite_link_copy_failed')), type: 'error' });
                                            }
                                        })()
                                    "
                                >
                                    {{ __('task.copy_invite_link') }}
                                </button>
                            @endif
                        </div>
                    </div>
                    <x-form.error name="email" />
                    <x-form.error name="invite" />
                    <x-form.error name="collaborators" />
                </div>
            @elseif ($task->collaborators->contains('id', auth()->id()))
                <form method="POST" action="{{ route('task.leave', $task) }}">
                    @csrf
                    <button type="submit" class="btn btn-outlined">{{ __('task.leave_task') }}</button>
                </form>
            @endif

            @if ($isOwner)
                <div class="border-t border-border/70 pt-4">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h3 class="text-sm font-semibold tracking-wide text-foreground">{{ __('task.activity_log') }}</h3>
                        <span class="text-xs text-muted-foreground">{{ $activityLogs->count() }}</span>
                    </div>

                    @if ($activityLogs->isEmpty())
                        <p class="text-sm text-muted-foreground">{{ __('task.activity_empty') }}</p>
                    @else
                        <div class="space-y-2">
                            @foreach ($activityLogs as $log)
                            @php
                                $meta = $log->metadata ?? [];
                                $actorName = $log->actor?->name ?? __('task.activity_system');
                                $activityKey = 'task.activity_'.$log->action;
                                $activityText = \Illuminate\Support\Facades\Lang::has($activityKey)
                                    ? __($activityKey, ['from' => $meta['from'] ?? '-', 'to' => $meta['to'] ?? '-', 'step' => $meta['step'] ?? '-', 'collaborator' => $meta['collaborator'] ?? '-', 'invitee' => $meta['invitee'] ?? '-'])
                                    : __('task.activity_unknown', ['action' => str_replace('_', ' ', $log->action)]);
                            @endphp
                            <div class="rounded-lg border border-border/70 bg-card/70 px-3 py-2">
                                <p class="text-sm text-foreground/90">
                                    {{ $activityText }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ __('task.activity_by', ['name' => $actorName]) }} &middot; {{ $log->created_at->diffForHumans() }}
                                </p>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </x-modal>

    @if (session('reopen_modal') === 'task-collaborators-activity')
        <div
            x-data
            x-init="
                setTimeout(() => {
                    window.dispatchEvent(new CustomEvent('open-modal', { detail: 'task-collaborators-activity' }));
                }, 40);
            "
        ></div>
    @endif
</x-layout>

