// Schema-planning: in welke fase zit een klant per schematype, en wanneer is hij toe aan een nieuw schema?
// Puur (geen database), zodat het los te testen is. Dagen zijn "YYYY-MM-DD" in Europe/Amsterdam.

import { addDays } from "../agenda";
import type { CoachingStatus, PlanType } from "../db/schema";

/** Binnen zoveel dagen telt een nieuw schema als "komende week". */
export const SOON_DAYS = 7;

const DEFAULT_RENEW_WEEKS: Record<PlanType, number> = { training: 6, voeding: 4 };

/** Looptijd van een schema: een trainingsschema volgens de duur in het schema, voeding 4 weken. */
export function defaultRenewWeeks(type: PlanType, durationWeeks?: number | null) {
  if (type === "training" && durationWeeks && durationWeeks >= 1 && durationWeeks <= 26) return Math.round(durationWeeks);
  return DEFAULT_RENEW_WEEKS[type];
}

export function defaultRenewOn(type: PlanType, fromDay: string, durationWeeks?: number | null) {
  return addDays(fromDay, defaultRenewWeeks(type, durationWeeks) * 7);
}

export const STAGE_GROUPS = [
  { id: "wacht", label: "Wacht op nieuw schema", hint: "Jij maakt het schema" },
  { id: "controleren", label: "Te controleren", hint: "Concept staat klaar" },
  { id: "binnenkort", label: "Komende week", hint: `Nieuw schema binnen ${SOON_DAYS} dagen` },
  { id: "ingepland", label: "Ingepland", hint: "Nieuw schema start later" },
  { id: "actief", label: "Actief schema", hint: "Loopt nog langer dan een week" },
  { id: "intake", label: "Wacht op intake", hint: "De klant is aan zet" },
  { id: "pauze", label: "Gepauzeerd", hint: "Coaching staat stil" },
] as const;
export type StageGroup = (typeof STAGE_GROUPS)[number]["id"];

export const STAGES = {
  eerste: { group: "wacht", label: "Eerste schema nodig" },
  verlopen: { group: "wacht", label: "Toe aan nieuw schema" },
  bezig: { group: "controleren", label: "AI is bezig" },
  mislukt: { group: "controleren", label: "Concept mislukt" },
  controleren: { group: "controleren", label: "Te controleren" },
  binnenkort: { group: "binnenkort", label: "Komende week" },
  gepland: { group: "ingepland", label: "Ingepland" },
  actief: { group: "actief", label: "Actief" },
  intake: { group: "intake", label: "Wacht op intake" },
  pauze: { group: "pauze", label: "Gepauzeerd" },
} as const satisfies Record<string, { group: StageGroup; label: string }>;
export type Stage = keyof typeof STAGES;

export type PipelineInput = {
  type: PlanType;
  coachingStatus: CoachingStatus;
  /** Gewenste schema's uit de intake; null als er nog geen intake is. */
  wants: readonly PlanType[] | null;
  /** Het schema dat de klant nu ziet. */
  current: { publishedDay: string; renewOn: string | null; durationWeeks: number | null } | null;
  /** Concept dat (nog) niet gepubliceerd is. */
  open: { status: "genereren" | "concept" | "fout"; stuck: boolean } | null;
  /** Goedgekeurd schema dat op een latere dag ingaat. */
  scheduled?: { startsOn: string } | null;
};

/** Hoort deze klant in het overzicht van dit schematype? */
export function inPipeline({ type, coachingStatus, wants, current, open, scheduled }: PipelineInput) {
  if (current || open || scheduled) return true;
  if (coachingStatus !== "aangevraagd" && coachingStatus !== "actief" && coachingStatus !== "gepauzeerd") return false;
  return wants === null || wants.includes(type);
}

/** Dag waarop het huidige schema vernieuwd moet worden. */
export function dueOn(type: PlanType, current: NonNullable<PipelineInput["current"]>) {
  return current.renewOn ?? defaultRenewOn(type, current.publishedDay, current.durationWeeks);
}

export function planStage(input: PipelineInput, today: string): { stage: Stage; dueOn: string | null } {
  const due = input.current ? dueOn(input.type, input.current) : null;
  const { open } = input;
  if (open) {
    const stage = open.status === "fout" || open.stuck ? "mislukt" : open.status === "genereren" ? "bezig" : "controleren";
    return { stage, dueOn: due };
  }
  // Het volgende schema is al goedgekeurd: de "nieuw schema"-datum is dan de startdatum daarvan.
  if (input.scheduled) return { stage: "gepland", dueOn: input.scheduled.startsOn };
  if (input.coachingStatus === "gepauzeerd" || input.coachingStatus === "gestopt") return { stage: "pauze", dueOn: due };
  if (!due) return { stage: input.wants === null ? "intake" : "eerste", dueOn: null };
  if (due <= today) return { stage: "verlopen", dueOn: due };
  if (due <= addDays(today, SOON_DAYS)) return { stage: "binnenkort", dueOn: due };
  return { stage: "actief", dueOn: due };
}

const GROUP_ORDER = Object.fromEntries(STAGE_GROUPS.map((g, i) => [g.id, i])) as Record<StageGroup, number>;

/** Meest dringende eerst: per groep, daarna de vroegste datum. */
export function compareByUrgency(a: { stage: Stage; dueOn: string | null; name: string }, b: { stage: Stage; dueOn: string | null; name: string }) {
  return (
    GROUP_ORDER[STAGES[a.stage].group] - GROUP_ORDER[STAGES[b.stage].group] ||
    (a.dueOn ?? "9999").localeCompare(b.dueOn ?? "9999") ||
    a.name.localeCompare(b.name, "nl")
  );
}

export function countGroups(rows: readonly { stage: Stage }[]) {
  const counts = Object.fromEntries(STAGE_GROUPS.map((g) => [g.id, 0])) as Record<StageGroup, number>;
  for (const r of rows) counts[STAGES[r.stage].group]++;
  return counts;
}

export function daysBetween(from: string, to: string) {
  return Math.round((Date.parse(`${to}T00:00:00Z`) - Date.parse(`${from}T00:00:00Z`)) / 864e5);
}

/** "vandaag", "over 5 dagen", "3 weken geleden" … */
export function relativeDay(today: string, day: string) {
  const n = daysBetween(today, day);
  if (n === 0) return "vandaag";
  if (n === 1) return "morgen";
  if (n === -1) return "gisteren";
  const abs = Math.abs(n);
  const amount = abs < 14 ? `${abs} dagen` : `${Math.round(abs / 7)} weken`;
  return n > 0 ? `over ${amount}` : `${amount} geleden`;
}
