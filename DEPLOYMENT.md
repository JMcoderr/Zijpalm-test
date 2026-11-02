# Deployment Runbook (Strato + FileZilla)

Deze checklist is bedoeld voor releases naar productie via FileZilla.

## Doel
- Veilig deployen met minimale downtime.
- Alleen gewijzigde bestanden uploaden.
- Laravel migraties en cache-opschoning correct uitvoeren.

## Benodigd
- Productie backup (bestanden + database).
- Toegang tot FileZilla.
- Bij voorkeur SSH-toegang voor artisan-commando's (Putty is de voorkeur).

## Nooit overschrijven
- `.env`
- `storage/` (behalve bewust geuploade gebruikersbestanden)
- `node_modules/`

## Release Stappen (aanbevolen)
1. Maak een backup van live.
2. Zet de site in onderhoud (alleen als SSH beschikbaar is):
   - `php artisan down`
3. Upload alleen gewijzigde bestanden via FileZilla.
5. Voer migraties uit:
   - `php artisan migrate --force`
6. Leeg caches:
   - `php artisan optimize:clear`
7. Zet de site weer online:
   - `php artisan up`
8. Controleer direct de belangrijkste pagina's en functionaliteit.


## Rollback Plan
1. Bewaar van tevoren een kopie van alle te wijzigen bestanden in een map zoals `rollback-YYYY-MM-DD`.
2. Bij problemen: oude bestanden terugzetten via FileZilla.
3. Daarna `php artisan optimize:clear` draaien.
4. Bij databaseproblemen: database backup terugzetten.

## Zonder SSH (beperking)
Zonder SSH kun je geen `php artisan migrate --force` en `php artisan optimize:clear` uitvoeren in de shell. 

Veilige alternatieven:
- Gebruik een command-runner in het hostingpaneel (als beschikbaar).
- Laat migrations uitvoeren via SSH door iemand met toegang.

## Medeorganisatoren: Alleen Deelnemers Inzien

Upload voor deze wijziging:
- `app/Http/Controllers/ActivityController.php`
- `app/Http/Controllers/ApplicationController.php`
- `app/Http/Requests/StoreActivityRequest.php`
- `app/Http/Requests/UpdateActivityRequest.php`
- `app/Models/Activity.php`
- `app/Policies/ActivityPolicy.php`
- `routes/web.php`
- `resources/views/activities/create.blade.php`
- `resources/views/activities/edit.blade.php`
- `resources/views/activities/read.blade.php`
- `resources/views/components/activity-organizers.blade.php`
- `database/migrations/2026_11_02_120000_add_organizer_user_ids_to_activities_table.php`
- `public/build/css.css`

Voer daarna `php artisan migrate --force` en `php artisan optimize:clear` uit.
Er is geen wijziging aan `.env` nodig. Upload geen tests of logbestanden.

Een admin selecteert bij een activiteit de accounts onder **Medeorganisatoren**.
Namen in het bestaande tekstveld **Organisator(en)** verlenen bewust geen toegang.
Ook voor bestaande activiteiten moeten de accounts eenmalig geselecteerd worden.
Een lege selectie trekt bestaande rechten in.

Controleer na de release met een geselecteerd niet-adminaccount:
- De deelnemerslijst van de gekoppelde activiteit gebruikt dezelfde weergave als bij admins, met alleen de downloadknop.
- De download is hetzelfde Excel-bestand als bij admins, inclusief betaalbedragen, antwoorden en interne opmerkingen.
- Kostenoverzicht en betaalgegevens op de pagina, overige adminpagina's, wijzigen, verwijderen en herinneringen versturen zijn niet toegankelijk.
- Een andere activiteit geeft geen toegang tot de lijst of download.
- Na verwijderen van de accountselectie is de download direct niet meer toegankelijk.
- Admins behouden hun bestaande overzicht en financiele export.
