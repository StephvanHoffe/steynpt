// Controle van teksten uit het beheer (bij opslaan) en van opgeslagen teksten (bij tonen). Puur en getest.
import type { Field, PageDef, RawValues, SubField } from "./fields";
import { placeholdersIn } from "./markup";

export const LINE_MAX = 160;
export const TEXT_MAX = 1200;
export const LIST_ITEM_MAX = 300;

/** Pagina's waar de balk bovenaan naartoe kan linken. */
export const SITE_LINKS = [
  { href: "/", label: "Homepage" },
  { href: "/online-coaching", label: "Online coaching" },
  { href: "/personal-training", label: "Personal training" },
  { href: "/ademcoaching", label: "Ademcoaching" },
  { href: "/voedingscoaching", label: "Voedingscoaching" },
  { href: "/tarieven", label: "Tarieven" },
  { href: "/over-steyn", label: "Over Steyn" },
  { href: "/vriend-uitnodigen", label: "Vriendenactie" },
  { href: "/contact", label: "Contact" },
  { href: "/registreren", label: "Account aanmaken" },
  { href: "/account/agenda", label: "Afspraak maken" },
] as const;

const PRICE = /^(\d{1,6}|\d{1,3}(\.\d{3})+)(,\d{2})?$/;

/** Bedrag als getal, voor bijvoorbeeld "online coaching vanaf". */
export const priceNumber = (value: string) => Number(value.replace(/\./g, "").replace(",", "."));

// Opschonen: Windows-regeleinden, spaties aan het eind van een regel en meer dan één lege regel achter elkaar.
const clean = (value: string) =>
  value
    .replace(/\r\n?/g, "\n")
    .split("\n")
    .map((l) => l.replace(/[ \t]+$/, ""))
    .join("\n")
    .replace(/\n{3,}/g, "\n\n")
    .trim();

const asString = (value: unknown) => (typeof value === "string" ? value : typeof value === "number" ? String(value) : "");

export type FieldErrors = Record<string, string>;

type Ctx = { errors: FieldErrors; vars: readonly string[] };

function checkVars(field: Field, value: string, path: string, ctx: Ctx) {
  const used = placeholdersIn(value);
  if (!used.length) return true;
  if (field.noVars) {
    ctx.errors[path] = "Hier kun je geen automatische waarden ({…}) gebruiken.";
    return false;
  }
  const unknown = used.filter((v) => !ctx.vars.includes(v));
  if (unknown.length) {
    ctx.errors[path] = `Onbekende automatische waarde ${unknown.map((u) => `{${u}}`).join(", ")}. Kies uit: ${ctx.vars.map((v) => `{${v}}`).join(", ")}.`;
    return false;
  }
  return true;
}

function normalizeSub(field: SubField | Field, input: unknown, path: string, ctx: Ctx): unknown {
  switch (field.kind) {
    case "check":
      return input === true || input === "true" || input === "on";
    case "list": {
      const raw = Array.isArray(input) ? input.map(asString) : asString(input).split("\n");
      const values = raw.map((v) => clean(v).replace(/\n+/g, " ")).filter(Boolean);
      const min = field.min ?? (field.optional ? 0 : 1);
      const max = field.max ?? 20;
      if (values.length < min) ctx.errors[path] = min === 1 ? "Vul minstens één punt in." : `Vul minstens ${min} punten in.`;
      else if (values.length > max) ctx.errors[path] = `Maximaal ${max} punten.`;
      else if (values.some((v) => v.length > LIST_ITEM_MAX)) ctx.errors[path] = `Een punt mag maximaal ${LIST_ITEM_MAX} tekens hebben.`;
      else values.every((v) => checkVars(field, v, path, ctx));
      return values;
    }
    case "items":
      throw new Error("Geneste lijsten worden niet ondersteund");
    default: {
      let value = clean(asString(input));
      // Een gewone regel heeft geen regeleinden; een titel mag er een paar hebben.
      if (field.kind === "line") value = field.rich ? value.replace(/\n+/g, "\n") : value.replace(/\s*\n\s*/g, " ");
      // Tekst zonder alinea's staat op de site als één alinea: een regeleinde wordt daar een spatie.
      if (field.kind === "text" && !field.paragraphs) value = value.replace(/\s*\n\s*/g, " ");
      if (field.kind !== "text" && field.kind !== "line") value = value.replace(/\s+/g, "");
      if (!value) {
        if (!field.optional) ctx.errors[path] = "Dit veld mag niet leeg zijn.";
        return value;
      }
      if (field.kind === "price") {
        if (!PRICE.test(value)) ctx.errors[path] = "Vul een bedrag in zoals 120 of 79,50, zonder €-teken.";
        return value;
      }
      if (field.kind === "link") {
        if (!SITE_LINKS.some((l) => l.href === value)) ctx.errors[path] = "Kies een pagina uit de lijst.";
        return value;
      }
      if (field.kind === "url") {
        if (!/^https:\/\/[^\s<>"]+\.[^\s<>"]+$/.test(value) || value.length > 300) ctx.errors[path] = "Vul een volledig webadres in dat begint met https://";
        return value;
      }
      const max = field.max ?? (field.kind === "text" ? TEXT_MAX : LINE_MAX);
      if (value.length > max) ctx.errors[path] = `Maximaal ${max} tekens (nu ${value.length}).`;
      else if (field.kind === "line" && field.rich && value.split("\n").length > 3) ctx.errors[path] = "Maximaal 3 regels.";
      else checkVars(field, value, path, ctx);
      return value;
    }
  }
}

