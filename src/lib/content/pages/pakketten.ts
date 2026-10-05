import { BREATHWORK_SESSION, ONLINE_PLANS, PT_PRICES } from "../../site";
import { check, definePage, items, line, list, price, section } from "../fields";

export const pakketten = definePage({
  slug: "pakketten",
  title: "Prijzen en pakketten",
  path: "/tarieven",
  description: "Online coaching, personal training en de ademsessie: namen, prijzen en wat erbij hoort.",
  sections: {
    online: section(
      "Online coaching",
      {
        plans: items(
          "Pakketten",
          "Pakket",
          {
            name: line("Naam", "", { max: 30 }),
            tagline: line("Korte omschrijving", "", { max: 80 }),
            price: price("Prijs per maand", ""),
            features: list("Wat zit erin", [], { hint: "Eén punt per regel." }),
            featured: check("Markeren als 'Meest gekozen'", false),
          },
          ONLINE_PLANS.map((p) => ({ name: p.name, tagline: p.tagline, price: p.price, features: p.features, featured: !!p.featured })),
          { fixed: true },
        ),
      },
      "Op de pagina's Online coaching en Tarieven, bij het aanmaken van een account en in Mijn omgeving. De laagste prijs is automatische waarde {online-vanaf}.",
    ),
    pt: section(
      "Personal training",
      {
        cards: items(
          "Pakketten",
          "Pakket",
          {
            label: line("Kleine kop", "", { max: 40 }),
            name: line("Naam", "", { max: 40 }),
            price: price("Prijs", ""),
            unit: line("Na de prijs", "", { max: 40, optional: true, hint: "Bijvoorbeeld 'per uur'. Leeg laten mag." }),
            features: list("Wat zit erin", [], { hint: "Eén punt per regel." }),
            note: line("Kleine tekst onderaan", "", { max: 120, optional: true }),
            featured: check("Markeren als 'Meest gekozen'", false),
          },
          PT_PRICES.map((c) => ({ label: c.label, name: c.name, price: c.price, unit: c.unit ?? "", features: c.features, note: c.note ?? "", featured: !!c.featured })),
          { min: 1, max: 6 },
        ),
      },
      "Op de pagina's Personal training en Tarieven.",
    ),
    adem: section(
      "Ademsessie 1-op-1",
      {
        name: line("Naam", "Ademsessie 1-op-1", { max: 40 }),
        label: line("Kleine kop", "Ademcoaching", { max: 40 }),
        price: price("Prijs per sessie", BREATHWORK_SESSION.price, { noVars: true, hint: "Automatische waarde {ademprijs}." }),
        duration: line("Duur", BREATHWORK_SESSION.duration, { max: 30, noVars: true, hint: "Automatische waarde {ademduur}, bijvoorbeeld '1,5 uur'." }),
        unit: line("Na de prijs (tarievenpagina)", "per sessie van {ademduur}", { max: 60 }),
        features: list("Wat zit erin", BREATHWORK_SESSION.features, { hint: "Eén punt per regel." }),
      },
      "Op de pagina's Ademcoaching en Tarieven.",
    ),
    labels: section("Overige", {
      featured: line("Label bij een gemarkeerd pakket", "Meest gekozen", { max: 30 }),
    }),
  },
});
