<x-layout>
    <div class="py-8 md:py-12 max-w-5xl mx-auto">
        <a href="{{ route('task.index') }}" class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-primary no-link-hover">
            <span>🡨 Terug</span>
        </a>
        <h1 class="mt-3 text-3xl sm:text-4xl font-bold tracking-tight">Algemene voorwaarden</h1>
        <p class="mt-2 text-sm text-muted-foreground">
            Versie: 1.1 - Laatst bijgewerkt: 25-02-2026.
        </p>

        <x-card is="section" hoverable="false" class="p-6 mt-8">
            <h2 class="text-xl font-semibold text-foreground">1. Identiteit aanbieder</h2>
            <div class="mt-3 text-sm text-muted-foreground space-y-1">
                <p><strong>Dienstnaam:</strong> Jayro</p>
                <p><strong>Type dienst:</strong> online takenbord en projectbeheer</p>
                <p><strong>Contact:</strong> via het supportformulier op de supportpagina</p>
                <p><strong>E-mail:</strong> <a href="mailto:support@jayro.app">support@jayro.app</a></p>
            </div>
        </x-card>

        <div class="mt-6 space-y-6">
            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">2. Toepasselijkheid</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Deze voorwaarden zijn van toepassing op elk gebruik van Jayro en op alle overeenkomsten
                    tussen gebruiker en aanbieder van Jayro.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">3. Account en gebruik</h2>
                <ul class="mt-3 list-disc pl-5 space-y-2 text-sm text-muted-foreground">
                    <li>Je bent verantwoordelijk voor geheimhouding van je inloggegevens.</li>
                    <li>Je gebruikt de dienst niet voor onrechtmatige of schadelijke doeleinden.</li>
                    <li>Je plaatst geen content die inbreuk maakt op rechten van derden.</li>
                </ul>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">4. Beschikbaarheid en onderhoud</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Jayro wordt geleverd als online dienst. Wij streven naar continuiteit, maar kunnen
                    geen 100% ononderbroken beschikbaarheid garanderen. Onderhoud kan tijdelijk invloed hebben
                    op toegankelijkheid.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">5. Intellectuele eigendom</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Alle rechten op software, merk, vormgeving en documentatie van Jayro blijven eigendom
                    van de aanbieder. Je ontvangt uitsluitend een beperkt, niet-overdraagbaar gebruiksrecht.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">6. Aansprakelijkheid</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Voor zover wettelijk toegestaan is aansprakelijkheid beperkt tot directe schade en maximaal
                    het bedrag dat in de 12 maanden voorafgaand aan de schade is betaald voor de dienst
                    (of EUR 0 indien gebruik kosteloos is), tenzij sprake is van opzet of bewuste roekeloosheid.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">7. Beeindiging</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Wij mogen toegang blokkeren of beeindigen bij misbruik, schending van deze voorwaarden
                    of wanneer dit noodzakelijk is voor veiligheid en naleving van wet- en regelgeving.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">8. Toepasselijk recht en geschillen</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Op deze voorwaarden is Nederlands recht van toepassing. Geschillen worden voorgelegd
                    aan de bevoegde rechter in Nederland, tenzij dwingend consumentenrecht anders bepaalt.
                </p>
            </x-card>

            <x-card is="section" hoverable="false" class="p-6">
                <h2 class="text-xl font-semibold text-foreground">9. Wijzigingen</h2>
                <p class="mt-3 text-sm text-muted-foreground">
                    Wij kunnen deze voorwaarden wijzigen. De meest actuele versie staat altijd op deze pagina.
                    Bij wezenlijke wijzigingen informeren wij gebruikers binnen de applicatie.
                </p>
            </x-card>
        </div>
    </div>
</x-layout>
