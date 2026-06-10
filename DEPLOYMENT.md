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

