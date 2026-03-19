@props(['task' => new App\Models\Task()])
@php
    $panelClass = 'rounded-xl border border-border/80 bg-card/55 p-3';
    $subPanelClass = 'rounded-xl border border-border/70 bg-card/60 p-3';
    $removeItemButtonClass = 'inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border/80 bg-card/70 text-muted-foreground transition-all duration-200 hover:border-primary/45 hover:text-primary hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_24%,transparent)] focus-visible:border-primary/55 focus-visible:text-primary focus-visible:shadow-[0_0_16px_color-mix(in_srgb,var(--color-primary)_28%,transparent)]';
    $addItemButtonClass = 'btn btn-outlined h-10 w-10 shrink-0 px-0 text-base focus-visible:border-primary/55 focus-visible:shadow-[0_0_16px_color-mix(in_srgb,var(--color-primary)_30%,transparent)]';

    $initialReminderDays = collect(old('reminder_days', $task->reminder_days ?? [7, 3, 1]))
        ->map(fn ($day) => (int) $day)
        ->filter(fn ($day) => $day >= 0)
        ->unique()
        ->sort()
        ->values()
        ->all();

    $initialRemindersEnabledRaw = old('reminders_enabled');
    if ($initialRemindersEnabledRaw === null) {
        $initialRemindersEnabled = $task->exists
            ? collect($task->reminder_days ?? [1, 3, 7])->isNotEmpty()
            : false;
    } else {
        $initialRemindersEnabled = in_array((string) $initialRemindersEnabledRaw, ['1', 'true', 'on'], true);
    }

    $initialRemoveImageRequested = in_array((string) old('remove_image', '0'), ['1', 'true', 'on'], true)
        && $task->hasUploadedImage();
@endphp

