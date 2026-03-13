<x-layout>
    <div class="py-8 md:py-12 max-w-6xl mx-auto">
        <a href="{{ route('admin.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>

        <h1 class="mt-3 break-words text-3xl font-bold tracking-tight text-foreground">
            {{ __('admin.user_tasks_title', ['name' => $targetUser->name]) }}
        </h1>
        <p class="mt-2 break-all text-sm text-muted-foreground sm:break-normal">
            {{ $targetUser->email }} &middot; {{ __('admin.user_tasks_count', ['count' => $tasks->total()]) }}
        </p>

        <div class="mt-6 grid gap-4 md:grid-cols-2">
            @forelse ($tasks as $task)
                <x-card href="{{ route('task.show', $task) }}" class="p-4">
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-base font-semibold text-foreground">{{ $task->title }}</h2>
                            <p class="mt-1 text-xs text-muted-foreground">
                                @if ($task->user_id === $targetUser->id)
                                    {{ __('admin.user_task_role_owner') }}
                                @else
                                    {{ __('admin.user_task_role_collaborator') }}
                                @endif
                                &middot;
                                {{ __('admin.task_owner_name', ['name' => $task->user?->name ?? '-']) }}
                            </p>
                        </div>
                        <x-task.status-label :status="$task->status->value">{{ $task->status->label() }}</x-task.status-label>
                    </div>

                    <p class="mt-2 line-clamp-3 text-sm text-muted-foreground">
                        {{ $task->description ?: __('task.no_description') }}
                    </p>

                    <div class="mt-3 flex items-center justify-between text-xs text-muted-foreground">
                        <span>{{ __('admin.collaborators_count', ['count' => $task->collaborators_count]) }}</span>
                        <span>{{ $task->created_at->diffForHumans() }}</span>
                    </div>
                </x-card>
            @empty
                <x-card is="div" hoverable="false" class="p-6 md:col-span-2">
                    <p class="text-sm text-muted-foreground">{{ __('admin.user_tasks_empty') }}</p>
                </x-card>
            @endforelse
        </div>

        <div class="mt-5">
            {{ $tasks->links() }}
        </div>

        <section class="mt-8 rounded-2xl border border-border/80 bg-[linear-gradient(165deg,color-mix(in_srgb,var(--color-card)_96%,transparent),color-mix(in_srgb,var(--color-input)_12%,var(--color-card)))] p-4 sm:p-5 shadow-[0_12px_28px_color-mix(in_srgb,black_8%,transparent)]">
            <div class="flex items-center justify-between gap-2">
                <h2 class="text-lg font-semibold text-foreground">{{ __('admin.user_audit_title') }}</h2>
                <span class="text-xs text-muted-foreground">{{ __('admin.user_audit_count', ['count' => $auditLogs->count()]) }}</span>
            </div>
            <p class="mt-1 text-sm text-muted-foreground">{{ __('admin.user_audit_subtitle') }}</p>

            <div class="mt-4 space-y-2.5">
                @forelse ($auditLogs as $auditLog)
                    @php
                        $metadata = $auditLog->metadata ?? [];
                        $taskTitle = (string) (data_get($metadata, 'task_title') ?: '#'.data_get($metadata, 'task_id'));
                        $activityKey = 'task.activity_'.$auditLog->action;
                        $activityText = \Illuminate\Support\Facades\Lang::has($activityKey)
                            ? __($activityKey, [
                                'from' => data_get($metadata, 'from', '-'),
                                'to' => data_get($metadata, 'to', '-'),
                                'step' => data_get($metadata, 'step', '-'),
                                'collaborator' => data_get($metadata, 'collaborator', '-'),
                                'invitee' => data_get($metadata, 'invitee', '-'),
                            ])
                            : __('admin.audit_action_generic', ['action' => $auditLog->action]);
                        $message = __('admin.audit_task_activity', [
                            'task' => $taskTitle,
                            'activity' => $activityText,
                        ]);
                    @endphp
                    <article class="rounded-xl border border-border/70 bg-card/70 p-3">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <p class="text-sm text-foreground">{{ $message }}</p>
                            <p class="text-xs text-muted-foreground">{{ $auditLog->created_at?->diffForHumans() }}</p>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ __('admin.audit_actor', ['name' => $auditLog->actor?->name ?? __('admin.audit_actor_system')]) }}
                            @if ($auditLog->actor?->email)
                                &middot; {{ $auditLog->actor->email }}
                            @endif
                        </p>
                    </article>
                @empty
                    <div class="rounded-xl border border-border/70 bg-card/70 p-4 text-sm text-muted-foreground">
                        {{ __('admin.user_audit_empty') }}
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-layout>
