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

## Bestanden: Prive Word- En Excelbibliotheek

Upload voor deze wijziging:
- `app/Http/Controllers/DocumentController.php`
- `app/Http/Requests/ReplaceDocumentRequest.php`
- `app/Http/Requests/StoreDocumentsRequest.php`
- `app/Models/Document.php`
- `config/filesystems.php`
- `routes/web.php`
- `resources/views/components/layouts/app/header.blade.php`
- `resources/views/admin/documents.blade.php`
- `database/migrations/2026_11_02_130000_create_documents_table.php`
- `database/migrations/2026_11_02_140000_add_folder_path_to_documents_table.php`
- `public/build/css.css`

Voer `php artisan migrate --force` en `php artisan optimize:clear` uit.
De map `storage/app/private/admin-documents` wordt bij de eerste upload aangemaakt en moet schrijfbaar zijn.
Bestanden staan niet op de publieke storage-schijf; maak hiervoor geen extra publieke symlink.
De mapstructuur wordt in de database bewaard; de bestandsinhoud blijft onder willekeurige namen op de private disk staan.
Neem zowel de documents-tabel als deze privemap op in de backups. Overschrijf of verwijder de map niet bij updates.
Er zijn geen nieuwe Composer-pakketten of `.env`-instellingen nodig.

Alleen admins zien **Bestanden**, direct naast **Lief & Leed**, en mogen de pagina, downloads, vervangacties en verwijderacties gebruiken.
De pagina toont een mapverkenner met kruimelpad; bij mapuploads blijven de relatieve submappen behouden.
Een bestand kan worden gedownload, lokaal bewerkt en als nieuwe versie in dezelfde map worden teruggezet. Direct bewerken in Office op de website vereist een aparte Microsoft 365-integratie.
Ondersteund: PDF, DOC, DOCX, XLS en XLSX; maximaal 500 bestanden per upload en 20 MB per bestand.
Controleer de PHP-hostinglimieten: `upload_max_filesize` moet minimaal 20M zijn, `max_file_uploads` minimaal 500,
en `post_max_size` moet boven de totale grootte van een uploadbatch liggen. De hostingprovider kan lagere limieten opleggen.
Mapselectie gebruikt de browserondersteunde `webkitdirectory`-bestandskiezer.
Er is geen automatische import of synchronisatie met Teams/SharePoint. Download de bronbestanden en upload ze op deze pagina.
Verwijder de bronbestanden pas nadat de nieuwe uploads en downloads gecontroleerd zijn.

Controleer na de release:
- Upload een PDF, Word- en Excel-bestand; ze verschijnen samen in de huidige map.
- Upload een complete map met submappen en controleer de mappenstructuur en het kruimelpad.
- Zoek in een map en download een bestand; de oorspronkelijke bestandsnaam blijft behouden.
- Vervang een bestand met een bewerkte versie en controleer dat de map behouden blijft en de nieuwe download klopt.
- Verwijder een testbestand en controleer dat alleen dat bestand verdwijnt.
- Een niet-ondersteund of vermomd bestand, bestand groter dan 20 MB of batch groter dan 500 wordt geweigerd.
- Een normaal lid en een uitgelogde bezoeker kunnen de pagina, download, vervang- en verwijderactie ook niet via een directe URL bereiken.
