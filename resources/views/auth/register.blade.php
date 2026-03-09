<x-layout>
    <x-form :title="__('auth.register_title')" :description="__('auth.register_description')">
        <form action="/register" method="POST" class="mt-5 space-y-6">
                @csrf
                <x-form.field :placeholder="__('auth.name_placeholder')" :label="__('auth.name')" name="name" />
                <x-form.field :placeholder="__('auth.email_placeholder')" :label="__('auth.email')" name="email" type="email" />
                <x-form.field :placeholder="__('auth.password_placeholder')" :label="__('auth.password')" name="password" type="password" />
                <x-form.field :placeholder="__('auth.password_confirmation_placeholder')" :label="__('auth.password_confirmation')" name="password_confirmation" type="password" />
                
                <button type="submit" class="btn mt-4 h-10 w-full sm:w-auto sm:ml-auto block">{{ __('auth.register_button') }}</button>

                <p class="text-sm text-muted-foreground">
                    {{ __('auth.register_help') }}
                    <a href="{{ route('support') }}" class="font-medium text-foreground hover:text-primary">{{ __('auth.register_help_link') }}</a>.
                </p>
        </form>
        <x-slot:outside>
            {{ __('auth.have_account') }}
            <a href="{{ route('login') }}" class="font-medium text-foreground hover:text-primary">{{ __('auth.login_button') }}</a>
        </x-slot:outside>
    </x-form>
</x-layout>
