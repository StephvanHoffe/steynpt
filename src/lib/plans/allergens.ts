import type { IntakeData } from "../intake";
import type { NutritionPlan } from "./schema";

// Hulpcontrole voor Steyn: zoekt in een voedingsschema naar ingrediënten die
// botsen met de allergieën of eetstijl van de klant. Dit is een heuristiek op
// trefwoorden en vervangt nooit de handmatige controle.

type Rule = { label: string; prefixes?: string[]; words?: string[]; freeFrom?: string[]; except?: string[] };

// Woorden die met een trefwoord beginnen maar er niets mee te maken hebben.
const NOT_A_MATCH = ["speculaas", "speculoos", "nootmuskaat", "pitaya", "kippenvel", "lampion"];
// "boterham" begint met "boter" maar is brood: geen zuivel (wel gluten).
const NOT_DAIRY = ["boterham"];

const DAIRY = ["melk", "yoghurt", "kwark", "kaas", "room", "boter", "skyr", "whey", "zuivel", "karnemelk", "hüttenkäse", "huttenkase", "cottage cheese", "mozzarella", "feta", "parmezaan", "ricotta", "mascarpone", "crème fraîche", "creme fraiche"];
const EGG_WORDS = ["ei", "eieren", "omelet", "omelette", "roerei", "spiegelei", "eiersalade", "eidooier", "mayonaise", "frittata", "shakshuka"];
const FISH = ["vis", "zalm", "tonijn", "kabeljauw", "makreel", "haring", "sardine", "sardines", "pangasius", "koolvis", "forel", "heilbot", "ansjovis", "tilapia", "schelvis", "kibbeling", "lekkerbekje"];
const SHELLFISH = ["garnaal", "garnalen", "krab", "kreeft", "langoustine", "scampi", "gamba"];
const MOLLUSCS = ["mossel", "mosselen", "oester", "oesters", "inktvis", "calamari", "sint-jakobsschelp", "kokkel"];
const MEAT = ["kip", "kipfilet", "kippendij", "rund", "rundvlees", "gehakt", "varken", "ham", "spek", "kalkoen", "worst", "biefstuk", "vlees", "salami", "chorizo", "bacon", "lam", "lamsvlees", "kalfsvlees", "rookvlees", "filet americain", "shoarma", "hamburger", "frikandel", "kipshoarma", "carpaccio"];
const PORK = ["varken", "varkensvlees", "ham", "spek", "bacon", "salami", "chorizo", "speklap", "procureur", "pancetta", "prosciutto", "rookworst", "gelatine"];

const ALLERGY_RULES: Record<string, Rule> = {
  gluten: { label: "gluten", prefixes: ["tarwe", "gluten", "rogge", "gerst", "spelt", "couscous", "bulgur", "pasta", "spaghetti", "brood", "boterham", "crackers", "beschuit", "wrap", "tortilla", "seitan", "paneermeel", "muesli", "granola", "pannenkoek", "bagel", "croissant", "pita"], words: ["havermout"], freeFrom: ["glutenvrij"] },
  melk: { label: "melk", prefixes: DAIRY, except: NOT_DAIRY },
  lactose: { label: "lactose", prefixes: DAIRY, freeFrom: ["lactosevrij"], except: NOT_DAIRY },
  ei: { label: "ei", words: EGG_WORDS },
  pinda: { label: "pinda", prefixes: ["pinda", "satésaus", "satesaus", "saté", "sate", "apenootjes"] },
  noten: { label: "noten", prefixes: ["noten", "noot", "amandel", "walnoot", "walnoten", "cashew", "hazelnoot", "hazelnoten", "pecan", "pistache", "macadamia", "paranoot", "marsepein", "notenpasta"] },
  soja: { label: "soja", prefixes: ["soja", "tofu", "tempeh", "edamame", "miso", "ketjap", "tamari"] },
  vis: { label: "vis", prefixes: FISH.filter((f) => f !== "vis"), words: ["vis"] },
  schaaldieren: { label: "schaaldieren", prefixes: SHELLFISH },
  weekdieren: { label: "weekdieren", prefixes: MOLLUSCS },
  selderij: { label: "selderij", prefixes: ["selderij", "bleekselderij", "knolselderij", "selder"] },
  mosterd: { label: "mosterd", prefixes: ["mosterd"] },
  sesam: { label: "sesam", prefixes: ["sesam", "tahin", "tahini", "hummus", "humus"] },
  lupine: { label: "lupine", prefixes: ["lupine"] },
  sulfiet: { label: "sulfiet", prefixes: ["sulfiet", "wijn", "gedroogde abrikoos", "gedroogde abrikozen"] },
};

