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

## Mollie
Om de Mollie API te kunnen gebruiken moet de API key worden geplaatst in het `.env` bestand: `MOLLIE_KEY=`

Daarnaast moet er tijdens de lokale ontwikkeling iets zoals ngrok gebruikt worden om de betalingen lokaal te doorlopen.