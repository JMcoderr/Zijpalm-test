# Zijpalm
De nieuwe Laravel 12 webapplicatie voor Zijpalm  

## Hosten
Om de app te draaien zijn er een aantal dependencies die je installeert met de volgende commandos:   
`composer install`  
`npm install`

Voor het live zetten via FileZilla/Strato, gebruik de checklist in `DEPLOYMENT.md`.

Voor het live draaien is het belangrijk dat er een npm build wordt gedaan:  
`npm run build`  

Om de app lokaal te draaien kan het volgende commando worden gebruikt:  
`composer run dev`

## Migrations
Voor het vullen van de database zijn er seeders gemaakt.  

Draai het volgende commando om de database te migreren en te seeden: `php artisan migrate:fresh --seed`  

Er wordt automatisch testdata gegenereerd als de `APP_ENV` op "local" staat  

In productie kan de databaseseeder worden aangeroepen en als de `APP_ENV` is aangepast naar "production" in het `.env` bestand wordt er alleen een standaard administrator gebruiker en de aanpasbare content op de website    

De database gegevens moeten natuurlijk ook ingevuld zijn in het `.env` bestand:  

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=zijpalm
DB_USERNAME=root
DB_PASSWORD=
```

## E-mail
Alle e-mails worden direct verstuurdt. Er wordt dus geen gebruik gemaakt van een queue.  

Om e-mails te versturen moeten de volgende variabelen in de `.env` worden aangepast:  

```env
MAIL_MAILER=smtp
MAIL_SCHEME=null
MAIL_HOST=127.0.0.1
MAIL_PORT=2525
MAIL_USERNAME=
MAIL_PASSWORD=


#POWERAUTOMATE VALUES
#MAIL_DELAY_MIN=
#MAIL_DELAY_MAX=
#BATCH_SIZE_MIN=
#BATCH_SIZE_MAX=

#MAIL_DELAY=
#BATCH_SIZE=

