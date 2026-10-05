// Bouwstenen voor het tekstbeheer: welke soorten velden er zijn en hoe een pagina is opgebouwd.
// Puur (geen database), zodat zowel de site, het beheer als de tests dit kunnen gebruiken.

type Base = {
  label: string;
  /** Uitleg onder het veld in het beheer. */
  hint?: string;
  /** Leeg laten mag (anders verplicht). */
  optional?: boolean;
  /** Dit veld is zelf een automatische waarde en mag daarom geen {codes} bevatten. */
  noVars?: boolean;
};

/** Eén regel tekst. `rich`: *sterretjes* geven de accentkleur en Enter een nieuwe regel (voor titels). */
export type LineField = Base & { kind: "line"; default: string; max?: number; rich?: boolean };
/** Tekst over meerdere regels. `paragraphs`: een lege regel begint een nieuwe alinea (anders wordt Enter een spatie). */
export type TextField = Base & { kind: "text"; default: string; max?: number; paragraphs?: boolean };
/** Opsomming: één punt per regel. */
export type ListField = Base & { kind: "list"; default: string[]; min?: number; max?: number };
/** Bedrag in hele euro's of met komma, zonder €-teken. */
export type PriceField = Base & { kind: "price"; default: string };
export type CheckField = Base & { kind: "check"; default: boolean };
/** Link naar een pagina van de site. */
export type LinkField = Base & { kind: "link"; default: string };
/** Volledige webadres (https://…). */
export type UrlField = Base & { kind: "url"; default: string };

export type SubField = LineField | TextField | ListField | PriceField | CheckField;
export type ItemsField<S extends Record<string, SubField> = Record<string, SubField>> = Base & {
  kind: "items";
  /** Naam van één item, zoals "Vraag" of "Review". */
  itemLabel: string;
  fields: S;
  default: ItemValue<S>[];
  /** Vast aantal (bijvoorbeeld omdat elk item een eigen icoon heeft): niet toevoegen of verwijderen. */
  fixed?: boolean;
  min?: number;
  max?: number;
};
export type Field = SubField | LinkField | UrlField | ItemsField;

export type FieldValue<F> = F extends ListField
  ? string[]
  : F extends CheckField
    ? boolean
    : F extends ItemsField<infer S>
      ? ItemValue<S>[]
      : string;
export type ItemValue<S extends Record<string, SubField>> = { [K in keyof S]: FieldValue<S[K]> };

export type SectionDef = { title: string; hint?: string; fields: Record<string, Field> };
export type PageDef = {
  slug: string;
  title: string;
  /** Adres op de site; null voor teksten die op meerdere pagina's staan. */
  path: string | null;
  description: string;
  sections: Record<string, SectionDef>;
};

export type SectionValues<S extends SectionDef> = { [F in keyof S["fields"]]: FieldValue<S["fields"][F]> };
export type PageValues<P extends PageDef> = { [K in keyof P["sections"]]: SectionValues<P["sections"][K]> };
/** Waarden zoals ze heen en weer gaan tussen beheer en server: per onderdeel per veld. */
export type RawValues = Record<string, Record<string, unknown>>;

type Opts<F> = Omit<F, "kind" | "label" | "default">;

export const line = (label: string, value: string, opts: Opts<LineField> = {}): LineField => ({ kind: "line", label, default: value, ...opts });
export const title = (label: string, value: string, opts: Opts<LineField> = {}): LineField => line(label, value, { rich: true, ...opts });
export const text = (label: string, value: string, opts: Opts<TextField> = {}): TextField => ({ kind: "text", label, default: value, ...opts });
export const list = (label: string, value: string[], opts: Opts<ListField> = {}): ListField => ({ kind: "list", label, default: value, ...opts });
export const price = (label: string, value: string, opts: Opts<PriceField> = {}): PriceField => ({ kind: "price", label, default: value, ...opts });
export const check = (label: string, value: boolean, opts: Opts<CheckField> = {}): CheckField => ({ kind: "check", label, default: value, ...opts });
export const link = (label: string, value: string, opts: Opts<LinkField> = {}): LinkField => ({ kind: "link", label, default: value, ...opts });
export const url = (label: string, value: string, opts: Opts<UrlField> = {}): UrlField => ({ kind: "url", label, default: value, ...opts });
export const items = <S extends Record<string, SubField>>(
  label: string,
  itemLabel: string,
  fields: S,
  value: ItemValue<S>[],
  opts: Omit<ItemsField<S>, "kind" | "label" | "default" | "itemLabel" | "fields"> = {},
): ItemsField<S> => ({ kind: "items", label, itemLabel, fields, default: value, ...opts });

export const section = <F extends Record<string, Field>>(title: string, fields: F, hint?: string) => ({ title, hint, fields });
export const definePage = <P extends PageDef>(page: P) => page;

/** Vaste blokken die op de meeste pagina's terugkomen. */
export const seoSection = (pageTitle: string, description: string) =>
  section(
    "Zoekmachines",
    {
      title: line("Paginatitel", pageTitle, { max: 90, hint: "Staat in het tabblad van de browser en als kop in Google. Houd hem kort." }),
      description: text("Omschrijving", description, { max: 300, hint: "De korte tekst onder de titel in Google. Ongeveer 150 tekens werkt het best." }),
    },
    "Niet zichtbaar op de pagina zelf.",
  );

export const ctaSection = (values: { title: string; text: string; primary: string; secondary: string }) =>
  section(
    "Afsluiter onderaan",
    {
      title: line("Titel", values.title, { max: 80 }),
      text: text("Tekst", values.text, { max: 300 }),
      primary: line("Witte knop", values.primary, { max: 40 }),
      secondary: line("Tweede knop", values.secondary, { max: 40 }),
    },
    "Het zwarte blok onderaan de pagina.",
  );

export const fieldKey = (page: string, sectionKey: string, field: string) => `${page}.${sectionKey}.${field}`;

/** Standaardwaarden van een hele pagina. */
export function pageDefaults<P extends PageDef>(page: P): PageValues<P> {
  const out: RawValues = {};
  for (const [s, def] of Object.entries(page.sections)) {
    out[s] = {};
    for (const [f, field] of Object.entries(def.fields)) out[s][f] = structuredClone(field.default);
  }
  return out as PageValues<P>;
}
