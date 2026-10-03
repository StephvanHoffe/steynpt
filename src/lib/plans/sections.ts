import type { PlanType } from "../db/schema";

/** Eigen onderdeel in het beheer per schematype. */
export const PLAN_SECTION = {
  training: { href: "/admin/trainingsschemas", title: "Trainingsschema's", one: "trainingsschema" },
  voeding: { href: "/admin/voedingsschemas", title: "Voedingsschema's", one: "voedingsschema" },
} as const satisfies Record<PlanType, { href: string; title: string; one: string }>;

export const planHref = (type: PlanType, id: number) => `${PLAN_SECTION[type].href}/${id}`;
export const newPlanHref = (type: PlanType, userId?: string) => `${PLAN_SECTION[type].href}/nieuw${userId ? `?lid=${userId}` : ""}`;
