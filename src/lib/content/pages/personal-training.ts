import { ctaSection, definePage, items, line, list, section, seoSection, text, title } from "../fields";

export const personalTraining = definePage({
  slug: "personal-training",
  title: "Personal training",
  path: "/personal-training",
  description: "1-op-1-training, topsport en de PT-pakketten.",
  sections: {
    hero: section("Bovenaan", {
      eyebrow: line("Kleine kop", "Personal training in Amsterdam Oud-West", { max: 60 }),
      title: title("Titel", "1-op-1. *100%* voor jouw doel.", { max: 80 }),
      intro: text(
        "Introductie",
        "Ongeacht jouw doel of sport: SteynPT gaat er 100% voor. Met een persoonlijk trainingsplan werken we zo efficiënt mogelijk naar jouw doel toe, met veel energie, aandacht voor de juiste uitvoering en een fijne sfeer. Je traint bij Gymbase aan de Overtoom, op een paar minuten van het Vondelpark, of op een plek die jou uitkomt.",
        { max: 500 },
      ),
      primary: line("Eerste knop", "Plan een gratis kennismaking", { max: 40 }),
      secondary: line("Tweede knop", "Bekijk de pakketten", { max: 40 }),
    }),
    leefstijl: section("Gezondere leefstijl", {
      eyebrow: line("Kleine kop", "Voor een gezondere leefstijl", { max: 60 }),
      title: title("Titel", "Een duidelijk plan, samen uitgevoerd", { max: 100 }),
      body: text(
        "Tekst",
        "Ik maak een gepersonaliseerd trainingsplan voor je, zodat we zo efficiënt mogelijk naar je doel toewerken. Met een duidelijk en overzichtelijk plan weet je precies wat je te wachten staat en wat je moet doen om jouw doel te bereiken.\n\nNaast ervaring in krachttraining heb ik een achtergrond in powerliften, boksen, CrossFit en sportspecifieke training. Samen maken we, indien gewenst, een mooie combinatie om jouw doel te bereiken.",
        { max: 2000, paragraphs: true },
      ),
      cardTitle: line("Kaart: titel", "Altijd inbegrepen", { max: 60 }),
      cardList: list(
        "Kaart: opsomming",
        [
          "Intakegesprek over je doelen en achtergrond",
          "Nulmeting: wegen, meten en bewegen",
          "Persoonlijk trainingsschema",
          "Voedingsadvies op basis van jouw doel",
          "Contactmomenten ook buiten de trainingen",
          "Trainen bij Gymbase (Overtoom, Oud-West) of op locatie",
        ],
        { max: 10, hint: "Eén punt per regel." },
      ),
    }),
    topsport: section("Specifieke doelen & topsport", {
      eyebrow: line("Kleine kop", "Specifieke doelen & topsport", { max: 60 }),
      title: title("Titel", "Begeleiding voor sporters die *meer* willen", { max: 100 }),
      intro: text(
        "Introductie",
        "Werk je naar een wedstrijd, wil je terugkomen na een blessure of zoek je die laatste procenten? Steyn is gespecialiseerd in de 1-op-1-begeleiding van (top)sporters en mensen met een specifiek doel.",
        { max: 500 },
      ),
      cards: items(
        "Kaarten",
        "Kaart",
        { title: line("Titel", "", { max: 60 }), text: text("Tekst", "", { max: 300 }) },
        [
          { title: "Doelgerichte periodisering", text: "Een plan dat toewerkt naar jouw wedstrijd, seizoen of moment suprême." },
          { title: "Sportspecifieke kracht", text: "Kracht, snelheid en explosiviteit vertaald naar jouw sport." },
          { title: "Blessurepreventie", text: "Bewegingsassessment en gerichte oefeningen om sterker en blessurevrij te blijven." },
          { title: "Herstel & ademhaling", text: "Slaap, voeding en ademtechnieken voor optimaal herstel en focus onder druk." },
          { title: "Techniekanalyse", text: "We analyseren je uitvoering en sturen bij, ook tussen de sessies door." },
          { title: "Past in je seizoen", text: "Afgestemd op trainingen bij je club, wedstrijden en reizen." },
        ],
        { fixed: true },
      ),
      primary: line("Eerste knop", "Bespreek jouw doel", { max: 40 }),
      secondary: line("Tweede knop", "Combineer met online coaching", { max: 40 }),
    }),
    tarieven: section(
      "Tarieven",
      {
        eyebrow: line("Kleine kop", "Tarieven", { max: 60 }),
        title: title("Titel", "1-op-1-pakketten", { max: 100 }),
        intro: text(
          "Introductie",
          "Train je graag 1-op-1 en wil je samen met Steyn alles uit je sessie halen? Kies dan een van de pakketten.",
          { max: 400 },
        ),
        button: line("Knop onder elk pakket", "Vraag dit pakket aan", { max: 40 }),
      },
      "De pakketten zelf pas je aan onder 'Prijzen en pakketten'.",
    ),
    reviews: section("Reviews", {
      eyebrow: line("Kleine kop", "Reviews", { max: 60 }),
      title: title("Titel", "Resultaat dat blijft", { max: 100 }),
    }),
    afsluiter: ctaSection({
      title: "Zin om kennis te maken?",
      text: "Plan een gratis kennismaking. We ontvangen je graag bij Gymbase.",
      primary: "Gratis kennismaking",
      secondary: "Of start online",
    }),
    seo: seoSection(
      "Personal trainer in Amsterdam Oud-West",
      "1-op-1 personal training bij Gymbase, Overtoom 371-w in Amsterdam Oud-West. Voor een gezondere leefstijl, specifieke doelen en topsport. Ook op locatie.",
    ),
  },
});
