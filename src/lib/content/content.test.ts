import assert from "node:assert/strict";
import { describe, it } from "node:test";
import { pageDefaults, type ItemsField } from "./fields";
import { fillText, fillVars, paragraphs, parseRich, placeholdersIn, plainRich } from "./markup";
import { ALL_PAGES, algemeen, ademcoaching, computeVars, home, onlineCoaching, pakketten, personalTraining, VAR_KEYS } from "./registry";
import { coerceStored, normalizePage, priceNumber, sameValue } from "./values";

describe("register", () => {
  it("heeft unieke paginanamen", () => {
    const slugs = ALL_PAGES.map((p) => p.slug);
    assert.equal(new Set(slugs).size, slugs.length);
  });

  for (const page of ALL_PAGES) {
    it(`standaardteksten van '${page.title}' zijn geldig en veranderen niet bij opslaan`, () => {
      const defaults = pageDefaults(page);
      const { values, errors } = normalizePage(page, defaults, VAR_KEYS);
      assert.deepEqual(errors, {});
      for (const [s, fields] of Object.entries(defaults)) {
        for (const [f, value] of Object.entries(fields)) {
          assert.ok(sameValue(values[s][f], value), `${page.slug}.${s}.${f} verandert bij opslaan`);
        }
      }
    });
  }

  it("gebruikt alleen bestaande automatische waarden in de standaardteksten", () => {
    const all = JSON.stringify(ALL_PAGES.map(pageDefaults));
    for (const key of placeholdersIn(all)) assert.ok(VAR_KEYS.includes(key as (typeof VAR_KEYS)[number]), key);
  });
});

describe("automatische waarden", () => {
  const vars = computeVars(pageDefaults(algemeen), pageDefaults(pakketten));

  it("komen uit de vriendenactie en de pakketten", () => {
    assert.equal(vars.ademprijs, "210");
    assert.equal(vars.ademduur, "1,5 uur");
    assert.equal(vars["online-vanaf"], "79");
    assert.equal(vars.actie, "Samen 50% korting");
  });

  it("laagste online prijs met komma", () => {
    const prices = pageDefaults(pakketten);
    prices.online.plans[1].price = "59,50";
    assert.equal(computeVars(pageDefaults(algemeen), prices)["online-vanaf"], "59,50");
    assert.equal(priceNumber("1.050"), 1050);
  });

  it("worden ingevuld in teksten, opsommingen en lijsten", () => {
    const filled = fillVars(pageDefaults(ademcoaching), vars);
    assert.equal(filled.faq.points[0], "1-op-1: 1,5 uur voor € 210");
    assert.match(filled.faq.questions[0].a, /duurt 1,5 uur en kost € 210\./);
    assert.equal(fillText("{onbekend} blijft", vars), "{onbekend} blijft");
    assert.deepEqual(placeholdersIn("{a} {b} {a} {Geen}"), ["a", "b"]);
  });
});

describe("opmaak", () => {
  it("accent en regeleinden in titels", () => {
    assert.deepEqual(parseRich("Sterker lichaam.\n*Gezonder* leven."), [
      { text: "Sterker lichaam.", accent: false },
      { br: true },
      { text: "Gezonder", accent: true },
      { text: " leven.", accent: false },
    ]);
    assert.deepEqual(parseRich("Een * los sterretje"), [{ text: "Een * los sterretje", accent: false }]);
    assert.deepEqual(parseRich("*a* en *b"), [
      { text: "a", accent: true },
      { text: " en *b", accent: false },
    ]);
    assert.equal(plainRich("Breng een vriend mee. *Samen 50% korting.*"), "Breng een vriend mee. Samen 50% korting.");
  });

  it("alinea's gescheiden door een lege regel", () => {
    assert.deepEqual(paragraphs("Een\n\n  \nTwee\ndrie\n\n"), ["Een", "Twee\ndrie"]);
  });
});

