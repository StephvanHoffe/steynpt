// Pagina's met weinig onderdelen: voedingscoaching, tarieven, over Steyn, vriendenactie, contact en privacy.
import { ctaSection, definePage, items, line, list, section, seoSection, text, title } from "../fields";

export const voedingscoaching = definePage({
  slug: "voedingscoaching",
  title: "Voedingscoaching",
  path: "/voedingscoaching",
  description: "Voedingsbegeleiding, waarbij Steyn helpt en hoe er gemeten wordt.",
  sections: {
    hero: section("Bovenaan", {
      eyebrow: line("Kleine kop", "Voedingscoach in Amsterdam Oud-West", { max: 60 }),
      title: title("Titel", "Voeding die *werkt* voor jou", { max: 80 }),
      intro: text(
        "Introductie",
        "Aan de hand van jouw doelen maak ik een gericht voedingsplan voor je. Stap voor stap verbeteren we je voeding en je gezondheid. Orthomoleculaire, leefstijl- en vitaliteitscoaching.",
        { max: 500 },
      ),
      primary: line("Knop", "Vraag een gratis kennismaking aan", { max: 40 }),
    }),
    begeleiding: section("Voedingsbegeleiding", {
      eyebrow: line("Kleine kop", "Voedingsbegeleiding", { max: 60 }),
      title: title("Titel", "De juiste balans in macro's én micro's", { max: 100 }),
      body: text(
        "Tekst",
        "Ik ben orthomoleculair voedingstherapeut en help je aan de juiste balans in macro- en micronutriënten. Geen crashdiëten, maar een plan dat past bij jouw leven en dat je volhoudt.\n\nWil je afvallen, aankomen of heb je klachten? Door je voeding aan te passen kunnen we samen veel bereiken.",
        { max: 2000, paragraphs: true },
      ),
      cardTitle: line("Kaart: titel", "Ik help je onder andere bij", { max: 60 }),
      cardList: list("Kaart: opsomming", ["Afvallen of aankomen", "Darmklachten", "Verhoogd cholesterol", "Verhoogde bloeddruk", "Acne", "Weinig energie"], {
        max: 12,
        hint: "Eén punt per regel.",
      }),
    }),
    meten: section("Meten is weten", {
      eyebrow: line("Kleine kop", "Meten is weten", { max: 60 }),
      title: title("Titel", "Resultaat dat je kunt zien", { max: 100 }),
      intro: text("Introductie", "We starten met een nulmeting en meten tussentijds je voortgang, zodat we precies weten wat werkt.", { max: 400 }),
      points: list(
        "Opsomming",
        ["Intake en analyse van je eetpatroon", "Voedingsplan op maat", "Tussentijdse metingen en bijsturing", "Onderdeel van elk PT- en online pakket"],
        { max: 8, hint: "Eén punt per regel." },
      ),
    }),
    faq: section("Veelgestelde vragen", {
      eyebrow: line("Kleine kop", "Veelgestelde vragen", { max: 60 }),
      title: title("Titel", "Vragen over voedingscoaching", { max: 100 }),
      questions: items(
        "Vragen",
        "Vraag",
        { q: line("Vraag", "", { max: 160 }), a: text("Antwoord", "", { max: 800 }) },
        [
          {
            q: "Wat doet een orthomoleculair voedingstherapeut?",
            a: "Een orthomoleculair voedingstherapeut kijkt niet alleen naar calorieën en macronutriënten (eiwitten, koolhydraten en vetten), maar ook naar micronutriënten zoals vitamines en mineralen. Zo werken we aan je doel én aan hoe je je voelt.",
          },
          {
            q: "Hoe verloopt voedingscoaching?",
            a: "We beginnen met een intake en een analyse van je eetpatroon. Daarna krijg je een voedingsplan op maat. Met tussentijdse metingen zien we wat werkt en sturen we bij.",
          },
          {
            q: "Moet ik een streng dieet volgen?",
            a: "Nee. Geen crashdiëten, maar een plan dat past bij jouw leven en dat je volhoudt. Stap voor stap verbeteren we je voeding.",
          },
          {
            q: "Kan voedingscoaching ook online?",
            a: "Ja. Voedingscoaching is onderdeel van online coaching: je voeding en je wekelijkse check-ins staan in je eigen dashboard. Woon je in Amsterdam, dan kun je ook langskomen bij Gymbase in Oud-West.",
          },
          {
            q: "Ik heb een medische aandoening. Is voedingscoaching dan geschikt?",
            a: "Voedingscoaching vervangt geen behandeling door je arts. Gebruik je medicijnen of heb je een aandoening, vertel het dan tijdens de intake. Waar nodig stemmen we het plan af met je arts.",
          },
        ],
        { min: 1, max: 15 },
      ),
    }),
    afsluiter: ctaSection({
      title: "Ook online mogelijk",
      text: "Voedingscoaching is onderdeel van online coaching. Je voeding en je wekelijkse check-ins staan gewoon in je eigen dashboard.",
      primary: "Bekijk online coaching",
      secondary: "Gratis kennismaking",
    }),
    seo: seoSection(
      "Voedingscoach in Amsterdam Oud-West",
      "Voedingscoaching door orthomoleculair voedingstherapeut Steyn van Leeuwen, in Amsterdam Oud-West of online. Bij afvallen, aankomen, energie en darmklachten.",
    ),
  },
});

