// Schrijft de standaardteksten en een set voorbeelden uit de TypeScript-versie van het tekstbeheer naar JSON,
// zodat de PHP-tests (tests/Unit/ContentTest.php) kunnen controleren dat de PHP-versie precies hetzelfde doet.
//
// Uitvoeren vanuit de hoofdmap van het project (waar src/ staat):
//   npx tsx php/tests/fixtures/dump-content-defaults.mts
//
// - content-defaults.json:  pageDefaults() van elke pagina, op slug, in de volgorde van het register.
// - content-reference.json: de volledige paginadefinities, VARS, SITE_LINKS en uitkomsten van normalizePage,
//                           resolvePage, computeVars en de opmaakfuncties voor lastige invoer.
import fs from "node:fs";
import path from "node:path";
import { fileURLToPath } from "node:url";
import { fieldKey, pageDefaults, type PageDef, type PageValues, type RawValues } from "../../../src/lib/content/fields";
import { fillText, paragraphs, parseRich, placeholdersIn, plainRich } from "../../../src/lib/content/markup";
import { ALL_PAGES, algemeen, computeVars, findPage, pakketten, VAR_KEYS, VARS } from "../../../src/lib/content/registry";
import { coerceStored, normalizePage, SITE_LINKS } from "../../../src/lib/content/values";

const here = path.dirname(fileURLToPath(import.meta.url));
const write = (name: string, data: unknown) => fs.writeFileSync(path.join(here, name), `${JSON.stringify(data, null, 2)}\n`);

// Letterlijke kopie van resolvePage uit src/lib/content/texts.ts: dat bestand laadt de database ("server-only")
// en kan buiten Next.js niet worden geïmporteerd.
function resolvePage(page: PageDef, stored: Map<string, string>): RawValues {
  const out: RawValues = {};
  for (const [s, section] of Object.entries(page.sections)) {
    out[s] = {};
    for (const [f, field] of Object.entries(section.fields)) {
      const raw = stored.get(fieldKey(page.slug, s, f));
      let value: unknown = structuredClone(field.default);
      if (raw !== undefined) {
        try {
          value = coerceStored(field, JSON.parse(raw));
        } catch {
          // Onleesbare waarde: standaardtekst.
        }
      }
      out[s][f] = value;
    }
  }
  return out;
}

const page = (slug: string) => {
  const p = findPage(slug);
  if (!p) throw new Error(`Onbekende pagina ${slug}`);
  return p;
};

write("content-defaults.json", Object.fromEntries(ALL_PAGES.map((p) => [p.slug, pageDefaults(p)])));

// --- Voorbeelden: invoer zoals het beheer hem kan insturen (JSON, zoals een PHP-request hem ook geeft) ---

