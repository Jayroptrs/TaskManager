<x-layout>
    <div class="py-8 md:py-12 max-w-5xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>&larr; {{ __('ui.back') }}</span>
        </a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">{{ __('terms.title') }}</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            {{ __('terms.version') }}
        </p>

        <x-card is="section" hoverable="false" class="p-4 sm:p-6 mt-8">
            <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.identity_title') }}</h2>
            <div class="mt-3 text-sm text-muted-foreground space-y-1">
                <p><strong>{{ __('terms.sections.service_name') }}</strong> {{ __('terms.sections.service_name_value') }}</p>
                <p><strong>{{ __('terms.sections.service_type') }}</strong> {{ __('terms.sections.service_type_value') }}</p>
                <p><strong>{{ __('terms.sections.contact') }}</strong> {{ __('terms.sections.contact_value') }}</p>
                <p><strong>{{ __('terms.sections.email') }}</strong> <a href="mailto:support@jayro.app">support@jayro.app</a></p>
            </div>
        </x-card>

        <div class="mt-6 space-y-6">
            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.applicability_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('terms.sections.applicability_text') }}</p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.account_title') }}</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    @foreach(__('terms.sections.account_items') as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.availability_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('terms.sections.availability_text') }}</p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.ip_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('terms.sections.ip_text') }}</p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.liability_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('terms.sections.liability_text') }}</p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.termination_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('terms.sections.termination_text') }}</p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.law_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('terms.sections.law_text') }}</p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-4 sm:p-6">
                <h2 class="text-xl font-semibold text-foreground">{{ __('terms.sections.changes_title') }}</h2>
                <p class="mt-3 text-sm text-muted-foreground">{{ __('terms.sections.changes_text') }}</p>
            </x-card>
        </div>
    </div>
</x-layout>


