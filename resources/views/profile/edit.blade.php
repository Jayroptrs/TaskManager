<x-layout>
    <x-form title="Wijzig je account" description="Iets aanpassen?" max-width="max-w-4xl" :back-href="route('task.index')">
        <form action="/profile" method="POST" class="mt-6 space-y-6">
            @csrf
            @method('PATCH')
            <div class="grid gap-6 lg:grid-cols-2">
                <section class="space-y-4 rounded-lg border border-border/70 p-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Accountgegevens</h2>
                    <x-form.field placeholder="Naam.." label="Naam" name="name" :value="$user->name" />
                    <x-form.field placeholder="Email.." label="Email" name="email" type="email" :value="$user->email" />
                </section>

                <section class="space-y-4 rounded-lg border border-border/70 p-4">
                    <h2 class="text-sm font-semibold uppercase tracking-wide text-muted-foreground">Wachtwoord wijzigen</h2>
                    <x-form.field placeholder="Huidig wachtwoord.." label="Huidig wachtwoord" name="current_password" type="password" />
                    <x-form.field placeholder="Wachtwoord.." label="Nieuw wachtwoord" name="password" type="password" />
                    <x-form.field placeholder="Herhaal nieuw wachtwoord.." label="Bevestig nieuw wachtwoord" name="password_confirmation" type="password" />
                </section>
            </div>

            <div class="flex justify-end pt-1">
                <button type="submit" class="btn h-10">Opslaan</button>
            </div>
        </form>
    </x-form>
</x-layout>
