import "server-only";
import { and, eq, ne, sql } from "drizzle-orm";
import { cache } from "react";
import { isStuck } from "@/components/plans/labels";
import { zonedParts } from "../agenda";
import { db, intakes, PLAN_TYPES, plans, type PlanType, users } from "../db";
import { compareByUrgency, countGroups, inPipeline, planStage, type PipelineInput, type Stage } from "./pipeline";

export type PipelineRow = {
  member: { id: string; firstName: string; lastName: string; email: string; plan: string | null; coachingStatus: PipelineInput["coachingStatus"] };
  type: PlanType;
  stage: Stage;
  dueOn: string | null;
  hasIntake: boolean;
  current: { id: number; title: string | null; publishedAt: Date } | null;
  open: { id: number; status: "genereren" | "concept" | "fout"; source: "ai" | "handmatig"; createdAt: Date } | null;
};

const isPlanType = (v: unknown): v is PlanType => (PLAN_TYPES as readonly unknown[]).includes(v);

function parseWants(raw: string | null): PlanType[] {
  try {
    const list: unknown = JSON.parse(raw ?? "[]");
    return Array.isArray(list) ? list.filter(isPlanType) : [];
  } catch {
    return [];
  }
}

/**
 * Alle klanten per schematype met hun fase, meest dringende eerst. Met `userId` alleen dat lid (ook als het buiten het overzicht valt).
 * Gecachet per request: de zijbalk en de pagina delen dezelfde berekening.
 */
export const loadPlanPipeline = cache(async (userId?: string) => {
  const today = zonedParts(new Date()).day;
  const [members, planRows, intakeRows] = await Promise.all([
    db
      .select({ id: users.id, firstName: users.firstName, lastName: users.lastName, email: users.email, plan: users.plan, coachingStatus: users.coachingStatus })
      .from(users)
      .where(userId ? eq(users.id, userId) : eq(users.role, "member")),
    db
      .select({
        id: plans.id,
        userId: plans.userId,
        type: plans.type,
        status: plans.status,
        source: plans.source,
        createdAt: plans.createdAt,
        updatedAt: plans.updatedAt,
        publishedAt: plans.publishedAt,
        renewOn: plans.renewOn,
        title: sql<string | null>`json_extract(${plans.content}, '$.title')`,
        durationWeeks: sql<number | null>`json_extract(${plans.content}, '$.durationWeeks')`,
      })
      .from(plans)
      .where(userId ? and(eq(plans.userId, userId), ne(plans.status, "vervangen")) : ne(plans.status, "vervangen"))
      .orderBy(plans.createdAt),
    db
      .select({ userId: intakes.userId, wants: sql<string | null>`json_extract(${intakes.data}, '$.wants')` })
      .from(intakes)
      .where(userId ? eq(intakes.userId, userId) : undefined),
  ]);

  const wantsBy = new Map(intakeRows.map((i) => [i.userId, parseWants(i.wants)]));
  // Per lid en type: het gepubliceerde schema en het nieuwste openstaande concept.
  const byKey = new Map<string, { current?: (typeof planRows)[number]; open?: (typeof planRows)[number] }>();
  for (const p of planRows) {
    const key = `${p.userId}:${p.type}`;
    const entry = byKey.get(key) ?? {};
    if (p.status === "gepubliceerd") entry.current = p;
    else entry.open = p;
    byKey.set(key, entry);
  }

  const result = { training: [] as PipelineRow[], voeding: [] as PipelineRow[] };
  for (const type of PLAN_TYPES) {
    for (const m of members) {
      const { current, open } = byKey.get(`${m.id}:${type}`) ?? {};
      const input: PipelineInput = {
        type,
        coachingStatus: m.coachingStatus,
        wants: wantsBy.get(m.id) ?? null,
        current: current?.publishedAt ? { publishedDay: zonedParts(current.publishedAt).day, renewOn: current.renewOn, durationWeeks: current.durationWeeks } : null,
        open: open && open.status !== "gepubliceerd" && open.status !== "vervangen" ? { status: open.status, stuck: isStuck(open.status, open.updatedAt) } : null,
      };
      if (!userId && !inPipeline(input)) continue;
      const { stage, dueOn } = planStage(input, today);
      result[type].push({
        member: m,
        type,
        stage,
        dueOn,
        hasIntake: wantsBy.has(m.id),
        current: current?.publishedAt ? { id: current.id, title: current.title, publishedAt: current.publishedAt } : null,
        open: input.open && open ? { id: open.id, status: input.open.status, source: open.source, createdAt: open.createdAt } : null,
      });
    }
    result[type].sort((a, b) => compareByUrgency({ ...a, name: `${a.member.firstName} ${a.member.lastName}` }, { ...b, name: `${b.member.firstName} ${b.member.lastName}` }));
  }
  return { today, rows: result, counts: { training: countGroups(result.training), voeding: countGroups(result.voeding) } };
});
export type PlanPipeline = Awaited<ReturnType<typeof loadPlanPipeline>>;