describe("controle bij opslaan", () => {
  const save = (input: unknown) => normalizePage(home, input, VAR_KEYS);

  it("schoont tekst op en laat niet-meegestuurde velden met rust", () => {
    const { values, errors } = save({ hero: { eyebrow: "  Nieuwe kop \r\n op twee regels  ", title: "Regel 1\r\n\r\n\r\n*Regel* 2" }, onbekend: { x: 1 } });
    assert.deepEqual(errors, {});
    assert.equal(values.hero.eyebrow, "Nieuwe kop op twee regels");
    assert.equal(values.hero.title, "Regel 1\n*Regel* 2");
    assert.equal(Object.keys(values.hero).length, 2);
    assert.equal(values.onbekend, undefined);
  });

  it("verplichte velden, lengte en onbekende codes", () => {
    const { errors } = save({ hero: { eyebrow: "  ", intro: "x".repeat(501), badgeText: "Vanaf {prijs}" } });
    assert.equal(errors["hero.eyebrow"], "Dit veld mag niet leeg zijn.");
    assert.match(errors["hero.intro"], /Maximaal 500 tekens/);
    assert.match(errors["hero.badgeText"], /Onbekende automatische waarde \{prijs\}/);
  });

  it("Enter wordt een spatie in tekst zonder alinea's, en blijft staan in tekst met alinea's", () => {
    assert.equal(save({ hero: { intro: "Regel een\nregel twee" } }).values.hero.intro, "Regel een regel twee");
    const pt = normalizePage(personalTraining, { leefstijl: { body: "Alinea een\n\n\nAlinea twee" } }, VAR_KEYS);
    assert.equal(pt.values.leefstijl.body, "Alinea een\n\nAlinea twee");
  });

  it("titels hebben maximaal drie regels", () => {
    assert.equal(save({ hero: { title: "a\nb\nc\nd" } }).errors["hero.title"], "Maximaal 3 regels.");
  });

  it("opsommingen: één punt per regel, lege regels vallen weg", () => {
    const { values, errors } = save({ diensten: { list: "Een\n\n Twee \n" } });
    assert.deepEqual(errors, {});
    assert.deepEqual(values.diensten.list, ["Een", "Twee"]);
    assert.equal(save({ diensten: { list: "" } }).errors["diensten.list"], "Vul minstens één punt in.");
    assert.equal(save({ diensten: { list: Array(9).fill("x") } }).errors["diensten.list"], "Maximaal 8 punten.");
  });

  it("vaste aantallen en lijsten met vragen", () => {
    assert.match(save({ hero: { stats: [{ value: "1", label: "a" }] } }).errors["hero.stats"], /altijd 4 items/);
    const faq = normalizePage(onlineCoaching, { faq: { questions: [{ q: "Vraag?", a: "" }] } }, VAR_KEYS);
    assert.equal(faq.errors["faq.questions.0.a"], "Dit veld mag niet leeg zijn.");
    const none = normalizePage(onlineCoaching, { faq: { questions: [] } }, VAR_KEYS);
    assert.equal(none.errors["faq.questions"], "Voeg minstens één vraag toe.");
  });

  it("prijzen, links, webadressen en automatische waarden", () => {
    const prices = normalizePage(
      pakketten,
      { adem: { price: "€ 210", duration: "{ademduur}" }, online: { plans: pageDefaults(pakketten).online.plans.map((p, i) => ({ ...p, price: ["79,50", "1.050", "12,5"][i] })) } },
      VAR_KEYS,
    );
    assert.match(prices.errors["adem.price"], /Vul een bedrag in/);
    assert.match(prices.errors["adem.duration"], /geen automatische waarden/);
    assert.equal(prices.errors["online.plans.0.price"], undefined);
    assert.equal(prices.errors["online.plans.1.price"], undefined);
    assert.match(prices.errors["online.plans.2.price"], /Vul een bedrag in/);
    assert.equal((prices.values.online.plans as { featured: boolean }[])[0].featured, false);

    const shared = normalizePage(algemeen, { aankondiging: { href: "https://example.com", show: "on" }, locatie: { instagramUrl: "javascript:alert(1)" } }, VAR_KEYS);
    assert.equal(shared.errors["aankondiging.href"], "Kies een pagina uit de lijst.");
    assert.equal(shared.values.aankondiging.show, true);
    assert.match(shared.errors["locatie.instagramUrl"], /https:\/\//);
  });
});

describe("opgeslagen teksten lezen", () => {
  const stats = home.sections.hero.fields.stats as ItemsField;
  const faq = onlineCoaching.sections.faq.fields.questions as ItemsField;

  it("valt terug op de standaard bij een verkeerde vorm", () => {
    assert.equal(coerceStored(home.sections.hero.fields.title, 12), home.sections.hero.fields.title.default);
    assert.deepEqual(coerceStored(home.sections.diensten.fields.list, ["a", 1]), home.sections.diensten.fields.list.default);
    assert.deepEqual(coerceStored(stats, [{ value: "1", label: "a" }]), stats.default);
    assert.equal(coerceStored(algemeen.sections.aankondiging.fields.show, "ja"), true);
  });

  it("vult ontbrekende onderdelen van een item aan", () => {
    const stored = coerceStored(faq, [{ q: "Eigen vraag" }, { q: "Nog een", a: "Antwoord" }, { q: 3, a: "x" }]) as { q: string; a: string }[];
    assert.equal(stored.length, 3);
    assert.equal(stored[0].q, "Eigen vraag");
    assert.equal(stored[0].a, faq.default[0].a);
    assert.equal(stored[2].q, faq.default[2].q);
  });
});
