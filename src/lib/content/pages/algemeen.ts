import { REFERRAL } from "../../referral-program";
import { LOCATIONS, SITE } from "../../site";
import { check, definePage, items, line, link, list, section, text, url } from "../fields";

export const algemeen = definePage({
  slug: "algemeen",
  title: "Op elke pagina",
  path: null,
  description: "Balk bovenaan, footer, vriendenactie, reviews, werkwijze, expertises en locatie.",
  sections: {
    aankondiging: section(
      "Balk bovenaan",
      {
        show: check("Balk tonen", true),
        label: line("Label", "Nieuw", { max: 20, optional: true, hint: "Het kleine gekleurde woord ervoor. Leeg laten mag." }),
        text: line("Tekst", "Nieuw: online coaching. Nodig een vriend uit en krijg samen 50% korting", { max: 120 }),
        href: link("Linkt naar", "/vriend-uitnodigen"),
      },
      "De zwarte balk boven het menu, op elke pagina.",
    ),
    vriendenactie: section(
      "Vriendenactie",
      {
        headline: line("Naam van de actie", REFERRAL.headline, { max: 60, noVars: true, hint: "Automatische waarde {actie}." }),
        friendReward: line("Wat de vriend krijgt", REFERRAL.friendReward, { max: 120, noVars: true, hint: "Automatische waarde {vriendkorting}." }),
        referrerReward: line("Wat de uitnodiger krijgt", REFERRAL.referrerReward, { max: 120, noVars: true, hint: "Automatische waarde {jouwkorting}." }),
        steps: items(
          "Stappen",
          "Stap",
          { title: line("Titel", "", { max: 80 }), text: text("Tekst", "", { max: 300 }) },
          [
            { title: "Deel je persoonlijke link", text: "Je vindt hem in Mijn omgeving en deelt hem met één tik via WhatsApp of e-mail." },
            { title: "Je vriend meldt zich aan", text: "Via jouw link krijgt je vriend {vriendkorting}." },
            { title: "Je vriend start met online coaching", text: "Jij krijgt dan {jouwkorting}. Steyn verrekent het met je volgende factuur." },
          ],
          { fixed: true },
        ),
      },
      "Deze teksten komen terug op de homepage, bij online coaching, de vriendenactie en in Mijn omgeving.",
    ),
    afsluiter: section(
      "Standaard afsluiter",
      {
        title: line("Titel", "Klaar om te starten?", { max: 80 }),
        text: text("Tekst", "Maak gratis een account aan, kies je pakket en Steyn neemt binnen 24 uur contact met je op.", { max: 300 }),
        primary: line("Witte knop", "Start online coaching", { max: 40 }),
        secondary: line("Tweede knop", "Gratis kennismaking", { max: 40 }),
      },
      "Het zwarte blok onderaan de homepage en de tarieven. Andere pagina's hebben een eigen afsluiter.",
    ),
    reviews: section(
      "Reviews",
      {
        reviews: items(
          "Reviews",
          "Review",
          { quote: text("Wat zegt de sporter?", "", { max: 400 }), name: line("Naam", "", { max: 60 }), role: line("Beroep of omschrijving", "", { max: 60, optional: true }) },
          [
            {
              quote: "Steyn heeft mij geleerd dat het niet alleen gaat om afvallen, maar om bewustwording van je leefstijl.",
              name: "Miranda 'd Weegman",
              role: "Operatieassistente",
            },
            {
              quote: "Ik ben al jaren bezig met afvallen maar bleef altijd rond hetzelfde gewicht. Sinds ik met Steyn train behaal ik eindelijk resultaat.",
              name: "Steve van Maanen",
              role: "Bakker",
            },
          ],
          { min: 1, max: 8 },
        ),
      },
      "Op de homepage, bij personal training en de tarieven.",
    ),
    werkwijze: section(
      "Werkwijze",
      {
        steps: items(
          "Stappen",
          "Stap",
          { title: line("Titel", "", { max: 60 }), text: text("Tekst", "", { max: 300 }) },
          [
            { title: "Intake", text: "We beginnen met een gesprek over jouw doelen, je achtergrond en wat je tot nu toe hebt geprobeerd." },
            { title: "Nulmeting", text: "We wegen, meten en bewegen: zo zien we precies waar we aan moeten werken." },
            { title: "Plan op maat", text: "Je krijgt een persoonlijk schema en voedingsplan met de ideale balans in macro- en micronutriënten." },
            { title: "Coaching", text: "Ook buiten de trainingen hebben we contact. We sturen bij tot je doel bereikt is, en daarna." },
          ],
          { min: 2, max: 6 },
        ),
      },
      "De genummerde stappen op de homepage en bij Over Steyn.",
    ),
    expertises: section(
      "Expertises",
      {
        list: list(
          "Expertises",
          ["Personal Trainer", "Orthomoleculair voedingstherapeut", "Leefstijl- en vitaliteitscoaching", "Ademcoaching", "Powerliften", "Boksen", "CrossFit", "Sportspecifieke training"],
          { hint: "Eén per regel. Op de homepage en bij Over Steyn." },
        ),
      },
    ),
    locatie: section(
      "Locatie en contact",
      {
        name: line("Naam van de locatie", LOCATIONS[0].name, { max: 60 }),
        street: line("Straat en huisnummer", LOCATIONS[0].street, { max: 80, hint: "De routeknop naar Google Maps gebruikt dit adres." }),
        city: line("Postcode en plaats", LOCATIONS[0].city, { max: 80 }),
        onLocationTitle: line("Kop 'op locatie'", "Op locatie", { max: 40 }),
        onLocation: text(
          "Tekst 'op locatie'",
          "Naast onze vaste locatie Gymbase komen we ook op locatie: in jouw favoriete park of in de kantine van je werk.",
          { max: 300 },
        ),
        onlineTitle: line("Kop 'online'", "Online", { max: 40 }),
        online: text("Tekst 'online'", "Met online coaching train je waar en wanneer jij wilt, met Steyn altijd binnen handbereik.", { max: 300 }),
        responseTime: line("Reactietijd", "Ik streef ernaar om binnen 24 uur contact met je op te nemen.", { max: 120, hint: "Op de contactpagina." }),
        instagramHandle: line("Instagram-naam", SITE.instagram.handle, { max: 40 }),
        instagramUrl: url("Instagram-link", SITE.instagram.url),
      },
      "Op de homepage, de contactpagina en in de footer.",
    ),
    footer: section(
      "Footer",
      {
        intro: text("Tekst onder het logo", "Personal training, online coaching, voedingscoaching en ademcoaching in Amsterdam.", { max: 200 }),
        extra: line("Regel onder de locatie", "Op locatie & online", { max: 60, optional: true }),
        copyright: line("Onderste regel", "SteynPT · Personal Training Amsterdam", { max: 80, hint: "Het © en het jaartal komen er automatisch voor." }),
      },
      "Het zwarte blok helemaal onderaan elke pagina.",
    ),
  },
});
