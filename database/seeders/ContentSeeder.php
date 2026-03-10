<?php

namespace Database\Seeders;

use App\FileType;
use App\Models\Content;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ContentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $email = config('mail.bestuur.address');
        $name = config('mail.bestuur.name');

        // Default
        // Content::create([
        //     'type' => 'text',
        //     'name' => 'home',
        //     'title' => 'Home',
        //     'text' => 'Welkom',
        //     'filePath' => null,
        //     'fileType' => null,
        // ]);

        // Content for the homepage
        Content::create([
            'type' => 'text',
            'name' => 'homepage-banner',
            'title' => 'Personeelsvereniging Zijpalm',
            'text' => '{"time":1746621250787,"blocks":[{"id":"pCtcQsZTRb","type":"header","data":{"text":"Personeelsvereniging van de Gemeente Almere","level":4}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'homepage-info',
            'title' => 'Wie zijn wij?',
            'text' => '{"time":1746621406186,"blocks":[{"id":"ZBzNB6msuM","type":"paragraph","data":{"text":"De personeelsvereniging ‘Zijpalm’ is opgericht in 1979 en is een actieve vereniging die regelmatig activiteiten organiseert voor haar leden. Voorbeelden zijn diverse (wekelijkse) sportactiviteiten, culturele voorstellingen, creatieve workshops, diverse clinic’s, stedentrips, activiteiten in de natuur, etc."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // In order to grab the email provided in the env, it has to be concatenated here first.
        $activityIdeaText = '{"time":1746621785856,"blocks":[{"id":"EZ9CHsp0HM","type":"paragraph","data":{"text":"<a href=\"mailto:'. $email . '?subject=Niew Idee voor Zijpalm\">Dien je idee in!</a>"}}],"version":"2.31.0-rc.7"}';

        Content::create([
            'type' => 'text',
            'name' => 'homepage-activity-idea',
            'title' => 'Heb jij nou een goed idee voor een nieuwe activiteit?',
            'text' => $activityIdeaText,
            'filePath' => null,
            'fileType' => null,
        ]);

        // Content for the "Lid Worden" page
        Content::create([
            'type' => 'text',
            'name' => 'lid-worden-banner',
            'title' => 'Lid Worden',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'lid-worden-info',
            'title' => 'Kom mee',
            'text' => '{"time":1746775105514,"blocks":[{"id":"sPqs5ONqKY","type":"paragraph","data":{"text":"Als werknemer van de gemeente Almere kan je lid worden van de personeelsvereniging."}},{"id":"q9FixML_t0","type":"paragraph","data":{"text":"De contributie van de personeelsvereniging bedraagt € 2,- per maand en wordt 1 maand ná aanmelding automatisch op je salaris ingehouden."}},{"id":"8zb0XWjxH-","type":"paragraph","data":{"text":"Je lidmaatschap gaat dus 1 maand ná aanmelden in "}},{"id":"iQi0bNpDWu","type":"paragraph","data":{"text":"Je aanmelden maar ook afmelden gaat via <a href=\"https://82393.afasinsite.nl/portal-insite-prs/tp-personeelsverenigingen\">MijnHRM</a>\n"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'lid-worden-aanmelden',
            'title' => 'Aanmelden inhuur / stagiaire / pensionado',
            'text' => '{"time":1764775101848,"blocks":[{"id":"qA8r0sKHHd","type":"paragraph","data":{"text":"Wanneer u zich aanmeldt om lid te worden van Zijpalm, wordt de eerste betaling bepaald door het aantal resterende maanden tot 1 januari van het volgende jaar."}},{"id":"oipAo3IXeQ","type":"paragraph","data":{"text":"De jaarlijkse contributie bedraagt €24,-"}},{"id":"DxZeKTX9eR","type":"paragraph","data":{"text":"Vanaf de maand van aanmelding ontvangt u een factuur tot het einde van het jaar."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Content for the "Lief en leed" page
        Content::create([
            'type' => 'text',
            'name' => 'lief-en-leed-banner',
            'title' => 'Lief & Leed',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'lief-en-leed-info',
            'title' => 'Over Lief en Leed',
            'text' => '{"time":1764773823533,"blocks":[{"id":"nU2vJNGD8P","type":"paragraph","data":{"text":"Stichting Lief en leed valt onder de auspiciën van personeelsvereniging Zijpalm en is bedoeld voor alle medewerkers in dienst van de gemeente. Bij leden van lief en leed wordt netto €0,65 op hun salaris ingehouden. "}},{"id":"Hm8Q8yVN_c","type":"paragraph","data":{"text":"Lief en leed betaalt bij diverse gebeurtenissen een geldbedrag uit aan degene die voor een collega een attentie verzorgt. Ben je nog geen lid en wil je alsnog deelnemen, dan kan je via de pagina zelf regelen je aanmelden of afmelden als deelnemer van de Lief en Leed van de Gemeente Almere. Vele collegae zijn je al voor gegaan."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'lief-en-leed-bijdragen',
            'title' => 'Welke bijdragen worden uit de lief en leedpot betaald?',
            'text' => '{"time":1747048748651,"blocks":[{"id":"baY04kfA3G","type":"list","data":{"style":"unordered","meta":{},"items":[{"content":"Voor geboorte € 25,00 ","meta":{},"items":[]},{"content":"Voor ziekte (na de eerste drie weken of bij acute ziekenhuisopname) € 25,00 ","meta":{},"items":[]},{"content":"Bij langdurige ziekte herhaling na iedere drie maanden € 25,00 (bij 100% ziekte!) ","meta":{},"items":[]},{"content":"Voor een huwelijk of geregistreerd partnerschap € 40,00 ","meta":{},"items":[]},{"content":"Voor (keuze)pensioen (einde van je werkcarrière) of bij vertrek bij de Gemeente Almere (als je langer dan 5 jaar bij de Gemeente Almere hebt gewerkt en langer dan 5 jaar lid bent geweest) €25,00 ","meta":{},"items":[]},{"content":"Voor dertigste verjaardag € 25,00 (nieuw vanaf 14-04-2023) ","meta":{},"items":[]},{"content":"Voor veertigste verjaardag € 25,00 (nieuw vanaf 14-04-2023) ","meta":{},"items":[]},{"content":"Voor vijftigste verjaardag € 25,00 ","meta":{},"items":[]},{"content":"Voor zestigste verjaardag € 25,00 (nieuw vanaf 19-04-2024) ","meta":{},"items":[]},{"content":"Voor vijfenzestigste verjaardag € 25,00 ","meta":{},"items":[]},{"content":"Voor 12½- of 25-jarig ambtsjubileum € 25,00 ","meta":{},"items":[]},{"content":"Voor 12½- of 25-jarig huwelijk of geregistreerd partnerschap € 25,00 ","meta":{},"items":[]},{"content":"Voor veertigjarig huwelijk of geregistreerd partnerschap € 40,00 ","meta":{},"items":[]},{"content":"Voor veertigjarig ambtsjubileum € 40,00 ","meta":{},"items":[]},{"content":"Bij overlijden personeelslid of huisgenoot € 50,00","meta":{},"items":[]}]}},{"id":"wUPVzVOrus","type":"paragraph","data":{"text":"Wil je weten of iemand meedoet ga dan naar de pagina met deelnemende medewerkers Lief en Leed om het op te zoeken. Contactpersonen: Is er een collega die in aanmerking komt voor een bijdrage, neem dan binnen drie maanden contact op via stel een vraag in MijnHRM , de medewerkers van de salarisadministratie zullen bij toerbeurt de bijdrage aan je overmaken."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'lief-en-leed-deelnemers',
            'title' => 'Deelnemers',
            'text' => '{"time":1747051730877,"blocks":[{"id":"giXP94K37_","type":"paragraph","data":{"text":"Wil je weten of iemand meedoet kijk dan op deze pagina en zoek de link naar deelnemende medewerkers Lief en Leed."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'lief-en-leed-contact',
            'title' => 'Aanvraagformulier',
            'text' => '{"time":1764774008671,"blocks":[{"id":"v8y7Ks3ib0","type":"paragraph","data":{"text":"Indien je een emailadres hebt van de gemeente Almere kan je een aanvraag doen voor een lid van de Lief & Leed. Klik hieronder om de aanvraag te starten."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Content for the "Info" page
        Content::create([
            'type' => 'text',
            'name' => 'info-banner',
            'title' => 'Over ons',
            'text' => null,
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'info-over',
            'title' => 'Over Zijpalm',
            'text' => '{"time":1746626262753,"blocks":[{"id":"cRr8wBrFBN","type":"paragraph","data":{"text":"Welkom op de website van de Personeelsvereniging Zijpalm."}},{"id":"gCmJgCYgJu","type":"paragraph","data":{"text":"                        De personeelsvereniging ‘Zijpalm’ is opgericht in 1979 en is een dynamische en actieve vereniging die regelmatig activiteiten organiseert voor haar leden, waarbij meestal één introducé is toegestaan. Voorbeelden van activiteiten die de personeelsvereniging organiseert zijn: diverse wekelijkse sportactiviteiten, culturele voorstellingen, creatieve workshops, diverse clinic’s, bezoek kerstmarkt, een buitenlandtrip, het Sinterklaasfeest en nog veel meer!"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'info-bijzonder-verlof',
            'title' => 'Buitengewoon verlof',
            'text' => '{"time":1746626248363,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Voor 1 daagse activiteiten heb je recht op 1 dag buitengewoon/bijzonder verlof. Vergeet niet daar gebruik van te maken. Klik hieronder voor de komende activiteiten."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'afmelden-zijpalm',
            'title' => 'Afmelden Zijpalm',
            'text' => '{"time":1748008383911,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Om af te melden voor de Zijpalm, druk op onderstaande knop"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'text',
            'name' => 'afmelden-zijpalm-medewerker',
            'title' => 'Afmelden Zijpalm',
            'text' => '{"time":1748003619977,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Afmelden kan als medewerker via <a href=\"https://82393.afasinsite.nl/aanmaken-loonmutatie-ess-incl-autorisatie-prs/aanmaken-afmelding-personeelsactiviteiten\">MijnHRM</a>"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        // Bestuur
        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-0',
            'title' => 'Hans Pieters',
            'text' => 'Penningmeester',
            'filePath' => 'images/bestuur/IMG_5897.jpeg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-1',
            'title' => 'Fred Dekker',
            'text' => 'Bestuurslid',
            'filePath' => 'images/bestuur/IMG_5874.jpeg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-2',
            'title' => 'Susette Altena',
            'text' => 'Voorzitter',
            'filePath' => 'images/bestuur/IMG_5882.jpeg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-3',
            'title' => 'Oscar Brouwers',
            'text' => 'Bestuurslid',
            'filePath' => 'images/bestuur/IMG_5918.jpeg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-4',
            'title' => 'Stephanie van der Zeeuw',
            'text' => 'Secretaris',
            'filePath' => 'images/bestuur/IMG_5902.jpeg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-5',
            'title' => 'Geert van Dam',
            'text' => 'Bestuurslid',
            'filePath' => 'images/bestuur/IMG_5890.jpeg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-6',
            'title' => 'Laura Boskemper',
            'text' => 'Bestuurslid',
            'filePath' => 'images/bestuur/IMG_5940.jpg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-7',
            'title' => 'Izabella Praxedes Vieira',
            'text' => 'Bestuurslid',
            'filePath' => 'images/bestuur/IMG_5906.jpeg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'bestuurslid',
            'name' => 'bestuurslid-8',
            'title' => 'Jenny Soffner',
            'text' => 'Bestuurslid',
            'filePath' => 'images/bestuur/IMG_5911.jpeg',
            'fileType' => FileType::Image,
        ]);

        // Files
        Content::create([
            'type' => 'file',
            'name' => 'background',
            'title' => null,
            'text' => null,
            'filePath' => 'content/zijpalm_background.jpg',
            'fileType' => FileType::Image,
        ]);

        Content::create([
            'type' => 'file',
            'name' => 'huishoudelijk-reglement',
            'title' => 'Huishoudelijk reglement',
            'text' => null,
            'filePath' => 'content/huishoudelijk_reglement.pdf',
            'fileType' => FileType::Pdf,
        ]);

        Content::create([
            'type' => 'file',
            'name' => 'privacy',
            'title' => 'Privacy',
            'text' => null,
            'filePath' => 'content/privacy.pdf',
            'fileType' => FileType::Pdf,
        ]);

        Content::create([
            'type' => 'file',
            'name' => 'statuten',
            'title' => 'Statuten',
            'text' => null,
            'filePath' => 'content/statuten.pdf',
            'fileType' => FileType::Pdf,
        ]);

        // Jaarverslagen
        Content::create([
            'type' => 'year-report',
            'name' => 'jaarverslag-2023',
            'title' => 'Jaarverslag 2023',
            'text' => '2023',
            'filePath' => 'content/2023-jaarverslag.pdf',
            'fileType' => FileType::Pdf,
        ]);

        // Emails
        Content::create([
            'type' => 'email',
            'name' => 'email-nieuw-lid',
            'title' => 'Welkom bij Zijpalm!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Leuk dat jij lid bent geworden van Zijpalm."}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"U kunt uw wachtwoord instellen door op de onderstaande knop te klikken:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-bestelling-betaald',
            'title' => 'Uw bestelling is betaald!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Uw bestelling is betaald!"}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"Hieronder vindt u de details van uw bestelling:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-betaling-mislukt',
            'title' => 'Betaling mislukt',
            'text' => '{"time":1763000000000,"blocks":[{"type":"paragraph","data":{"text":"Uw betaling is helaas mislukt."}},{"type":"paragraph","data":{"text":"Probeer het alstublieft opnieuw via de betaallink."}}],"version":"2.31.0"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-activiteit-aangemeld',
            'title' => 'Bevestiging aanmelding',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Leuk dat jij je hebt aangemeld. Hierbij bevestigen wij dat jouw aanmelding is binnengekomen."}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"Zie de details van uw aanmelding hieronder:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-activiteit-aangemeld-reserve',
            'title' => null,
            'text' => '{"time":1750667926653,"blocks":[{"id":"suHhoLsGwp","type":"paragraph","data":{"text":"U bent op de <mark class=\"cdx-marker\">reservelijst </mark>geplaatst! Als er een plek voor u vrij komt krijgt u een email met een betaallink die binnen 2 werkdagen betaalt dient te worden om mee te doen aan de activiteit"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-activiteit-afgemeld',
            'title' => 'U bent succesvol afgemeld voor: ',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Wat jammer dat jij je hebt afgemeld. Hierbij bevestigen wij dat je succesvol bent afgemeld."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-reserve-upgrade',
            'title' => 'U mag meedoen met:',
            'text' => '{"time":1750425193847,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Joepie! U mag mee met de activiteit."}},{"id":"PYmkk9GUu1","type":"paragraph","data":{"text":"Betaal het bedrag binnen 2 werkdagen om mee te mogen. Als u niet binnen 2 werkdagen betaalt vervalt de reservering"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-bestuur-nieuwe-leden',
            'title' => 'Nieuwe Zijpalm leden',
            'text' => '{"time":1750425193847,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"De volgende nieuwe leden hebben zich aangemeld voor Zijpalm:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-bestuur-nieuwe-bestelling',
            'title' => 'Nieuwe Bestelling',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Er is een nieuwe bestelling aangemaakt."}},{"id":"UIZfWpOyZC","type":"paragraph","data":{"text":"Hieronder vindt u de details van de bestelling:"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-bestuur-activiteit-aanmeldingen',
            'title' => 'Aanmeldingen voor activiteit:',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Hierbij alle aanmeldingen voor de activiteit"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-nieuwe-activiteit',
            'title' => 'Er is een nieuwe activiteit aangemaakt!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"De volgende activiteit is aangemaakt!"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-herinnering-activiteit-deelnemers',
            'title' => 'Herinnering: Een activiteit start binnenkort!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Een activiteit waarvoor u bent ingeschreven start binnenkort!"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-herinnering-activiteit-niet-deelnemers',
            'title' => 'Herinnering: Meld u aan!',
            'text' => '{"time":1750075440441,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"Herinnering: schrijf u nu in voor de volgende activiteit!"}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);

        Content::create([
            'type' => 'email',
            'name' => 'email-reset-wachtwoord',
            'title' => 'Hallo',
            'text' => '{"time":1769171792368,"blocks":[{"id":"xnNwYZd4RN","type":"paragraph","data":{"text":"U ontvangt deze email omdat we een wachtwoord reset aanvraag hebben ontvangen voor uw account."}}],"version":"2.31.0-rc.7"}',
            'filePath' => null,
            'fileType' => null,
        ]);
    }
}