export const tarieven = definePage({
  slug: "tarieven",
  title: "Tarieven",
  path: "/tarieven",
  description: "De koppen en uitleg op de tarievenpagina. De prijzen zelf staan onder 'Prijzen en pakketten'.",
  sections: {
    hero: section("Bovenaan", {
      eyebrow: line("Kleine kop", "Personal training en coaching in Amsterdam", { max: 60 }),
      title: title("Titel", "Tarieven", { max: 80 }),
      intro: text(
        "Introductie",
        "Transparante prijzen, geen verrassingen. Kies het pakket dat bij jouw doel past, of plan eerst een gratis kennismaking.",
        { max: 500 },
      ),
      primary: line("Knop", "Gratis kennismaking", { max: 40 }),
    }),
    menu: section(
      "Snelmenu",
      {
        online: line("Online coaching", "Online coaching", { max: 30 }),
        pt: line("Personal training", "1-op-1-training", { max: 30 }),
        adem: line("Ademcoaching", "Ademcoaching", { max: 30 }),
      },
      "De knoppen onder de titel die naar elk blok springen.",
    ),
    online: section("Online coaching", {
      eyebrow: line("Kleine kop", "Nieuw · Online coaching", { max: 60 }),
      title: title("Titel", "Online coaching", { max: 100 }),
      intro: text("Introductie", "Maandelijkse begeleiding met je eigen dashboard, wekelijkse check-ins en je metingen in één overzicht.", { max: 400 }),
    }),
    pt: section("Personal training", {
      eyebrow: line("Kleine kop", "1-op-1-trainingen", { max: 60 }),
      title: title("Titel", "Personal training", { max: 100 }),
      intro: text(
        "Introductie",
        "Train je graag 1-op-1 en wil je samen met Steyn alles uit je sessie halen? Kies dan een van de pakketten.",
        { max: 400 },
      ),
      button: line("Knop onder elk pakket", "Vraag dit pakket aan", { max: 40 }),
      note: line("Kleine tekst onder de pakketten", "", { max: 200, optional: true }),
    }),
    adem: section("Ademcoaching", {
      eyebrow: line("Kleine kop", "1-op-1 en in groepsverband", { max: 60 }),
      title: title("Titel", "Ademcoaching", { max: 100 }),
      intro: text(
        "Introductie",
        "Een persoonlijke ademsessie met een vast tarief. Voor bedrijven, sportteams en vriendengroepen zijn groepssessies op aanvraag.",
        { max: 400 },
      ),
      soloButton: line("1-op-1: knop", "Plan een ademsessie", { max: 40 }),
      groupLabel: line("Groep: kleine kop", "In groepsverband", { max: 30 }),
      groupTitle: line("Groep: titel", "Groepssessie", { max: 40 }),
      groupPrice: line("Groep: prijs", "Op aanvraag", { max: 30 }),
      groupText: text(
        "Groep: tekst",
        "Opzet, duur en tarief stemmen we af op jullie groepsgrootte en locatie.",
        { max: 300 },
      ),
      groupButton: line("Groep: knop", "Vraag een groepssessie aan", { max: 40 }),
      moreLink: line("Groep: link eronder", "Meer over ademcoaching", { max: 40 }),
    }),
    reviews: section("Reviews", {
      eyebrow: line("Kleine kop", "Reviews", { max: 60 }),
      title: title("Titel", "Wat sporters zeggen", { max: 100 }),
    }),
    seo: seoSection(
      "Tarieven personal training en coaching in Amsterdam",
      "Alle tarieven van SteynPT: 1-op-1 personal training bij Gymbase in Amsterdam Oud-West, online coaching en ademcoaching (1-op-1 en in groepsverband).",
    ),
  },
});

