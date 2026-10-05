# SteynPT

De nieuwe website van SteynPT (www.steynpt.nl): personal training, online coaching, voedingscoaching en ademcoaching in Amsterdam. Klanten maken een account aan, boeken zelf afspraken, zien hun voortgang in hun dashboard en nodigen vrienden uit voor online coaching.

Gebouwd met Next.js 16 (App Router), TypeScript, Tailwind CSS 4 en een SQLite/libSQL-database via Drizzle ORM.

## Wat zit erin

**Website**

| Pagina | Pad |
| --- | --- |
| Home | `/` |
| Online coaching (uitgelicht, met pakketten) | `/online-coaching` |
| Personal training + topsport & specifieke doelen | `/personal-training` |
| Ademcoaching: 1-op-1 (1,5 uur, € 210) en groepssessies op aanvraag | `/ademcoaching` |
| Voedingscoaching | `/voedingscoaching` |
| Tarieven (zelfde prijzen als de oude site) | `/tarieven` |
| Over Steyn | `/over-steyn` |
| Vriendenactie online coaching + voorwaarden | `/vriend-uitnodigen` |
| Contact / gratis kennismaking | `/contact` |
| Privacyverklaring | `/privacy` |

Oude URL's (`/over-steynpt`, `/vraag-een-gratis-kennismaking-aan`, `/rewards`, `/small-group-training`) worden doorgestuurd.

De vormgeving is zakelijk zwart-wit met één accentkleur (petrol, `#0b6f78`) en een lichtgrijze vlakkleur. Alle kleuren staan als tokens in `src/app/globals.css`.

**Mijn omgeving (klant)**

