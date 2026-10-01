# SteynPT

De nieuwe website van SteynPT (www.steynpt.nl): personal training, online coaching, voedingscoaching en ademcoaching in Amsterdam. Bezoekers kunnen een account aanmaken voor online coaching, punten sparen met **SteynPT Rewards** en vrienden uitnodigen.

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
| Small group training | `/small-group-training` |
| Tarieven (zelfde prijzen als de oude site) | `/tarieven` |
| Over Steyn | `/over-steyn` |
| Rewards, acties & vrienden uitnodigen | `/rewards` |
| Contact / gratis kennismaking | `/contact` |
| Privacyverklaring | `/privacy` |

Oude WordPress-URL's (`/over-steynpt`, `/vraag-een-gratis-kennismaking-aan`) worden doorgestuurd.

**Accounts & loyaliteit**

- Registreren (`/registreren`), inloggen (`/inloggen`), uitloggen, profiel bewerken, wachtwoord wijzigen en account verwijderen.
- Dashboard (`/account`) met coachingstatus, bericht van Steyn, wekelijkse check-in (energie, slaap, voeding, trainingen, gewicht), streak, puntensaldo, niveau (Brons → Platina), beloningen inwisselen en puntenhistorie.
- Vrienden uitnodigen met een persoonlijke link (`/r/CODE`) die je deelt via WhatsApp, e-mail of de deelknop. De uitnodiging blijft 60 dagen bewaard in een cookie.
- Acties met start- en einddatum (bijvoorbeeld dubbele welkomstpunten) worden automatisch toegepast en op de site getoond.
- Beheer (`/admin`) voor Steyn: contactaanvragen, leden, coachingstatus en bericht per lid, ingewisselde beloningen (leveren of annuleren met terugboeking) en punten corrigeren.

**Trainings- en voedingsschema's met AI**

1. **Intake (klant):** in Mijn omgeving (`/account/intake`) geeft de klant het doel, lichaamsgegevens, trainingswensen (frequentie, duur, ervaring, locatie, materiaal, blessures) en voeding (eetstijl, de 14 allergenen + lactose, wat de klant niet lust, eetmomenten) op, plus toestemming voor het gebruik van deze gegevens.
2. **AI-concept:** voor klanten met online coaching (status *aangevraagd* of *actief*) maakt Claude (`claude-opus-5-5`) direct een concept. Energie en macro's worden eerst met een vaste formule berekend (Mifflin-St Jeor); de AI krijgt geen naam of contactgegevens. De output heeft een vaste structuur (structured outputs) zodat Steyn alles kan bewerken.
3. **Controle (Steyn):** in `/admin` staan de schema's ter controle. Per schema (`/admin/schemas/[id]`) ziet Steyn de intake ernaast, een automatische waarschuwing als ingrediënten botsen met allergieën of eetstijl, en kan hij alles aanpassen: oefeningen, sets, maaltijden, hoeveelheden, richtwaarden en tips. Met een instructie (bijv. "geen squats vanwege de knie") laat hij een nieuw concept maken, of hij start een leeg schema.
4. **Publiceren:** pas na *Goedkeuren & publiceren* ziet de klant het schema in Mijn omgeving, met een printknop (ook voor pdf).

Een klant zonder online coaching kan de intake wel invullen; Steyn kan dan vanaf de ledenpagina (`/admin/leden/[id]`) zelf een concept laten maken. Er geldt een limiet van 6 automatische concepten per klant per dag.

## Content aanpassen

| Wat | Waar |
| --- | --- |
| Prijzen, pakketten, locaties, reviews, menu, doelen | `src/lib/site.ts` |
| Puntenregels, niveaus, beloningen, acties | `src/lib/loyalty.ts` |
| Intakevragen en berekening richtwaarden | `src/lib/intake.ts` |
| Instructies voor de AI | `src/lib/plans/prompt.ts` |
| Allergenen- en dieetcontrole | `src/lib/plans/allergens.ts` |
| Logo en foto's | `public/brand/`, `public/images/` |
| Kleuren en typografie | `src/app/globals.css` |

## Lokaal draaien

```bash
npm install
cp .env.example .env        # pas eventueel ADMIN_EMAILS aan
npm run db:migrate          # maakt data/steynpt.db aan
npm run dev                 # http://localhost:3000
```

Overige scripts: `npm run build`, `npm start`, `npm run typecheck`, `npm test` (unit tests voor de puntenregels) en `npm run db:generate` (nieuwe migratie na een wijziging in `src/lib/db/schema.ts`).

### AI instellen

Zet `ANTHROPIC_API_KEY` (aan te maken op platform.claude.com). Zonder sleutel werkt de site gewoon, maar maakt Steyn de schema's zelf. Voor lokaal testen zonder sleutel: `AI_MOCK=1` levert voorbeeldconcepten op (niet gebruiken in productie).

Een concept maken duurt meestal één à twee minuten en kost naar schatting 10 tot 25 cent per schema. Het genereren draait na het versturen van het formulier op de achtergrond (`after()`); de pagina's die dat starten hebben `maxDuration = 300`, zodat het ook op serverless hosting genoeg tijd krijgt.

### Beheerder worden

Zet je e-mailadres in `ADMIN_EMAILS` (komma-gescheiden voor meerdere). Wie zich met dat adres registreert of inlogt, krijgt automatisch toegang tot `/admin`.

## Live zetten

De site heeft een Node.js-server en een database nodig.

- **Eigen server of VPS** (bijvoorbeeld met een persistente schijf): `npm ci && npm run db:migrate && npm run build && npm start`. De SQLite-database staat in `data/`; maak daar back-ups van.
- **Vercel of andere serverless hosting**: gebruik een [Turso](https://turso.tech)-database. Zet `DATABASE_URL=libsql://…` en `DATABASE_AUTH_TOKEN`, en draai `npm run db:migrate` één keer tegen die database.

Zet altijd `NEXT_PUBLIC_SITE_URL` op het echte domein, zodat de uitnodigingslinks kloppen.

## Nog te bevestigen

- **Prijzen online coaching** (Start € 79, Pro € 129, Performance € 199 per maand) zijn een voorstel. Bestaande diensten hebben dezelfde prijzen als de oude site.
- **Ademcoaching** staat op "tarief op aanvraag".
- **Puntenwaarden, beloningen, niveauvoordelen en de lanceringsactie** (dubbele welkomstpunten tot 1 januari 2027) zijn voorstellen.
- Online betalen zit er nog niet in. Na een aanmelding plant Steyn een intake en zet het lid daarna in `/admin` op *actief*. Een betaalkoppeling (bijvoorbeeld Mollie) kan later worden toegevoegd.
- E-mailnotificaties bij nieuwe aanvragen zijn er nog niet; nieuwe aanvragen staan in `/admin`.
- De privacyverklaring is een basisversie; laat deze juridisch controleren. Voor het gebruik van AI met gezondheidsgegevens: sluit de verwerkersovereenkomst (DPA) met Anthropic af en controleer of de tekst over doorgifte buiten de EU klopt met je afspraken.
- De berekende richtwaarden en de allergenencontrole zijn hulpmiddelen; Steyn blijft verantwoordelijk voor de controle van elk schema.