BESTUUR_EMAIL=zijpalm@almere.nl
BESTUUR_NAME="Zijpalm Bestuur"
```

De huidige mailserver-instellingen zijn zo ingesteld dat de SMTP-server van Strato wordt gebruikt. Voor lokale development wordt een sandbox gebruikt, zoals Docker.

De standaardwaarden voor het versturen naar Power Automate en de standaardwaarden die Power Automate zelf gebruikt kunnen hier worden aangepast. Wanneer de mail- en batchwaarden niet zijn opgegeven in de .env of buiten de ingestelde limieten vallen, wordt automatisch teruggevallen op de standaardwaarden die in de applicatie zelf zijn vastgelegd. Indien nodig kan een beheerder deze waarden eenmalig via de website voor een specifieke e-mail aanpassen (wordt niet opgeslagen in de .env/applicatie).

De waarden bij Bestuur gaan over het e-mailadres waarop Power Automate is ingesteld en van waaruit verdere acties worden uitgevoerd.

### Power Automate: inkomende mail doorsturen
Voor het automatisch doorsturen van alles wat binnenkomt op `zijpalm@almere.nl` naar `hans@zijpalm.nl`, maak deze flow in Power Automate:

1. Ga naar Power Automate en klik op **Create** > **Automated cloud flow**.
2. Geef de flow naam: `Zijpalm - Inbox Forward naar Hans`.
3. Kies trigger: **Office 365 Outlook - When a new email arrives (V3)**.
4. Stel bij de trigger in:
	- `Original Mailbox Address`: `zijpalm@almere.nl`
	- `Folder`: `Inbox`
	- `Only with Attachments`: `No` (alles meenemen)
	- `Include Attachments`: `Yes`
5. Voeg actie toe: **Office 365 Outlook - Forward an email (V2)**.
6. Stel bij de actie in:
	- `Message Id`: kies dynamische waarde **Message Id** uit de trigger
	- `To`: `hans@zijpalm.nl`
	- `Comment`: `Automatisch doorgestuurd vanaf zijpalm@almere.nl`
7. Sla de flow op en test met een testmail naar `zijpalm@almere.nl`.

Tip: als je ook een kopie naar bestuur wilt houden, voeg dan in de Forward-actie een extra ontvanger toe in `Cc` of maak een tweede actie.

## Mollie
### Webwinkel (Shop/Overig)
De desktop- en mobiele navigatie openen de volledige webwinkel. Producten en shopactiviteiten hebben aparte overzichten; de oude handdoekenlink verwijst naar het productoverzicht. Bij activiteiten staat het aantal resterende plaatsen, bij producten de voorraad. Beide prijzen zijn zichtbaar, ook zonder inloggen.

Beheerders gebruiken **Toevoegen** om meerdere producten of activiteiten aan te maken. Prijzen accepteren een punt of komma met maximaal twee decimalen. Een lege niet-ledenprijs gebruikt de ledenprijs. Activiteitsvelden zijn alleen van toepassing op activiteiten. **Verbergen voor bezoekers** houdt een item zichtbaar voor beheerders, maar onzichtbaar en niet bestelbaar voor bezoekers. Kopieen beginnen verborgen en moeten eerst gepubliceerd worden.

Gastbestellingen zijn alleen mogelijk als **Niet-leden mogen ook bestellen** is aangevinkt. De bestelling bewaart de gebruikte prijs per stuk, zodat latere prijswijzigingen bestaande bestellingen niet veranderen. Zowel betaalde als nog openstaande bestellingen reserveren plaatsen; mislukte/geannuleerde betalingen niet.

Het detailpaneel gebruikt Tailwind, zonder aanvullende regels in de centrale stylesheet. Op desktop staat links de product-/activiteitsinformatie en rechts het bestel-/aanmeldpaneel, in gelijke, bovenaan uitgelijnde kolommen met de bestaande blauwe Zijpalm-stijl. Op mobiel staat de informatie boven het formulier. Producten hebben **Bestellen**, activiteiten **Aanmelden** met **Aantal deelnemers**. De stappen zijn een genummerde lijst zonder nummerbubbels, met een apart vlak per stap. De aantalkeuze is een licht, afgerond bedieningsvlak met gecentreerde plus-/minknoppen van 48 pixels en een wit handmatig invoerveld; uitgeschakelde knoppen blijven herkenbaar. Met de plus-/minknoppen of het aantalveld verandert het totaal direct mee. Ongeldige aantallen worden expliciet aangegeven, niet stilzwijgend aangepast; de server blijft aantallen, voorraad en beschikbare plaatsen controleren. Gesloten aanmeldperiodes tonen een melding in plaats van een onbruikbaar aanmeldformulier. Upload na een wijziging van de Tailwind-opmaak ook de opnieuw gebouwde CSS.

Beide detailpanelen hebben een blauwe achtergrond met een blauw verloop en lichte tekst. De vaste blauwe achtergrond blijft zichtbaar als een browser het verloop niet ondersteunt. De stappen staan in twee rustige, omrande vlakken: naast elkaar op grotere schermen en onder elkaar op mobiel. Upload beide deelviews samen met de gebouwde CSS en wis zo nodig de Blade-cache met `php artisan view:clear`. `AppServiceProvider` voegt een inhoudshash toe aan de CSS-URL van Vite, zodat een gewijzigde upload van `public/build/css.css` automatisch een nieuwe browsercache-versie krijgt. JavaScript-URL's en de Vite-ontwikkelserver blijven ongewijzigd.

Een betaalde webshopbestelling verstuurt een klantbevestiging via het bestaande Power Automate-formaat en een afzonderlijke bestuursmelding naar `BESTUUR_EMAIL`. De bestuursmelding bevat de contactgegevens van de besteller. De klantmail gebruikt **Je bestelling**, vermeldt een totaalbedrag en bij producten de ophaalinstructie. Een mailfout laat de webhook mislukken en rolt de databasewijziging terug, zodat Mollie opnieuw kan proberen. Als een van de twee mails al verzonden was voor de fout, kan die mail bij een herhaling dubbel aankomen.

Onder **Bestellingen bekijken** kunnen beheerders filteren op producten/activiteiten en een specifiek item. **Download Excel** gebruikt dezelfde filters en exporteert contactgegevens, status, aantallen en historische prijzen. Bij een gemengde bestelling bevat een gefilterde export alleen de passende bestelregels; **Bestelling totaal** blijft het totaal van de hele bestelling.

Er zijn geen nieuwe migraties of configuratie-instellingen nodig. Bouw met `npm run build` en upload ook de gewijzigde CSS in `public/build`. Wis na het uploaden de route- en viewcache (`php artisan route:clear`, `php artisan view:clear`). Draai geen seeders op productie: bestaande mailteksten worden bij het renderen aangepast zonder aangepaste teksten te overschrijven. Controleer op productie met een Mollie-testbetaling ook de SMTP/Power Automate-aflevering.

### Aanmeldingen handmatig corrigeren
Beheerders kunnen op de activiteitpagina naast **Deelnemer toevoegen** op het potloodknopje **Aanmeldingen bewerken** klikken. Zoek een deelnemer, kies **Aangemeld**, **Reserve** of **In afwachting van betaling** en sla de wijzigingen op. Gasten volgen de status van de bijbehorende aanmelding.

Dit is een statuscorrectie: betalingen worden niet als betaald gemarkeerd, betaallinks worden niet geannuleerd en er worden geen e-mails verstuurd of bedragen teruggestort. Voor afmelden met eventuele terugbetaling blijft de prullenbak beschikbaar. Reserves worden tijdens het opslaan niet automatisch doorgeschoven; de bestaande doorschuifprocedure blijft beschikbaar bij een volgende annulering. Betalingsupdates plaatsen een reserve niet terug op de deelnemerslijst. Bij later doorschuiven wordt rekening gehouden met reeds betaalde, niet-teruggestorte bedragen.

Voor deze functie zijn geen migraties nodig. Bouw de frontend met `npm run build`, upload de gewijzigde PHP- en Blade-bestanden en de gebouwde CSS/manifest in `public/build`, en wis indien nodig de route- en viewcache (`php artisan route:clear` en `php artisan view:clear`).

Om de Mollie API te kunnen gebruiken moet de API key worden geplaatst in het `.env` bestand: `MOLLIE_KEY=`

Daarnaast moet er tijdens de lokale ontwikkeling iets zoals ngrok gebruikt worden om de betalingen lokaal te doorlopen.