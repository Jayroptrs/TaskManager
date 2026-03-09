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
                        <h2 class="text-base font-semibold text-foreground">{{ $task->title }}</h2>
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
    </div>
</x-layout>
