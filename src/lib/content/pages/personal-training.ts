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
    faq: section("Veelgestelde vragen", {
      eyebrow: line("Kleine kop", "Veelgestelde vragen", { max: 60 }),
      title: title("Titel", "Vragen over personal training in Amsterdam", { max: 100 }),
      questions: items(
        "Vragen",
        "Vraag",
        { q: line("Vraag", "", { max: 160 }), a: text("Antwoord", "", { max: 800 }) },
        [
          {
            q: "Wat kost een personal trainer bij SteynPT?",
            a: "Dat hangt af van hoeveel trainingen je neemt. Je kiest een losse training of een pakket van 10, 20 of 40 trainingen; hoe groter het pakket, hoe voordeliger per training. Alle prijzen staan hierboven en op de tarievenpagina.",
          },
          {
            q: "Waar vinden de trainingen plaats?",
            a: "Bij Gymbase aan de Overtoom in Amsterdam Oud-West, op een paar minuten lopen van het Vondelpark. Liever op een andere plek? Dan trainen we op locatie, bijvoorbeeld in het park of op je werk.",
          },
          {
            q: "Is de kennismaking echt gratis?",
            a: "Ja. In een gratis kennismaking van een half uur bespreken we je doelen en je achtergrond, en krijg je een rondleiding bij Gymbase. Daarna beslis je zelf of je wilt starten.",
          },
          {
            q: "Ik heb nog nooit in een gym getraind. Is personal training iets voor mij?",
            a: "Zeker. Steyn stemt je trainingsplan af op jouw niveau en let op de juiste uitvoering, zodat je veilig en met vertrouwen begint. Train je al jaren of heb je een specifiek sportdoel, dan krijg je net zo goed een plan op maat.",
          },
          {
            q: "Kan ik met z'n tweeën trainen?",
            a: "Ja, duo-training kan. Je traint dan samen met een vriend, partner of collega, tegen een toeslag per sessie. Je vindt die bij de tarieven.",
          },
          {
            q: "Kan ik personal training combineren met online coaching?",
            a: "Ja. Veel sporters combineren een paar 1-op-1-trainingen bij Gymbase met online coaching voor de dagen ertussen. Zo heb je ook buiten de trainingen een plan en een coach die meekijkt.",
          },
        ],
        { min: 1, max: 15 },
      ),
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
