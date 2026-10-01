import "server-only";
import { and, count, eq, inArray, isNotNull, isNull } from "drizzle-orm";
import { contactRequests, db, plans, users } from "./db";

/** Tellers voor het beheer: wat wacht er op Steyn? */
export async function adminCounts() {
  const [[schemas], [requests], [applied], [rewards]] = await Promise.all([
    db.select({ n: count() }).from(plans).where(inArray(plans.status, ["concept", "fout"])),
    db.select({ n: count() }).from(contactRequests).where(eq(contactRequests.handled, false)),
    db.select({ n: count() }).from(users).where(eq(users.coachingStatus, "aangevraagd")),
    db
      .select({ n: count() })
      .from(users)
      .where(and(eq(users.coachingStatus, "actief"), isNotNull(users.referredById), isNull(users.referralRewardAt))),
  ]);
  return { schemas: schemas.n, requests: requests.n, applied: applied.n, rewards: rewards.n };
}
export type AdminCounts = Awaited<ReturnType<typeof adminCounts>>;
