"use server";

import { eq } from "drizzle-orm";
import { revalidatePath } from "next/cache";
import { requireAdmin } from "../auth";
import { fieldKey } from "../content/fields";
import { findPage, VAR_KEYS } from "../content/registry";
import { normalizePage, sameValue } from "../content/values";
import { db, siteTexts } from "../db";
import type { SiteTextsState } from "./types";

/**
 * Slaat de teksten van één pagina op. Een tekst die gelijk is aan de standaardtekst wordt niet bewaard (een eerder
 * aangepaste versie wordt dan verwijderd), zodat de site daar de standaardtekst uit de code blijft volgen.
 */
export async function saveSiteTextsAction(_prev: SiteTextsState, formData: FormData): Promise<SiteTextsState> {
  const admin = await requireAdmin("/admin/teksten");
  const page = findPage(String(formData.get("page") ?? ""));
  if (!page) return { error: "Deze pagina bestaat niet (meer)." };

  const raw = String(formData.get("values") ?? "");
  if (raw.length > 500_000) return { error: "Dit is te veel tekst om in één keer op te slaan." };
  let input: unknown;
  try {
    input = JSON.parse(raw);
  } catch {
    return { error: "De teksten konden niet worden gelezen. Laad de pagina opnieuw en probeer het nog eens." };
  }

  const { values, errors } = normalizePage(page, input, VAR_KEYS);
  const errorCount = Object.keys(errors).length;
  if (errorCount) {
    return { error: errorCount === 1 ? "Er is 1 veld dat nog niet klopt. Het staat hieronder in rood." : `Er zijn ${errorCount} velden die nog niet kloppen. Ze staan hieronder in rood.`, fieldErrors: errors };
  }

  const prefix = `${page.slug}.`;
  const stored = new Map((await db.select({ key: siteTexts.key, value: siteTexts.value }).from(siteTexts)).filter((r) => r.key.startsWith(prefix)).map((r) => [r.key, r.value]));
  const now = new Date();
  const changes: { key: string; value: string | null }[] = [];
  for (const [s, fields] of Object.entries(values)) {
    for (const [f, value] of Object.entries(fields)) {
      const key = fieldKey(page.slug, s, f);
      if (sameValue(value, page.sections[s].fields[f].default)) {
        if (stored.has(key)) changes.push({ key, value: null });
      } else {
        const json = JSON.stringify(value);
        if (stored.get(key) !== json) changes.push({ key, value: json });
      }
    }
  }

  if (changes.length) {
    await db.transaction(async (tx) => {
      for (const { key, value } of changes) {
        if (value === null) await tx.delete(siteTexts).where(eq(siteTexts.key, key));
        else
          await tx
            .insert(siteTexts)
            .values({ key, value, updatedById: admin.id, updatedAt: now })
            .onConflictDoUpdate({ target: siteTexts.key, set: { value, updatedById: admin.id, updatedAt: now } });
      }
    });
    // Teksten staan op elke pagina (kop, footer) en ook in Mijn omgeving en het beheer.
    revalidatePath("/", "layout");
  }

  return {
    success: changes.length
      ? `Opgeslagen. ${changes.length === 1 ? "1 tekst is" : `${changes.length} teksten zijn`} direct bijgewerkt op de website.`
      : "Opgeslagen. Er was niets veranderd.",
    saved: values,
    savedAt: Date.now(),
  };
}
