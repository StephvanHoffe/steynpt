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
| Ademcoaching in groepsverband | `/ademcoaching` |
| Voedingscoaching | `/voedingscoaching` |
| Tarieven (zelfde prijzen als de oude site) | `/tarieven` |
| Over Steyn | `/over-steyn` |
| Vriendenactie online coaching + voorwaarden | `/vriend-uitnodigen` |
| Contact / gratis kennismaking | `/contact` |
| Privacyverklaring | `/privacy` |

Oude URL's (`/over-steynpt`, `/vraag-een-gratis-kennismaking-aan`, `/rewards`, `/small-group-training`) worden doorgestuurd.

De vormgeving is zakelijk zwart-wit met één accentkleur (petrol, `#0b6f78`) en een lichtgrijze vlakkleur. Alle kleuren staan als tokens in `src/app/globals.css`.

**Mijn omgeving (klant)**

- Registreren (`/registreren`), inloggen (`/inloggen`), uitloggen, profiel bewerken, wachtwoord wijzigen en account verwijderen.
- Dashboard (`/account`) met volgende afspraak, schema's, voortgang (grafieken van de laatste metingen), coachingstatus, bericht van Steyn, wekelijkse check-in met reeks ("3 weken op rij") en de vriendenactie.
- Agenda (`/account/agenda`): afspraak boeken in drie stappen (type, locatie, dag en tijd), komende afspraken bekijken, afzeggen en per afspraak een `.ics`-bestand downloaden voor de eigen agenda.
- Voortgang (`/account/voortgang`): alle metingen in grafieken en een tabel.

**Beheer (Steyn)**

- `/admin`: overzicht met komende afspraken, schema's ter controle, vriendenkortingen die nog verrekend moeten worden, contactaanvragen en leden (coachingstatus en bericht per lid).
- `/admin/leden/[id]`: intake, schema's, metingen invoeren en verwijderen, komende afspraken van dat lid, en door wie het lid is uitgenodigd.
- `/admin/agenda`: alle afspraken, beschikbaarheid per week, vrije dagen en de iCal-koppeling.

## Agenda

1. **Beschikbaarheid instellen.** In `/admin/agenda` stelt Steyn per weekdag tijdvakken in met een locatie (bijvoorbeeld maandag 07:00–12:00 Gymbase). Klanten zien alleen tijden binnen die vakken. Zolang er niets is ingesteld, kan niemand boeken.
2. **Vrije dagen en vakanties.** Blokkeer hele dagen; bestaande afspraken in die periode blijven staan (Steyn krijgt een melding en zegt ze zelf af).
3. **Afspraaktypes en regels** staan in `src/lib/agenda.ts`:

   | Type | Duur | Locaties |
   | --- | --- | --- |
   | Personal training | 60 min | Gymbase, op locatie |
   | Gratis kennismaking (max. 1 tegelijk) | 30 min | Gymbase, online |
   | Meting | 30 min | Gymbase |
   | Online coaching videocall (alleen voor actieve coachingklanten) | 30 min | Online |

   Boeken kan vanaf 12 uur en tot 6 weken vooruit, afzeggen tot 24 uur van tevoren, maximaal 8 komende afspraken per klant. Tijden starten elk half uur. Dubbel boeken is niet mogelijk; de vrije tijd wordt bij het bevestigen opnieuw gecontroleerd.

4. **Koppelen met Google of Apple Agenda (iCal).** In `/admin/agenda` staat een geheime abonnementslink (`/ical/<token>.ics`) met alle afspraken, inclusief naam, telefoonnummer, e-mail en opmerking van de klant. Afgezegde afspraken worden als geannuleerd doorgegeven en verdwijnen dan uit de agenda.
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

De teksten en kortingen staan in `src/lib/referral-program.ts`. Het puntensysteem (Rewards) is verwijderd.

## Trainings- en voedingsschema's met AI

1. **Intake (klant):** in Mijn omgeving (`/account/intake`) geeft de klant de volgende gegevens op, plus toestemming voor het gebruik ervan:
   - doel en lichaamsgegevens;
   - trainingswensen: frequentie, duur, ervaring, locatie, materiaal en blessures;
   - voeding: eetstijl, de 14 allergenen + lactose, wat de klant niet lust en eetmomenten.