const emoji = "\u{1F4AA}";
const normalizeInputs: { slug: string; input: unknown }[] = [
  { slug: "home", input: { hero: { eyebrow: "  Nieuwe kop \r\n op twee regels  ", title: "Regel 1\r\n\r\n\r\n*Regel* 2" }, onbekend: { x: 1 } } },
  {
    slug: "home",
    input: {
      hero: {
        eyebrow: " Harde spatie regel ﻿",
        title: "a\nb\nc\nd",
        intro: "x".repeat(501),
        badgeText: "Vanaf {prijs} en {actie} en {foo} en {prijs}",
        badgeLabel: "{Geen} code",
      },
    },
  },
  { slug: "home", input: { hero: { intro: emoji.repeat(251), badgeLabel: emoji.repeat(10), primary: "  \t \n " } } },
  { slug: "home", input: { hero: { eyebrow: 12.5, primary: 1e21, secondary: 0.1, badgeLabel: 1e-7, badgeText: 12345678901234567890, title: true } } },
  { slug: "home", input: { hero: { stats: [{ value: 12, label: 1.5 }, { value: true }, "x", null] } } },
  { slug: "home", input: { hero: { stats: [{ value: "1", label: "a" }] } } },
  { slug: "home", input: { diensten: { list: "Een\r\n\n Twee \t\n \nDrie {actie}\n{onbekend}\n{nog-een}" } } },
  { slug: "home", input: { diensten: { list: ["a", 12, null, true, "  b  ", "c\n\nd", "e\r\nf", ""] } } },
  { slug: "home", input: { diensten: { list: "" } } },
  { slug: "home", input: { diensten: { list: Array(9).fill("x") } } },
  { slug: "home", input: { diensten: { list: 42 } } },
  { slug: "home", input: { aanbod: { onlinePoints: ["kort", "y".repeat(301)], services: [] } } },
  { slug: "home", input: { aanbod: { onlinePoints: ["Vanaf {online-vanaf}", "{x}"] } } },
  { slug: "home", input: { hero: { title: "Een * los\n\n\n*sterretje*\n" } } },
  { slug: "home", input: "geen object" },
  { slug: "home", input: null },
  { slug: "home", input: [] },
  { slug: "home", input: { hero: ["x"], diensten: "y", online: null } },
  { slug: "home", input: { hero: {} } },
  {
    slug: "pakketten",
    input: {
      adem: { price: "€ 210", duration: "{ademduur}", features: [], unit: "per sessie van {ademduur}", name: "" },
      online: {
        plans: [
          { name: "Start", tagline: "Plan", price: "79,50", features: "Een\nTwee", featured: "on" },
          { name: "Pro", tagline: "Meer", price: "1.050", features: ["Een"], featured: "true" },
          { name: "Performance", tagline: "Meest", price: "12,5", features: ["Een"], featured: 1 },
        ],
      },
    },
  },
  {
    slug: "pakketten",
    input: {
      adem: { price: " 1 050,00 " },
      online: { plans: [{ price: "1.000.000" }, { price: "0,99" }, { price: "1234567" }] },
      pt: { cards: [] },
      labels: { featured: "" },
    },
  },
  { slug: "pakketten", input: { adem: { price: "1.05" }, pt: { cards: Array(7).fill({ label: "a", name: "b", price: "1" }) } } },
  {
    slug: "algemeen",
    input: {
      aankondiging: { show: "on", href: "https://example.com", label: "", text: "Tekst\nmet regel" },
      locatie: { instagramUrl: "javascript:alert(1)", instagramHandle: "" },
      vriendenactie: { headline: "{actie}", friendReward: "Korting {vriendkorting}", steps: [{ title: "a", text: "b" }] },
    },
  },
  { slug: "algemeen", input: { aankondiging: { show: true, href: " /tarieven " }, locatie: { instagramUrl: " https:// www.example . com/x " } } },
  { slug: "algemeen", input: { aankondiging: { show: "1", href: "/onbekend" }, locatie: { instagramUrl: `https://${"a".repeat(300)}.nl` } } },
  { slug: "algemeen", input: { aankondiging: { show: false }, locatie: { instagramUrl: "https://example" } } },
  { slug: "algemeen", input: { werkwijze: { steps: [{ title: "Een", text: "Twee" }] }, reviews: { reviews: Array(9).fill({ quote: "q", name: "n" }) } } },
  { slug: "algemeen", input: { expertises: { list: Array(21).fill("x") }, footer: { extra: "", copyright: "" } } },
  { slug: "personal-training", input: { leefstijl: { body: "Alinea een\n\n\nAlinea twee\r\n\r\nAlinea drie   \nzelfde alinea", title: "a\n\n\nb" } } },
  { slug: "online-coaching", input: { faq: { questions: [{ q: "Vraag?", a: "" }] } } },
  { slug: "online-coaching", input: { faq: { questions: [] } } },
  { slug: "online-coaching", input: { faq: { questions: "geen lijst" } } },
  { slug: "online-coaching", input: { hero: { note: "" }, stappen: { steps: [{}, {}, {}, {}, {}, {}, {}] } } },
  { slug: "privacy", input: { onderdelen: { sections: [{ title: "Kop", body: "Een\r\n\r\n\r\n\r\nTwee\n \nDrie" }] } } },
  { slug: "tarieven", input: { pt: { note: "" }, hero: { title: "*Tarieven*" } } },
  { slug: "contact", input: { hero: { tipText: "{online-vanaf} {ademprijs} {ademduur} {actie} {vriendkorting} {jouwkorting}" } } },
];

const normalize = normalizeInputs.map(({ slug, input }) => ({ slug, input, output: normalizePage(page(slug), input, VAR_KEYS) }));