- Registreren (`/registreren`), inloggen (`/inloggen`) met tweestapsverificatie, uitloggen, profiel bewerken, wachtwoord wijzigen en account verwijderen. Zie [Beveiliging](#beveiliging).
- Dashboard (`/account`) met volgende afspraak, schema's, voortgang (grafieken van de laatste metingen), coachingstatus, bericht van Steyn, wekelijkse check-in met reeks ("3 weken op rij") en de vriendenactie.
- Agenda (`/account/agenda`): afspraak boeken in drie stappen (type, locatie, dag en tijd), komende afspraken bekijken, afzeggen en per afspraak een `.ics`-bestand downloaden voor de eigen agenda.
- Voortgang (`/account/voortgang`): alle metingen in grafieken en een tabel.

**Beheer (Steyn)**

Het beheer heeft een eigen opmaak met een zijbalk (op de telefoon een balk bovenaan). Tellers laten zien wat aandacht nodig heeft.

- `/admin` Overzicht: per schematype hoeveel klanten op een nieuw schema wachten, hoeveel er de komende week een nieuw schema krijgen en hoeveel er een actief schema hebben. Verder de afspraken van vandaag en de dagen erna, en wat er te doen is: schema's controleren, nieuwe aanvragen, coachingaanvragen en vriendenkortingen om te verrekenen.
- `/admin/agenda` Agenda: weergave per dag, week, maand of als lijst. Klik op een afspraak voor alle gegevens, om hem te verplaatsen of te annuleren. Klik op een leeg moment in de dag- of weekweergave om daar een afspraak in te plannen.
- `/admin/agenda/nieuw`: Steyn plant zelf een afspraak in voor een klant (ook buiten de vaste beschikbaarheid; dubbel boeken kan niet).
- `/admin/leden`: alle leden met zoeken en filteren op coachingstatus, volgende afspraak en laatste check-in. Per lid (`/admin/leden/[id]`): coachingstatus en bericht, de status van beide schema's, metingen, komende afspraken en de intake.
- `/admin/trainingsschemas` en `/admin/voedingsschemas`: per schematype alle klanten met de fase waarin ze zitten, het huidige schema en wanneer ze toe zijn aan een nieuw schema. Filteren per fase en zoeken op naam. Via *Nieuw trainingsschema* (`…/nieuw`) maak je een schema: je kiest de startdatum en begint met een AI-concept, een kopie van het huidige schema of leeg. Een schema bekijken, bewerken en publiceren gaat via `…/[id]`. Oude links naar `/admin/schemas` worden doorgestuurd.
- `/admin/aanvragen`: contactaanvragen, open of afgehandeld.
- `/admin/teksten` Website-teksten: alle teksten van de website aanpassen, zonder code. Zie [Website-teksten](#website-teksten).
- `/admin/agenda/instellingen`: beschikbaarheid per week, vrije dagen en de koppeling met Google of Apple Agenda.

## Agenda

1. **Beschikbaarheid instellen.** In `/admin/agenda/instellingen` stelt Steyn per weekdag tijdvakken in met een locatie (bijvoorbeeld maandag 07:00–12:00 Gymbase). Klanten zien alleen tijden binnen die vakken. Zolang er niets is ingesteld, kan niemand boeken.
2. **Vrije dagen en vakanties.** Blokkeer hele dagen; bestaande afspraken in die periode blijven staan (Steyn krijgt een melding en zegt ze zelf af).
3. **Afspraaktypes en regels** staan in `src/lib/agenda.ts`:

   | Type | Duur | Locaties |
   | --- | --- | --- |
   | Personal training | 60 min | Gymbase, op locatie |
   | Gratis kennismaking (max. 1 tegelijk) | 30 min | Gymbase, online |
   | Meting | 30 min | Gymbase |
   | Online coaching videocall (alleen voor actieve coachingklanten) | 30 min | Online |

   Boeken kan vanaf 12 uur en tot 6 weken vooruit, afzeggen tot 24 uur van tevoren, maximaal 8 komende afspraken per klant. Tijden starten elk half uur. Dubbel boeken is niet mogelijk; de vrije tijd wordt bij het bevestigen opnieuw gecontroleerd.

   Steyn kan zelf afspraken inplannen en verplaatsen. Verplaatsen maakt een nieuwe afspraak en annuleert de oude in één keer. Er gaan nog geen e-mails uit; laat de klant het dus zelf weten.

4. **Koppelen met Google of Apple Agenda (iCal).** In `/admin/agenda/instellingen` staat een geheime abonnementslink (`/ical/<token>.ics`) met alle afspraken, inclusief naam, telefoonnummer, e-mail en opmerking van de klant. Afgezegde afspraken worden als geannuleerd doorgegeven en verdwijnen dan uit de agenda.
   - **Google Agenda:** op een computer naar calendar.google.com → *Andere agenda's* → **+** → *Via URL*, plak de link en kies *Agenda toevoegen*. Google ververst geabonneerde agenda's zelf, meestal een paar keer per dag; een nieuwe afspraak kan dus enkele uren later verschijnen.
   - **Apple Agenda (iPhone, iPad, Mac):** open de *webcal*-link op het apparaat, of kies op de Mac *Archief → Nieuw agenda-abonnement*. Zet *Vernieuw automatisch* bijvoorbeeld op elk uur.
   - Is de link per ongeluk gedeeld? Kies *Nieuwe link maken*; de oude werkt direct niet meer.

   Het is een export: afspraken die Steyn in Google of Apple zelf maakt, worden niet teruggezet in de website. Blokkeer die tijden via beschikbaarheid of vrije dagen.

## Voortgang

Steyn voert metingen in op de ledenpagina: gewicht, vetpercentage, spiermassa, taille, heup, borst, bovenarm en bovenbeen (alles optioneel, minimaal één waarde) met een notitie. De klant ziet die direct in het dashboard: per waarde de laatste meting en het verschil met de eerste, plus een grafiek met tooltip en een tabel. Velden en grenzen staan in `src/lib/progress.ts`.

## Vriendenactie online coaching

Elke klant heeft een persoonlijke link (`/r/CODE`) en code, te delen via WhatsApp, e-mail of de deelknop. De uitnodiging blijft 60 dagen bewaard in een cookie.

- De nieuwe klant krijgt 50% korting op de eerste maand online coaching.
- De uitnodiger krijgt 50% korting op een maand online coaching zodra de vriend start (coachingstatus *actief*).
- In `/admin` staat een lijst *Vriendenkorting te verrekenen*. Na het verwerken in de factuur klikt Steyn op *Verrekend*; de klant ziet de status in het dashboard.

De naam van de actie, de kortingen en de drie stappen past Steyn aan in het beheer onder *Website-teksten → Op elke pagina* (standaardwaarden in `src/lib/referral-program.ts`). Het puntensysteem (Rewards) is verwijderd.

## Website-teksten

Onder **Website-teksten** in het beheer (`/admin/teksten`) past Steyn de teksten van de site aan: per pagina (homepage, online coaching, personal training, ademcoaching, voedingscoaching, tarieven, Over Steyn, vriendenactie, contact en privacy) en voor wat op meerdere pagina's staat:

- **Op elke pagina**: de balk bovenaan (tekst, label, link, aan/uit), de vriendenactie, de standaard afsluiter, reviews, werkwijze, expertises, adres en contact, en de footer.
- **Prijzen en pakketten**: namen, prijzen en inhoud van de online pakketten, de PT-pakketten (toevoegen, verwijderen, volgorde, "Meest gekozen") en de ademsessie 1-op-1.

Opslaan is direct zichtbaar op de site. Per pagina ook de titel en omschrijving voor Google.

Hoe het werkt:

- In titels geven `*sterretjes*` de accentkleur en begint Enter een nieuwe regel. In lange teksten begint een lege regel een nieuwe alinea; in opsommingen staat elk punt op een eigen regel.
- **Automatische waarden** zoals `{ademprijs}`, `{ademduur}`, `{online-vanaf}`, `{actie}`, `{vriendkorting}` en `{jouwkorting}` worden overal ingevuld. Verander je de prijs van de ademsessie, dan klopt hij meteen in de introductie, de veelgestelde vragen, de tarieven en de omschrijving voor Google. Het bewerkscherm laat zien hoe de tekst op de site wordt.
- Elke tekst heeft een standaardtekst (de tekst uit de code). Met *Standaardtekst* zet je één veld terug, of alles op een pagina. Een teruggezette tekst wordt niet meer opgeslagen.
- Bij opslaan wordt alles gecontroleerd: geen lege verplichte velden, maximale lengte, geldige bedragen en alleen bekende automatische waarden. Wijzigingen die nog niet zijn opgeslagen zie je per veld, en de pagina waarschuwt als je weggaat zonder op te slaan. Ctrl+S slaat op.

Technisch: aangepaste teksten staan in de tabel `site_texts` (sleutel `pagina.onderdeel.veld`, waarde als JSON). Welke teksten er zijn, met hun standaardtekst, staat in `src/lib/content/pages/`. Een nieuwe tekst aanpasbaar maken: voeg een veld toe aan de pagina in dat register en lees hem op de pagina met `getTexts(...)` (`src/lib/content/texts.ts`). Een opgeslagen waarde die niet meer past bij het veld wordt genegeerd, en als de database niet bereikbaar is, toont de site de standaardteksten. De openbare pagina's worden daarom per bezoek opgebouwd in plaats van vooraf bij het bouwen.

Niet via het beheer: het menu, de knoppen in de kop, de onderwerpen van het contactformulier, foto's en de teksten in Mijn omgeving en bij het inloggen.

## Trainings- en voedingsschema's met AI

1. **Intake (klant):** in Mijn omgeving (`/account/intake`) geeft de klant de volgende gegevens op, plus toestemming voor het gebruik ervan:
   - doel en lichaamsgegevens;
   - trainingswensen: frequentie, duur, ervaring, locatie, materiaal en blessures;
   - voeding: eetstijl, de 14 allergenen + lactose, wat de klant niet lust en eetmomenten.
2. **AI-concept:** voor klanten met online coaching (status *aangevraagd* of *actief*) maakt Claude (`claude-opus-5-5`) direct een concept.
   - Energie en macro's worden eerst met een vaste formule berekend (Mifflin-St Jeor).
   - De AI krijgt geen naam of contactgegevens.
   - De output heeft een vaste structuur (structured outputs), zodat Steyn alles kan bewerken.
3. **Controle (Steyn):** in `/admin/trainingsschemas` en `/admin/voedingsschemas` staan de schema's ter controle. Per schema (`…/[id]`):
   - Steyn ziet de intake ernaast.
   - Er verschijnt een automatische waarschuwing als ingrediënten botsen met allergieën of eetstijl.
   - Hij kan alles aanpassen: oefeningen, sets, maaltijden, hoeveelheden, richtwaarden en tips.
   - Met een instructie (bijv. "geen squats vanwege de knie") laat hij een nieuw concept maken, of hij start een leeg schema.
4. **Publiceren:** pas na *Goedkeuren & publiceren* ziet de klant het schema in Mijn omgeving, met een printknop (ook voor pdf). Bij het publiceren kiest Steyn de datum voor het volgende schema (*Nieuw schema op*). Standaard is dat de duur van het trainingsschema, of 4 weken voor voeding, gerekend vanaf de start. Later aanpassen kan op dezelfde plek.
   - **Startdatum:** bij een nieuw schema (en in de editor, zolang de klant het nog niet ziet) kies je *Start op*. Ligt die in de toekomst, dan wordt de knop *Goedkeuren & inplannen*: het schema krijgt de status *Ingepland* en de klant ziet het pas vanaf die dag in Mijn omgeving. Tot dan blijft het huidige schema zichtbaar; de klant ziet alleen wanneer het nieuwe klaarstaat. Bij een klant met een lopend schema staat de startdatum standaard op de dag dat dat schema afloopt.
   - Op de startdag wordt het ingeplande schema automatisch gepubliceerd en vervangt het het vorige. Er draait daarvoor geen achtergrondtaak: dat gebeurt zodra de klant of Steyn een pagina met schema's opent.
   - Een ingepland schema kun je nog bewerken, verschuiven of *Terugzetten naar concept*.
5. **Vernieuwen:** op basis van die datum laat het beheer per klant de fase zien:

   | Fase | Betekenis |
   | --- | --- |
   | Wacht op nieuw schema | Nog geen schema (wel een intake), of de datum is bereikt |
   | Te controleren | Er staat een concept klaar, de AI is bezig of het concept is mislukt |
   | Komende week | Het nieuwe schema is binnen 7 dagen nodig |
   | Ingepland | Het volgende schema is al goedgekeurd en start op een latere datum |
   | Actief schema | Het schema loopt nog langer dan een week |
   | Wacht op intake | De klant moet de intake nog invullen; met *Herinnering mailen* stuur je een mailtje |
   | Gepauzeerd | Coaching gepauzeerd of gestopt |

   In het overzicht staan klanten met online coaching (aangevraagd, actief of gepauzeerd) die dit schematype in de intake hebben gekozen, plus iedereen voor wie al een schema is gemaakt.

Een klant zonder online coaching kan de intake wel invullen; Steyn kan dan via *Nieuw trainingsschema* of *Nieuw voedingsschema* zelf een concept laten maken. Er geldt een limiet van 6 automatische concepten per klant per dag.

## Beveiliging

**Tweestapsverificatie (verplicht voor iedereen, ook voor Steyn)**

Inloggen gaat in twee stappen: eerst e-mailadres en wachtwoord, dan een code van 6 cijfers uit een authenticator-app (Google Authenticator, Microsoft Authenticator, de Wachtwoorden-app op de iPhone, 1Password …). Er is geen sms of e-mail voor nodig.

- **Instellen:** direct na het registreren, en voor bestaande accounts bij de eerstvolgende keer inloggen (`/inloggen/verificatie`). Je scant een QR-code (of typt de sleutel over) en bevestigt met de eerste code. Daarna krijg je 8 herstelcodes om te bewaren. Pas dan is het account te gebruiken. Sessies van vóór de tweestapsverificatie tellen niet meer.
- **Herstelcodes:** telefoon niet bij de hand? Elke herstelcode werkt één keer in plaats van de code uit de app. In het profiel maak je nieuwe herstelcodes, of koppel je een nieuwe telefoon.
- **Beveiliging van de codes:** een code werkt maar één keer en 30 seconden voor of na de juiste tijd. Na 5 foute codes moet je opnieuw inloggen met je wachtwoord, en per account zijn maximaal 6 foute codes per uur mogelijk. Alleen de hashes van de herstelcodes worden opgeslagen.
- **Telefoon en herstelcodes kwijt (klant):** Steyn zet de tweestapsverificatie terug bij het lid, onder *Inloggen en beveiliging*. Het lid wordt overal uitgelogd en koppelt bij de volgende keer inloggen een nieuwe telefoon. Controleer eerst of je echt met het lid zelf spreekt.
- **Telefoon en herstelcodes kwijt (Steyn):** op de server `npm run auth:reset-2fa -- steyn@steynpt.nl`. Steyn koppelt dan bij het inloggen opnieuw een telefoon.

**Wachtwoord om de 8 weken**

- Een week van tevoren verschijnt een melding in Mijn omgeving en in het beheer.
- Is het wachtwoord ouder dan 8 weken, dan stuurt elke pagina door naar `/wachtwoord-vernieuwen`. Daar kies je een nieuw wachtwoord: minimaal 8 tekens en niet hetzelfde als het huidige.
- Na een nieuw wachtwoord word je op andere apparaten uitgelogd.
- De termijn staat in `src/lib/totp.ts` (`PASSWORD_MAX_AGE_DAYS`). Bestaande accounts kregen bij de update 8 weken vanaf dat moment.

In de demo zijn de twee voorbeeldaccounts (inloggen met één klik) uitgezonderd; een nieuw account in de demo doorloopt de tweestapsverificatie wel.

## Content aanpassen

| Wat | Waar |
| --- | --- |
| Teksten, prijzen, pakketten, reviews, adres, aankondigingsbalk | In het beheer onder *Website-teksten* (standaardteksten in `src/lib/content/pages/`) |
| Menu, pakket-id's, doelen en onderwerpen van het contactformulier | `src/lib/site.ts` |
| Afspraaktypes, duur, locaties en boekingsregels | `src/lib/agenda.ts` |
| Vriendenactie: code-opbouw (teksten en kortingen staan in het beheer) | `src/lib/referral-program.ts` |
| Meetwaarden en grenzen | `src/lib/progress.ts` |
| Intakevragen en berekening richtwaarden | `src/lib/intake.ts` |
| Instructies voor de AI | `src/lib/plans/prompt.ts` |
| Looptijd van schema's en de fases (o.a. "komende week" = 7 dagen) | `src/lib/plans/pipeline.ts` |
| Allergenen- en dieetcontrole | `src/lib/plans/allergens.ts` |
| Logo en foto's | `public/brand/`, `public/images/` |
| Kleuren en typografie | `src/app/globals.css` |
| Voorbeeldgegevens van de demo | `scripts/seed-demo.mts` |

## Lokaal draaien

```bash
npm install
cp .env.example .env        # pas eventueel ADMIN_EMAILS aan
npm run db:migrate          # maakt data/steynpt.db aan of werkt hem bij
npm run dev                 # http://localhost:3000
```

Overige scripts:

- `npm run build` en `npm start`
- `npm run typecheck`
- `npm test`: unit tests voor tijdzones, vrije tijden, iCal, metingen, check-in-reeks, intake en allergenen.
- `npm run db:generate`: nieuwe migratie na een wijziging in `src/lib/db/schema.ts`.

### AI instellen

Zet `ANTHROPIC_API_KEY`. Steyn maakt die zelf aan op platform.claude.com, zodat de kosten op zijn eigen account komen. Zonder sleutel werkt de site gewoon, maar maakt Steyn de schema's zelf. Voor lokaal testen zonder sleutel: `AI_MOCK=1` levert voorbeeldconcepten op (niet gebruiken in productie).

Een concept maken duurt meestal één à twee minuten en kost naar schatting 10 tot 25 cent per schema. Het genereren draait na het versturen van het formulier op de achtergrond (`after()`). De pagina's die dat starten hebben `maxDuration = 300`, zodat het ook op serverless hosting genoeg tijd krijgt.

### Beheerder worden

Zet je e-mailadres in `ADMIN_EMAILS` (komma-gescheiden voor meerdere). Wie zich met dat adres registreert of inlogt, krijgt automatisch toegang tot `/admin`.

## Live zetten

De site heeft een Node.js-server en een database nodig.

- **Eigen server of VPS** (bijvoorbeeld met een persistente schijf): `npm ci && npm run db:migrate && npm run build && npm start`. De SQLite-database staat in `data/`; maak daar back-ups van.
- **Vercel of andere serverless hosting**: gebruik een [Turso](https://turso.tech)-database. Zet `DATABASE_URL=libsql://…` en `DATABASE_AUTH_TOKEN`, en draai `npm run db:migrate` één keer tegen die database.

Zet `NEXT_PUBLIC_SITE_URL` op het echte domein, zodat de uitnodigingslinks kloppen. Draai na elke update `npm run db:migrate`. De laatste migratie voegt de tweestapsverificatie toe: iedereen, ook Steyn, koppelt bij de eerstvolgende keer inloggen een authenticator-app.

## Demo online zetten

Met `DEMO_MODE=1` draait de site als demo:

- Bovenaan staat een demobalk.
- Op de inlogpagina en in de demobalk log je met één klik in als voorbeeldklant (Lisa Jansen) of als Steyn (beheer).
- De database wordt gevuld met voorbeeldklanten, afspraken, metingen, schema's, check-ins en contactaanvragen. De datums zijn relatief aan vandaag.
- De AI draait altijd in testmodus, dus er zijn geen kosten.
- De site is niet vindbaar in zoekmachines.
- De twee demo-accounts kunnen niet worden verwijderd of van wachtwoord wisselen, en hebben geen tweestapsverificatie nodig.
- Wie als Steyn inlogt, kan ook de website-teksten aanpassen. Die zijn dan voor alle bezoekers van de demo te zien, tot de demo opnieuw start en alles weer de standaardtekst is.

**Op Render (gratis):**

1. Maak een account op [render.com](https://render.com) en koppel GitHub met toegang tot deze repository.
2. Open [render.com/deploy?repo=https://github.com/StephvanHoffe/steynpt](https://render.com/deploy?repo=https://github.com/StephvanHoffe/steynpt), of kies in Render *New → Blueprint* en selecteer de repository. Render leest `render.yaml`.
3. Klik op *Deploy Blueprint*. Na een paar minuten staat de demo op een adres als `https://steynpt-demo.onrender.com`.

Op het gratis plan valt de demo na een kwartier zonder bezoek in slaap. Het eerste bezoek daarna duurt ongeveer een minuut. Bij elke herstart begint de demo weer met de voorbeeldgegevens; wat bezoekers invoeren, verdwijnt dan.

**Lokaal:**

```bash
DEMO_MODE=1 npm run build
DEMO_MODE=1 npm run demo:start   # http://localhost:3000
```

`DEMO_MODE=1 npm run db:seed-demo -- --reset` maakt de database leeg en vult hem opnieuw. **Gebruik dit nooit op de echte database.** Zonder `DEMO_MODE=1` weigert het script te draaien.

## Nog te bevestigen

- **Prijzen online coaching** (Start € 79, Pro € 129, Performance € 199 per maand) zijn een voorstel. Bestaande diensten hebben dezelfde prijzen als de oude site.
- **Vriendenactie** (50% korting voor beide) is een voorstel.
- **Afspraaktypes, duur en boekingsregels** (12 uur vooraf, afzeggen tot 24 uur, 6 weken vooruit) zijn voorstellen.
- Online betalen zit er nog niet in. Na een aanmelding plant Steyn een intake en zet het lid daarna in `/admin` op *actief*. Een betaalkoppeling (bijvoorbeeld Mollie) kan later worden toegevoegd.
- E-mails (bevestiging van een afspraak, herinneringen, nieuwe aanvragen) zijn er nog niet. Afspraken staan in `/admin` en via iCal in de agenda van Steyn; de klant kan per afspraak een `.ics` downloaden.
- De privacyverklaring is een basisversie; laat deze juridisch controleren. Voor het gebruik van AI met gezondheidsgegevens: sluit de verwerkersovereenkomst (DPA) met Anthropic af en controleer of de tekst over doorgifte buiten de EU klopt met je afspraken.
- De berekende richtwaarden en de allergenencontrole zijn hulpmiddelen; Steyn blijft verantwoordelijk voor de controle van elk schema.