export const overSteyn = definePage({
  slug: "over-steyn",
  title: "Over Steyn",
  path: "/over-steyn",
  description: "Het verhaal van Steyn en de werkwijze.",
  sections: {
    hero: section("Bovenaan", {
      eyebrow: line("Kleine kop", "Personal trainer in Amsterdam", { max: 60 }),
      title: title("Titel", "Over *Steyn*", { max: 80 }),
      intro: text(
        "Introductie",
        "Fulltime personal trainer en voedingscoach. Geboren in Hoevelaken, werkzaam in Amsterdam, en elke dag nog aan het doorleren.",
        { max: 500 },
      ),
      primary: line("Knop", "Vraag een kennismaking aan", { max: 40 }),
    }),
    verhaal: section("Mijn verhaal", {
      eyebrow: line("Kleine kop", "Mijn verhaal", { max: 60 }),
      title: title("Titel", "Van hernia naar passie", { max: 100 }),
      body: text(
        "Tekst",
        [
          "Ik ben Steyn van Leeuwen, fulltime personal trainer en voedingscoach. Ik ben geboren in Hoevelaken en nu werkzaam als PT in Amsterdam. Toen ik 15 was, begon ik met krachttraining: door het hockeyen had ik een beginnende hernia in mijn onderrug, en de fysiotherapeut raadde het me aan.",
          "Ik vond hierin mijn passie en kwam er al snel achter dat er veel onduidelijkheid is in de fitnesswereld: iedereen vindt er iets anders van. Daar is mijn interesse begonnen. Ik heb meerdere opleidingen gevolgd in personal training en voeding, en ik blijf elke dag doorleren.",
          "Ik geef persoonlijke trainingen, maak voedingsplannen op maat, begeleid sporters naar specifieke doelen en geef ademcoaching, 1-op-1 en in groepsverband. Samen werken we aan jouw doelen: binnen, buiten, in de gym, thuis, op kantoor of online.",
          "Wil je serieus aan de slag met je gezondheid en weten wat ik voor je kan betekenen? Neem dan contact met mij op!",
        ].join("\n\n"),
        { max: 3000, paragraphs: true },
      ),
      expertisesTitle: line("Kop boven de expertises", "Expertises", { max: 40, hint: "De expertises zelf pas je aan onder 'Op elke pagina'." }),
    }),
    werkwijze: section(
      "Werkwijze",
      {
        eyebrow: line("Kleine kop", "Werkwijze", { max: 60 }),
        title: title("Titel", "Zo werken we samen", { max: 100 }),
        intro: text(
          "Introductie",
          "Ik coach je niet alleen tijdens de trainingen. Ook daarbuiten hebben we contactmomenten om je gezondheid naar een hoger niveau te tillen.",
          { max: 400 },
        ),
      },
      "De stappen zelf pas je aan onder 'Op elke pagina'.",
    ),
    afsluiter: ctaSection({
      title: "Leuk om eens kennis te maken!",
      text: "We ontvangen je graag bij Gymbase, of start direct online.",
      primary: "Gratis kennismaking",
      secondary: "Start online coaching",
    }),
    seo: seoSection(
      "Over Steyn van Leeuwen, personal trainer in Amsterdam",
      "Maak kennis met Steyn van Leeuwen: fulltime personal trainer, voedingscoach en orthomoleculair voedingstherapeut in Amsterdam.",
    ),
  },
});

