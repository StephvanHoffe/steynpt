import "server-only";
import { and, count, eq, isNotNull, isNull } from "drizzle-orm";
import { contactRequests, db, users } from "./db";
import { loadPlanPipeline } from "./plans/pipeline-server";

/** Tellers voor het beheer: wat wacht er op Steyn? */
export async function adminCounts() {
  const [pipeline, [requests], [applied], [rewards]] = await Promise.all([
    loadPlanPipeline(),
    db.select({ n: count() }).from(contactRequests).where(eq(contactRequests.handled, false)),
    db.select({ n: count() }).from(users).where(eq(users.coachingStatus, "aangevraagd")),
    db
      .select({ n: count() })
      .from(users)
      .where(and(eq(users.coachingStatus, "actief"), isNotNull(users.referredById), isNull(users.referralRewardAt))),
  ]);
  // Schema's die op Steyn wachten: nog te maken of te controleren.
  const planAction = (c: (typeof pipeline.counts)["training"]) => c.wacht + c.controleren;
  return {
    training: planAction(pipeline.counts.training),
    voeding: planAction(pipeline.counts.voeding),
    requests: requests.n,
    applied: applied.n,
    rewards: rewards.n,
  };
}
export type AdminCounts = Awaited<ReturnType<typeof adminCounts>>;