const DIET_RULES: Record<string, Rule> = {
  vegetarisch: { label: "vegetarisch", prefixes: [...MEAT, ...FISH.filter((f) => f !== "vis"), ...SHELLFISH, ...MOLLUSCS], words: ["vis"] },
  veganistisch: {
    label: "veganistisch",
    prefixes: [...MEAT, ...FISH.filter((f) => f !== "vis"), ...SHELLFISH, ...MOLLUSCS, ...DAIRY, "honing"],
    words: ["vis", ...EGG_WORDS],
    except: NOT_DAIRY,
  },
  pescotarisch: { label: "pescotarisch", prefixes: MEAT },
  halal: { label: "halal", prefixes: PORK, except: ["hamburger"] },
};

// Woorden direct vóór een treffer die aangeven dat het ingrediënt er juist níet in zit.
const ABSENT = ["zonder", "geen"];
// Bij eetstijlen: woorden die aangeven dat het om een plantaardige vervanger gaat.
const SUBSTITUTE = ["vegetarisch", "vegan", "veganistisch", "plantaardig", "vega"];

export type AllergenWarning = { term: string; reason: string; where: string };

const LETTER = /\p{L}/u;

function findTerm(haystack: string, term: string, wholeWord: boolean) {
  const hits: { index: number; word: string }[] = [];
  let from = 0;
  for (;;) {
    const index = haystack.indexOf(term, from);
    if (index === -1) return hits;
    from = index + term.length;
    if (index > 0 && LETTER.test(haystack[index - 1])) continue;
    let end = index + term.length;
    while (end < haystack.length && LETTER.test(haystack[end])) end++;
    if (wholeWord && end !== index + term.length) continue;
    hits.push({ index, word: haystack.slice(index, end) });
  }
}

function isNeutralized(haystack: string, index: number, word: string, rule: Rule, diet: boolean) {
  if (word.includes("vrij") || word.includes("vervanger")) return true;
  if ([...NOT_A_MATCH, ...(rule.except ?? [])].some((w) => word.startsWith(w))) return true;
  if (/^\s*-?vervanger/.test(haystack.slice(index + word.length))) return true;
  // Alleen de twee woorden direct ervoor tellen mee ("zonder noten", "lactosevrije kwark").
  const previous = haystack.slice(Math.max(0, index - 40), index).split(/[^\p{L}-]+/u).filter(Boolean).slice(-2);
  const markers = [...ABSENT, ...(rule.freeFrom ?? []), ...(diet ? SUBSTITUTE : [])];
  return previous.some((w) => markers.some((m) => w.startsWith(m)));
}

function matchRule(haystack: string, rule: Rule, diet: boolean): string | null {
  for (const [terms, whole] of [
    [rule.words ?? [], true],
    [rule.prefixes ?? [], false],
  ] as const) {
    for (const term of terms) {
      for (const hit of findTerm(haystack, term, whole)) {
        if (!isNeutralized(haystack, hit.index, hit.word, rule, diet)) return hit.word;
      }
    }
  }
  return null;
}

function nutritionTexts(plan: NutritionPlan) {
  const texts: { where: string; text: string }[] = [];
  plan.meals.forEach((meal, i) => {
    const mealName = meal.name || `Maaltijd ${i + 1}`;
    meal.options.forEach((option, j) => {
      texts.push({ where: `${mealName} › ${option.title || `optie ${j + 1}`}`, text: `${option.title} ${option.ingredients}` });
    });
  });
  plan.tips.forEach((tip, i) => texts.push({ where: `Tip ${i + 1}`, text: tip }));
  texts.push({ where: "Toelichting", text: plan.summary });
  // "avoid" wordt bewust overgeslagen: daar horen allergenen juist genoemd te worden.
  return texts;
}

export function findAllergenWarnings(plan: NutritionPlan, intake: Pick<IntakeData, "allergies" | "diet">): AllergenWarning[] {
  const rules = [
    ...intake.allergies.map((a) => ({ rule: ALLERGY_RULES[a], reason: `allergie: ${ALLERGY_RULES[a]?.label ?? a}`, diet: false })),
    ...(DIET_RULES[intake.diet] ? [{ rule: DIET_RULES[intake.diet], reason: `eetstijl: ${DIET_RULES[intake.diet].label}`, diet: true }] : []),
  ].filter((r) => r.rule);

  const warnings: AllergenWarning[] = [];
  for (const { where, text } of nutritionTexts(plan)) {
    const haystack = text.toLowerCase();
    for (const { rule, reason, diet } of rules) {
      const term = matchRule(haystack, rule, diet);
      if (term && !warnings.some((w) => w.where === where && w.reason === reason)) warnings.push({ term, reason, where });
    }
  }
  return warnings;
}
