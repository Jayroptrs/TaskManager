<x-layout>
    <x-form :title="__('auth.login_title')" :description="__('auth.login_description')">
        <form action="/login" method="POST" class="mt-5 space-y-6">
                @csrf
                <x-form.field :placeholder="__('auth.email_placeholder')" :label="__('auth.email')" name="email" type="email" />
                <x-form.field :placeholder="__('auth.password_placeholder')" :label="__('auth.password')" name="password" type="password" />

                <p class="text-sm text-muted-foreground">
                    {{ __('auth.login_help') }}
                    <a href="{{ route('support') }}" class="font-medium text-foreground hover:text-primary">{{ __('auth.login_help_link') }}</a>.
                </p>
                
                <button type="submit" class="btn mt-4 h-10 w-full sm:w-auto sm:ml-auto block" data-test="login-button">{{ __('auth.login_button') }}</button>
        </form>
        <x-slot:outside>
            {{ __('auth.no_account') }}
            <a href="{{ url('/register') }}" class="font-medium text-foreground hover:text-primary">{{ __('auth.register_button') }}</a>
        </x-slot:outside>
    </x-form>
</x-layout>
