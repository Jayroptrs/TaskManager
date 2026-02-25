<x-layout>
    <x-form title="Registreer een account" description="Begin vandaag met het bijhouden van je taken!">
        <form action="/register" method="POST" class="mt-5 space-y-6">
                @csrf
                <x-form.field placeholder="Naam.." label="Naam" name="name" />
                <x-form.field placeholder="Email.." label="Email" name="email" type="email" />
                <x-form.field placeholder="Wachtwoord.." label="Wachtwoord" name="password" type="password" />
                <x-form.field placeholder="Herhaal wachtwoord.." label="Bevestig wachtwoord" name="password_confirmation" type="password" />
                
                <button type="submit" class="btn mt-4 h-10 w-full sm:w-auto sm:ml-auto block">Registreer</button>
        </form>
    </x-form>
</x-layout>
