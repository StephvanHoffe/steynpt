import "server-only";
import { connection } from "next/server";
import { cache } from "react";
import { db, siteTexts } from "../db";
import { ONLINE_PLANS, type OnlinePlan, type PriceCard } from "../site";
import { fieldKey, type PageDef, type PageValues, type RawValues } from "./fields";
import { fillVars } from "./markup";
import { algemeen, computeVars, findPage, pakketten } from "./registry";
import { coerceStored } from "./values";

/**
 * Alle aangepaste teksten, één keer per verzoek. `connection()` zorgt dat pagina's met deze teksten niet bij het bouwen
 * al worden vastgelegd: een wijziging in het beheer is dan direct zichtbaar. Lukt het lezen niet (bijvoorbeeld een
 * database die nog niet is bijgewerkt), dan toont de site de standaardteksten in plaats van een foutmelding.
 */
const loadStored = cache(async () => {
  await connection();
  try {
    const rows = await db.select({ key: siteTexts.key, value: siteTexts.value }).from(siteTexts);
    return new Map(rows.map((r) => [r.key, r.value]));
  } catch (error) {
    console.error("Website-teksten konden niet worden geladen; de standaardteksten worden getoond.", error);
    return new Map<string, string>();
  }
});

/** Teksten van een pagina zonder {codes} in te vullen: zoals ze in het beheer staan. */
export function resolvePage(page: PageDef, stored: Map<string, string>): RawValues {
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

const rawTexts = cache(async (slug: string) => {
  const page = findPage(slug);
  if (!page) throw new Error(`Onbekende pagina met teksten: ${slug}`);
  return resolvePage(page, await loadStored());
});

/** De automatische waarden ({ademprijs} en zo) zoals ze nu zijn ingesteld. */
export const textVars = cache(async () =>
  computeVars((await rawTexts(algemeen.slug)) as PageValues<typeof algemeen>, (await rawTexts(pakketten.slug)) as PageValues<typeof pakketten>),
);

const filledTexts = cache(async (slug: string) => fillVars(await rawTexts(slug), await textVars()));

/** Teksten van een pagina zoals de bezoeker ze ziet: aangepast of standaard, met de automatische waarden ingevuld. */
export const getTexts = <P extends PageDef>(page: P) => filledTexts(page.slug) as Promise<PageValues<P>>;

/** Teksten zoals ze in het beheer staan, met de standaardtekst waar niets is aangepast. */
export const getEditableTexts = async (page: PageDef) => rawTexts(page.slug);

/** De balk bovenaan de site, of null als Steyn hem heeft uitgezet. */
export async function getAnnouncement() {
  const { aankondiging: a } = await getTexts(algemeen);
  return a.show ? { label: a.label, text: a.text, href: a.href } : null;
}
export type Announcement = Awaited<ReturnType<typeof getAnnouncement>>;

// --- Pakketten in de vorm die de rest van de site gebruikt ---

export async function getOnlinePlans(): Promise<OnlinePlan[]> {
  const { online } = await getTexts(pakketten);
  return ONLINE_PLANS.map((plan, i) => ({ ...plan, ...online.plans[i] }));
}

/** Naam van een online pakket zoals Steyn hem heeft ingesteld (of undefined bij geen of onbekend pakket). */
export async function onlinePlanName(id: string | null | undefined) {
  const index = ONLINE_PLANS.findIndex((p) => p.id === id);
  return index < 0 ? undefined : (await getTexts(pakketten)).online.plans[index].name;
}

/** Zoekfunctie voor plekken die veel leden tegelijk tonen. */
export async function onlinePlanNames() {
  const plans = await getOnlinePlans();
  return (id: string | null | undefined) => plans.find((p) => p.id === id)?.name;
}

export async function getPtPrices(): Promise<PriceCard[]> {
  const { pt } = await getTexts(pakketten);
  return pt.cards.map((c) => ({ ...c, unit: c.unit || undefined, note: c.note || undefined }));
}

export async function getBreathworkPrice(): Promise<PriceCard> {
  const { adem } = await getTexts(pakketten);
  return { name: adem.name, label: adem.label, price: adem.price, unit: adem.unit, features: adem.features };
}