const resolveInputs: { slug: string; stored: Record<string, string> }[] = [
  {
    slug: "home",
    stored: {
      "home.hero.title": JSON.stringify("Eigen *titel*"),
      "home.hero.eyebrow": "12",
      "home.hero.intro": "geen json",
      "home.hero.primary": "null",
      "home.hero.secondary": "{}",
      "home.hero.stats": JSON.stringify([{ value: "1", label: "a" }]),
      "home.diensten.list": JSON.stringify(["a", 1]),
      "home.aanbod.onlinePoints": JSON.stringify(["x", "y"]),
      "home.aanbod.services": JSON.stringify([{ title: "A" }, { title: 1, text: "B" }, null, "x", [1]]),
      "home.onbekend.x": JSON.stringify("x"),
      "algemeen.aankondiging.text": JSON.stringify("hoort bij een andere pagina"),
    },
  },
  {
    slug: "online-coaching",
    stored: {
      "online-coaching.faq.questions": JSON.stringify([{ q: "Eigen vraag" }, { q: "Nog een", a: "Antwoord" }, { q: 3, a: "x" }, "rij", null, [1, 2], { a: "Zes" }]),
      "online-coaching.hero.note": JSON.stringify(""),
      "online-coaching.stappen.steps": "[]",
    },
  },
  {
    slug: "algemeen",
    stored: {
      "algemeen.aankondiging.show": "false",
      "algemeen.aankondiging.label": JSON.stringify("ja"),
      "algemeen.reviews.reviews": "{}",
      "algemeen.expertises.list": JSON.stringify({ 0: "a" }),
      "algemeen.vriendenactie.steps": JSON.stringify([{ title: "a" }, { title: "b" }, { title: "c", text: "d" }]),
      "algemeen.werkwijze.steps": "",
    },
  },
  {
    slug: "pakketten",
    stored: {
      "pakketten.online.plans": JSON.stringify([
        { name: "A", features: "x", featured: "yes", price: 79 },
        { name: "B", tagline: "T", price: "59,50", features: ["f"], featured: true, extra: "weg" },
        {},
      ]),
      "pakketten.pt.cards": JSON.stringify(Array(5).fill({ name: "X" })),
      "pakketten.adem.features": "[\"a\",\"b\"]",
    },
  },
];

const resolve = resolveInputs.map(({ slug, stored }) => ({ slug, stored, output: resolvePage(page(slug), new Map(Object.entries(stored))) }));

const priceLists = [
  ["79", "129", "199"],
  ["59,50", "129", "199"],
  ["0,125", "1", "2"],
  ["12,345", "99", "99"],
  ["2,675", "9", "9"],
  ["1,005", "9", "9"],
  ["", "x", "y"],
  ["abc", "x", "y"],
  ["1.050", "1.000.000", "3"],
  ["1.000.000.000.000.000.000.000.000", "1.000.000.000.000.000.000.000.000", "1.000.000.000.000.000.000.000.000"],
  ["123.456.789.012.345.678.901", "999.999.999.999.999.999.999.999", "999.999.999.999.999.999.999.999"],
  ["0x10", "1e3", "99"],
  ["-5", "12", "7"],
  ["0,000001", "1", "1"],
  ["Infinity", "-Infinity", "NaN"],
  [" 7 ", " 8 ", "9"],
  ["0b101", "0o17", "+.5"],
  ["1,5,5", "1,", ",5"],
  ["99,999", "100", "100"],
  ["0,995", "1", "1"],
];
const shared = pageDefaults(algemeen);
const vars = priceLists.map((prices) => {
  const values = pageDefaults(pakketten) as PageValues<typeof pakketten>;
  prices.forEach((p, i) => (values.online.plans[i].price = p));
  return { prices, output: computeVars(shared, values) };
});

const markupVars = computeVars(shared, pageDefaults(pakketten));
const richInputs = ["Sterker lichaam.\n*Gezonder* leven.", "Een * los sterretje", "*a* en *b", "", "*", "**", "***", "a**b", "*\n*", "0*0*0", "x*", "*x", "a\n\nb", "*Samen 50%* korting *nu*"];
const markup = {
  parseRich: richInputs.map((input) => ({ input, output: parseRich(input) })),
  plainRich: [...richInputs, "Breng een vriend mee. *Samen 50% korting.*", "  a  b \n *c* ", "﻿x﻿", "a\tb c"].map((input) => ({ input, output: plainRich(input) })),
  paragraphs: ["Een\n\n  \nTwee\ndrie\n\n", " \n \nx", "a\n \nb", "0\n\n0", "", "\n\n\n", "a\r\n\r\nb"].map((input) => ({ input, output: paragraphs(input) })),
  placeholdersIn: ["{a} {b} {a} {Geen}", "{a-1}{1a}{a_b}{-a}{ab-}", "{{actie}}", "geen"].map((input) => ({ input, output: placeholdersIn(input) })),
  fillText: ["{actie} {onbekend} {online-vanaf}", "{{actie}}", "€ {ademprijs},- voor {ademduur}"].map((input) => ({ input, output: fillText(input, markupVars) })),
};

write("content-reference.json", { pages: ALL_PAGES, vars: VARS, varKeys: VAR_KEYS, siteLinks: SITE_LINKS, cases: { normalize, resolve, vars, markup } });
console.log(`Geschreven: content-defaults.json en content-reference.json (${ALL_PAGES.length} pagina's)`);