2. **AI-concept:** voor klanten met online coaching (status *aangevraagd* of *actief*) maakt Claude (`claude-opus-5-5`) direct een concept.
   - Energie en macro's worden eerst met een vaste formule berekend (Mifflin-St Jeor).
   - De AI krijgt geen naam of contactgegevens.
   - De output heeft een vaste structuur (structured outputs), zodat Steyn alles kan bewerken.
3. **Controle (Steyn):** in `/admin` staan de schema's ter controle. Per schema (`/admin/schemas/[id]`):
   - Steyn ziet de intake ernaast.
   - Er verschijnt een automatische waarschuwing als ingrediënten botsen met allergieën of eetstijl.
   - Hij kan alles aanpassen: oefeningen, sets, maaltijden, hoeveelheden, richtwaarden en tips.
   - Met een instructie (bijv. "geen squats vanwege de knie") laat hij een nieuw concept maken, of hij start een leeg schema.
4. **Publiceren:** pas na *Goedkeuren & publiceren* ziet de klant het schema in Mijn omgeving, met een printknop (ook voor pdf).

Een klant zonder online coaching kan de intake wel invullen; Steyn kan dan vanaf de ledenpagina zelf een concept laten maken. Er geldt een limiet van 6 automatische concepten per klant per dag.

## Content aanpassen

| Wat | Waar |
| --- | --- |
| Prijzen, pakketten, locaties, reviews, menu, aankondigingsbalk | `src/lib/site.ts` |
| Afspraaktypes, duur, locaties en boekingsregels | `src/lib/agenda.ts` |
| Vriendenactie (kortingen en teksten) | `src/lib/referral-program.ts` |
| Meetwaarden en grenzen | `src/lib/progress.ts` |
| Intakevragen en berekening richtwaarden | `src/lib/intake.ts` |
| Instructies voor de AI | `src/lib/plans/prompt.ts` |
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

Zet `NEXT_PUBLIC_SITE_URL` op het echte domein, zodat de uitnodigingslinks kloppen. Draai na elke update `npm run db:migrate`. Deze versie voegt de tabellen voor agenda en metingen toe en verwijdert de puntentabellen.

## Demo online zetten

Met `DEMO_MODE=1` draait de site als demo:

- Bovenaan staat een demobalk.
- Op de inlogpagina en in de demobalk log je met één klik in als voorbeeldklant (Lisa Jansen) of als Steyn (beheer).
- De database wordt gevuld met voorbeeldklanten, afspraken, metingen, schema's, check-ins en contactaanvragen. De datums zijn relatief aan vandaag.
- De AI draait altijd in testmodus, dus er zijn geen kosten.
- De site is niet vindbaar in zoekmachines.
- De twee demo-accounts kunnen niet worden verwijderd of van wachtwoord wisselen.

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
- **Ademcoaching** staat op "tarief op aanvraag".
- **Vriendenactie** (50% korting voor beide) is een voorstel.
- **Afspraaktypes, duur en boekingsregels** (12 uur vooraf, afzeggen tot 24 uur, 6 weken vooruit) zijn voorstellen.
- Online betalen zit er nog niet in. Na een aanmelding plant Steyn een intake en zet het lid daarna in `/admin` op *actief*. Een betaalkoppeling (bijvoorbeeld Mollie) kan later worden toegevoegd.
- E-mails (bevestiging van een afspraak, herinneringen, nieuwe aanvragen) zijn er nog niet. Afspraken staan in `/admin` en via iCal in de agenda van Steyn; de klant kan per afspraak een `.ics` downloaden.
- De privacyverklaring is een basisversie; laat deze juridisch controleren. Voor het gebruik van AI met gezondheidsgegevens: sluit de verwerkersovereenkomst (DPA) met Anthropic af en controleer of de tekst over doorgifte buiten de EU klopt met je afspraken.
- De berekende richtwaarden en de allergenencontrole zijn hulpmiddelen; Steyn blijft verantwoordelijk voor de controle van elk schema.
