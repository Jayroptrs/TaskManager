<x-layout>
    <div class="py-6 sm:py-8 max-w-4xl mx-auto">
        <div class="flex flex-col gap-4 sm:flex-row sm:justify-between sm:items-center">
            <a href="{{ route('task.index') }}" class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-primary transition-colors duration-200">
                <span>&larr; Terug</span>
            </a>

            <div class="flex flex-wrap items-center gap-2 sm:gap-3">
                <button
                    x-data
                    @click="$dispatch('open-modal', 'edit-task')"
                    class="btn btn-outlined w-full sm:w-auto transition-all duration-200 ease-out hover:-translate-y-0.5 hover:shadow-[0_6px_16px_rgba(0,100,100,0.25)]">
                    &#x270F;&#xFE0F; Bewerk taak
                </button>

                <form method="POST" action="{{ route('task.destroy', $task) }}" class="w-full sm:w-auto">
                    @csrf
                    @method('DELETE')

                    <button
                        class="btn btn-danger-outlined w-full sm:w-auto transition-all duration-200 ease-out hover:-translate-y-0.5">
                        &#x1F5D1;&#xFE0F; Verwijder taak
                    </button>
                </form>
            </div>
        </div>

        <div class="mt-8 space-y-6">
            @if ($task->image_path)
                <div class="rounded-lg overflow-hidden">
                    <img src="{{ asset('storage/' . $task->image_path) }}" alt="" class="w-full h-auto object-cover">
                </div>
            @endif

            <h1 class="font-bold text-3xl sm:text-4xl">{{ $task->title }}</h1>

            <div class="mt-2 flex gap-x-3 items-center">
                <x-task.status-label :status="$task->status->value">{{ $task->status->label() }}</x-task.status-label>
                <div class="text-muted-foreground text-sm">{{ $task->created_at->diffForHumans() }}</div>
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
                    <h3 class="font-bold text-xl mt-6">Subtaken</h3>

                    <div class="mt-3 space-y-4">
                        @foreach ($task->steps as $step)
                            <x-card class="text-primary font-medium flex gap-x-3 items-center">
                                <form method="POST" action="{{ route('step.update', $step) }}">
                                    @csrf
                                    @method('PATCH')
                                    <div class="flex items-center gap-x-3">
                                        <button type="submit" role="checkbox" class="size-5 flex items-center justify-center rounded-lg text-primary-foreground border border-primary {{ $step->completed ? 'bg-primary' : 'border border-primary' }}">&check;</button>
                                        <span class="{{ $step->completed ? 'line-through text-muted-foreground' : 'text-white' }}">{{ $step->description }}</span>
                                    </div>
                                </form>
                            </x-card>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (!empty($task->links) && count($task->links))
                <div>
                    <h3 class="font-bold text-xl mt-6">Links</h3>

                    <div class="mt-3 space-y-4">
                        @foreach ($task->links as $link)
                            <x-card :href="$link" class="text-primary font-medium flex gap-x-3 items-center">
                                <p>Link</p>
                                {{ $link }}
                            </x-card>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        <x-task.modal :task="$task" />
    </div>
</x-layout>