export const vriendUitnodigen = definePage({
  slug: "vriend-uitnodigen",
  title: "Vriendenactie",
  path: "/vriend-uitnodigen",
  description: "Uitleg en voorwaarden van de vriendenactie.",
  sections: {
    hero: section(
      "Bovenaan",
      {
        eyebrow: line("Kleine kop", "Vriendenactie online coaching", { max: 60 }),
        title: title("Titel", "Breng een vriend mee. *{actie}.*", { max: 100 }),
        intro: text(
          "Introductie",
          "Train je al bij Steyn? Nodig een vriend uit voor online coaching. Je vriend krijgt {vriendkorting} en jij krijgt {jouwkorting} zodra je vriend start.",
          { max: 500 },
        ),
        primary: line("Eerste knop", "Naar mijn uitnodigingslink", { max: 40 }),
        secondary: line("Tweede knop", "Nog geen account? Meld je aan", { max: 40 }),
      },
      "De korting zelf en de drie stappen pas je aan onder 'Op elke pagina'.",
    ),
    stappen: section("Zo werkt het", {
      eyebrow: line("Kleine kop", "Zo werkt het", { max: 60 }),
      title: title("Titel", "In drie stappen", { max: 100 }),
      intro: text(
        "Introductie",
        "Je persoonlijke link staat in Mijn omgeving. Wie zich via jouw link aanmeldt, wordt automatisch aan jou gekoppeld; in je dashboard zie je wie zich heeft aangemeld en wie al is gestart.",
        { max: 500 },
      ),
    }),
    voorwaarden: section("Voorwaarden", {
      title: title("Titel", "Voorwaarden vriendenactie", { max: 100 }),
      points: list(
        "Voorwaarden",
        [
          "De actie geldt voor nieuwe klanten van online coaching die zich aanmelden via een persoonlijke uitnodigingslink of -code.",
          "De nieuwe klant krijgt {vriendkorting}.",
          "De uitnodiger krijgt {jouwkorting} per vriend die daadwerkelijk start; Steyn verrekent dit met een volgende factuur.",
          "Kortingen zijn niet inwisselbaar voor geld en niet te combineren met andere acties.",
          "Jezelf uitnodigen of meerdere accounts aanmaken is niet toegestaan.",
          "SteynPT kan de actie aanpassen of beëindigen; reeds verdiende kortingen blijven geldig.",
        ],
        { max: 15, hint: "Eén voorwaarde per regel." },
      ),
    }),
    afsluiter: ctaSection({
      title: "Nog geen klant?",
      text: "Maak een gratis account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op.",
      primary: "Bekijk online coaching",
      secondary: "Gratis kennismaking",
    }),
    seo: seoSection("Vriend uitnodigen", "Nodig een vriend uit voor online coaching bij SteynPT. {actie}: je vriend krijgt {vriendkorting}."),
  },
});

export const contact = definePage({
  slug: "contact",
  title: "Contact",
  path: "/contact",
  description: "De contactpagina met het aanvraagformulier.",
  sections: {
    hero: section(
      "Bovenaan",
      {
        eyebrow: line("Kleine kop", "Gratis kennismaking bij Gymbase in Amsterdam", { max: 60 }),
        title: title("Titel", "Leuk om eens *kennis* te maken!", { max: 80 }),
        intro: text(
          "Introductie",
          "We nodigen je graag uit bij Gymbase voor een gratis kennismaking. We vertellen je meer over onze werkwijze, geven je een rondleiding en horen graag meer over jouw verwachtingen en doelen.",
          { max: 500 },
        ),
        tipTitle: line("Tip: vetgedrukt begin", "Wist je dat", { max: 40 }),
        tipText: line("Tip: tekst", "SteynPT ook buiten de gym traint?", { max: 160, hint: "Daarna volgt automatisch de tekst 'op locatie' van 'Op elke pagina'." }),
      },
      "Adres, reactietijd en Instagram pas je aan onder 'Op elke pagina'.",
    ),
    formulier: section("Formulier", {
      title: title("Titel", "Vraag een kennismaking aan", { max: 80 }),
      intro: text("Introductie", "Vul je gegevens in, dan nemen we contact met je op om een moment te plannen.", { max: 300 }),
    }),
    seo: seoSection(
      "Contact en gratis kennismaking bij Gymbase Amsterdam",
      "Vraag een gratis kennismaking aan. Je bent welkom bij Gymbase, Overtoom 371-w in Amsterdam Oud-West: vlak bij het Vondelpark, tram 1 om de hoek.",
    ),
  },
});

