<x-layout>
    <x-form title="Inloggen" description="Leuk dat je er bent!">
        <form action="/login" method="POST" class="mt-5 space-y-6">
                @csrf
                <x-form.field placeholder="Email.." label="Email" name="email" type="email" />
                <x-form.field placeholder="Wachtwoord.." label="Wachtwoord" name="password" type="password" />

                <p class="text-sm text-muted-foreground">
                    Problemen met inloggen?
                    <a href="{{ route('support') }}" class="font-medium text-foreground hover:text-primary">Neem contact op via support</a>.
                </p>
                
                <button type="submit" class="btn mt-4 h-10 w-full sm:w-auto sm:ml-auto block" data-test="login-button">Inloggen</button>
        </form>
    </x-form>
</x-layout>
