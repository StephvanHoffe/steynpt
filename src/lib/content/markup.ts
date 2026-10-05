// Kleine opmaak in teksten uit het beheer: {codes} voor automatische waarden en *sterretjes* voor de accentkleur.
// Puur: de site gebruikt dit bij het tonen, het beheer voor de voorvertoning en de controle bij opslaan.

const PLACEHOLDER = /\{([a-z][a-z0-9-]*)\}/g;

/** Alle {codes} in een tekst, zonder dubbele. */
export function placeholdersIn(value: string): string[] {
  return [...new Set([...value.matchAll(PLACEHOLDER)].map((m) => m[1]))];
}

/** Vervangt bekende {codes}; onbekende blijven staan (die houdt het opslaan al tegen). */
export function fillText(value: string, vars: Record<string, string>): string {
  return value.replace(PLACEHOLDER, (whole, key: string) => (key in vars ? vars[key] : whole));
}

/** Vult {codes} in alle teksten van een waarde: tekst, opsomming of lijst met items. */
export function fillVars<T>(value: T, vars: Record<string, string>): T {
  if (typeof value === "string") return fillText(value, vars) as T;
  if (Array.isArray(value)) return value.map((v) => fillVars(v, vars)) as T;
  if (value && typeof value === "object") {
    return Object.fromEntries(Object.entries(value).map(([k, v]) => [k, fillVars(v, vars)])) as T;
  }
  return value;
}

export type RichPart = { text: string; accent: boolean } | { br: true };

/** Deelt een titel op in gewone tekst, *accent* en regeleinden. Een los sterretje blijft gewoon staan. */
export function parseRich(value: string): RichPart[] {
  const parts: RichPart[] = [];
  value.split("\n").forEach((line, i) => {
    if (i > 0) parts.push({ br: true });
    const pieces = line.split("*");
    // Bij een oneven aantal sterretjes is het laatste niet gesloten: dat is gewoon een teken.
    if (pieces.length % 2 === 0) pieces.splice(pieces.length - 2, 2, `${pieces[pieces.length - 2]}*${pieces[pieces.length - 1]}`);
    pieces.forEach((text, j) => text && parts.push({ text, accent: j % 2 === 1 }));
  });
  return parts;
}

/** Titel zonder opmaaktekens, voor plekken zonder opmaak (zoals de omschrijving in Google). */
export const plainRich = (value: string) =>
  parseRich(value)
    .map((p) => ("br" in p ? " " : p.text))
    .join("")
    .replace(/\s+/g, " ")
    .trim();

/** Alinea's: gescheiden door een lege regel. */
export const paragraphs = (value: string) =>
  value
    .split(/\n\s*\n/)
    .map((p) => p.trim())
    .filter(Boolean);