export const privacy = definePage({
  slug: "privacy",
  title: "Privacyverklaring",
  path: "/privacy",
  description: "De privacyverklaring.",
  sections: {
    intro: section("Bovenaan", {
      eyebrow: line("Kleine kop", "SteynPT", { max: 60 }),
      title: title("Titel", "Privacyverklaring", { max: 80 }),
      intro: text(
        "Introductie",
        "SteynPT gaat zorgvuldig om met je persoonsgegevens en houdt zich aan de Algemene Verordening Gegevensbescherming (AVG). Hieronder lees je welke gegevens we verwerken en waarom.",
        { max: 600 },
      ),
    }),
    onderdelen: section("Onderdelen", {
      sections: items(
        "Onderdelen",
        "Onderdeel",
        { title: line("Kop", "", { max: 100 }), body: text("Tekst", "", { max: 4000, paragraphs: true }) },
        [
          {
            title: "Wie zijn wij?",
            body: "SteynPT is de onderneming van Steyn van Leeuwen, personal trainer in Amsterdam. SteynPT is verantwoordelijk voor de verwerking van je persoonsgegevens zoals beschreven in deze verklaring. Heb je een vraag over je privacy of wil je een verzoek doen? Neem contact op via het contactformulier op deze website.",
          },
          {
            title: "Welke gegevens verwerken we?",
            body: [
              "Contactaanvragen: je naam, e-mailadres, (optioneel) telefoonnummer, je interesse en je bericht.",
              "Account: je naam, e-mailadres, telefoonnummer, doel, gekozen pakket, door wie je bent uitgenodigd en wie jij hebt uitgenodigd (vriendenactie).",
              "Afspraken: type, datum, tijd, locatie en je eventuele opmerking. Steyn zet deze afspraken, met je naam en contactgegevens, via een beveiligde, geheime link in zijn eigen agenda (bijvoorbeeld Google of Apple Agenda).",
              "Metingen: gewicht, vetpercentage, spiermassa en omtrekmaten die Steyn met je bijhoudt. Dit zijn gezondheidsgegevens; je ziet ze zelf in Mijn omgeving.",
              "Check-ins: je wekelijkse scores voor energie, slaap en voeding, aantal trainingen, eventueel je gewicht en opmerkingen. Dit zijn gezondheidsgegevens; we verwerken ze alleen met jouw uitdrukkelijke toestemming en uitsluitend voor je coaching.",
              "Intake: je doel, geslacht, geboortejaar, lengte, gewicht, activiteit, trainingservaring en -wensen, blessures, eetstijl, allergieën en eventuele medische aandachtspunten. Ook dit zijn gezondheidsgegevens; we gebruiken ze alleen met jouw uitdrukkelijke toestemming en alleen om je trainings- en voedingsschema te maken.",
            ].join("\n\n"),
          },
          {
            title: "Gebruik van AI voor je schema",
            body: [
              "Voor een eerste opzet van je trainings- en voedingsschema gebruiken we het AI-model Claude van Anthropic. We sturen daarvoor alleen de intakegegevens die nodig zijn voor het schema, zonder je naam, e-mailadres of telefoonnummer.",
              "De AI neemt geen beslissingen over jou: Steyn controleert en past elk schema aan voordat je het te zien krijgt. Anthropic verwerkt de gegevens als verwerker en gebruikt ze volgens zijn zakelijke voorwaarden niet om AI-modellen te trainen. Anthropic is gevestigd in de Verenigde Staten; de doorgifte gebeurt op basis van passende waarborgen, zoals de standaardcontractbepalingen van de Europese Commissie.",
              "Je kunt je toestemming altijd intrekken. Neem dan contact met ons op; Steyn maakt je schema dan volledig zelf.",
            ].join("\n\n"),
          },
          {
            title: "Waarom?",
            body: "Om contact met je op te nemen, afspraken te plannen, je coaching te verzorgen, je voortgang te volgen en de vriendenactie uit te voeren. Nieuwsbrieven en acties ontvang je alleen als je daar zelf voor kiest.",
          },
          {
            title: "Hoe lang bewaren we je gegevens?",
            body: "Zolang je account bestaat of zolang nodig is voor je traject. Contactaanvragen zonder vervolg verwijderen we uiterlijk na 12 maanden. Wettelijke bewaarplichten (zoals voor facturen) blijven gelden.",
          },
          {
            title: "Delen met anderen",
            body: "We verkopen je gegevens nooit. We delen ze alleen met partijen die nodig zijn om de website en je coaching te laten werken (zoals de hosting, de AI-dienst hierboven en de agenda-app van Steyn), onder passende afspraken.",
          },
          {
            title: "Beveiliging",
            body: "Wachtwoorden worden versleuteld opgeslagen en je sessie is beveiligd met een veilige cookie. Alleen Steyn heeft toegang tot je coachinggegevens en intake.",
          },
          {
            title: "Jouw rechten",
            body: "Je kunt je gegevens altijd inzien en aanpassen in je profiel, en je account met alle bijbehorende gegevens zelf verwijderen. Voor andere verzoeken (zoals een kopie van je gegevens of het intrekken van toestemming) kun je contact met ons opnemen. Ben je het niet eens met hoe we met je gegevens omgaan? Dan kun je een klacht indienen bij de Autoriteit Persoonsgegevens.",
          },
          {
            title: "Cookies",
            body: "We gebruiken alleen functionele cookies: om je ingelogd te houden, om je taalkeuze te onthouden en om bij te houden via wiens uitnodiging je binnenkomt. Er worden geen tracking- of advertentiecookies geplaatst.",
          },
        ],
        { min: 1, max: 20 },
      ),
    }),
    seo: seoSection("Privacyverklaring", "Hoe SteynPT omgaat met je persoonsgegevens."),
  },
});