<x-modal name="{{ $task->exists ? 'edit-task' : 'create-task' }}" title="{{ $task->exists ? __('task.modal_edit_title') : __('task.modal_create_title') }}" maxWidth="max-w-4xl">
    <form
        x-data="{
        todayDate: @js(now()->toDateString()),
        clientErrors: {},
        status: @js(old('status', $task->status->value)),
        priority: @js(old('priority', $task->priority?->value ?? \App\TaskPriority::MEDIUM->value)),
        dueDate: @js(old('due_date', $task->due_date?->toDateString())),
        newLink: '',
        links: @js(old('links', $task->links ?? [])),
        newTag: '',
        tags: @js(old('tags', $task->tags ?? [])),
        newStep: '',
        steps: @js(old('steps', $task->steps->map->only(['id', 'description', 'completed', 'assigned_user_id']))),
        remindersEnabled: @js((bool) $initialRemindersEnabled),
        reminderDays: @js($initialReminderDays === [] ? [1, 3, 7] : $initialReminderDays),
        newReminderDay: '',
        hasUploadedImage: @js((bool) $task->hasUploadedImage()),
        originalImageUrl: @js($task->imageUrl()),
        imageUrl: @js($task->imageUrl()),
        imageObjectUrl: null,
        removeImageRequested: @js((bool) $initialRemoveImageRequested),
        markImageForRemoval() {
            if (!this.hasUploadedImage) return;
            this.removeImageRequested = true;
            this.imageUrl = null;
        },
        undoImageRemoval() {
            this.removeImageRequested = false;
            this.imageUrl = this.originalImageUrl;
        },
        handleImageChange(event) {
            const file = event.target.files?.[0] ?? null;
            if (!file) {
                this.imageUrl = this.removeImageRequested ? null : this.originalImageUrl;
                return;
            }

            this.removeImageRequested = false;
            if (this.imageObjectUrl) {
                URL.revokeObjectURL(this.imageObjectUrl);
            }
            this.imageObjectUrl = URL.createObjectURL(file);
            this.imageUrl = this.imageObjectUrl;
        },
        addReminderDay() {
            if (!this.remindersEnabled) {
                return;
            }
            const parsedDay = Number.parseInt(String(this.newReminderDay).trim(), 10);
            if (Number.isNaN(parsedDay) || parsedDay < 0 || parsedDay > 365) {
                return;
            }
            this.reminderDays.push(parsedDay);
            this.reminderDays = [...new Set(this.reminderDays)].sort((a, b) => a - b);
            this.newReminderDay = '';
        },
        removeReminderDay(day) {
            this.reminderDays = this.reminderDays.filter((value) => value !== day);
        },
        restoreDefaultReminderDays() {
            this.reminderDays = [1, 3, 7];
        },
        toggleRemindersEnabled() {
            this.remindersEnabled = !this.remindersEnabled;
            if (this.remindersEnabled && this.reminderDays.length === 0) {
                this.reminderDays = [1, 3, 7];
            }
        },
        addStep() {
            const value = String(this.newStep ?? '').trim();
            if (value.length === 0) {
                return;
            }
            this.steps.push({ description: value, completed: false, assigned_user_id: null });
            this.newStep = '';
        },
        addLink() {
            const value = String(this.newLink ?? '').trim();
            if (value.length === 0) {
                return;
            }
            this.links.push(value);
            this.newLink = '';
        },
        addTag() {
            const value = String(this.newTag ?? '').trim();
            if (value.length === 0) {
                return;
            }
            if (!this.tags.includes(value)) {
                this.tags.push(value);
            }
            this.newTag = '';
        },
        hasPastDueDate() {
            const value = String(this.dueDate ?? '').trim();
            return value !== '' && value < this.todayDate;
        },
        clearClientError(field) {
            delete this.clientErrors[field];
        },
        validateClientSide() {
            this.clientErrors = {};

            const title = String(this.$refs.titleInput?.value ?? '').trim();
            if (title.length === 0) {
                this.clientErrors.title = @js(__('task.title_required_error'));
                this.$nextTick(() => this.$refs.titleInput?.focus());
            }

            return Object.keys(this.clientErrors).length === 0;
        },
        }"
        @keydown.escape.stop="$dispatch('close-modal')"
        @task-create-due-date-selected.window="dueDate = $event.detail?.dueDate ?? dueDate"
        @submit="if (!validateClientSide()) { $event.preventDefault(); }"
        method="POST"
        action="{{ $task->exists ? route('task.update', $task) : route('task.store') }}"
        enctype="multipart/form-data"
        novalidate
        >
        @csrf

        @if($task->exists)
            @method('PATCH')
        @endif

        <div class="space-y-4 sm:space-y-5">
            <x-form.field
                :label="__('task.title_label')"
                name="title"
                :placeholder="__('task.title_placeholder')"
                autofocus
                required
                x-ref="titleInput"
                @input="clearClientError('title')"
                @blur="if (String($event.target.value ?? '').trim() === '') { clientErrors.title = @js(__('task.title_required_error')); }"
                :value="$task->title"/>
            <p x-show="clientErrors.title" x-text="clientErrors.title" class="-mt-2 text-sm text-red-500" x-cloak></p>

            <div class="grid gap-4 lg:grid-cols-2">
                <div class="{{ $panelClass }} space-y-2">
                    <label for="status" class="label">{{ __('task.status') }}</label>

                    <div class="flex flex-wrap gap-2 sm:gap-3">
                        @foreach (App\TaskStatus::cases() as $status)
                            <button
                            type="button"
                            @click="status = @js($status->value)"
                            class="btn flex-1 h-10 whitespace-nowrap px-2 text-xs sm:px-3 sm:text-sm"
                            :class="{'btn-outlined': status!== @js($status->value)}"
                            >
                                {{ $status->label() }}
                            </button>
                        @endforeach

                        <input hidden type="text" name="status" :value="status" class="input">
                    </div>

                    <x-form.error name="status" />
                </div>

                <div class="{{ $panelClass }} space-y-2">
                    <label for="priority" class="label">{{ __('task.priority') }}</label>

                    <div class="flex flex-wrap gap-2 sm:gap-3">
                        @foreach (App\TaskPriority::cases() as $priority)
                            <button
                                type="button"
                                @click="priority = @js($priority->value)"
                                class="btn flex-1 h-10 whitespace-nowrap px-2 text-xs sm:px-3 sm:text-sm"
                                :class="{'btn-outlined': priority!== @js($priority->value)}"
                            >
                                {{ $priority->label() }}
                            </button>
                        @endforeach

                        <input type="hidden" name="priority" :value="priority">
                    </div>

                    <x-form.error name="priority" />
                </div>
            </div>

            <x-form.field
                :label="__('task.description_label')"
                name="description"
                type="textarea"
                :placeholder="__('task.description_placeholder')"
                :value="$task->description"/>

            <div class="{{ $panelClass }} space-y-3">
                <div>
                    <x-form.field
                        :label="__('task.due_date_label')"
                        name="due_date"
                        type="date"
                        :value="$task->due_date?->toDateString()"
                        x-model="dueDate"
                        x-bind:class="String(dueDate ?? '').trim() !== '' ? 'is-filled' : ''"
                    />
                    <p
                        x-show="hasPastDueDate()"
                        x-transition.opacity.duration.180ms
                        class="mt-2 inline-flex items-center gap-2 rounded-lg border border-amber-500/25 bg-amber-500/8 px-2.5 py-1.5 text-xs text-amber-200"
                        x-cloak
                    >
                        <span aria-hidden="true" class="inline-block h-2 w-2 rounded-full bg-amber-400/80"></span>
                        <span>{{ __('task.past_due_date_warning') }}</span>
                    </p>
                </div>

                <div
                    class="w-full space-y-2"
                    x-show="String(dueDate ?? '').trim() !== ''"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 -translate-y-2"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-2"
                    x-cloak
                >
                    <label class="label">{{ __('task.reminders_label') }}</label>
                    <div class="rounded-xl border border-border/80 bg-card/70 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-medium text-foreground/85" x-text="remindersEnabled ? @js(__('task.reminders_on')) : @js(__('task.reminders_off'))"></p>
                            <button
                                type="button"
                                @click="toggleRemindersEnabled()"
                                class="btn btn-outlined h-8 px-2.5 text-xs"
                            >
                                <span x-text="remindersEnabled ? @js(__('task.disable_reminders')) : @js(__('task.enable_reminders'))"></span>
                            </button>
                        </div>

                        <div x-show="remindersEnabled" x-transition.opacity.duration.150ms>
                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="(day, index) in reminderDays" :key="`selected-reminder-day-${day}-${index}`">
                                    <button
                                        type="button"
                                        @click="removeReminderDay(day)"
                                        class="inline-flex h-9 items-center gap-2 rounded-lg border border-primary/50 bg-primary/15 px-3 text-xs font-semibold text-foreground transition-all duration-250 ease-out hover:border-primary hover:shadow-[0_0_14px_color-mix(in_srgb,var(--color-primary)_36%,transparent)]"
                                        :aria-label="@js(__('task.remove_reminder_day'))"
                                    >
                                        <span x-text="@js(__('task.reminder_days_before', ['days' => '__DAYS__'])).replace('__DAYS__', day)"></span>
                                        <span aria-hidden="true" class="text-sm leading-none">&times;</span>
                                    </button>
                                </template>
                            </div>

                            <div class="mt-2 flex items-center gap-2">
                                <x-form.input
                                    type="number"
                                    min="0"
                                    max="365"
                                    step="1"
                                    x-model="newReminderDay"
                                    placeholder="{{ __('task.reminder_custom_placeholder') }}"
                                    class="flex-1"
                                    @keydown.enter.prevent="addReminderDay()"
                                />
                                <button
                                    type="button"
                                    @click="addReminderDay()"
                                    class="btn h-10 px-3 text-xs sm:text-sm"
                                >
                                    {{ __('task.add_reminder_day') }}
                                </button>
                            </div>
                            <p class="mt-2 text-xs text-muted-foreground">{{ __('task.reminders_hint') }}</p>
                            <button
                                type="button"
                                @click="restoreDefaultReminderDays()"
                                class="mt-1 inline-flex items-center text-xs font-medium text-primary no-link-hover hover:text-foreground"
                            >
                                {{ __('task.restore_default_reminders') }}
                            </button>
                        </div>

                        <p x-show="!remindersEnabled" x-transition.opacity.duration.150ms class="mt-2 text-xs text-muted-foreground">
                            {{ __('task.reminders_disabled_hint') }}
                        </p>
                    </div>
                    <input type="hidden" name="reminders_enabled" :value="remindersEnabled ? 1 : 0" :disabled="String(dueDate ?? '').trim() === ''">
                    <template x-for="(day, index) in reminderDays" :key="`reminder-day-${day}-${index}`">
                        <input type="hidden" name="reminder_days[]" :value="day" :disabled="String(dueDate ?? '').trim() === '' || !remindersEnabled">
                    </template>
                    <x-form.error name="reminder_days" />
                    <x-form.error name="reminder_days.0" />
                </div>
            </div>

            <div class="{{ $panelClass }} space-y-2">
                <label for="image" class="label">{{ __('task.image_label') }}</label>

                <div class="space-y-2" x-show="imageUrl" x-cloak>
                    <img :src="imageUrl" src="{{ $task->imageUrl() }}" alt="" class="h-auto w-full rounded-lg object-cover">
                </div>

                <template x-if="hasUploadedImage && !removeImageRequested">
                    <button type="button" @click="markImageForRemoval()" class="btn btn-outlined h-10 w-full">
                        {{ __('task.remove_image') }}
                    </button>
                </template>
                <template x-if="hasUploadedImage && removeImageRequested">
                    <div class="space-y-2 rounded-lg border border-border/70 bg-card/70 p-2.5">
                        <p class="text-xs text-muted-foreground">{{ __('task.uploaded_image_remove_pending_hint') }}</p>
                        <button type="button" @click="undoImageRemoval()" class="btn btn-outlined h-9 w-full text-xs">
                            {{ __('task.undo_image_remove') }}
                        </button>
                    </div>
                </template>
                <template x-if="!hasUploadedImage">
                    <p class="text-xs text-muted-foreground">{{ __('task.default_image_locked_hint') }}</p>
                </template>

                <input type="file" name="image" accept="image/*" @change="handleImageChange($event)">
                <input type="hidden" name="remove_image" :value="removeImageRequested ? 1 : 0">
                <x-form.error name="image"/>
            </div>

            <div class="{{ $panelClass }} space-y-3">
                <div class="space-y-1">
                    <h3 class="label">{{ __('task.structure_section') }}</h3>
                    <p class="text-xs text-muted-foreground">{{ __('task.keyboard_shortcuts_hint') }}</p>
                </div>

                <div class="grid gap-4 lg:grid-cols-3">
                    <div class="{{ $subPanelClass }}">
                    <fieldset class="space-y-2.5">
                        <label class="label">{{ __('task.steps') }}</label>

                        <template x-for="(step, index) in steps" :key="step.id || index">
                            <div class="flex items-start gap-2.5">
                                <div class="flex-1">
                                    <x-form.input
                                        type="text"
                                        readonly
                                        x-bind:name="`steps[${index}][description]`"
                                        x-bind:value="step.description"
                                        class="flex-1 cursor-default opacity-80"
                                    />

                                    <input
                                        type="hidden"
                                        :name="`steps[${index}][completed]`"
                                        :value="step.completed ? 1 : 0"
                                    >
                                    <input
                                        type="hidden"
                                        :name="`steps[${index}][assigned_user_id]`"
                                        :value="step.assigned_user_id ?? ''"
                                    >
                                </div>

                                <button
                                    class="{{ $removeItemButtonClass }}"
                                    type="button"
                                    :aria-label="@js(__('task.remove_step'))"
                                    @click="steps.splice(index, 1)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="M4 4L12 12M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <div class="flex items-center gap-2.5">
                            <x-form.input
                                type="text"
                                x-model="newStep"
                                id="new-step"
                                placeholder="{{ __('task.steps_placeholder') }}"
                                class="flex-1"
                                spellcheck="false"
                                @keydown.enter.prevent="addStep()"
                            />

                            <button
                                class="{{ $addItemButtonClass }}"
                                type="button"
                                @click="addStep()"
                                :disabled="newStep.trim().length === 0"
                                :aria-label="@js(__('task.add_step'))"
                                >
                                +
                            </button>
                        </div>
                    </fieldset>
                    </div>

                    <div class="{{ $subPanelClass }}">
                    <fieldset class="space-y-2.5">
                        <label class="label">{{ __('task.links') }}</label>

                        <template x-for="(link, index) in links" :key="`${link}-${index}`">
                            <div class="flex items-center gap-2.5">
                                <x-form.input type="text" readonly name="links[]" x-model="link" class="flex-1 cursor-default opacity-80" />

                                <button
                                    class="{{ $removeItemButtonClass }}"
                                    type="button"
                                    :aria-label="@js(__('task.remove_link'))"
                                    @click="links.splice(index, 1)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="M4 4L12 12M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <div class="flex items-center gap-2.5">
                            <x-form.input
                                type="url"
                                x-model="newLink"
                                id="new-link"
                                data-test="new-link"
                                placeholder="{{ __('task.link_placeholder') }}"
                                autocomplete="url"
                                class="flex-1"
                                spellcheck="false"
                                @keydown.enter.prevent="addLink()"
                            />

                            <button
                                class="{{ $addItemButtonClass }}"
                                type="button"
                                @click="addLink()"
                                :disabled="newLink.trim().length === 0"
                                :aria-label="@js(__('task.add_link'))"
                                >
                                +
                            </button>
                        </div>
                    </fieldset>
                    </div>

                    <div class="{{ $subPanelClass }}">
                    <fieldset class="space-y-2.5">
                        <label class="label">{{ __('task.tags') }}</label>

                        <template x-for="(tag, index) in tags" :key="`${tag}-${index}`">
                            <div class="flex items-center gap-2.5">
                                <x-form.input type="text" readonly name="tags[]" x-model="tag" class="flex-1 cursor-default opacity-80" />

                                <button
                                    class="{{ $removeItemButtonClass }}"
                                    type="button"
                                    :aria-label="@js(__('task.remove_tag'))"
                                    @click="tags.splice(index, 1)"
                                >
                                    <svg class="h-4 w-4" viewBox="0 0 16 16" fill="none" aria-hidden="true">
                                        <path d="M4 4L12 12M12 4L4 12" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" />
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <div class="flex items-center gap-2.5">
                            <x-form.input
                                type="text"
                                x-model="newTag"
                                id="new-tag"
                                placeholder="{{ __('task.tags_placeholder') }}"
                                class="flex-1"
                                spellcheck="false"
                                @keydown.enter.prevent="addTag()"
                            />

                            <button
                                class="{{ $addItemButtonClass }}"
                                type="button"
                                @click="addTag()"
                                :disabled="newTag.trim().length === 0"
                                :aria-label="@js(__('task.add_tag'))"
                            >
                                +
                            </button>
                        </div>
                    </fieldset>
                    </div>
                </div>
            </div>

            @if (! $task->exists)
                <p class="text-xs text-muted-foreground">
                    {{ __('task.create_invite_after_create_hint') }}
                </p>
            @endif

            <div class="sticky bottom-0 z-20 mt-2 pt-2">
                <div class="ml-auto w-fit rounded-2xl border border-border/55 bg-[color:color-mix(in_srgb,var(--color-card)_44%,transparent)] px-3 py-2.5 backdrop-blur-xl shadow-[0_-10px_24px_color-mix(in_srgb,black_10%,transparent),0_0_18px_color-mix(in_srgb,var(--color-primary)_14%,transparent)]">
                    <div class="flex flex-col-reverse items-center justify-end gap-3 sm:flex-row sm:gap-x-4">
                        <button type="button" @click="$dispatch('close-modal')" class="text-sm font-medium text-muted-foreground transition-colors duration-200 hover:text-foreground">
                            {{ __('task.cancel') }}
                        </button>
                        <button type="submit" class="btn">{{ __('task.save') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</x-modal>
