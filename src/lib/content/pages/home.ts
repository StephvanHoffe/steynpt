import { SITE } from "../../site";
import { definePage, items, line, list, section, seoSection, text, title } from "../fields";

const card = { title: line("Titel", "", { max: 60 }), text: text("Tekst", "", { max: 300 }) };

export const home = definePage({
  slug: "home",
  title: "Homepage",
  path: "/",
  description: "De eerste pagina van de site.",
  sections: {
    hero: section("Bovenaan", {
      eyebrow: line("Kleine kop", "Personal training · Amsterdam Oud-West & online", { max: 80 }),
      title: title("Titel", "Sterker lichaam.\n*Gezonder* leven.", { max: 80 }),
      intro: text(
        "Introductie",
        "Ik ben Steyn van Leeuwen, personal trainer en orthomoleculair voedingstherapeut. Ik help je aan een gezondere leefstijl, begeleid je 1-op-1 naar je specifieke doel en coach sporters naar hun beste prestatie. Vanaf nu ook online, waar je ook bent.",
        { max: 500 },
      ),
      primary: line("Eerste knop", "Start online coaching", { max: 40 }),
      secondary: line("Tweede knop", "Gratis kennismaking", { max: 40 }),
      stats: items(
        "Kerncijfers",
        "Kerncijfer",
        { value: line("Groot", "", { max: 12 }), label: line("Klein eronder", "", { max: 40 }) },
        [
          { value: "1-op-1", label: "persoonlijke aandacht" },
          { value: "Online", label: "waar je ook bent" },
          { value: "24 uur", label: "reactietijd" },
          { value: "100%", label: "inzet voor jouw doel" },
        ],
        { fixed: true },
      ),
      badgeLabel: line("Kaartje op de foto: kleine kop", "Nieuw", { max: 20 }),
      badgeText: line("Kaartje op de foto: tekst", "Online coaching vanaf € {online-vanaf} per maand", { max: 60 }),
    }),
    diensten: section("Balk met diensten", {
      list: list("Diensten", ["Personal training", "Online coaching", "Ademcoaching", "Voedingscoaching", "Topsport", "Leefstijl"], {
        max: 8,
        hint: "Eén per regel. De smalle balk onder de foto.",
      }),
    }),
    online: section("Online coaching", {
      eyebrow: line("Kleine kop", "Nieuw bij SteynPT", { max: 60 }),
      title: title("Titel", "Online coaching. *Jouw coach*, altijd en overal.", { max: 100, hint: "Tussen *sterretjes* krijgt de tekst een markering." }),
      intro: text(
        "Introductie",
        "Dezelfde persoonlijke aanpak als in de gym, nu in je eigen online dashboard. Je krijgt een plan op maat, checkt wekelijks in en Steyn stuurt bij. Ideaal als je zelfstandig traint, veel reist of naast je PT-sessies extra begeleiding wilt.",
        { max: 500 },
      ),
      features: items(
        "Kenmerken",
        "Kenmerk",
        card,
        [
          { title: "Schema op maat", text: "Afgestemd op je doel, niveau en agenda." },
          { title: "Voedingsplan", text: "De juiste balans in macro- en micronutriënten." },
          { title: "Check-ins & metingen", text: "Je voortgang overzichtelijk in je dashboard." },
          { title: "Direct contact", text: "Steyn stuurt bij waar nodig." },
        ],
        { fixed: true },
      ),
      primary: line("Eerste knop", "Bekijk de pakketten", { max: 40 }),
      secondary: line("Tweede knop", "Gratis account aanmaken", { max: 40 }),
    }),
    aanbod: section("Aanbod", {
      eyebrow: line("Kleine kop", "Aanbod", { max: 60 }),
      title: title("Titel", "Eén coach, alles voor jouw doel", { max: 100 }),
      intro: text(
        "Introductie",
        "Met een breed scala aan opleidingen en jarenlange ervaring durft Steyn iedereen een garantie op resultaat te geven. Ben jij er klaar voor?",
        { max: 400 },
      ),
      button: line("Knop", "Alle tarieven", { max: 40 }),
      services: items(
        "Diensten",
        "Dienst",
        card,
        [
          { title: "Online coaching", text: "Schema, voedingsplan en wekelijkse check-ins in je eigen dashboard. Train waar en wanneer jij wilt." },
          { title: "Personal training", text: "1-op-1-training voor een gezondere leefstijl. Ongeacht jouw doel of sport: SteynPT gaat er 100% voor." },
          { title: "Topsport & specifieke doelen", text: "Sportspecifieke begeleiding, periodisering en blessurepreventie voor sporters die meer willen." },
          {
            title: "Ademcoaching",
            text: "1-op-1 of in groepsverband werken aan rust, focus, herstel en energie. Groepssessies voor teams, bedrijven en vriendengroepen op aanvraag.",
          },
          { title: "Voedingscoaching", text: "Bij afvallen en aankomen. Orthomoleculaire, leefstijl- en vitaliteitscoaching." },
        ],
        { fixed: true, hint: "Vaste volgorde: online coaching, personal training, topsport, ademcoaching, voedingscoaching. Elke kaart linkt naar zijn eigen pagina." },
      ),
      onlinePoints: list(
        "Punten bij online coaching",
        ["Trainings- en voedingsschema op maat", "Wekelijkse check-in en maandelijkse evaluatie", "Afspraken en voortgang in je dashboard", "Vanaf € {online-vanaf} per maand"],
        { max: 6, hint: "Eén per regel. Staan in de grote kaart van online coaching." },
      ),
      badge: line("Label op de grote kaart", "Nieuw", { max: 20 }),
      more: line("Link onder elke kaart", "Lees meer", { max: 30 }),
    }),
    over: section("Ontmoet Steyn", {
      eyebrow: line("Kleine kop", "Ontmoet Steyn", { max: 60 }),
      title: title("Titel", "Hallo, ik ben Steyn van Leeuwen", { max: 100 }),
      intro: text(
        "Introductie",
        "Fulltime personal trainer en voedingscoach, geboren in Hoevelaken en werkzaam in Amsterdam. Door een beginnende hernia van het hockeyen ontdekte ik krachttraining. Daar vond ik mijn passie, en de motivatie om duidelijkheid te brengen in de fitnesswereld.",
        { max: 600 },
      ),
      cardTitle: line("Kaartje op de foto: groot", "15 jaar", { max: 20 }),
      cardText: text("Kaartje op de foto: tekst", "Zo oud was ik toen ik op advies van de fysio begon met krachttraining.", { max: 160 }),
      button: line("Knop", "Lees mijn verhaal", { max: 40 }),
    }, "De expertises pas je aan onder 'Op elke pagina'."),
    werkwijze: section("Werkwijze", {
      eyebrow: line("Kleine kop", "Werkwijze", { max: 60 }),
      title: title("Titel", "Van intake tot resultaat", { max: 100 }),
      intro: text("Introductie", "Of je nu in de gym traint of online: iedere samenwerking begint met een intake en een plan op maat.", { max: 400 }),
    }, "De stappen zelf pas je aan onder 'Op elke pagina'."),
    vriendenactie: section("Vriendenactie", {
      eyebrow: line("Kleine kop", "Vriendenactie", { max: 60 }),
      title: title("Titel", "Breng een vriend mee. *{actie}.*", { max: 100 }),
      intro: text("Introductie", "Nodig een vriend uit voor online coaching. Je vriend krijgt {vriendkorting}; jij krijgt {jouwkorting} zodra je vriend start.", { max: 400 }),
      primary: line("Eerste knop", "Maak een gratis account aan", { max: 40 }),
      secondary: line("Tweede knop", "Zo werkt het", { max: 40 }),
    }),
    reviews: section("Reviews", {
      eyebrow: line("Kleine kop", "Reviews", { max: 60 }),
      title: title("Titel", "Wat sporters zeggen", { max: 100 }),
    }, "De reviews zelf pas je aan onder 'Op elke pagina'."),
    locaties: section("Locaties", {
      eyebrow: line("Kleine kop", "Bezoek ons", { max: 60 }),
      title: title("Titel", "Trainen waar het jou uitkomt", { max: 100 }),
      intro: text("Introductie", "Bij Gymbase aan de Overtoom in Amsterdam Oud-West, op jouw favoriete plek of volledig online.", { max: 400 }),
    }),
    seo: seoSection("SteynPT · Personal training en online coaching in Amsterdam", SITE.description),
  },
});