function normalizeField(field: Field, input: unknown, path: string, ctx: Ctx): unknown {
  if (field.kind !== "items") return normalizeSub(field, input, path, ctx);
  const rows = Array.isArray(input) ? input : [];
  const fixed = field.fixed ? field.default.length : null;
  const min = fixed ?? field.min ?? 1;
  const max = fixed ?? field.max ?? 20;
  const values = rows.map((row, i) => {
    const obj = row && typeof row === "object" ? (row as Record<string, unknown>) : {};
    return Object.fromEntries(Object.entries(field.fields).map(([k, sub]) => [k, normalizeSub(sub, obj[k], `${path}.${i}.${k}`, ctx)]));
  });
  const name = field.itemLabel.toLowerCase();
  if (fixed !== null && values.length !== fixed) ctx.errors[path] = `Dit onderdeel heeft altijd ${fixed} items.`;
  else if (values.length < min) ctx.errors[path] = `Voeg minstens ${min === 1 ? `één ${name}` : `${min} items`} toe.`;
  else if (values.length > max) ctx.errors[path] = `Maximaal ${max} items.`;
  return values;
}

/**
 * Controleert en schoont wat het beheer instuurt. Alleen velden die in de pagina bestaan tellen mee;
 * een veld dat niet is meegestuurd, blijft ongewijzigd en staat dus ook niet in `values`.
 */
export function normalizePage(page: PageDef, input: unknown, vars: readonly string[]) {
  const ctx: Ctx = { errors: {}, vars };
  const values: RawValues = {};
  const data = input && typeof input === "object" ? (input as Record<string, unknown>) : {};
  for (const [s, section] of Object.entries(page.sections)) {
    const given = data[s] && typeof data[s] === "object" ? (data[s] as Record<string, unknown>) : null;
    if (!given) continue;
    for (const [f, field] of Object.entries(section.fields)) {
      if (!(f in given)) continue;
      (values[s] ??= {})[f] = normalizeField(field, given[f], `${s}.${f}`, ctx);
    }
  }
  return { values, errors: ctx.errors };
}

// --- Opgeslagen waarden lezen ---

function coerceSub(field: SubField | Field, raw: unknown, fallback: unknown): unknown {
  switch (field.kind) {
    case "check":
      return typeof raw === "boolean" ? raw : fallback;
    case "list":
      return Array.isArray(raw) && raw.every((v) => typeof v === "string") ? raw : fallback;
    case "items":
      return fallback;
    default:
      return typeof raw === "string" ? raw : fallback;
  }
}

const emptyOf = (field: SubField) => (field.kind === "list" ? [] : field.kind === "check" ? false : "");

/**
 * Een opgeslagen waarde die niet (meer) past bij het veld, bijvoorbeeld na een wijziging in de code,
 * wordt de standaardtekst. Zo kan een oude of beschadigde waarde de site nooit breken.
 */
export function coerceStored(field: Field, raw: unknown): unknown {
  if (field.kind !== "items") return coerceSub(field, raw, structuredClone(field.default));
  if (!Array.isArray(raw)) return structuredClone(field.default);
  if (field.fixed && raw.length !== field.default.length) return structuredClone(field.default);
  return raw.map((row, i) => {
    const obj = row && typeof row === "object" ? (row as Record<string, unknown>) : {};
    const base = (field.default[i] ?? {}) as Record<string, unknown>;
    return Object.fromEntries(
      Object.entries(field.fields).map(([k, sub]) => [k, coerceSub(sub, obj[k], structuredClone(base[k] ?? emptyOf(sub)))]),
    );
  });
}

// JSON met vaste volgorde van sleutels, zodat dezelfde inhoud altijd dezelfde tekst oplevert.
const stable = (value: unknown): unknown =>
  Array.isArray(value)
    ? value.map(stable)
    : value && typeof value === "object"
      ? Object.fromEntries(Object.keys(value).sort().map((k) => [k, stable((value as Record<string, unknown>)[k])]))
      : value;

/** Zelfde inhoud? Gebruikt om te bepalen of een tekst nog de standaardtekst is. */
export const sameValue = (a: unknown, b: unknown) => JSON.stringify(stable(a)) === JSON.stringify(stable(b));
