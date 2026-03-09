@props(['task' => new App\Models\Task()])
@php
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
            : true;
    } else {
        $initialRemindersEnabled = in_array((string) $initialRemindersEnabledRaw, ['1', 'true', 'on'], true);
    }
@endphp

<x-modal name="{{ $task->exists ? 'edit-task' : 'create-task' }}" title="{{ $task->exists ? __('task.modal_edit_title') : __('task.modal_create_title') }}" maxWidth="max-w-4xl">
    <form
        x-data="{
        status: @js(old('status', $task->status->value)),
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
        }"
        method="POST"
        action="{{ $task->exists ? route('task.update', $task) : route('task.store') }}"
        enctype="multipart/form-data"
        >
        @csrf

        @if($task->exists)
            @method('PATCH')
        @endif

        <div class="space-y-6">
            <x-form.field
                :label="__('task.title_label')"
                name="title"
                :placeholder="__('task.title_placeholder')"
                autofocus
                required
                :value="$task->title"/>

            <div class="space-y-2">
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

            <div class="flex flex-col gap-4 lg:flex-row">
                <div
                    class="w-full transition-all duration-300 ease-out"
                    :class="String(dueDate ?? '').trim() !== '' ? 'lg:w-1/2' : 'lg:w-full'"
                >
                    <x-form.field
                        :label="__('task.due_date_label')"
                        name="due_date"
                        type="date"
                        :value="$task->due_date?->toDateString()"
                        x-model="dueDate"
                        x-bind:class="String(dueDate ?? '').trim() !== '' ? 'is-filled' : ''"
                    />
                </div>

                <div
                    class="space-y-2 w-full lg:w-1/2"
                    x-show="String(dueDate ?? '').trim() !== ''"
                    x-transition:enter="transition-all duration-300 ease-out"
                    x-transition:enter-start="opacity-0 -translate-x-3"
                    x-transition:enter-end="opacity-100 translate-x-0"
                    x-transition:leave="transition-all duration-200 ease-in"
                    x-transition:leave-start="opacity-100 translate-x-0"
                    x-transition:leave-end="opacity-0 -translate-x-3"
                    x-cloak
                >
                    <label class="label">{{ __('task.reminders_label') }}</label>
                    <div class="rounded-xl border border-border/80 bg-card/70 p-2.5">
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

            <x-form.field
                :label="__('task.description_label')"
                name="description"
                type="textarea"
                :placeholder="__('task.description_placeholder')"
                :value="$task->description"/>

                <div class="space-y-2">
                    <label for="image" class="label">{{ __('task.image_label') }}</label>

                    @if ($task->imageUrl())
                        <div class="space-y-2">
                            <img src="{{ $task->imageUrl() }}" alt="" class="w-full h-auto object-cover rounded-lg">
                        </div>

                        <button class="btn btn-outlined h-10 w-full" form="delete-image-form">{{ __('task.remove_image') }}</button>
                    @endif

                    <input type="file" name="image" accept="image/*">
                    <x-form.error name="image"/>
                </div>

                <div>
                    <fieldset class="space-y-3">
                        <label class="label">{{ __('task.steps') }}</label>

                        <template x-for="(step, index) in steps" :key="step.id || index">
                            <div class="flex items-start gap-3">
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
                                    class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border text-lg font-bold leading-none transition-transform duration-300 hover:scale-125 hover:rotate-180"
                                    type="button"
                                    :aria-label="@js(__('task.remove_step'))"
                                    @click="steps.splice(index, 1)"
                                >
                                    &#10006;
                                </button>
                            </div>
                        </template>

                    <div class="flex items-center gap-3">
                        <x-form.input
                            type="text"
                            x-model="newStep"
                            id="new-step"
                            placeholder="{{ __('task.steps_placeholder') }}"
                            class="flex-1"
                            spellcheck="false"
                        />

                        <button
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border text-lg font-bold leading-none transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-105 hover:shadow-[0_10px_25px_rgba(99,102,241,0.35)]"
                            type="button"
                            @click="steps.push({description: newStep.trim(), completed: false, assigned_user_id: null});
                            newStep = '';"
                            :disabled="newStep.trim().length === 0"
                            :aria-label="@js(__('task.add_step'))"
                            >
                            +
                        </button>
                    </div>
                </fieldset>
            </div>

                <div>
                    <fieldset class="space-y-3">
                        <label class="label">{{ __('task.links') }}</label>

                        <template x-for="(link, index) in links" :key="link">
                            <div class="flex items-center gap-3">
                                <x-form.input type="text" readonly name="links[]" x-model="link" class="flex-1 cursor-default opacity-80" />

                                <button
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border text-lg font-bold leading-none transition-transform duration-300 hover:scale-130 hover:rotate-180"
                                type="button"
                                :aria-label="@js(__('task.remove_link'))"
                                @click="links.splice(index, 1)"
                                >
                                &#10006;
                            </button>
                        </div>
                    </template>

                    <div class="flex items-center gap-3">
                        <x-form.input
                            type="url"
                            x-model="newLink"
                            id="new-link"
                            data-test="new-link"
                            placeholder="https://example.com"
                            autocomplete="url"
                            class="flex-1"
                            spellcheck="false"
                        />

                        <button
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border text-lg font-bold leading-none transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-105 hover:shadow-[0_10px_25px_rgba(99,102,241,0.35)]"
                            type="button" @click="links.push(newLink.trim()); newLink = '';"
                            :disabled="newLink.trim().length === 0"
                            :aria-label="@js(__('task.add_link'))"
                            >
                            +
                        </button>
                    </div>
                </fieldset>
            </div>

            <div>
                <fieldset class="space-y-3">
                    <label class="label">{{ __('task.tags') }}</label>

                    <template x-for="(tag, index) in tags" :key="`${tag}-${index}`">
                        <div class="flex items-center gap-3">
                            <x-form.input type="text" readonly name="tags[]" x-model="tag" class="flex-1 cursor-default opacity-80" />

                            <button
                                class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border text-lg font-bold leading-none transition-transform duration-300 hover:scale-130 hover:rotate-180"
                                type="button"
                                :aria-label="@js(__('task.remove_tag'))"
                                @click="tags.splice(index, 1)"
                            >
                                &#10006;
                            </button>
                        </div>
                    </template>

                    <div class="flex items-center gap-3">
                        <x-form.input
                            type="text"
                            x-model="newTag"
                            id="new-tag"
                            placeholder="{{ __('task.tags_placeholder') }}"
                            class="flex-1"
                            spellcheck="false"
                        />

                        <button
                            class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-border text-lg font-bold leading-none transition-all duration-300 ease-out hover:-translate-y-1 hover:scale-105 hover:shadow-[0_10px_25px_rgba(99,102,241,0.35)]"
                            type="button"
                            @click="
                                const value = newTag.trim();
                                if (value.length > 0 && !tags.includes(value)) {
                                    tags.push(value);
                                }
                                newTag = '';
                            "
                            :disabled="newTag.trim().length === 0"
                            :aria-label="@js(__('task.add_tag'))"
                        >
                            +
                        </button>
                    </div>
                </fieldset>
            </div>

            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-x-5">
                <button type="button" @click="$dispatch('close-modal')">{{ __('task.cancel') }}</button>
                <button type="submit" class="btn">{{ __('task.save') }}</button>
            </div>

            @if (! $task->exists)
                <p class="text-xs text-muted-foreground">
                    {{ __('task.create_invite_after_create_hint') }}
                </p>
            @endif
        </div>
    </form>
    
    @if ($task->image_path)
        <form method="POST" action="{{ route('task.image.destroy', $task) }}" id="delete-image-form">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-modal>



