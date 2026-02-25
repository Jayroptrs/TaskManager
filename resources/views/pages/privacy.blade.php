<x-layout>
    <div class="py-8 md:py-12 max-w-5xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>🡨 Terug</span>
        </a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">Privacyverklaring (AVG)</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Versie: 1.1 - Laatst bijgewerkt: 25-02-2026.
        </p>

        <x-card is="section" hoverable="false" class="p-6 mt-8">
            <h2 class="text-xl font-semibold text-foreground">1. Wie is verwerkingsverantwoordelijke?</h2>
            <div class="mt-3 text-sm text-muted-foreground space-y-1">
                <p><strong>Dienst:</strong> Jayro</p>
                <p><strong>Contact:</strong> via het supportformulier op de supportpagina</p>
                <p><strong>E-mail privacy:</strong> <a href="mailto:privacy@jayro.app">privacy@jayro.app</a></p>
            </div>
        </x-card>

        <div class="mt-6 space-y-6">
            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">2. Welke persoonsgegevens verwerken wij?</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>Accountgegevens: naam, e-mailadres en gehashte wachtwoorden.</li>
                    <li>Gebruikersinhoud: taken, subtaken, tags, links en eventuele afbeeldingen.</li>
                    <li>Technische gegevens: IP-adres, user-agent en sessie-informatie voor beveiliging en werking.</li>
                    <li>Communicatiegegevens: berichten die je aan support stuurt.</li>
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">3. Doeleinden en grondslagen (art. 6 AVG)</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>Uitvoering overeenkomst: account aanmaken, inloggen, taken beheren.</li>
                    <li>Gerechtvaardigd belang: beveiliging, misbruikpreventie en foutanalyse.</li>
                    <li>Wettelijke verplichting: bewaren van gegevens indien wettelijk vereist.</li>
                    <li>Toestemming: alleen waar wettelijk nodig.</li>
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">4. Ontvangers, verwerkers en doorgifte</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>Wij delen gegevens alleen met partijen die nodig zijn voor de dienstverlening, zoals hostingproviders.</li>
                    <li>Bij gebruik van reCAPTCHA (voor niet-ingelogde supportberichten) worden verificatiegegevens gedeeld met Google.</li>
                    <li>Met verwerkers sluiten wij verwerkersovereenkomsten waar nodig.</li>
                    <li>Doorgifte buiten de EER gebeurt alleen met passende waarborgen, zoals Standard Contractual Clauses (indien van toepassing).</li>
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">5. Bewaartermijnen</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>Account- en taakdata: zolang je account actief is.</li>
                    <li>Bij verwijdering van een account worden gekoppelde accountgegevens verwijderd volgens de ingestelde database-relaties.</li>
                    <li>Supportberichten en beveiligingsrelevante metadata bewaren wij niet langer dan noodzakelijk voor afhandeling, beveiliging en wettelijke verplichtingen.</li>
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">6. Jouw privacyrechten</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Je hebt recht op inzage, rectificatie, verwijdering, beperking, overdraagbaarheid van gegevens en bezwaar.
                    Verzoeken kun je indienen via <a href="mailto:privacy@jayro.app">privacy@jayro.app</a> of via het supportformulier.
                    We reageren in principe binnen 1 maand.
                </p>
                <p class="mt-3 text-sm text-muted-foreground">
                    Je hebt ook het recht om een klacht in te dienen bij de Autoriteit Persoonsgegevens.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">7. Beveiliging</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Wij nemen passende technische en organisatorische maatregelen om persoonsgegevens te beschermen tegen verlies,
                    ongeautoriseerde toegang en misbruik.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">8. Wijzigingen</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Wij kunnen deze privacyverklaring wijzigen. De meest actuele versie staat altijd op deze pagina.
                </p>
            </x-card>
        </div>
    </div>
</x-layout>
