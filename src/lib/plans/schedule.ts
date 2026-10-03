import "server-only";
import { and, asc, eq, lte, ne } from "drizzle-orm";
import { cache } from "react";
import { zonedParts, zonedTimeToUtc } from "../agenda";
import { db, plans } from "../db";

/**
 * Ingeplande schema's waarvan de startdatum is aangebroken worden gepubliceerd en vervangen het vorige schema.
 * Er draait geen achtergrondtaak: dit gebeurt zodra de klant of Steyn een pagina met schema's opent (eenmaal per verzoek).
 */
export const activateDuePlans = cache(async () => {
  const today = zonedParts(new Date()).day;
  const due = await db
    .select({ id: plans.id, userId: plans.userId, type: plans.type, startsOn: plans.startsOn })
    .from(plans)
    .where(and(eq(plans.status, "gepland"), lte(plans.startsOn, today)))
    .orderBy(asc(plans.startsOn));

  for (const p of due) {
    const now = new Date();
    await db.transaction(async (tx) => {
      const [activated] = await tx
        .update(plans)
        .set({ status: "gepubliceerd", publishedAt: zonedTimeToUtc(p.startsOn ?? today, "00:00"), updatedAt: now })
        .where(and(eq(plans.id, p.id), eq(plans.status, "gepland")))
        .returning({ id: plans.id });
      if (!activated) return;
      await tx
        .update(plans)
        .set({ status: "vervangen", updatedAt: now })
        .where(and(eq(plans.userId, p.userId), eq(plans.type, p.type), eq(plans.status, "gepubliceerd"), ne(plans.id, p.id)));
    });
  }
  return due.length;
});
