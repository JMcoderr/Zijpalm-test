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
Om de Mollie API te kunnen gebruiken moet de API key worden geplaatst in het `.env` bestand: `MOLLIE_KEY=`

Daarnaast moet er tijdens de lokale ontwikkeling iets zoals ngrok gebruikt worden om de betalingen lokaal te doorlopen.