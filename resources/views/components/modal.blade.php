@props(['name', 'title', 'maxWidth' => 'max-w-2xl'])
<div 
    x-data="{ show: false, name: @js($name) }" 
    x-init="
        $watch('show', value => {
            const body = document.body;
            const key = 'modalOpenCount';
            let count = Number.parseInt(body.dataset[key] ?? '0', 10);

            if (value) {
                count += 1;
                body.dataset[key] = String(count);
                body.classList.add('overflow-hidden');
                return;
            }

            count = Math.max(0, count - 1);
            if (count === 0) {
                delete body.dataset[key];
                body.classList.remove('overflow-hidden');
            } else {
                body.dataset[key] = String(count);
            }
        });
    "
    x-show="show"
    @open-modal.window="if($event.detail === name) show = true"
    @close-modal="show = false"
    class="fixed inset-0 z-[120] flex items-center justify-center bg-black/50 p-3 sm:p-4 backdrop-blur-xs"
    @keydown.escape.window="show = false"
    x-transition:enter="ease-out duration-180"
    x-transition:enter-start="opacity-100"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-250"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-100"
    style="display: none"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-{{ $name }}-title"
    :aria-hidden="!show"
    tabindex="-1"
    >
    <div
        class="w-full flex justify-center"
        x-show="show"
        x-transition:enter="ease-out duration-300"
        x-transition:enter-start="opacity-0 -translate-y-8 -translate-x-8"
        x-transition:enter-end="opacity-100 translate-y-0 translate-x-0"
        x-transition:leave="ease-in duration-250"
        x-transition:leave-start="opacity-100 translate-y-0 translate-x-0"
        x-transition:leave-end="opacity-0 -translate-y-6 -translate-x-6"
    >
    <x-card
        is="div"
        :hoverable="false"
        @click.away="show = false"
        class="modal-scrollbar shadow-xl {{ $maxWidth }} w-full max-h-[85dvh] overflow-auto"
    >
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
</div>




