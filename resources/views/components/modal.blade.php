@props(['name', 'title'])
<div 
    x-data="{ show: false, name: @js($name) }" 
    x-show="show"
    @open-modal.window="if($event.detail === name) show = true"
    @close-modal="show = false"
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-3 sm:p-4 backdrop-blur-xs"
    @keydown.escape.window="show = false"
    x-transition:enter="ease-out duration-300"
    x-transition:enter-start="opacity-0 -translate-y-8 -translate-x-8"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-250"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0 -translate-y-6 -translate-x-6"
    style="display: none"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-{{ $name }}-title"
    :aria-hidden="!show"
    tabindex="-1"
    >
    <x-card is="div" :hoverable="false" @click.away="show = false" class="modal-scrollbar shadow-xl max-w-2xl w-full max-h-[85dvh] overflow-auto">
        <div class="flex justify-between items-center">
            <h2 id="modal-{{ $name }}-title" class="text-xl font-bold"> {{ $title }}</h2>

            <button
                @click='show = false'
                aria-label="close-modal"
                class="inline-flex size-8 items-center justify-center rounded-full text-muted-foreground transition-all duration-300 hover:scale-110 hover:rotate-180 hover:text-foreground">
                &#10005;
            </button>
        </div>

        <div class="mt-4">
            {{ $slot }}
        </div>
    </x-card>
</div>