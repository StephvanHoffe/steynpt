import { ctaSection, definePage, items, line, list, section, seoSection, text, title } from "../fields";

const card = { title: line("Titel", "", { max: 60 }), text: text("Tekst", "", { max: 300 }) };

export const ademcoaching = definePage({
  slug: "ademcoaching",
  title: "Ademcoaching",
  path: "/ademcoaching",
  description: "De ademsessie 1-op-1, groepssessies op aanvraag en veelgestelde vragen.",
  sections: {
    hero: section("Bovenaan", {
      eyebrow: line("Kleine kop", "Ademcoaching in Amsterdam, 1-op-1 en in groepsverband", { max: 60 }),
      title: title("Titel", "Adem in. *Kom tot rust.* Presteer beter.", { max: 80 }),
      intro: text(
        "Introductie",
        "Je ademhaling is het krachtigste gereedschap dat je altijd bij je hebt. In een persoonlijke ademsessie van {ademduur} leer je hoe je met je adem stress verlaagt, je focus vergroot en sneller herstelt. Met je team of groep kan het ook: groepssessies zijn op aanvraag.",
        { max: 500 },
      ),
      primary: line("Eerste knop", "Plan een ademsessie", { max: 40 }),
      secondary: line("Tweede knop", "Groepssessie aanvragen", { max: 40 }),
    }),
    voordelen: section("Wat het je oplevert", {
      eyebrow: line("Kleine kop", "Wat het je oplevert", { max: 60 }),
      title: title("Titel", "Kleine verandering, groot effect", { max: 100 }),
      intro: text(
        "Introductie",
        "Ademcoaching sluit naadloos aan bij de visie van SteynPT: een gezonde leefstijl draait niet alleen om trainen en voeding, maar ook om rust en herstel.",
        { max: 400 },
      ),
      cards: items(
        "Kaarten",
        "Kaart",
        card,
        [
          { title: "Rust & focus", text: "Leer je zenuwstelsel tot rust te brengen en helder te blijven onder druk." },
          { title: "Beter slapen", text: "Ademtechnieken die je helpen ontspannen en dieper herstellen." },
          { title: "Meer energie", text: "Een efficiëntere ademhaling geeft meer energie gedurende de dag." },
          { title: "Sportprestatie", text: "Verbeter je uithoudingsvermogen, herstel tussen inspanningen en concentratie." },
        ],
        { fixed: true },
      ),
    }),
    vormen: section(
      "1-op-1 of in een groep",
      {
        eyebrow: line("Kleine kop", "Twee vormen", { max: 60 }),
        title: title("Titel", "1-op-1 of met je groep", { max: 100 }),
        soloLabel: line("1-op-1: kleine kop", "1-op-1", { max: 30 }),
        perSession: line("1-op-1: na de prijs", "per sessie", { max: 30 }),
        soloButton: line("1-op-1: knop", "Plan een ademsessie", { max: 40 }),
        groupLabel: line("Groep: kleine kop", "In groepsverband", { max: 30 }),
        groupTitle: line("Groep: titel", "Groepssessie", { max: 40 }),
        groupPrice: line("Groep: prijs", "Op aanvraag", { max: 30 }),
        groupText: text("Groep: tekst", "Opzet, duur en tarief stemmen we af op jullie groepsgrootte en locatie.", { max: 300 }),
        groups: items(
          "Groep: voor wie",
          "Groep",
          card,
          [
            { title: "Bedrijven", text: "Een vitaliteitssessie op kantoor of tijdens een teamdag. Werkt direct tegen werkstress." },
            { title: "Sportteams", text: "Ademtraining als onderdeel van warming-up, herstel en mentale voorbereiding." },
            { title: "Vrienden & groepen", text: "Samen iets nieuws ervaren, binnen bij Gymbase of buiten in het park." },
          ],
          { fixed: true },
        ),
        groupButton: line("Groep: knop", "Vraag een groepssessie aan", { max: 40 }),
      },
      "Naam, prijs, duur en inhoud van de 1-op-1-sessie pas je aan onder 'Prijzen en pakketten'.",
    ),
    sessie: section("Zo ziet een sessie eruit", {
      title: title("Titel", "Zo ziet een sessie eruit", { max: 100 }),
      steps: items(
        "Stappen",
        "Stap",
        card,
        [
          { title: "Uitleg", text: "Wat gebeurt er in je lichaam als je ademt, en waarom werkt dit?" },
          { title: "Oefenen", text: "Basistechnieken voor ontspanning, focus en energie die je overal kunt toepassen." },
          { title: "Begeleide ademsessie", text: "Een langere sessie waarin Steyn je stap voor stap begeleidt." },
          { title: "Meenemen", text: "Je gaat naar huis met concrete oefeningen voor je dagelijks leven of sport." },
        ],
        { min: 2, max: 6 },
      ),
    }),
    faq: section("Veelgestelde vragen", {
      eyebrow: line("Kleine kop", "Veelgestelde vragen", { max: 60 }),
      title: title("Titel", "Goed om te weten", { max: 100 }),
      points: list(
        "Opsomming",
        ["1-op-1: {ademduur} voor € {ademprijs}", "Groepssessies op aanvraag, vanaf 3 personen", "Geen ervaring nodig", "Op locatie, bij Gymbase of buiten"],
        { max: 8, hint: "Eén punt per regel." },
      ),
      questions: items(
        "Vragen",
        "Vraag",
        { q: line("Vraag", "", { max: 160 }), a: text("Antwoord", "", { max: 800 }) },
        [
          {
            q: "Hoe lang duurt een 1-op-1-ademsessie en wat kost het?",
            a: "Een 1-op-1-ademsessie duurt {ademduur} en kost € {ademprijs}. In die tijd is er ruimte voor uitleg, oefenen en een langere begeleide ademsessie.",
          },
          {
            q: "Hoe werkt een groepssessie?",
            a: "Groepssessies zijn op aanvraag. Neem contact op, dan stemmen we de opzet, duur en prijs af op jullie groepsgrootte en locatie. Een sessie werkt het best met kleine tot middelgrote groepen.",
          },
          { q: "Heb ik ervaring nodig?", a: "Nee. Iedere sessie start met uitleg en de oefeningen worden afgestemd op beginners én gevorderden." },
          {
            q: "Is ademcoaching voor iedereen geschikt?",
            a: "Voor de meeste mensen wel. Ben je zwanger, heb je epilepsie, hart- en vaatziekten of andere medische klachten? Meld het vooraf, dan passen we de oefeningen aan of overleggen we eerst met je arts.",
          },
          { q: "Waar vindt een sessie plaats?", a: "Bij Gymbase in Amsterdam, bij jullie op kantoor of buiten, zolang er een rustige plek is om te liggen of te zitten." },
        ],
        { min: 1, max: 15 },
      ),
    }),
    afsluiter: ctaSection({
      title: "Plan je ademsessie",
      text: "Kom voor een persoonlijke sessie, of vertel ons over je team of groep, dan stellen we een groepssessie op maat voor.",
      primary: "Plan een 1-op-1-sessie",
      secondary: "Groepssessie aanvragen",
    }),
    seo: seoSection(
      "Ademcoaching 1-op-1 en in groepsverband",
      "Ademcoaching door Steyn van Leeuwen in Amsterdam: een 1-op-1-ademsessie van {ademduur} voor € {ademprijs}, of een groepssessie op aanvraag voor bedrijven, sportteams en vriendengroepen.",
    ),
  },
});
