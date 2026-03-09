<x-layout>
    <div class="py-8 md:py-12 max-w-5xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">{{ __('privacy.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            {{ __('privacy.version') }}
        </p>

        <x-card is="section" hoverable="false" class="p-6 mt-8">
            <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.controller_title') }}</h2>
            <div class="mt-3 text-sm text-muted-foreground space-y-1">
                <p><strong>{{ __('privacy.sections.service') }}</strong> {{ __('privacy.sections.service_value') }}</p>
                <p><strong>{{ __('privacy.sections.contact') }}</strong> {{ __('privacy.sections.contact_value') }}</p>
                <p><strong>{{ __('privacy.sections.email') }}</strong> <a href="mailto:privacy@jayro.app">privacy@jayro.app</a></p>
            </div>
        </x-card>

        <div class="mt-6 space-y-6">
            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.data_title') }}</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    @foreach(__('privacy.sections.data_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.purpose_title') }}</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    @foreach(__('privacy.sections.purpose_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.sharing_title') }}</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    @foreach(__('privacy.sections.sharing_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.retention_title') }}</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    @foreach(__('privacy.sections.retention_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.rights_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ __('privacy.sections.rights_text_1', ['email' => 'privacy@jayro.app']) }}
                </p>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ __('privacy.sections.rights_text_2') }}
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.security_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ __('privacy.sections.security_text') }}
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('privacy.sections.changes_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    {{ __('privacy.sections.changes_text') }}
                </p>
            </x-card>
        </div>
    </div>
</x-layout>

