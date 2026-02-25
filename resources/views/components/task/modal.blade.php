@props(['task' => new App\Models\Task()])

<x-modal name="{{ $task->exists ? 'edit-task' : 'create-task' }}" title="{{ $task->exists ? 'Bewerk je taak' : 'Maak een nieuwe taak' }}">
    <form
        x-data="{
        status: @js(old('status', $task->status->value)),
        newLink: '',
        links: @js(old('links', $task->links ?? [])),
        newTag: '',
        tags: @js(old('tags', $task->tags ?? [])),
        newStep: '',
        steps: @js(old('steps', $task->steps->map->only(['id', 'description', 'completed']))),
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
                label="Titel"
                name="title"
                placeholder="Titel van je taak"
                autofocus
                required
                :value="$task->title"/>

            <div class="space-y-2">
                <label for="status" class="label">Status</label>

                <div class="flex flex-wrap gap-2 sm:gap-3">
                    @foreach (App\TaskStatus::cases() as $status)
                        <button
                        type="button"
                        @click="status = @js($status->value)"
                        class="btn flex-1 h-10"
                        :class="{'btn-outlined': status!== @js($status->value)}"
                        >
                            {{ $status->label() }}
                        </button>
                    @endforeach

                    <input hidden type="text" name="status" :value="status" class="input">
                </div>

                <x-form.error name="status" />
            </div>

            <x-form.field
                label="Beschrijving"
                name="description"
                type="textarea"
                placeholder="Beschrijf je taak.."
                :value="$task->description"/>

                <div class="space-y-2">
                    <label for="image" class="label">Uitgelichte afbeelding</label>

                    @if ($task->image_path)
                        <div class="space-y-2">
                            <img src="{{ asset('storage/' . $task->image_path) }}" alt="" class="w-full h-auto object-cover rounded-lg">
                        </div>

                        <button class="btn btn-outlined h-10 w-full" form="delete-image-form">Verwijder afbeelding</button>
                    @endif

                    <input type="file" name="image" accept="image/*">
                    <x-form.error name="image"/>
                </div>

                <div>
                    <fieldset class="space-y-3">
                        <label class="label">Stappen</label>

                        <template x-for="(step, index) in steps" :key="step.id || index">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-x-5">
                                <input
                                    readonly
                                    :name="`steps[${index}][description]`"
                                    :value="step.description"
                                    class="input flex-1 cursor-default opacity-80"
                                >

                                <input
                                    type="hidden"
                                    :name="`steps[${index}][completed]`"
                                    :value="step.completed ? 1 : 0"
                                >

                                <button
                                    class="transition-transform duration-300 hover:scale-125 hover:rotate-180"
                                    type="button"
                                    aria-label="Remove step"
                                    @click="steps.splice(index, 1)"
                                >
                                    ❌
                                </button>
                            </div>
                        </template>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-x-5">
                        <input
                            x-model="newStep"
                            id="new-step"
                            placeholder="Wat zijn de stappen?"
                            class="input flex-1"
                            spellcheck="false"
                            >
                            
                        <button
                            class="transition-all font-bold text-xl text- duration-300 ease-out hover:-translate-y-1
                            hover:scale-110 hover:shadow-[0_10px_25px_rgba(99,102,241,0.5)]
                            border border-border px-3 py-1 rounded-lg w-full sm:w-auto"
                            type="button"
                            @click="steps.push({description: newStep.trim(), completed: false});
                            newStep = '';"
                            :disabled="newStep.trim().length === 0"
                            aria-label="Add a new step"
                            >
                            +
                        </button>
                    </div>
                </fieldset>
            </div>

                <div>
                    <fieldset class="space-y-3">
                        <label class="label">Links</label>

                        <template x-for="(link, index) in links" :key="link">
                            <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-x-5">
                                <input readonly name="links[]" x-model="link" class="input flex-1 cursor-default opacity-80">

                                <button
                                class="transition-transform duration-300 hover:scale-130 hover:rotate-180"
                                type="button"
                                aria-label="Remove link"
                                @click="links.splice(index, 1)"
                                >
                                ❌
                            </button>
                        </div>
                    </template>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-x-5">
                        <input
                            x-model="newLink"
                            type="url"
                            id="new-link"
                            data-test="new-link"
                            placeholder="https://example.com"
                            autocomplete="url"
                            class="input flex-1"
                            spellcheck="false"
                            >
                            
                        <button
                            class="transition-all font-bold text-xl text- duration-300 ease-out hover:-translate-y-1
                            hover:scale-110 hover:shadow-[0_10px_25px_rgba(99,102,241,0.5)]
                            border border-border px-3 py-1 rounded-lg w-full sm:w-auto"
                            type="button" @click="links.push(newLink.trim()); newLink = '';"
                            :disabled="newLink.trim().length === 0"
                            aria-label="Add a new link"
                            >
                            +
                        </button>
                    </div>
                </fieldset>
            </div>

            <div>
                <fieldset class="space-y-3">
                    <label class="label">Tags</label>

                    <template x-for="(tag, index) in tags" :key="`${tag}-${index}`">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-x-5">
                            <input readonly name="tags[]" x-model="tag" class="input flex-1 cursor-default opacity-80">

                            <button
                                class="transition-transform duration-300 hover:scale-130 hover:rotate-180"
                                type="button"
                                aria-label="Remove tag"
                                @click="tags.splice(index, 1)"
                            >
                                ❌
                            </button>
                        </div>
                    </template>

                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:gap-x-5">
                        <input
                            x-model="newTag"
                            type="text"
                            id="new-tag"
                            placeholder="Bijv. saas, marketing, side-project"
                            class="input flex-1"
                            spellcheck="false"
                        >

                        <button
                            class="transition-all font-bold text-xl text- duration-300 ease-out hover:-translate-y-1
                            hover:scale-110 hover:shadow-[0_10px_25px_rgba(99,102,241,0.5)]
                            border border-border px-3 py-1 rounded-lg w-full sm:w-auto"
                            type="button"
                            @click="
                                const value = newTag.trim();
                                if (value.length > 0 && !tags.includes(value)) {
                                    tags.push(value);
                                }
                                newTag = '';
                            "
                            :disabled="newTag.trim().length === 0"
                            aria-label="Add a new tag"
                        >
                            +
                        </button>
                    </div>
                </fieldset>
            </div>
                
            <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 sm:gap-x-5">
                <button type="button" @click="$dispatch('close-modal')">Annuleren</button>
                <button type="submit" class="btn">Opslaan</button>
            </div>
        </div>
    </form>
    
    @if ($task->image_path)
        <form method="POST" action="{{ route('task.image.destroy', $task) }}" id="delete-image-form">
            @csrf
            @method('DELETE')
        </form>
    @endif
</x-modal>
